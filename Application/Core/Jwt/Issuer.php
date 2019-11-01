<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * AAM JWT Issuer
 *
 * @package AAM
 * @version 6.0.0
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
     * @access public
     * @version 6.0.0
     */
    public function validateToken($token)
    {
        try {
            $headers = $this->extractTokenHeaders($token);

            if (strpos($headers->alg, 'RS') === 0) {
                $path = AAM_Core_Config::get('authentication.jwt.publicKeyPath');
                $key = (is_readable($path) ? file_get_contents($path) : null);
            } else {
                $key = AAM_Core_Config::get(
                    'authentication.jwt.secret', SECURE_AUTH_KEY
                );
            }

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
                    throw new Exception(__('Token has been revoked', AAM_KEY));
                }
            }

            $response->isValid = true;
        } catch (Exception $ex) {
            $response = array(
                'isValid' => false,
                'reason'  => $ex->getMessage()
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
     * @access public
     * @throws Exception
     * @version 6.0.0
     */
    public function issueToken($args = array(), $expires = null)
    {
        if (!empty($expires)) {
            if (is_a($expires, 'DateTime')) {
                $time = $expires;
            } else {
                $time = DateTime::createFromFormat(
                    'm/d/Y, H:i O', $expires, new DateTimeZone('UTC')
                );
            }
        } else {
            $time = new DateTime(
                AAM_Core_Config::get('authentication.jwt.expires', '+24 hours'),
                new DateTimeZone('UTC')
            );
        }

        $claims = apply_filters(
            'aam-jwt-claims-filter',
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
     * @access public
     * @version 6.0.0
     */
    public function extractTokenHeaders($token)
    {
        $parts   = explode('.', $token);
        $headers = array();
        try {
            $headers = Firebase\JWT\JWT::jsonDecode(
                Firebase\JWT\JWT::urlsafeB64Decode($parts[0])
            );
        } catch (Exception $ex) {
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                'Invalid JWT token: ' . $ex->getMessage(),
                AAM_VERSION
            );
        }

        return (object) $headers;
    }

    /**
     * Extract token claims
     *
     * @param string $token
     *
     * @return object
     *
     * @access public
     * @version 6.0.0
     */
    public function extractTokenClaims($token)
    {
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
     * @access protected
     * @version 6.0.0
     */
    protected function getJWTSigningAttributes()
    {
        $alg = strtoupper(
            AAM_Core_Config::get('authentication.jwt.algorithm', 'HS256')
        );

        if (strpos($alg, 'RS') === 0) {
            $path = AAM_Core_Config::get('authentication.jwt.privateKeyPath');
            $key = (is_readable($path) ? file_get_contents($path) : null);
        } else {
            $key = AAM_Core_Config::get('authentication.jwt.secret', SECURE_AUTH_KEY);
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