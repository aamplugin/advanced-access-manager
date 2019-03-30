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
 * @package AAM
 * @author  Vasyl Martyniuk <vasyl@vasyltech.com>
 * @since   v5.9.2
 */
class AAM_Core_Jwt_Issuer {

    /**
     * Just a local cache
     *
     * @var array
     */
    protected $cache = array();

    /**
     * Validate JWT token
     * 
     * @param string $token
     * 
     * @return stdClass
     * 
     * @access public
     */
    public function validateToken($token) {
        try {
            $headers = $this->extractTokenHeaders($token);

            if (strpos($headers->alg, 'RS') === 0) {
                $filepath = AAM_Core_Config::get('authentication.jwt.publicKeyPath');
                $key = (is_readable($filepath) ? file_get_contents($filepath) : null);
            } else {
                $key = AAM_Core_Config::get('authentication.jwt.secret', SECURE_AUTH_KEY);
            }

            // Step #1. Check if token is actually valid
            $response = Firebase\JWT\JWT::decode(
                $token, $key, array_keys(Firebase\JWT\JWT::$supported_algs)
            );

            // Step #2. If token is "revocable", make sure that claimed user still has
            // the token in the meta
            if (!empty($response->revocable)) {
                $tokens = $this->getUsersTokens($response->userId);
                if (!in_array($token, $tokens, true)) {
                    throw new Exception(__('Token has been revoked', AAM_KEY));
                }
            }

            $response->status = 'valid';
        } catch (Exception $ex) {
            $response = array_merge(array(
                'status' => 'invalid',
                'reason' => $ex->getMessage()
            ), (array) $this->extractTokenClaims($token));
        }

        return (object) $response;
    }
    
    /**
     * Issue JWT token
     * 
     * @param array           $args
     * @param string|DateTime $expires
     * 
     * @return stdClass
     * 
     * @access public
     * @throws Exception
     */
    public function issueToken($args = array(), $expires = null) {
        if (!empty($expires)) {
            if (is_a($expires, 'DateTime')) {
                $time = $expires;
            } else {
                $time = DateTime::createFromFormat('m/d/Y, H:i O', $expires);
            }
        } else {
            $time = new DateTime(
                AAM_Core_Config::get('authentication.jwt.expires', '+24 hours')
            );
        }

        $claims = apply_filters(
            'aam-jwt-claims-filter', 
            array_merge(
                array(
                    "iat" => time(),
                    'iss' => get_site_url(),
                    'exp' => $time->format('m/d/Y, H:i O'),
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
     */
    public static function extractTokenHeaders($token) {
        $parts = explode('.', $token);
        
        try {
            $headers = Firebase\JWT\JWT::jsonDecode(
                Firebase\JWT\JWT::urlsafeB64Decode($parts[0])
            );
        } catch (Exception $ex) {
            $headers = new stdClass();
        }

        return $headers;
    }

    /**
     * Extract token claims
     *
     * @param string $token
     * 
     * @return object
     * 
     * @access public
     */
    public static function extractTokenClaims($token) {
        $parts = explode('.', $token);

        try {
            $claims = Firebase\JWT\JWT::jsonDecode(
                Firebase\JWT\JWT::urlsafeB64Decode($parts[1])
            );
        } catch (Exception $ex) {
            $claims = new stdClass();
        }

        return $claims;
    }

    /**
     * Get JWT attributes for signing
     *
     * @return object
     * 
     * @access protected
     */
    protected function getJWTSigningAttributes() {
        $alg = strtoupper(
            AAM_Core_Config::get('authentication.jwt.algorithm', 'HS256')
        );

        if (strpos($alg, 'RS') === 0) {
            $filepath = AAM_Core_Config::get('authentication.jwt.privateKeyPath');
            $key = (is_readable($filepath) ? file_get_contents($filepath) : null);
        } else {
            $key = AAM_Core_Config::get('authentication.jwt.secret', SECURE_AUTH_KEY);
        }

        return (object) array(
            'alg' => $alg,
            'key' => $key
        );
    }

    /**
     * Get user's tokens
     *
     * @param int $userId
     * 
     * @return array
     * 
     * @access protected
     */
    protected function getUsersTokens($userId) {
        if (!isset($this->cache[$userId])) {
            $list = get_user_meta($userId, 'aam-jwt');
            $this->cache[$userId] = is_array($list) ? $list : array();
        }

        return $this->cache[$userId];
    }

    /**
     * Generate random uuid
     *
     * @return string
     */
    protected function generateUuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,
    
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,
    
            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
    
}