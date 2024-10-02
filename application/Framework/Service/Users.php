<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service user manager
 *
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.32 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
 */
class AAM_Framework_Service_Users
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Return paginated list of user
     *
     * @return array Array of AAM_Framework_Proxy_User
     *
     * @access public
     * @version 6.9.32
     */
    public function get_user_list(array $args = [])
    {
        try {
            $args = array_merge([
                'blog_id'        => get_current_blog_id(),
                'fields'         => 'all',
                'number'         => 10,
                'offset'         => 0,
                'search'         => '',
                'result_type'    => 'full',
                'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
                'orderby'        => 'display_name'
            ], $args);

            $query  = new WP_User_Query($args);
            $result = [];

            if ($args['result_type'] !== 'summary') {
                $result['list'] = [];

                foreach ($query->get_results() as $user) {
                    array_push($result['list'], $this->_prepare_user(
                        new AAM_Framework_Proxy_User($user))
                    );
                }
            }

            if (in_array($args['result_type'], ['full', 'summary'], true)) {
                $result['summary'] = [
                    'total_count'    => count_users()['total_users'],
                    'filtered_count' => $query->get_total()
                ];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $args['result_type'] === 'list' ? $result['list'] : $result;
    }

    /**
     * Get user by its identifier
     *
     * The identifier can be either ID, user_login or user_email.
     *
     * @param int|string $identifier
     * @param boolean    $return_proxy
     *
     * @return array|AAM_Framework_Proxy_User
     *
     * @access public
     * @version 6.9.32
     * @throws OutOfRangeException
     */
    public function get_user($identifier, $return_proxy = true)
    {
        try {
            if (is_numeric($identifier)) { // Get user by ID
                $user = get_user_by('id', $identifier);
            } elseif (is_string($identifier) && $identifier !== '*') {
                if (strpos($identifier, '@') > 0) { // Email?
                    $user = get_user_by('email', $identifier);
                } else {
                    $user = get_user_by('login', $identifier);
                }
            } else {
                $user = $identifier;
            }

            if (!is_a($user, 'WP_User')) {
                throw new OutOfRangeException(
                    "User with identifier {$identifier} does not exist"
                );
            } else {
                $user = new AAM_Framework_Proxy_User($user);
            }

            $result = $return_proxy ? $user : $this->_prepare_user($user);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Update user
     *
     * @param int|string $identifier
     * @param array      $data
     * @param boolean    $return_proxy
     *
     * @return array|AAM_Framework_Proxy_User
     *
     * @access public
     * @version 6.9.32
     */
    public function update($identifier, $data, $return_proxy = true)
    {
        try {
            $user = $this->get_user($identifier);
            $user->update($data);

            $result = $return_proxy ? $user : $this->_prepare_user($user);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset user
     *
     * @param int|string $identifier
     * @param boolean    $return_proxy
     *
     * @return array|AAM_Framework_Proxy_User
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
     * @since 6.9.32 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function reset($identifier, $return_proxy = true)
    {
        try {
            $user = $this->get_user($identifier);

            $user->reset();

            $result = $return_proxy ? $user : $this->_prepare_user($user);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Verify that user is active, not expired and simply can be logged in
     *
     * @param int|string|WP_User|AAM_Framework_Proxy_User $identifier
     *
     * @return mixed
     *
     * @access public
     * @version 6.9.33
     */
    public function verify_user_state($identifier)
    {
        try {
            $user = $this->get_user($identifier);

            // Step #1. Verify that user is active
            if (!$user->is_user_active()) {
                throw new DomainException(__(
                    '[ERROR]: User is inactive. Contact website administrator.',
                    AAM_KEY
                ));
            }

            // Step #2. Verify that user is not expired
            if ($user->is_user_access_expired()) {
                throw new DomainException(__(
                    '[ERROR]: User access is expired. Contact website administrator.',
                    AAM_KEY
                ));
            }

            $result = $user;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Prepare user data
     *
     * @param AAM_Framework_Proxy_User $wp_user
     *
     * @return array
     *
     * @access private
     * @version 6.9.32
     */
    private function _prepare_user(AAM_Framework_Proxy_User $user)
    {
        $response = [
            'id'                    => $user->ID,
            'user_login'            => $user->user_login,
            'display_name'          => $user->display_name,
            'user_level'            => intval($user->user_level),
            'roles'                 => $this->_prepare_user_roles($user->roles),
            'assigned_capabilities' => $user->caps,
            'all_capabilities'      => $user->allcaps,
            'status'                => $user->status
        ];

        $expires_at = $user->expires_at;

        if (!empty($expires_at)) {
            $response['expiration'] = [
                'expires_at'           => $user->expires_at->format(DateTime::RFC3339),
                'expires_at_timestamp' => $user->expires_at->getTimestamp(),
                'trigger'              => $user->expiration_trigger
            ];
        }

        return apply_filters('aam_get_user_filter', $response, $user);
    }

    /**
     * Prepare list of roles
     *
     * @param array $roles
     *
     * @return array
     *
     * @access private
     * @version 6.9.32
     */
    private function _prepare_user_roles($roles)
    {
        $response = array();

        $names = wp_roles()->get_names();

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (array_key_exists($role, $names)) {
                    $response[] = translate_user_role($names[$role]);
                }
            }
        }

        return $response;
    }

}