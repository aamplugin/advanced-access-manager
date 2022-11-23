<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core policy token evaluator
 *
 * @since 6.9.3 https://github.com/aamplugin/advanced-access-manager/issues/235
 * @since 6.8.3 https://github.com/aamplugin/advanced-access-manager/issues/205
 * @since 6.3.0 Fixed bug that was causing fatal error policies that have conditions
 *              defined for Capability & Role resources
 * @since 6.2.1 Added POLICY_META token
 * @since 6.2.0 Enhanced access policy with more tokens. DATETIME now returns time in
 *              UTC timezone
 * @since 6.1.0 Added support for the new token `AAM_CONFIG`
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.3
 */
class AAM_Core_Policy_Token
{

    /**
     * Literal map token's type to the executable method that returns actual value
     *
     * @var array
     *
     * @since 6.8.5 https://github.com/aamplugin/advanced-access-manager/issues/216
     * @since 6.8.3 https://github.com/aamplugin/advanced-access-manager/issues/205
     * @since 6.3.0 Added PHP_GLOBAL, WP_NETWORK_OPTION token and changed
     *                    WP_OPTION callback
     * @since 6.2.1 Added `POLICY_META` token
     * @since 6.2.0 Added `POLICY_PARAM`, `WP_SITE` token & changed the
     *              DATETIME callback
     * @since 6.1.0 Added `AAM_CONFIG` token
     * @since 6.0.0 Initial implementation of the property
     *
     * @access protected
     * @version 6.8.5
     */
    protected static $map = array(
        'USER'              => 'AAM_Core_Policy_Token::getUserValue',
        'USER_OPTION'       => 'AAM_Core_Policy_Token::getUserOptionValue',
        'USER_META'         => 'AAM_Core_Policy_Token::getUserMetaValue',
        'DATETIME'          => 'AAM_Core_Policy_Token::getDatetime',
        'HTTP_GET'          => 'AAM_Core_Request::get',
        'HTTP_QUERY'        => 'AAM_Core_Request::get',
        'HTTP_POST'         => 'AAM_Core_Request::post',
        'HTTP_COOKIE'       => 'AAM_Core_Request::cookie',
        'PHP_SERVER'        => 'AAM_Core_Request::server',
        'PHP_GLOBAL'        => 'AAM_Core_Policy_Token::getGlobalVariable',
        'ARGS'              => 'AAM_Core_Policy_Token::getArgValue',
        'ENV'               => 'getenv',
        'CONST'             => 'AAM_Core_Policy_Token::getConstant',
        'WP_OPTION'         => 'AAM_Core_Policy_Token::getWPOption',
        'JWT'               => 'AAM_Core_Policy_Token::getJwtClaim',
        'AAM_CONFIG'        => 'AAM_Core_Policy_Token::getConfig',
        'POLICY_PARAM'      => 'AAM_Core_Policy_Token::getParam',
        'POLICY_META'       => 'AAM_Core_Policy_Token::getPolicyMeta',
        'WP_SITE'           => 'AAM_Core_Policy_Token::getSiteParam',
        'WP_NETWORK_OPTION' => 'AAM_Core_Policy_Token::getNetworkOption',
        'THE_POST'          => 'AAM_Core_Policy_Token::getCurrentPostValue'
    );

    /**
     * Evaluate collection of tokens and replace them with values
     *
     * @param string $part   String with tokens
     * @param array  $tokens Extracted token
     * @param array  $args   Inline arguments
     *
     * @return string
     *
     * @since 6.1.0 Changed `getValue` method to `getTokenValue`
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public static function evaluate($part, array $tokens, array $args = array())
    {
        foreach ($tokens as $token) {
            $val  = self::getTokenValue($token, $args);
            $part = str_replace(
                $token,
                (is_scalar($val) || is_null($val) ? $val : json_encode($val)),
                $part
            );
        }

        return $part;
    }

    /**
     * Get token value
     *
     * @param string $token
     * @param array  $args
     *
     * @return mixed
     *
     * @since 6.8.3 https://github.com/aamplugin/advanced-access-manager/issues/206
     * @since 6.3.3 https://github.com/aamplugin/advanced-access-manager/issues/50
     * @since 6.1.0 Initial implementation of the method
     *
     * @access public
     * @version 6.8.3
     */
    public static function getTokenValue($token, $args = array())
    {
        $parts = explode('.', preg_replace('/^\$\{([^}]+)\}$/', '${1}', $token), 2);

        if (isset(self::$map[$parts[0]])) {
            if ($parts[0] === 'ARGS') {
                $value = call_user_func(self::$map[$parts[0]], $parts[1], $args);
            } else {
                $value = call_user_func(self::$map[$parts[0]], $parts[1]);
            }
        } elseif ($parts[0] === 'CALLBACK') {
            $value = self::evaluateCallback($parts[1], $args);
        } else {
            $value = apply_filters(
                'aam_policy_token_value_filter', null, $parts[0], $parts[1], $args
            );
        }

        return $value;
    }

