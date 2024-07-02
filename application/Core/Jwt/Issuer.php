<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM JWT Issuer
 *
 * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/221
 * @since 6.8.6 https://github.com/aamplugin/advanced-access-manager/issues/221
 * @since 6.7.2 https://github.com/aamplugin/advanced-access-manager/issues/165
 * @since 6.1.0 Enriched error response with more details
 * @since 6.0.4 Bug fixing. Timezone was handled incorrectly and ttl did not take in
 *              consideration numeric "in seconds" value
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.0
 *
 * @deprecated 6.8.5 Will be removed from AAM in 7.0.0 version
 * @todo Remove this class in 7.0.0 release
 */
class AAM_Core_Jwt_Issuer
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * Validate JWT token
     *
     * @param string $token
     *
     * @return object
     *
     * @since 6.8.6 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.1.0 Enriched error response with more details
     * @since 6.0.4 Making sure that JWT expiration is checked with UTC timezone
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.8.6
     */
    public function validateToken($token)
    {
        _deprecated_function(
            __CLASS__ . '::' . __FUNCTION__,
            '6.8.5',
            'AAM_Core_Jwt_Manager::validate'
        );

        try {
            $headers = $this->extractTokenHeaders($token);
            $service = AAM_Framework_Manager::configs();

            if (strpos($headers->alg, 'RS') === 0) {
                $path = $service->get_config('authentication.jwt.publicKeyPath');
                $key = (is_readable($path) ? file_get_contents($path) : null);
            } else {
                $key = $service->get_config(
                    'authentication.jwt.secret', SECURE_AUTH_KEY
                );
            }

            // Making sure that timestamp is UTC
            Firebase\JWT\JWT::$timestamp = (new DateTime(
                'now', new DateTimeZone('UTC')
            ))->getTimestamp();

            // Step #1. Check if token is actually valid
            $response = Firebase\JWT\JWT::decode(
                $token, $key, array_keys(Firebase\JWT\JWT::$supported_algs)
            );

            // Step #2. If token is "revocable", make sure that claimed user still has
            // the token in the meta
            if (!empty($response->revocable)) {
                $tokens = get_user_option(
                    AAM_Service_Jwt::DB_OPTION, $response->userId
                );

                if (!is_array($tokens) || !in_array($token, $tokens, true)) {
                    throw new Exception(
                        __('Token has been revoked', AAM_KEY),
                        410
                    );
                }
            }

            $response->isValid = true;
        } catch (Exception $ex) {
            $status   = $ex->getCode();
            $response = array(
                'isValid' => false,
                'reason'  => $ex->getMessage(),
                'status'  => (!empty($status) ? $status : 400)
            );
        }

        return (object) $response;
    }

    /**
     * Issue JWT token
     *
     * @param array           $args
     * @param string|DateTime $expires
     *
     * @return object
     *
     * @since 6.8.6 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.0.4 Fixed the bug when `authentication.jwt.expires` is defined in
     *              seconds
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @throws Exception
     * @version 6.8.6
     */
    public function issueToken($args = array(), $expires = null)
    {
        _deprecated_function(
            __CLASS__ . '::' . __FUNCTION__,
            '6.8.5',
            'AAM_Core_Jwt_Manager::encode'
        );

        if (!empty($expires)) {
            $time = $expires;
        } else {
            $ttl = AAM_Framework_Manager::configs()->get_config(
                'authentication.jwt.expires', '+24 hours'
            );

            if (is_numeric($ttl)) {
                $ttl = "+{$ttl} seconds";
            }

            $time = new DateTime($ttl, new DateTimeZone('UTC'));
        }

        $claims = apply_filters(
            'aam_jwt_claims_filter',
            array_merge(
                array(
                    "iat" => time(),
                    'iss' => get_site_url(),
                    'exp' => $time->getTimestamp(),
                    'jti' => $this->generateUuid()
                ),
                $args
            )
        );

        // Determine algorithm and key
        $attr = $this->getJWTSigningAttributes();

        return (object) array(
            'token'  => Firebase\JWT\JWT::encode($claims, $attr->key, $attr->alg),
            'claims' => $claims
        );
    }

    /**
     * Extract tokens headers
     *
     * @param string $token
     *
     * @return object
     *
     * @access protected
     * @version 6.0.0
     */
    protected function extractTokenHeaders($token)
    {
        $parts   = explode('.', $token);
        $headers = Firebase\JWT\JWT::jsonDecode(
            Firebase\JWT\JWT::urlsafeB64Decode($parts[0])
        );

        return (object) $headers;
    }

    /**
     * Extract token claims
     *
     * @param string $token
     *
     * @return object
     *
     * @since 6.8.6 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.8.6
     */
    public function extractTokenClaims($token)
    {
        _deprecated_function(
            __CLASS__ . '::' . __FUNCTION__,
            '6.8.5',
            'AAM_Core_Jwt_Manager::decode'
        );

        $parts  = explode('.', $token);
        $claims = array();

        try {
            $claims = Firebase\JWT\JWT::jsonDecode(
                Firebase\JWT\JWT::urlsafeB64Decode($parts[1])
            );
        } catch (Exception $ex) {
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                'Invalid JWT token: ' . $ex->getMessage(),
                AAM_VERSION
            );
        }

        return (object) $claims;
    }

    /**
     * Get JWT attributes for signing
     *
     * @return object
     *
     * @since 6.7.2 https://github.com/aamplugin/advanced-access-manager/issues/165
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.7.2
     */
    protected function getJWTSigningAttributes()
    {
        $service = AAM_Framework_Manager::configs();
        $alg     = strtoupper(
            $service->get_config('authentication.jwt.algorithm', 'HS256')
        );

        if (strpos($alg, 'RS') === 0) {
            $path       = $service->get_config('authentication.jwt.privateKeyPath');
            $key        = (is_readable($path) ? file_get_contents($path) : null);
            $passphrase = $service->get_config('authentication.jwt.passphrase', false);

            if($passphrase && extension_loaded('openssl')) {
                $key = openssl_pkey_get_private($key, $passphrase);
            }
        } else {
            $key = $service->get_config('authentication.jwt.secret', SECURE_AUTH_KEY);
        }

        return (object) array(
            'alg' => $alg,
            'key' => $key
        );
    }

    /**
     * Generate random uuid
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

}