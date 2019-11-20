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
 * AAM core policy token evaluator
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Core_Policy_Token
{

    /**
     * Literal map token's type to the executable method that returns actual value
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected static $map = array(
        'USER'         => 'AAM_Core_Policy_Token::getUserValue',
        'USER_OPTION'  => 'AAM_Core_Policy_Token::getUserOptionValue',
        'USER_META'    => 'AAM_Core_Policy_Token::getUserMetaValue',
        'DATETIME'     => 'date',
        'HTTP_GET'     => 'AAM_Core_Request::get',
        'HTTP_QUERY'   => 'AAM_Core_Request::get',
        'HTTP_POST'    => 'AAM_Core_Request::post',
        'HTTP_COOKIE'  => 'AAM_Core_Request::cookie',
        'PHP_SERVER'   => 'AAM_Core_Request::server',
        'ARGS'         => 'AAM_Core_Policy_Token::getArgValue',
        'ENV'          => 'getenv',
        'CONST'        => 'AAM_Core_Policy_Token::getConstant',
        'WP_OPTION'    => 'AAM_Core_API::getOption',
        'JWT'          => 'AAM_Core_Policy_Token::getJwtClaim'
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
     * @access public
     * @version 6.0.0
     */
    public static function evaluate($part, array $tokens, array $args = array())
    {
        foreach ($tokens as $token) {
            $val = self::getValue(
                preg_replace('/^\$\{([^}]+)\}$/', '${1}', $token),
                $args
            );

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
     * @access protected
     * @version 6.0.0
     */
    protected static function getValue($token, $args)
    {
        $value = null;
        $parts = explode('.', $token);

        if (isset(self::$map[$parts[0]])) {
            if ($parts[0] === 'ARGS') {
                $value = call_user_func(self::$map[$parts[0]], $parts[1], $args);
            } else {
                $value = call_user_func(self::$map[$parts[0]], $parts[1]);
            }
        } elseif ($parts[0] === 'CALLBACK') {
            $value = is_callable($parts[1]) ? call_user_func($parts[1], $args) : null;
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
     * @access protected
     * @version 6.0.0
     */
    protected static function getUserValue($prop)
    {
        $user = AAM::getUser();

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
                foreach ((array) $user->allcaps as $cap => $effect) {
                    if (!empty($effect)) {
                        $value[] = $cap;
                    }
                }
                break;

            default:
                $value = (is_a($user, 'AAM_Core_Subject_User') ? $user->{$prop} : null);
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

}