    /**
     * Evaluate CALLBACK expression
     *
     * @param string $exp
     * @param array  $args
     *
     * @return mixed
     *
     * @access protected
     * @version 6.8.3
     */
    protected static function evaluateCallback($exp, $args)
    {
        $response = null;
        $cb       = self::_parseFunction($exp, $args);

        if (!is_null($cb)) {
            if (is_callable($cb['func']) || function_exists($cb['func'])) {
                $result = call_user_func_array($cb['func'], $cb['args']);

                if (!empty($cb['xpath'])) {
                    $response = self::_getValueByXPath($result, $cb['xpath']);
                } else {
                    $response = $result;
                }
            }
        }

        return $response;
    }

    /**
     * Parse CALLBACK expression
     *
     * @param string $exp
     * @param array  $args
     *
     * @return array
     *
     * @access private
     * @version 6.8.3
     */
    private static function _parseFunction($exp, $args)
    {
        $response = null;
        $regex    = '/^([^(]+)\(?([^)]*)\)?(.*)$/i';

        if (preg_match($regex, $exp, $match)) {
            // The second part is the collection of arguments that we pass to
            // the function
            $markers = array_map('trim', explode(',', $match[2]));
            $values  = [];

            foreach($markers as $marker) {
                if (preg_match('/^\'.*\'$/', $marker) === 1) { // This is literal string
                    array_push($values, trim($marker, '\''));
                } elseif (strpos($marker, '.') !== false) { // Potentially another marker
                    array_push($values, self::getTokenValue($marker, $args));
                } else {
                    array_push($values, $marker);
                }
            }

            $response = array(
                'func'  => trim($match[1]),
                'args'  => $values,
                'xpath' => trim($match[3])
            );
        }

        return $response;
    }

    /**
     * Get value by xpath
     *
     * This method supports multiple different path
     *
     * @param mixed  $obj
     * @param string $xpath
     *
     * @return mixed
     *
     * @access private
     * @version 6.8.3
     */
    private static function _getValueByXPath($obj, $xpath)
    {
        $value = $obj;
        $path  = trim(
            str_replace(
                array('["', '[', '"]', ']', '..'), '.', $xpath
            ),
            ' .' // white space is important!
        );

        foreach(explode('.', $path) as $l) {
            if (is_object($value)) {
                if (isset($value->{$l})) {
                    $value = $value->{$l};
                } else {
                    $value = null;
                    break;
                }
            } else if (is_array($value)) {
                if (array_key_exists($l, $value)) {
                    $value = $value[$l];
                } else {
                    $value = null;
                    break;
                }
            }
        }

        return $value;
    }

    /**
     * Get USER's value
     *
     * @param string $prop
     *
     * @return mixed
     *
     * @since 6.9.3 https://github.com/aamplugin/advanced-access-manager/issues/235
     * @since 6.3.0 Fixed bug that caused "Fatal error: Allowed memory size of XXX
     *              bytes exhausted"
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.3
     */
    protected static function getUserValue($prop)
    {
        $value = null;
        $user  = wp_get_current_user();

        switch (strtolower($prop)) {
            case 'ip':
            case 'ipaddress':
                $value = AAM_Core_Request::server('REMOTE_ADDR');
                break;

            case 'authenticated':
            case 'isauthenticated':
                $value = is_user_logged_in();
                break;

            case 'capabilities':
            case 'caps':
                $allcaps = is_a($user, 'WP_User') ? (array)$user->allcaps : array();

                foreach ($allcaps as $cap => $effect) {
                    if (!empty($effect)) {
                        $value[] = $cap;
                    }
                }
                break;

            default:
                $value = (is_a($user, 'WP_User') ? $user->{$prop} : null);
                break;
        }

        return $value;
    }

