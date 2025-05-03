<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM framework utilities
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_Utility_Jwt implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * When checking nbf, iat or expiration times,
     * we want to provide some extra leeway time to
     * account for clock skew.
     *
     * @var int
     * @access private
     *
     * @version 7.0.0
     */
    private $_leeway = 0;

    /**
     * Collection of supported signing algorithms
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_supported_algs = array(
        'ES384' => array('openssl', 'SHA384'),
        'ES256' => array('openssl', 'SHA256'),
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'RS256' => array('openssl', 'SHA256'),
        'RS384' => array('openssl', 'SHA384'),
        'RS512' => array('openssl', 'SHA512'),
        'EdDSA' => array('sodium_crypto', 'EdDSA'),
    );

    /**
     * Create new token
     *
     * @param int        $user_id
     * @param array      $claims  [Optional]
     * @param int|string $ttl     [Optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function issue($user_id, array $claims = [], $ttl = null)
    {
        if (is_null($ttl)) {
            $ttl = '+24 hours';
        } elseif (is_numeric($ttl)) {
            $ttl = "+{$ttl} seconds";
        } elseif (!is_string($ttl)) {
            throw new InvalidArgumentException('Invalid token ttl');
        }

        $time   = new DateTime($ttl, new DateTimeZone('UTC'));
        $claims = array_merge(
            [ 'jti' => $this->_generate_uuid() ],
            $claims,
            [
                'iat'     => time(),
                'iss'     => get_site_url(),
                'exp'     => $time->getTimestamp(),
                'user_id' => $user_id
            ]
        );

        // Generating token & return result
        return [
            'token'  => $this->_encode($claims),
            'claims' => $claims
        ];
    }

    /**
     * Validate token and return claims if valid
     *
     * @param string $token
     *
     * @return bool|WP_Error
     * @access private
     *
     * @version 7.0.0
     */
    public function validate($token)
    {
        $result = true;

        try {
            // Validating header segment. Make sure that all necessary properties
            // are defined correctly
            $headers = $this->_validate_header($token);

            // Get signing attributes
            $attrs = $this->_get_signing_attributes(false, $headers['alg']);

            // Verify the signature
            $this->_validate_signature($token, $attrs->key);

            $tms    = (new DateTime('now', new DateTimeZone('UTC')))->getTimestamp();
            $claims = $this->_decode_segment($token, 1);

            // Check the nbf if it is defined. This is the time that the
            // token can actually be used. If it's not yet that time, abort.
            if (isset($claims['nbf']) && $claims['nbf'] > ($tms + $this->_leeway)) {
                throw new RuntimeException(
                    'Cannot take token prior to ' . date(DateTime::ATOM, $claims['nbf'])
                );
            }

            // Check that this token has been created before 'now'. This prevents
            // using tokens that have been created for later use (and haven't
            // correctly used the nbf claim).
            if (isset($claims['iat']) && $claims['iat'] > ($tms + $this->_leeway)) {
                throw new RuntimeException(
                    'Cannot take token prior to ' . date(DateTime::ATOM, $claims['iat'])
                );
            }

            // Check if this token has expired.
            if (isset($claims['exp']) && ($tms - $this->_leeway) >= $claims['exp']) {
                throw new RuntimeException('Expired token');
            }
        } catch (Exception $e) {
            $result = new WP_Error('invalid_token', $e->getMessage());
        }

        return $result;
    }

    /**
     * Determine if token is valid
     *
     * @param string $token
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_valid($token)
    {
        $result = $this->validate($token);

        return is_bool($result) ? $result : false;
    }

    /**
     * Decode a token and return claims
     *
     * @param string $token
     *
     * @return array|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function decode($token)
    {
        try {
            $result = $this->_decode_segment($token, 1);
        } catch (Exception $e) {
            $result = new WP_Error('invalid_token', $e->getMessage());
        }

        return $result;
    }

    /**
     * Encode payload
     *
     * @param array $claims
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _encode(array $claims)
    {
        $attrs = $this->_get_signing_attributes(true);

        // Encode the JWT headers first
        $segments = array($this->_url_safe_b64_encode($this->_json_encode(array(
            'typ' => 'JWT',
            'alg' => $attrs->alg
        ))));

        // Next, let's encode the payload
        array_push($segments, $this->_url_safe_b64_encode(
            $this->_json_encode($claims)
        ));

        // Adding signature to as the last segment
        array_push($segments, $this->_url_safe_b64_encode($this->_sign(
            implode('.', $segments),
            $attrs->key,
            $attrs->alg
        )));

        return implode('.', $segments);
    }

    /**
     * Validate token header
     *
     * @param string $token
     *
     * @return stdClass
     *
     * @access protected
     * @version 7.0.0
     */
    private function _validate_header($token)
    {
        $headers = $this->_decode_segment($token);

        if (empty($headers['alg'])) {
            throw new UnexpectedValueException('Empty algorithm');
        }

        if (empty($this->_supported_algs[$headers['alg']])) {
            throw new UnexpectedValueException('Algorithm not supported');
        }

        return $headers;
    }

    /**
     * Generate random uuid
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _generate_uuid()
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

    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string $msg  The message to sign
     * @param mixed  $key  The secret key.
     * @param string $alg  Supported algorithms are 'ES384','ES256', 'HS256', 'HS384',
     *                    'HS512', 'RS256', 'RS384', and 'RS512'
     *
     * @return string An encrypted message
     * @access private
     *
     * @version 7.0.0
     */
    private function _sign($msg, $key, $alg)
    {
        $signature = null;

        if (empty($this->_supported_algs[$alg])) {
            throw new RuntimeException('Algorithm not supported');
        } elseif (empty($key)) {
            throw new InvalidArgumentException('The signing key cannot be empty');
        }

        list($function, $algorithm) = $this->_supported_algs[$alg];

        if ($function === 'hash_hmac') {
            $signature = hash_hmac($algorithm, $msg, $key, true);
        } elseif ($function === 'openssl') {
            $success = openssl_sign($msg, $signature, $key, $algorithm);

            if (!$success) {
                throw new RuntimeException('OpenSSL unable to sign data');
            }

            if ($alg === 'ES256') {
                $signature = $this->_signature_from_der($signature, 256);
            } elseif ($alg === 'ES384') {
                $signature = $this->_signature_from_der($signature, 384);
            }
        } elseif ($function === 'sodium_crypto') {
            if (!function_exists('sodium_crypto_sign_detached')) {
                throw new RuntimeException('libsodium is not available');
            }

            // The last non-empty line is used as the key.
            $lines     = array_filter(explode("\n", $key));
            $key       = base64_decode((string) end($lines));
            $signature = sodium_crypto_sign_detached($msg, $key);
        } else {
            throw new RuntimeException('Algorithm not supported');
        }

        return $signature;
    }

    /**
     * Get token signing attributes like algorithm and key
     *
     * @param bool   $to_sign
     * @param string $alg
     *
     * @return stdClass
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_signing_attributes($to_sign = false, $alg = null)
    {
        if (empty($alg)) {
            $alg = AAM_Framework_Manager::_()->config->get(
                'service.jwt.signing_algorithm', 'HS256'
            );
        }

        $alg_upper = strtoupper($alg);

        if (strpos($alg_upper, 'RS') === 0) {
            $key = $this->_get_signing_key_from_cert($to_sign);
        } else {
            $key = AAM_Framework_Manager::_()->config->get(
                'service.jwt.signing_secret', SECURE_AUTH_KEY
            );
        }

        return (object) array(
            'alg' => $alg_upper,
            'key' => $key
        );
    }

    /**
     * Validate token's signature
     *
     * @param string $token
     * @param string $key
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    private function _validate_signature($token, $key)
    {
        $headers   = $this->_decode_segment($token);
        $signature = $this->_decode_segment($token, 2, true);

        if ($headers['alg'] === 'ES256' || $headers['alg'] === 'ES384') {
            // OpenSSL expects an ASN.1 DER sequence for ES256/ES384 signatures
            $signature = $this->_signature_to_der($signature);
        }

        // Now verifying the token
        list($a, $b)                = explode('.', $token);
        list($function, $algorithm) = $this->_supported_algs[$headers['alg']];
        $msg                        = "{$a}.{$b}";

        if ($function === 'openssl') {
            $success = openssl_verify($msg, $signature, $key, $algorithm) === 1;
        } else if ($function === 'sodium_crypto') {
            if (!function_exists('sodium_crypto_sign_verify_detached')) {
                throw new RuntimeException('libsodium is not available');
            }

            // The last non-empty line is used as the key.
            $lines   = array_filter(explode("\n", $key));
            $key     = base64_decode((string) end($lines));
            $success = sodium_crypto_sign_verify_detached($signature, $msg, $key);
        } else {
            $hash    = hash_hmac($algorithm, $msg, $key, true);
            $success = $this->_constant_time_equals($hash, $signature);
        }

        if ($success !== true) {
            throw new RuntimeException('Invalid token signature');
        }
    }

    /**
     * Comparing hashes
     *
     * @param string $left  The string of known length to compare against
     * @param string $right The user-supplied string
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _constant_time_equals($left, $right)
    {
        $response = false;

        if (function_exists('hash_equals')) {
            $response = hash_equals($left, $right);
        } else {
            $len = min($this->_safe_strlen($left), $this->_safe_strlen($right));

            $status = 0;
            for ($i = 0; $i < $len; $i++) {
                $status |= (ord($left[$i]) ^ ord($right[$i]));
            }

            $status |= ($this->_safe_strlen($left) ^ $this->_safe_strlen($right));

            $response = $status === 0;
        }

        return $response;
    }

    /**
     * Get the number of bytes in cryptographic strings.
     *
     * @param string $str
     *
     * @return int
     * @access private
     *
     * @version 7.0.0
     */
    private function _safe_strlen($str)
    {
        $response = 0;

        if (function_exists('mb_strlen')) {
            $response = mb_strlen($str, '8bit');
        } else {
            $response = strlen($str);
        }

        return $response;
    }

    /**
     * Convert an ECDSA signature to an ASN.1 DER sequence
     *
     * @param string $sig The ECDSA signature to convert
     *
     * @return string The encoded DER object
     * @access private
     *
     * @version 7.0.0
     */
    private function _signature_to_der($sig)
    {
        // Separate the signature into r-value and s-value
        $length      = max(1, (int) (strlen($sig) / 2));
        list($r, $s) = str_split($sig, $length);

        // Trim leading zeros
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        // Convert r-value and s-value from unsigned big-endian integers to
        // signed two's complement
        if (ord($r[0]) > 0x7f) {
            $r = "\x00" . $r;
        }

        if (ord($s[0]) > 0x7f) {
            $s = "\x00" . $s;
        }

        return $this->_encode_der(
            0x10, $this->_encode_der(0x02, $r) . $this->_encode_der(0x02, $s)
        );
    }

    /**
     * Encodes signature from a DER object.
     *
     * @param string $der binary signature in DER format
     * @param int    $keySize the number of bits in the key
     *
     * @return string  the signature
     * @access private
     *
     * @version 7.0.0
     */
    private function _signature_from_der($der, $keySize)
    {
        // OpenSSL returns the ECDSA signatures as a binary ASN.1 DER SEQUENCE
        list($offset, $_) = $this->_read_der($der);
        list($offset, $r) = $this->_read_der($der, $offset);
        list($offset, $s) = $this->_read_der($der, $offset);

        // Convert r-value and s-value from signed two's compliment to unsigned
        // big-endian integers
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        // Pad out r and s so that they are $keySize bits long
        $r = str_pad($r, $keySize / 8, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, $keySize / 8, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    /**
     * Reads binary DER-encoded data and decodes into a single object
     *
     * @param string $der    the binary data in DER format
     * @param int    $offset the offset of the data stream containing the object
     *                       to decode
     *
     * @return array{int, string|null} the new offset and the decoded object
     * @access private
     *
     * @version 7.0.0
     */
    private function _read_der($der, $offset = 0)
    {
        $pos         = $offset;
        $size        = strlen($der);
        $constructed = (ord($der[$pos]) >> 5) & 0x01;
        $type        = ord($der[$pos++]) & 0x1f;

        // Length
        $len = ord($der[$pos++]);
        if ($len & 0x80) {
            $n = $len & 0x1f;
            $len = 0;
            while ($n-- && $pos < $size) {
                $len = ($len << 8) | ord($der[$pos++]);
            }
        }

        // Value
        if ($type === 0x03) {
            $pos++; // Skip the first contents octet (padding indicator)
            $data = substr($der, $pos, $len - 1);
            $pos += $len - 1;
        } elseif (!$constructed) {
            $data = substr($der, $pos, $len);
            $pos += $len;
        } else {
            $data = null;
        }

        return array($pos, $data);
    }

    /**
     * Encodes a value into a DER object.
     *
     * @param int    $type DER tag
     * @param string $value the value to encode
     *
     * @return string  the encoded object
     * @access private
     *
     * @version 7.0.0
     */
    private function _encode_der($type, $value)
    {
        $tag_header = 0;

        if ($type === 0x10) {
            $tag_header |= 0x20;
        }

        // Type
        $der = chr($tag_header | $type);

        // Length
        $der .= chr(strlen($value));

        return $der . $value;
    }

    /**
     * Extract key from certificate
     *
     * @param bool $to_sign
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_signing_key_from_cert($to_sign)
    {
        $response = null;

        if (extension_loaded('openssl')) {
            $config = AAM_Framework_Manager::_()->config;

            if ($to_sign) {
                $path = str_replace('{ABSPATH}', ABSPATH, $config->get(
                    'service.jwt.private_cert_path'
                ));

                $key        = (is_readable($path) ? file_get_contents($path) : null);
                $passphrase = $config->get('service.jwt.private_cert_passphrase');
                $response   = openssl_pkey_get_private($key, $passphrase);
            } else {
                $path = str_replace('{ABSPATH}', ABSPATH, $config->get(
                    'service.jwt.public_cert_path'
                ));

                $key      = (is_readable($path) ? file_get_contents($path) : null);
                $response = openssl_pkey_get_public($key);
            }
        }

        return $response;
    }

    /**
     * Decode a token's segment
     *
     * @param string  $token
     * @param integer $segment
     * @param boolean $returnRaw
     *
     * @return mixed
     *
     * @access protected
     * @version 7.0.0
     */
    private function _decode_segment($token, $segment = 0, $return_raw = false)
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }

        // Base64 decode the value
        $decoded = $this->_url_safe_b64_decode($segments[$segment]);

        return $return_raw ? $decoded : $this->_json_decode($decoded);
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     * @access private
     *
     * @version 7.0.0
     */
    private function _url_safe_b64_decode($input)
    {
        $remainder = strlen($input) % 4;

        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     * @access private
     *
     * @version 7.0.0
     */
    private function _url_safe_b64_encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * Decode a JSON string into a PHP object.
     *
     * @param string $input JSON string
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _json_decode($input)
    {
        $result = json_decode($input, true, 512, JSON_BIGINT_AS_STRING);

        if ($errno = json_last_error()) {
            throw new RuntimeException('Failed to decode JSON: ' . esc_js($errno));
        } elseif (!is_array($result)) {
            throw new UnexpectedValueException('Unexpected segment value');
        }

        return $result;
    }

    /**
     * Encode a PHP array into a JSON string.
     *
     * @param array<mixed> $input A PHP array
     *
     * @return string JSON representation of the PHP array
     * @access private
     *
     * @version 7.0.0
     */
    private function _json_encode(array $input): string
    {
        if (PHP_VERSION_ID >= 50400) {
            $json = json_encode($input, JSON_UNESCAPED_SLASHES);
        } else {
            // PHP 5.3 only
            $json = json_encode($input);
        }

        if ($errno = json_last_error()) {
            throw new RuntimeException('Failed to encode JSON: ' . esc_js($errno));
        } elseif ($json === false) {
            throw new RuntimeException(
                'Provided object could not be encoded to valid JSON'
            );
        }

        return $json;
    }

}