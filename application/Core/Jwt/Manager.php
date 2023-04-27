<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM JWT Manager
 *
 * Majority of the work is taken from the Firebase PHP JWT library. The code was
 * adopted to work with PHP 5.6.0+.
 *
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/273
 * @since 6.9.8  https://github.com/aamplugin/advanced-access-manager/issues/263
 * @since 6.9.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.10
 *
 * @link https://github.com/firebase/php-jwt
 */
class AAM_Core_Jwt_Manager
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * When checking nbf, iat or expiration times,
     * we want to provide some extra leeway time to
     * account for clock skew.
     *
     * @var int
     * @version 6.9.0
     */
    private $_leeway = 0;

    /**
     * Collection of supported signing algorithms
     *
     * @var array
     *
     * @access private
     * @version 6.9.0
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
     * Verify that the provided token is valid
     *
     * @param string $token
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.0
     */
    public function validate($token)
    {
        try {
            // Validating headers segment. Make sure that all necessary properties
            // are defined correctly
            $this->validateHeaders($token);

            // Get signing attributes
            $attrs = $this->getSigningAttributes();

            // Verify the signature
            $this->validateSignature($token, $attrs->key);

            $timestamp = (new DateTime('now', new DateTimeZone('UTC')))->getTimestamp();
            $payload   = $this->decodeSegment($token, 1);

            // Check the nbf if it is defined. This is the time that the
            // token can actually be used. If it's not yet that time, abort.
            if (isset($payload->nbf) && $payload->nbf > ($timestamp + $this->_leeway)) {
                throw new Exception(
                    'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->nbf)
                );
            }

            // Check that this token has been created before 'now'. This prevents
            // using tokens that have been created for later use (and haven't
            // correctly used the nbf claim).
            if (isset($payload->iat) && $payload->iat > ($timestamp + $this->_leeway)) {
                throw new Exception(
                    'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->iat)
                );
            }

            // Check if this token has expired.
            if (isset($payload->exp) && ($timestamp - $this->_leeway) >= $payload->exp) {
                throw new Exception('Expired token');
            }
        } catch (Exception $e) {
            $payload = new WP_Error(1, $e->getMessage(), $e);
        }

        return $payload;
    }

    /**
     * Encode payload
     *
     * @param array $payload
     *
     * @since 6.9.8 https://github.com/aamplugin/advanced-access-manager/issues/263
     * @since 6.9.0 Initial implementation of the method
     *
     * @return string
     * @version 6.9.8
     */
    public function encode($payload)
    {
        $attrs = $this->getSigningAttributes();

        // Encode the JWT headers first
        $segments = array($this->urlsafeB64Encode($this->jsonEncode(array(
            'typ' => 'JWT',
            'alg' => $attrs->alg
        ))));


        $ttl = AAM_Core_Config::get('authentication.jwt.expires', '+24 hours');

        if (is_numeric($ttl)) {
            $ttl = "+{$ttl} seconds";
        }

        $time = new DateTime($ttl, new DateTimeZone('UTC'));

        $claims = apply_filters(
            'aam_jwt_claims_filter',
            array_merge(
                array(
                    "iat" => time(),
                    'iss' => get_site_url(),
                    'exp' => $time->getTimestamp(),
                    'jti' => $this->generateUuid()
                ),
                $payload
            )
        );

        // Next, let's encode the payload
        array_push($segments, $this->urlsafeB64Encode($this->jsonEncode($claims)));

        // Adding signature to as the last segment
        array_push($segments, $this->urlsafeB64Encode($this->sign(
            implode('.', $segments),
            $attrs->key,
            $attrs->alg
        )));

        return (object) array(
            'token'  => implode('.', $segments),
            'claims' => $claims
        );
    }

    /**
     * Decode token
     *
     * @param string $token
     *
     * @return WP_Error|stdClass
     * @version 6.9.0
     */
    public function decode($token)
    {
        return $this->validate($token, $this->getSigningAttributes()->key);
    }

    /**
     * Just extract claims and ignore errors
     *
     * @param string $token
     *
     * @return array|null
     *
     * @access public
     * @version 6.9.10
     */
    public function extractClaims($token)
    {
        $response = null;

        try {
            $response = $this->decodeSegment($token, 1);
        } catch (Exception $e) {
            // Do nothing
        }

        return $response;
    }

    /**
     * Generate random uuid
     *
     * @return string
     *
     * @access protected
     * @version 6.9.0
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

    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string $msg  The message to sign
     * @param string|resource|OpenSSLAsymmetricKey|OpenSSLCertificate  $key  The secret key.
     * @param string $alg  Supported algorithms are 'ES384','ES256', 'HS256', 'HS384',
     *                    'HS512', 'RS256', 'RS384', and 'RS512'
     *
     * @return string An encrypted message
     * @version 6.9.0
     *
     * @throws DomainException Unsupported algorithm or bad key was specified
     */
    protected function sign($msg, $key, $alg)
    {
        $signature = null;

        if (empty($this->_supported_algs[$alg])) {
            throw new DomainException('Algorithm not supported');
        } elseif (empty($key) || !is_string($key)) {
            throw new InvalidArgumentException('The signing key must be a string');
        }

        list($function, $algorithm) = $this->_supported_algs[$alg];

        if ($function === 'hash_hmac') {
            $signature = hash_hmac($algorithm, $msg, $key, true);
        } elseif ($function === 'openssl') {
            $success = openssl_sign($msg, $signature, $key, $algorithm);

            if (!$success) {
                throw new DomainException('OpenSSL unable to sign data');
            }
            if ($alg === 'ES256') {
                $signature = $this->signatureFromDER($signature, 256);
            } elseif ($alg === 'ES384') {
                $signature = $this->signatureFromDER($signature, 384);
            }
        } elseif ($function === 'sodium_crypto') {
            if (!function_exists('sodium_crypto_sign_detached')) {
                throw new DomainException('libsodium is not available');
            }

            // The last non-empty line is used as the key.
            $lines     = array_filter(explode("\n", $key));
            $key       = base64_decode((string) end($lines));
            $signature = sodium_crypto_sign_detached($msg, $key);
        } else {
            throw new DomainException('Algorithm not supported');
        }

        return $signature;
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     * @version 6.9.0
     */
    protected function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * Encode a PHP array into a JSON string.
     *
     * @param array<mixed> $input A PHP array
     *
     * @return string JSON representation of the PHP array
     * @version 6.9.0
     *
     * @throws DomainException Provided object could not be encoded to valid JSON
     */
    protected function jsonEncode(array $input): string
    {
        if (PHP_VERSION_ID >= 50400) {
            $json = json_encode($input, JSON_UNESCAPED_SLASHES);
        } else {
            // PHP 5.3 only
            $json = json_encode($input);
        }

        if ($errno = json_last_error()) {
            throw new DomainException('Failed to encode JSON with error ' . $errno);
        } elseif ($json === false) {
            throw new DomainException('Provided object could not be encoded to valid JSON');
        }

        return $json;
    }

    /**
     * Get token signing attributes like algorithm and key
     *
     * @return stdClass
     *
     * @access protected
     * @version 6.9.0
     */
    protected function getSigningAttributes()
    {
        $alg = strtoupper(
            AAM_Core_Config::get('authentication.jwt.algorithm', 'HS256')
        );

        if (strpos($alg, 'RS') === 0) {
            $path       = AAM_Core_Config::get('authentication.jwt.privateKeyPath');
            $key        = (is_readable($path) ? file_get_contents($path) : null);
            $passphrase = AAM_Core_Config::get('authentication.jwt.passphrase', false);

            if ($passphrase && extension_loaded('openssl')) {
                $key = openssl_pkey_get_private($key, $passphrase);
            }
        } else {
            $key = AAM_Core_Config::get('authentication.jwt.secret', SECURE_AUTH_KEY);
        }

        return (object) array(
            'alg' => $alg,
            'key' => $key
        );
    }

    /**
     * Validate token headers
     *
     * @param string $token
     *
     * @return void
     *
     * @access protected
     * @version 6.9.0
     */
    protected function validateHeaders($token)
    {
        $headers = $this->decodeSegment($token);

        if (empty($headers->alg)) {
            throw new UnexpectedValueException('Empty algorithm');
        }

        if (empty($this->_supported_algs[$headers->alg])) {
            throw new UnexpectedValueException('Algorithm not supported');
        }
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
     * @version 6.9.0
     */
    protected function validateSignature($token, $key)
    {
        $headers   = $this->decodeSegment($token);
        $signature = $this->decodeSegment($token, 2, true);

        if ($headers->alg === 'ES256' || $headers->alg === 'ES384') {
            // OpenSSL expects an ASN.1 DER sequence for ES256/ES384 signatures
            $signature = $this->signatureToDER($signature);
        }

        // Now verifying the token
        list($a, $b)                = explode('.', $token);
        list($function, $algorithm) = $this->_supported_algs[$headers->alg];
        $msg                        = "{$a}.{$b}";

        if ($function === 'openssl') {
            $success = openssl_verify($msg, $signature, $key, $algorithm) === 1;
        } else if ($function === 'sodium_crypto') {
            if (!function_exists('sodium_crypto_sign_verify_detached')) {
                throw new DomainException('libsodium is not available');
            }

            // The last non-empty line is used as the key.
            $lines   = array_filter(explode("\n", $key));
            $key     = base64_decode((string) end($lines));
            $success = sodium_crypto_sign_verify_detached($signature, $msg, $key);
        } else {
            $hash    = hash_hmac($algorithm, $msg, $key, true);
            $success = $this->constantTimeEquals($hash, $signature);
        }

        if ($success !== true) {
            throw new Exception('Invalid token signature');
        }
    }

    /**
     * Comparing hashes
     *
     * @param string $left  The string of known length to compare against
     * @param string $right The user-supplied string
     *
     * @return bool
     *
     * @access protected
     * @version 6.9.0
     */
    protected function constantTimeEquals($left, $right)
    {
        $response = false;

        if (function_exists('hash_equals')) {
            $response = hash_equals($left, $right);
        } else {
            $len = min($this->safeStrlen($left), $this->safeStrlen($right));

            $status = 0;
            for ($i = 0; $i < $len; $i++) {
                $status |= (ord($left[$i]) ^ ord($right[$i]));
            }

            $status |= ($this->safeStrlen($left) ^ $this->safeStrlen($right));

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
     * @version 6.9.0
     */
    protected function safeStrlen($str)
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
     * Decode a token's segment
     *
     * @param string  $token
     * @param integer $segment
     * @param boolean $returnRaw
     *
     * @return mixed
     *
     * @access protected
     * @version 6.9.0
     */
    protected function decodeSegment($token, $segment = 0, $returnRaw = false)
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }

        // Base64 decode the value
        $decoded = $this->urlsafeB64Decode($segments[$segment]);

        return $returnRaw ? $decoded : $this->jsonDecode($decoded);
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     *
     * @throws InvalidArgumentException invalid base64 characters
     * @version 6.9.0
     */
    protected function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;

        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Decode a JSON string into a PHP object.
     *
     * @param string $input JSON string
     *
     * @return mixed The decoded JSON string
     * @version 6.9.0
     *
     * @throws DomainException Provided string was invalid JSON
     */
    protected function jsonDecode($input)
    {
        $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);

        if (is_array($obj)) { // Covering scenario when value is empty array
            $obj = (object) $obj;
        }

        if ($errno = json_last_error()) {
            throw new DomainException('Failed to decode JSON with error ' . $errno);
        } elseif (!is_a($obj, stdClass::class)) {
            throw new UnexpectedValueException('Unexpected segment value');
        }

        return $obj;
    }

    /**
     * Convert an ECDSA signature to an ASN.1 DER sequence
     *
     * @param   string $sig The ECDSA signature to convert
     *
     * @return  string The encoded DER object
     * @version 6.9.0
     */
    protected function signatureToDER($sig)
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

        return self::encodeDER(
            0x10,
            self::encodeDER(0x02, $r) .
                self::encodeDER(0x02, $s)
        );
    }

    /**
     * Encodes signature from a DER object.
     *
     * @param   string  $der binary signature in DER format
     * @param   int     $keySize the number of bits in the key
     *
     * @return  string  the signature
     * @version 6.9.0
     */
    protected function signatureFromDER($der, $keySize)
    {
        // OpenSSL returns the ECDSA signatures as a binary ASN.1 DER SEQUENCE
        list($offset, $_) = $this->readDER($der);
        list($offset, $r) = $this->readDER($der, $offset);
        list($offset, $s) = $this->readDER($der, $offset);

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
     * @param string $der the binary data in DER format
     * @param int $offset the offset of the data stream containing the object
     * to decode
     *
     * @return array{int, string|null} the new offset and the decoded object
     * @version 6.9.0
     */
    protected function readDER($der, $offset = 0)
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
     * @param   int     $type DER tag
     * @param   string  $value the value to encode
     *
     * @return  string  the encoded object
     * @version 6.9.0
     */
    protected function encodeDER($type, $value)
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

}