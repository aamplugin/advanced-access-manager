<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

use Vectorface\Whip\Whip,
    DeviceDetector\ClientHints,
    DeviceDetector\DeviceDetector,
    DeviceDetector\Parser\Client\Browser,
    DeviceDetector\Parser\OperatingSystem;

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
     * @version 7.0.7
     */
    protected static $map = array(
        'USER'              => 'AAM_Framework_Policy_Marker::get_user_value',
        'USER_AGENT'        => 'AAM_Framework_Policy_Marker::get_user_agent',
        'USER_OPTION'       => 'AAM_Framework_Policy_Marker::get_user_option_value',
        'USER_META'         => 'AAM_Framework_Policy_Marker::get_user_meta_value',
        'DATETIME'          => 'AAM_Framework_Policy_Marker::get_datetime',
        'HTTP_GET'          => 'AAM_Framework_Policy_Marker::get_query',
        'HTTP_QUERY'        => 'AAM_Framework_Policy_Marker::get_query',
        'HTTP_POST'         => 'AAM_Framework_Policy_Marker::get_post',
        'HTTP_COOKIE'       => 'AAM_Framework_Policy_Marker::get_cookie',
        'PHP_SERVER'        => 'AAM_Framework_Policy_Marker::get_server',
        'CALLBACK'          => 'AAM_Framework_Policy_Marker::get_from_callback',
        'ENV'               => 'getenv',
        'CONST'             => 'AAM_Framework_Policy_Marker::get_constant',
        'WP_OPTION'         => 'AAM_Framework_Policy_Marker::get_wp_option',
        'AAM_CONFIG'        => 'AAM_Framework_Policy_Marker::get_config',
        'POLICY_PARAM'      => 'AAM_Framework_Policy_Marker::get_param',
        'POLICY_META'       => 'AAM_Framework_Policy_Marker::get_policy_meta',
        'WP_SITE'           => 'AAM_Framework_Policy_Marker::get_site_param',
        'WP_NETWORK_OPTION' => 'AAM_Framework_Policy_Marker::get_network_option',
        'THE_POST'          => 'AAM_Framework_Policy_Marker::get_current_post_prop',
        'JWT'               => 'AAM_Framework_Policy_Marker::get_jwt_claim',
        // Below markers can have complex xpath - they are treated differently
        'PHP_GLOBAL'        => 'AAM_Framework_Policy_Marker::get_global_variable',
        'ARGS'              => 'AAM_Framework_Policy_Marker::get_arg_value',
        'AAM_API'           => 'AAM_Framework_Policy_Marker::get_api',
    );

    /**
     * Evaluate expression and replace markers
     *
     * The following method takes into consideration the following scenarios:
     * - Literal values: 1, "hello", true, 3.4 or [1,2,3]
     * - Single marker: "${PHP_GLOBAL.env}"
     * - Single marker with static addition: "${PHP_QUERY.ref}-more"
     * - Multiple marker with or without addition: "${WP_USER.first}-${WP_USER.last}"
     * - All above with typecast
     *
     * @param string  $exp
     * @param array   $args
     * @param boolean $typecast [Optional]
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.7
     */
    public static function execute($exp, $args = [], $typecast = true)
    {
        if (is_string($exp)) { // Evaluate only strings
            // Removing typecast so we have a clean marker set
            $clean        = preg_replace('/^\(\*([\w]+)\)/i', '', $exp);
            $has_typecast = strlen($clean) !== strlen($exp);

            if (preg_match_all('/(\$\{[^}]+\})/', $clean, $match)) {
                // Iterate over each marker in the expression and concatenate it
                // all into one string. Take into consideration that some markers may
                // return not a scalar value
                $multi_markers = count($match[1]) > 1;
                $result        = $multi_markers ? $clean : '';

                foreach ($match[1] as $marker) {
                    $token = self::get_marker_value($marker, $args);

                    // If multiple markers are in the expression, apply a specific way
                    // of merging them into one string
                    if ($multi_markers) {
                        if (is_bool($token)) {
                            $token = $token ? 'true' : 'false';
                        } elseif (is_null($token)) {
                            $token = '';
                        } elseif (is_scalar($token)) {
                            $token = (string) $token;
                        } else {
                            $token = json_encode($token);
                        }

                        $result = str_replace($marker, $token, $result);
                    } elseif (strlen($clean) !== strlen($marker)) { // Has addition?
                        $result = str_replace($marker, (string) $token, $clean);
                    } else {
                        $result = $token;
                    }
                }
            } else { // Just pass whatever is (e.g. "(*int)5" or "true")
                $result = $clean;
            }

            // Perform type casting if necessary
            if ($has_typecast && $typecast) {
                $result = AAM_Framework_Policy_Typecast::execute($exp, $result);
            }
        } else {
            $result = $exp;
        }

        return $result;
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
     * @version 7.0.7
     */
    public static function get_marker_value($marker, $args = [])
    {
        // Stripping the marker wrapper if present ${}
        if (strpos($marker, '${') === 0) {
            $marker = trim($marker, '${}');
        }

        // Splitting marker into
        $segments = explode('.', $marker, 2);

        if (count($segments) === 2) { // Marker has to have source and xpath
            if (array_key_exists($segments[0], self::$map)) {
                $value = call_user_func(
                    self::$map[$segments[0]],
                    $segments[1],
                    $args
                );
            } else {
                $value = apply_filters(
                    'aam_policy_marker_value_filter',
                    null,
                    $segments[0],
                    $segments[1],
                    $args
                );
            }
        } else {
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                sprintf('Invalid marker: %s', $marker),
                AAM_VERSION
            );

            $value = null;
        }

        return $value;
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
     * Get USER_AGENT value
     *
     * This marker utilizes the Device Detector functionality
     *
     * @param string $xpath
     *
     * @return string
     * @access protected
     *
     * @version 7.0.7
     */
    protected static function get_user_agent($xpath)
    {
        $detector = new DeviceDetector(
            AAM_Framework_Manager::_()->misc->get($_SERVER, 'HTTP_USER_AGENT'),
            ClientHints::factory($_SERVER)
        );

        $detector->parse();

        // Normalize the path
        $prop = strtolower($xpath);

        // If xpath starts with "is", assume methods like isSmartphone or isTv
        if (strpos($xpath, 'is') === 0) {
            $value = call_user_func([$detector, $xpath]);
        } elseif (in_array($prop, [ 'getosfamily', 'osfamily' ], true)) {
            $value = OperatingSystem::getOsFamily($detector->getOs('name'));
        }  elseif (in_array($prop, [ 'getbrowserfamily', 'browserfamily' ], true)) {
            $value = Browser::getBrowserFamily($detector->getClient('name'));
        } else {
            $value = null;
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

    /**
     * Evaluate CALLBACK expression
     *
     * @param string $xpath
     * @param array  $args
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.7
     */
    protected static function get_from_callback($xpath, $args)
    {
        $value = null;
        $cb    = self::_parse_callback($xpath, $args);

        if (!is_null($cb)) {
            if (is_callable($cb['func']) || function_exists($cb['func'])) {
                $value = call_user_func_array($cb['func'], $cb['args']);
            }
        }

        return $value;
    }

    /**
     * Get global variable's value
     *
     * @param string $xpath
     * @param array  $args
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.7
     */
    protected static function get_global_variable($xpath, $args)
    {
        return self::_resolve_complex_chain($GLOBALS, $xpath, $args);
    }

    /**
     * Get AAM API
     *
     * @param string $xpath
     * @param array  $args
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.7
     */
    protected static function get_api($xpath, $args)
    {
        return self::_resolve_complex_chain(AAM::api(), $xpath, $args);
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
     * @version 7.0.7
     */
    protected static function get_arg_value($xpath, $args)
    {
        return self::_resolve_complex_chain($args, $xpath, $args);
    }

    /**
     * Resolve complex marker
     *
     * @param mixed  $source
     * @param string $xpath
     * @param array  $args
     *
     * @return mixed
     * @access private
     * @static
     *
     * @version 7.0.7
     */
    private static function _resolve_complex_chain($source, $xpath, $args)
    {
        $result = $source;

        // Splitting the xpath into sub-segments
        foreach(self::_parse_to_segments($xpath) as $segment) {
            if (strpos($segment, '(') !== false) { // This segment calls method
                if (is_object($result)) {
                    $cb = self::_parse_callback($segment, $args);

                    if ($cb !== null) {
                        $result = call_user_func_array(
                            [ $result, $cb['func'] ],
                            $cb['args']
                        );
                    } else {
                        $result = null;
                    }
                } else {
                    $result = null;
                }
            } else {
                $result = AAM_Framework_Policy_Xpath::get_value_by_xpath(
                    $result, $segment
                );
            }
        }

        return $result;
    }

    /**
     * Parse callback expression
     *
     * @param string $exp
     * @param array  $args
     *
     * @return array
     * @access private
     *
     * @version 7.0.7
     */
    private static function _parse_callback($exp, $args)
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
                    array_push($values, self::get_marker_value($marker, $args));
                } else {
                    array_push($values, $marker);
                }
            }

            $response = [
                'func'  => trim($match[1]),
                'args'  => $values
            ];
        }

        return $response;
    }

    /**
     * Parse marker into segments that will be used to get value
     *
     * Example of markers:
     *  - CALLBACK.MyApp\Auth::isRegistered
     *  - CALLBACK.is_admin
     *  - CALLBACK.is_network_active()
     *  - PHP_GLOBAL.Players[0].profile.name
     *  - USER.address["physical"].zip
     *  - PHP_GLOBAL.Country[USA][NC][Charlotte]
     *  - MARKER.0929431.amount
     *  - AAM_API.posts.is_restricted(abc)
     *  - PHP_GLOBAL.user.get_order(45).is_fulfilled
     *  - CALLBACK.sanitize_title(USER.display_name)
     *  - CALLBACK.sanitize_title(USER.roles[3].is_active, true)
     *  - CALLBACK.current_user_can('edit_post', 10)
     *
     * @param string $str
     * @return array
     *
     * @access private
     * @static
     *
     * @version 7.0.7
     */
    private static function _parse_to_segments($str)
    {
        $in_args = $in_index = $in_str = false;
        $results = [];
        $segment = '';

        for($i = 0; $i < strlen($str); $i++) {
            $chr = $str[$i];

            if ($chr === '.') {
                if (!$in_args && !$in_index && !$in_str) {
                    array_push($results, $segment);
                    $segment = '';
                } else {
                    $segment .= $chr;
                }
            } else {
                if (in_array($chr, ['"', "'"], true)) {
                    $in_str = !$in_str;
                } elseif ($chr === '[') {
                    $in_index = true;
                } elseif ($chr === ']') {
                    $in_index = false;
                } elseif ($chr === '(') {
                    $in_args = true;
                } elseif ($chr === ')') {
                    $in_args = false;
                }

                $segment .= $chr;
            }
        }

        if (!empty($segment)) {
            array_push($results, $segment);
        }

        return $results;
    }

}