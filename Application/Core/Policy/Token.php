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
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @since AAM v5.8.2
 */
final class AAM_Core_Policy_Token {
    
    /**
     * Literal map token's type to the executable method that returns actual value
     * 
     * @var array
     * 
     * @access protected
     * @static 
     */
    protected static $map = array(
        'USER'      => 'AAM_Core_Policy_Token::getUserValue',
        'USERMETA'  => 'AAM_Core_Policy_Token::getUserMetaValue',
        'DATETIME'  => 'AAM_Core_Policy_Token::getDateTimeValue',
        'GET'       => 'AAM_Core_Request::get',
        'QUERY'     => 'AAM_Core_Request::get',
        'POST'      => 'AAM_Core_Request::post',
        'COOKIE'    => 'AAM_Core_Request::cookie',
        'SERVER'    => 'AAM_Core_Request::server',
        'ARGS'      => 'AAM_Core_Policy_Token::getArgValue',
        'CONST'     => 'AAM_Core_Policy_Token::defined'
    );
    
    /**
     * Evaluate collection of tokens and replace them with values
     * 
     * @param string $part   String with tokens
     * @param array  $tokens Extracted token
     * 
     * @return string
     * 
     * @access public
     * @static
     */
    public static function evaluate($part, array $tokens, array $args = array()) {
        foreach($tokens as $token) {
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
     * @static
     */
    protected static function getValue($token, $args) {
        $value = null;
        $parts = explode('.', $token);

        if (isset(self::$map[$parts[0]])) {
            if ($parts[0] === 'ARG') {
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
     * @static
     */
    protected static function getUserValue($prop) {
        $user = AAM::api()->getUser();
        
        switch(strtolower($prop)) {
            case 'ip':
            case 'ipaddress':
                $value = AAM_Core_Request::server('REMOTE_ADDR');
                break;
            
            case 'authenticated':
            case 'isauthenticated':
                $value = $user->isVisitor() ? false : true;
                break;

            case 'capabilities':
            case 'caps':
                $value = array();
                foreach((array) $user->allcaps as $cap => $effect) {
                    if (!empty($effect)) {
                        $value[] = $cap;
                    }
                }
                break;
            
            default:
                $value = $user->{$prop};
                break;
        }
        
        return $value;
    }

    /**
     * Get user meta value(s)
     *
     * @param string $metakey
     * 
     * @return void
     * 
     * @access protected
     * @static
     */
    protected static function getUserMetaValue($metakey) {
        $value = null;
        $id    = get_current_user_id();

        if (!empty($id)) { // Only authenticated users have some sort of meta
            $meta = get_user_meta($id, $metakey);

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
     * @static
     */
    protected static function getArgValue($prop, $args) {
        return (isset($args[$prop]) ? $args[$prop] : null);
    }
    
    /**
     * Get current datetime value
     * 
     * @param string $prop
     * 
     * @return string
     * 
     * @access protected
     * @static
     */
    protected static function getDateTimeValue($prop) {
        return date($prop);
    }
    
    /**
     * Get a value for the defined constant
     *
     * @param string $const
     * 
     * @return mixed
     * 
     * @access protected
     * @static
     */
    protected static function defined($const) {
        return (defined($const) ? constant($const) : null);
    }
    
}