    /**
     * Get user option value(s)
     *
     * @param string $option_name
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function getUserOptionValue($option_name)
    {
        $value = null;
        $id    = get_current_user_id();

        if (!empty($id)) { // Only authenticated users have some sort of meta
            $value = get_user_option($option_name, $id);
        }

        return $value;
    }

    /**
     * Get currently viewed post property
     *
     * @param string $option_name
     *
     * @return mixed
     *
     * @access protected
     * @version since 6.8.3
     */
    protected static function getCurrentPostValue($option_name)
    {
        $post = AAM_Core_API::getCurrentPost();

        return is_a($post, 'AAM_Core_Object_Post') ? $post->{$option_name} : null;
    }

    /**
     * Get user meta value(s)
     *
     * @param string $meta_key
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function getUserMetaValue($meta_key)
    {
        $value = null;
        $id    = get_current_user_id();

        if (!empty($id)) { // Only authenticated users have some sort of meta
            $meta = get_user_meta($id, $meta_key);

            // If $meta has only one value in the array, then extract it, otherwise
            // return the array of values
            if (count($meta) === 1) {
                $value = array_shift($meta);
            } else {
                $value = array_values($meta);
            }
        }

        return $value;
    }

    /**
     * Get inline argument
     *
     * @param string $prop
     * @param array  $args
     *
     * @return mixed
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function getArgValue($prop, $args)
    {
        return (isset($args[$prop]) ? $args[$prop] : null);
    }

    /**
     * Get JWT claim property
     *
     * @param string $prop
     *
     * @return mixed
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function getJwtClaim($prop)
    {
        return apply_filters('aam_get_jwt_claim', null, $prop);
    }

    /**
     * Get a value for the defined constant
     *
     * @param string $const
     *
     * @return mixed
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function getConstant($const)
    {
        return (defined($const) ? constant($const) : null);
    }

    /**
     * Get database option
     *
     * @param string $option
     *
     * @return mixed
     *
     * @access protected
     * @version 6.3.0
     */
    protected static function getWPOption($option)
    {
        if (is_multisite()) {
            $result = get_blog_option(get_current_blog_id(), $option);
        } else {
            $result = get_option($option);
        }

        return $result;
    }

    /**
     * Get AAM configuration
     *
     * @param string $config
     *
     * @return mixed
     *
     * @access protected
     * @version 6.1.0
     */
    protected static function getConfig($config)
    {
        return AAM::api()->getConfig($config);
    }

    /**
     * Get access policy param
     *
     * @param string $param
     *
     * @return mixed
     *
     * @access protected
     * @version 6.2.0
     */
    protected static function getParam($param)
    {
        return AAM::api()->getAccessPolicyManager()->getParam($param);
    }

    /**
     * Get access policy metadata
     *
     * @param string $meta
     *
     * @return mixed
     *
     * @since 6.3.0 Fixed potential bug https://github.com/aamplugin/advanced-access-manager/issues/38
     * @since 6.2.1 Initial implementation of the method
     *
     * @access protected
     * @version 6.3.0
     */
    protected static function getPolicyMeta($meta)
    {
        $parts = explode('.', $meta, 2);

        return get_post_meta(intval($parts[0]), $parts[1], true);
    }

    /**
     * Get current datetime
     *
     * @param string $format
     *
     * @return string
     *
     * @access protected
     * @version 6.2.0
     */
    protected static function getDatetime($format)
    {
        $result = null;

        try {
            $result = (new DateTime('now', new DateTimeZone('UTC')))->format($format);
        } catch (Exception $e) {
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                'Invalid date/time format: ' . $e->getMessage(),
                AAM_VERSION
            );
        }

        return $result;
    }

    /**
     * Get current blog details
     *
     * @param string $param
     *
     * @return mixed
     *
     * @access protected
     * @version 6.2.0
     */
    protected static function getSiteParam($param)
    {
        $result = null;

        if (is_multisite()) {
            $result = get_blog_details()->{$param};
        } elseif ($param === 'blog_id') {
            $result = get_current_blog_id();
        }

        return $result;
    }

    /**
     * Get global variable's value
     *
     * @param string $var
     *
     * @return mixed
     *
     * @since 6.8.5 https://github.com/aamplugin/advanced-access-manager/issues/216
     * @since 6.3.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.8.5
     */
    protected static function getGlobalVariable($var)
    {
        return self::_getValueByXPath($GLOBALS, $var);
    }

    /**
     * Get network option
     *
     * @param string $option
     *
     * @return mixed
     *
     * @access protected
     * @version 6.3.0
     */
    protected static function getNetworkOption($option)
    {
        return get_site_option($option, null);
    }

}