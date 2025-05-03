<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for JWT Tokens
 *
 * @package AAM
 * @version 7.0.0
 *
 * @link https://github.com/firebase/php-jwt
 */
class AAM_Framework_Service_Jwts implements AAM_Framework_Service_Interface
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * JWT Registry DB option
     *
     * @version 7.0.0
     */
    const DB_OPTION = 'aam_jwt_registry';

    /**
     * Cache token registry
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_registry = null;

    /**
     * Return list of tokens
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_tokens()
    {
        try {
            $result = [];

            foreach($this->_get_registry() as $token) {
                array_push($result, $this->_prepare_token($token));
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_tokens method
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function tokens()
    {
        return $this->get_tokens();
    }

    /**
     * Find token by the field
     *
     * @param mixed  $search
     * @param string $claim  [Optional]
     *
     * @return array|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function get_token_by($search, $claim = null)
    {
        try {
            $found = null;

            if (is_string($claim)) {
                foreach($this->_get_registry() as $token) {
                    $claims = $this->jwt->decode($token);

                    if (array_key_exists($claim, $claims)
                        && $claims[$claim] === $search
                    ) {
                        $found = $token;
                        break;
                    }
                }
            } else {
                $filtered = array_filter(
                    $this->_get_registry(),
                    function($t) use ($search) {
                        return $t === $search;
                    }
                );

                $found = count($filtered) ? array_shift($filtered) : null;
            }

            if (is_null($found)) {
                throw new OutOfRangeException('Token does not exist');
            } else {
                $result = $this->_prepare_token($found);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_token_by method
     *
     * @param mixed  $search
     * @param string $claim  [Optional]
     *
     * @return string|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function token_by($search, $claim = null)
    {
        return $this->get_token_by($search, $claim);
    }

    /**
     * Create new token
     *
     * @param array $claims         [Optional]
     * @param array $settings       [Optional]
     *
     * @return array|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function issue(array $claims = [], array $settings = [])
    {
        try {
            $user   = $this->_get_access_level();
            $config = array_merge([
                'revocable'   => true,
                'refreshable' => false,
                'ttl'         => $this->config->get(
                    'service.jwt.expires_in', '+24 hours'
                )
            ], $settings);


            if (is_numeric($config['ttl'])) {
                $config['ttl'] = "+{$settings['ttl']} seconds";
            } elseif (!is_string($config['ttl'])) {
                throw new InvalidArgumentException('Invalid token ttl');
            }

            if (!is_bool($config['revocable'])) {
                throw new InvalidArgumentException('Invalid revocable indicator');
            }

            if (!is_bool($config['refreshable'])) {
                throw new InvalidArgumentException('Invalid refreshable indicator');
            }

            $claims = apply_filters('aam_jwt_claims_filter', array_merge(
                $claims,
                [
                    'revocable'   => $config['revocable'],
                    'refreshable' => $config['refreshable']
                ]
            ));

            // Generate a token
            $result = array_merge(
                $this->jwt->issue($user->ID, $claims, $config['ttl']),
                [ 'is_valid' => true ]
            );

            // Register token
            if ($config['revocable']) {
                $this->_add_to_registry($result['token']);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Revoke JWT token
     *
     * @param string $token
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function revoke($token)
    {
        try {
            $result = $this->_remove_from_registry($token);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Refresh existing token (if allowed)
     *
     * @param string $token
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function refresh($token)
    {
        try {
            // Validating the token first
            $result = $this->_validate($token);

            if (!is_wp_error($result)) {
                $claims = $this->jwt->decode($token);

                if (!empty($claims['refreshable'])) {
                    // Determine tokens ttl
                    $ttl = $claims['exp'] - $claims['iat'];

                    // Add time when token was refreshed
                    $claims['rat'] = time();

                    // Issue new token with the same duration
                    $result = $this->jwt->issue($claims['user_id'], $claims, $ttl);

                    // Revoke given token && add new one
                    if ($claims['revocable']) {
                        $this->_remove_from_registry($token);
                        $this->_add_to_registry($result['token']);
                    }
                } else {
                    throw new LogicException('The given token is not refreshable');
                }
            } else {
                throw new RuntimeException($result->get_error_message());
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Validate JWT token
     *
     * @param string $token
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function validate($token)
    {
        try {
            $result = $this->_validate($token);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset all tokens
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function reset()
    {
        try {
            // Save token
            $result = delete_user_option(
                $this->_get_access_level()->ID, self::DB_OPTION
            );

            // Reset internal registry
            $this->_registry = null;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Verify that service is properly configured
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        $access_level = $this->_get_access_level();

        if ($access_level->type !== AAM_Framework_Type_AccessLevel::USER) {
            throw new LogicException(
                'The JWT service expects ONLY user access level'
            );
        }
    }

    /**
     * Validate given token
     *
     * @param string $token
     *
     * @return bool|WP_Error
     * @access private
     *
     * @version 7.0.0
     */
    private function _validate($token)
    {
        // Step #1. Let's verify that token is properly signed and not expired
        $result = $this->jwt->validate($token);

        if (!is_wp_error($result)) {
            // Step #2. Let's verify that token is part of registry if it is
            // revocable
            $claims = $this->jwt->decode($token);

            if (!empty($claims['revocable'])) {
                $filtered = array_filter(
                    $this->_get_registry(),
                    function($t) use ($token) {
                        return $t === $token;
                    }
                );

                if (empty($filtered)) {
                    throw new RuntimeException('Unregistered token');
                }
            }
        } else {
            throw new RuntimeException(esc_js($result->get_error_message()));
        }

        return $result;
    }

    /**
     * Get list of tokens issued for a given user
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_registry()
    {
        if (is_null($this->_registry)) {
            $registry = get_user_option(
                self::DB_OPTION, $this->_get_access_level()->ID
            );

            // Making sure the registry is not corrupted
            $this->_registry = is_array($registry) ? $registry : [];
        }

        return $this->_registry;
    }

    /**
     * Persist token in DB
     *
     * @param string $token
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _add_to_registry($token)
    {
        $registry      = $this->_get_registry();
        $registry_size = $this->config->get('service.jwt.registry_size', 10);

        // Make sure that we do not overload the user meta
        if (count($registry) >= $registry_size) {
            array_shift($registry);
        }

        // Add new token to the registry
        $registry[] = $token;

        // Update local registry cache
        $this->_registry = $registry;

        // Save token
        $result = update_user_option(
            $this->_get_access_level()->ID, self::DB_OPTION, $this->_registry
        );

        if (!$result) {
            throw new RuntimeException('Failed to register a token');
        }

        return $result;
    }

    /**
     * Remove a token from the registry
     *
     * @param string $token
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _remove_from_registry($token)
    {
        // Filter out token that we are deleting
        $tokens   = $this->_get_registry();
        $filtered = array_filter($tokens, function($t) use ($token) {
            return $t !== $token;
        });

        // Did we actually remove a token?
        if (count($tokens) === count($filtered)) {
            throw new OutOfRangeException('Provided token is not registered');
        }

        // Save token
        $result = update_user_option(
            $this->_get_access_level()->ID, self::DB_OPTION, $filtered
        );

        // Update a local cache
        $this->_registry = $filtered;

        if (!$result) {
            throw new RuntimeException('Failed to revoke the token');
        }

        return $result;
    }

    /**
     * Prepare token model
     *
     * @param string $token
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_token($token)
    {
        $is_valid = $this->jwt->validate($token);
        $claims   = $this->jwt->decode($token);

        $result = [
            'token'    => $token,
            'claims'   => $claims,
            'is_valid' => $is_valid === true
        ];

        if (is_wp_error($is_valid)) {
            $result['error'] = $is_valid->get_error_message();
        }

        return $result;
    }

}