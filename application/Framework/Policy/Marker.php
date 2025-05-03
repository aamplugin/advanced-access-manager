<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

use Vectorface\Whip\Whip;

/**
 * AAM access policy marker evaluator
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Policy_Marker
{

    /**
     * Literal map token's type to the executable method that returns actual value
     *
     * @var array
     * @access protected
     *
     * @version 7.0.0
     */
    protected static $map = array(
        'USER'              => 'AAM_Framework_Policy_Marker::get_user_value',
        'USER_OPTION'       => 'AAM_Framework_Policy_Marker::get_user_option_value',
        'USER_META'         => 'AAM_Framework_Policy_Marker::get_user_meta_value',
        'DATETIME'          => 'AAM_Framework_Policy_Marker::get_datetime',
        'HTTP_GET'          => 'AAM_Framework_Policy_Marker::get_query',
        'HTTP_QUERY'        => 'AAM_Framework_Policy_Marker::get_query',
        'HTTP_POST'         => 'AAM_Framework_Policy_Marker::get_post',
        'HTTP_COOKIE'       => 'AAM_Framework_Policy_Marker::get_cookie',
        'PHP_SERVER'        => 'AAM_Framework_Policy_Marker::get_server',
        'PHP_GLOBAL'        => 'AAM_Framework_Policy_Marker::get_global_variable',
        'ARGS'              => 'AAM_Framework_Policy_Marker::get_arg_value',
        'ENV'               => 'getenv',
        'CONST'             => 'AAM_Framework_Policy_Marker::get_constant',
        'WP_OPTION'         => 'AAM_Framework_Policy_Marker::get_wp_option',
        'AAM_CONFIG'        => 'AAM_Framework_Policy_Marker::get_config',
        'POLICY_PARAM'      => 'AAM_Framework_Policy_Marker::get_param',
        'POLICY_META'       => 'AAM_Framework_Policy_Marker::get_policy_meta',
        'WP_SITE'           => 'AAM_Framework_Policy_Marker::get_site_param',
        'WP_NETWORK_OPTION' => 'AAM_Framework_Policy_Marker::get_network_option',
        'THE_POST'          => 'AAM_Framework_Policy_Marker::get_current_post_prop',
        'JWT'               => 'AAM_Framework_Policy_Marker::get_jwt_claim'
    );

    /**
     * Evaluate expression and replace markers
     *
     * @param string  $exp
     * @param array   $args
     * @param boolean $type_cast [Optional]
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public static function execute($exp, $args = [], $type_cast = true)
    {
        if (preg_match_all('/(\$\{[^}]+\})/', $exp, $match)) {
            foreach ($match[1] as $marker) {
                $value = self::get_marker_value($marker, $args);
                $value = is_null($value) ? '' : $value;

                // Replace marker in the expression BUT ONLY if there are multiple
                // markers in the expression
                if (count($match[1]) > 1) {
                    $exp = str_replace(
                        $marker,
                        (is_scalar($value) ? $value : json_encode($value)),
                        $exp
                    );
                } else {
                    $exp = $value;
                }
            }
        }

        // Perform type casting if necessary
        return $type_cast ? AAM_Framework_Policy_Typecast::execute($exp) : $exp;
    }

    /**
     * Get marker value
     *
     * @param string $marker
     * @param array  $args
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public static function get_marker_value($marker, $args = [])
    {
        $parts = explode('.', preg_replace('/^\$\{([^}]+)\}$/', '${1}', $marker), 2);

        if (array_key_exists($parts[0], self::$map)) {
            if ($parts[0] === 'ARGS') {
                $value = call_user_func(self::$map[$parts[0]], $parts[1], $args);
            } else {
                $value = call_user_func(self::$map[$parts[0]], $parts[1], $args);
            }
        } elseif ($parts[0] === 'CALLBACK') {
            $value = self::evaluate_callback($parts[1], $args);
        } else {
            $value = apply_filters(
                'aam_policy_marker_value_filter', null, $parts[0], $parts[1], $args
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
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function evaluate_callback($exp, $args)
    {
        $response = null;
        $cb       = self::_parse_function($exp, $args);

        if (!is_null($cb)) {
            if (is_callable($cb['func']) || function_exists($cb['func'])) {
                $result = call_user_func_array($cb['func'], $cb['args']);

                if (!empty($cb['xpath'])) {
                    $response = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                        $result, $cb['xpath']
                    );
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
     * @access private
     *
     * @version 7.0.0
     */
    private static function _parse_function($exp, $args)
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
                    array_push(
                        $values,
                        self::get_marker_value('${' . $marker . '}', $args)
                    );
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
     * Get USER's value
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_user_value($xpath)
    {
        $value = null;
        $user  = wp_get_current_user();

        // Support few sudo params
        switch (strtolower($xpath)) {
            case 'id':
                $value = is_user_logged_in() ? $user->ID : null;
                break;

            case 'ip':
            case 'ipaddress':
                $whip  = new Whip();
                $value = $whip->getValidIpAddress();
                break;

            case 'authenticated':
            case 'isauthenticated':
                $value = is_user_logged_in();
                break;

            case 'capabilities':
            case 'caps':
                $all   = $user->allcaps;
                $value = array();

                if (is_array($all)) {
                    foreach ($all as $cap => $effect) {
                        if (!empty($effect)) {
                            $value[] = $cap;
                        }
                    }
                }
                break;

            default:
                $value = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                    $user, $xpath
                );
                break;
        }

        return $value;
    }

    /**
     * Get user option value(s)
     *
     * @param string $xpath
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_user_option_value($xpath)
    {
        $value = null;

        // Getting option name from the xpath
        $path = AAM_Framework_Policy_Xpath::parse_xpath($xpath);

        if (is_user_logged_in() && !empty($path[0])) {
            $value = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                get_user_option(array_shift($path), get_current_user_id()),
                $path
            );
        }

        return $value;
    }

    /**
     * Get user meta value(s)
     *
     * @param string $xpath
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_user_meta_value($xpath)
    {
        $result = null;

        // Getting option name from the xpath
        $path = AAM_Framework_Policy_Xpath::parse_xpath($xpath);

        if (is_user_logged_in() && !empty($path[0])) {
            $meta_data = get_user_meta(get_current_user_id(), array_shift($path));

            // If meta data has only one value in the array, then extract it,
            // otherwise return the array of values
            if (count($meta_data) === 1) {
                $value = array_shift($meta_data);
            } else {
                $value = array_values($meta_data);
            }

            $result = AAM_Framework_Policy_Xpath::get_value_by_xpath($value, $path);
        }

        return $result;
    }

    /**
     * Get inline argument
     *
     * @param string $xpath
     * @param array  $args
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_arg_value($xpath, $args)
    {
        return AAM_Framework_Policy_Xpath::get_value_by_xpath($args, $xpath);
    }

    /**
     * Get JWT claim property
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_jwt_claim($xpath)
    {
        $result    = null;
        $jwt_token = apply_filters('aam_current_jwt_filter', null);

        if (is_string($jwt_token)) {
            $claims = AAM_Framework_Manager::_()->jwt->decode($jwt_token);

            if (!is_wp_error($claims)) {
                $result = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                    $claims, $xpath
                );
            }
        }

        return $result;
    }

    /**
     * Get a value for the defined constant
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_constant($xpath)
    {
        $path = AAM_Framework_Policy_Xpath::parse_xpath($xpath);
        $const = array_shift($path);

        if (!empty($const) && defined($const)) {
            $result = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                constant($const), $path
            );
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get database option
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_wp_option($xpath)
    {
        $path   = AAM_Framework_Policy_Xpath::parse_xpath($xpath);
        $option = array_shift($path);

        if (!empty($option)) {
            $result = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                AAM_Framework_Manager::_()->db->read($option),
                $path
            );
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get AAM configuration
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_config($xpath)
    {
        $path   = AAM_Framework_Policy_Xpath::parse_xpath($xpath);
        $config = array_shift($path);

        if (!empty($config)) {
            $result = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                AAM_Framework_Manager::_()->config->get($config),
                $path
            );
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get access policy param
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_param($xpath)
    {
        $path  = AAM_Framework_Policy_Xpath::parse_xpath($xpath);
        $param = isset($path[0]) ? $path[0] : null;

        if (!empty($param)) {
            $result = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                AAM_Framework_Manager::_()->policies()->get_params($param),
                $path
            );
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get access policy metadata
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_policy_meta($xpath)
    {
        $path      = AAM_Framework_Policy_Xpath::parse_xpath($xpath);
        $policy_id = isset($path[0]) ? intval(array_shift($path)) : null;
        $meta      = isset($path[0]) ? array_shift($path) : null;

        if (!empty($policy_id) && !empty($meta)) {
            $value = get_post_meta($policy_id, $meta, true);

            if (!empty($path)) {
                $result = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                    $value, $path
                );
            } else {
                $result = $value;
            }
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get current datetime
     *
     * @param string $format
     *
     * @return string
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_datetime($format)
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
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_site_param($xpath)
    {
        $result = null;

        if (is_multisite()) {
            $result = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                get_blog_details(), $xpath
            );
        } elseif ($xpath === 'blog_id') {
            $result = get_current_blog_id();
        }

        return $result;
    }

    /**
     * Get global variable's value
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_global_variable($xpath)
    {
        return AAM_Framework_Policy_Xpath::get_value_by_xpath($GLOBALS, $xpath);
    }

    /**
     * Get value from query params
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_query($xpath)
    {
        return AAM_Framework_Manager::_()->misc->get($_GET, $xpath);
    }

    /**
     * Get value from POST params
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_post($xpath)
    {
        return AAM_Framework_Manager::_()->misc->get($_POST, $xpath);
    }

    /**
     * Get value from COOKIE params
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_cookie($xpath)
    {
        return AAM_Framework_Manager::_()->misc->get($_COOKIE, $xpath);
    }

    /**
     * Get value from SERVER params
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_server($xpath)
    {
        return AAM_Framework_Manager::_()->misc->get($_SERVER, $xpath);
    }

    /**
     * Get network option
     *
     * @param string $xpath
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_network_option($xpath)
    {
        $path   = AAM_Framework_Policy_Xpath::parse_xpath($xpath);
        $option = array_shift($path);

        if (!empty($option)) {
            $result = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                get_site_option($option, null),
                $path
            );
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get currently viewed post property
     *
     * @param string $prop
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    protected static function get_current_post_prop($prop)
    {
        $result = null;
        $post   = AAM_Framework_Manager::_()->misc->get_current_post();

        if (is_a($post, WP_Post::class)) {
            if (property_exists($post, $prop)) {
                $result = $post->{$prop};
            } else { // Let's consider pulling the proper from postmeta
                $result = get_post_meta($post->ID, $prop, true);
            }
        }

        return $result;
    }

}