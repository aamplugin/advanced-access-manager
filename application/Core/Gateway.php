<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core API gateway
 *
 * @method AAM_Framework_Service_Roles roles(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Urls urls(mixed $runtime_context = null)
 * @method AAM_Framework_Service_ApiRoutes api_routes(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Jwts jwts(mixed $runtime_context = null)
 * @method AAM_Framework_Service_LoginRedirect login_redirect(mixed $runtime_context = null)
 * @method AAM_Framework_Service_LogoutRedirect logout_redirect(mixed $runtime_context = null)
 * @method AAM_Framework_Service_NotFoundRedirect not_found_redirect(mixed $runtime_context = null)
 * @method AAM_Framework_Service_BackendMenu backend_menu(mixed $runtime_context = null)
 * @method AAM_Framework_Service_AdminToolbar admin_toolbar(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Metaboxes metaboxes(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Widgets widgets(mixed $runtime_context = null)
 * @method AAM_Framework_Service_AccessDeniedRedirect access_denied_redirect(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Identities identities(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Content content(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Users users(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Capabilities capabilities(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Capabilities caps(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Configs configs(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Settings settings(mixed $runtime_context = null)
 * @method AAM_Framework_Service_AccessLevels access_levels(mixed $runtime_context = null)
 *
 * @package AAM
 * @version 7.0.0
 */
final class AAM_Core_Gateway
{

    /**
     * Single instance of itself
     *
     * @var AAM_Core_Gateway
     *
     * @access private
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * Default context shared by all services
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_default_context = [];

    /**
     * Collection of services
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_service_methods = [];

    /**
     * Constructor
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct()
    {
        $this->_service_methods = apply_filters('aam_api_gateway_services_filter', [
            'roles'                  => AAM_Framework_Service_Roles::class,
            'urls'                   => AAM_Framework_Service_Urls::class,
            'api_routes'             => AAM_Framework_Service_ApiRoutes::class,
            'jwts'                   => AAM_Framework_Service_Jwts::class,
            'login_redirect'         => AAM_Framework_Service_LoginRedirect::class,
            'logout_redirect'        => AAM_Framework_Service_LogoutRedirect::class,
            'not_found_redirect'     => AAM_Framework_Service_NotFoundRedirect::class,
            'backend_menu'           => AAM_Framework_Service_BackendMenu::class,
            'admin_toolbar'          => AAM_Framework_Service_AdminToolbar::class,
            'metaboxes'              => AAM_Framework_Service_Metaboxes::class,
            'widgets'                => AAM_Framework_Service_Widgets::class,
            'access_denied_redirect' => AAM_Framework_Service_AccessDeniedRedirect::class,
            'identities'             => AAM_Framework_Service_Identities::class,
            'content'                => AAM_Framework_Service_Content::class,
            'users'                  => AAM_Framework_Service_Users::class,
            'capabilities'           => AAM_Framework_Service_Capabilities::class,
            'caps'                   => AAM_Framework_Service_Capabilities::class,
            'configs'                => AAM_Framework_Service_Configs::class,
            'settings'               => AAM_Framework_Service_Settings::class,
            'access_levels'          => AAM_Framework_Service_AccessLevels::class
        ]);
    }

    /**
     * Prevent from fatal errors
     *
     * @param string $name
     * @param array  $args
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __call($name, $args)
    {
        $result = null;

        if (array_key_exists($name, $this->_service_methods)) {
            $result = $this->_return_service(
                $this->_service_methods[$name], array_shift($args)
            );
        } else {
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                "The method {$name} is not defined in the AAM API",
                AAM_VERSION
            );
        }

        return $result;
    }

    /**
     * Get user by their's identifier
     *
     * If no identifier provided, the current user will be return. If user is not
     * authenticated, the visitor access level will be returned.
     *
     * @param string|int|WP_User|null $identifier
     *
     * @return AAM_Framework_AccessLevel_User|AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 7.0.0
     */
    public function user($identifier = null)
    {
        $service = $this->access_levels();

        if (is_null($identifier)) {
            $result = AAM::current_user();
        } else {
            $result = $service->get(
                AAM_Framework_Type_AccessLevel::USER, $identifier
            );
        }

        return $result;
    }

    /**
     * Get role access level
     *
     * @param string $role_slug
     *
     * @return AAM_Framework_AccessLevel_Role
     *
     * @access public
     * @version 7.0.0
     */
    public function role($role_slug)
    {
        return $this->access_levels()->get(
            AAM_Framework_Type_AccessLevel::ROLE, $role_slug
        );
    }

    /**
     * Get visitor access level
     *
     * @return AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 7.0.0
     */
    public function visitor()
    {
        return $this->access_levels()->get(
            AAM_Framework_Type_AccessLevel::VISITOR
        );
    }

    /**
     * Get visitor access level
     *
     * @return AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 7.0.0
     */
    public function anonymous()
    {
        return $this->visitor();
    }

    /**
     * Get visitor access level
     *
     * @return AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 7.0.0
     */
    public function guest()
    {
        return $this->visitor();
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function default()
    {
        return $this->access_levels()->get(
            AAM_Framework_Type_AccessLevel::DEFAULT
        );
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function all()
    {
        return $this->default();
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function everyone()
    {
        return $this->default();
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function anyone()
    {
        return $this->default();
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function any()
    {
        return $this->default();
    }

    /**
     * Setup the framework manager
     *
     * @param array $default_context
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function setup(array $default_context = [])
    {
        if (is_array($default_context)) {
            $this->_default_context = $default_context;
        }
    }

    /**
     * Return an instance of requested service
     *
     * @param string $service_class_name
     * @param mixed  $context
     *
     * @return AAM_Framework_Service_Interface
     *
     * @access private
     * @version 7.0.0
     */
    private function _return_service($service_class_name, $context)
    {
        $new_context = [];

        // Parse the incoming context and determine correct access level
        if (is_a($context, AAM_Framework_AccessLevel_Interface::class)) {
            $new_context = [ 'access_level' => $context ];
        } elseif (is_string($context)) {
            // Trying to parse the context and extract the access level
            if (in_array($context, [ 'visitor', 'anonymous', 'guest'], true)) {
                $new_context = [ 'access_level' => $this->visitor() ];
            } elseif (in_array($context, [ 'default', 'all', 'anyone', 'everyone' ], true)) {
                $new_context = [ 'access_level' => $this->all() ];
            } elseif (strpos($context, ':')) {
                list($access_level, $id) = explode(':', $context, 2);

                if ($access_level === 'role') {
                    $new_context = [ 'access_level' => $this->role($id) ];
                } elseif ($access_level === 'user') {
                    $new_context = [ 'access_level' => $this->user($id) ];
                }
            }
        } elseif (is_array($context)) {
            $new_context = $context;
        }

        return call_user_func(
            "{$service_class_name}::get_instance",
            array_merge($this->_default_context, $new_context)
        );
    }

    /**
     * Prepare Access Policy manager but only if service is enabled
     *
     * @param AAM_Core_Subject $subject
     * @param boolean          $skipInheritance
     *
     * @return AAM_Core_Policy_Manager|null
     *
     * @since 6.1.0 Added $skipInheritance flag to insure proper settings inheritance
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     * @deprecated
     */
    public function getAccessPolicyManager(
        AAM_Core_Subject $subject = null, $skipInheritance = false
    ) {
        if (is_null($subject)) {
            $subject = AAM::getUser();
        }

        if (AAM::api()->configs()->get_config(
            AAM_Service_AccessPolicy::FEATURE_FLAG
        )) {
            $manager = AAM_Core_Policy_Factory::get($subject, $skipInheritance);
        } else {
            $manager = null;
        }

        return $manager;
    }

    /**
     * Reset all AAM settings and configurations
     *
     * @return void
     *
     * @access public
     *
     * @version 6.9.6
     * @deprecated
     */
    public function reset()
    {
        AAM_Core_API::clearSettings();
    }

    /**
     * Merge two set of access settings into one
     *
     * The merging method also takes in consideration the access settings preference
     * defined in ConfigPress
     *
     * @param array  $set1
     * @param array  $set2
     * @param string $objectType
     * @param string $preference
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     * @deprecated 7.0.0 Moved to resource abstract
     */
    public function mergeSettings($set1, $set2, $objectType, $preference = null)
    {
        $merged = array();

        // If preference is not explicitly defined, fetch it from the AAM configs
        if (is_null($preference)) {
            $default_preference = $this->configs()->get_config(
                'core.settings.merge.preference'
            );

            $preference = $this->configs()->get_config(
                "core.settings.{$objectType}.merge.preference",
                $default_preference
            );
        }

        // First get the complete list of unique keys
        $keys = array_keys($set1);
        foreach (array_keys($set2) as $key) {
            if (!in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        foreach ($keys as $key) {
            // There can be only two types of preferences: "deny" or "allow". Based
            // on that, choose access settings that have proper effect as following:
            //
            //   - If set1 and set2 have two different preferences, get the one that
            //     has correct preference;
            //   - If set1 and set2 have two the same preferences, choose the set2
            //   - If only set1 has access settings, use set1 as-is
            //   - If only set2 has access settings, use set2 as-is
            //   - If set1 and set2 have different effect than preference, choose
            //     set2
            $effect1 = $this->computeAccessOptionEffect($set1, $key);
            $effect2 = $this->computeAccessOptionEffect($set2, $key);
            $effect  = ($preference === 'deny');

            // Access Option is either boolean true or array with "enabled" key
            // set as boolean true
            if ($effect1 === $effect2) { // both equal
                $merged[$key] = $set2[$key];
            } elseif ($effect1 === $effect) { // set1 matches preference
                $merged[$key] = $set1[$key];
            } elseif ($effect2 === $effect) { // set2 matches preference
                $merged[$key] = $set2[$key];
            } else {
                if ($preference === 'allow') {
                    $option = isset($set2[$key]) ? $set2[$key] : $set1[$key];
                    if (is_array($option)) {
                        $option['enabled'] = false;
                    } else {
                        $option = false;
                    }
                    $merged[$key] = $option;
                } elseif (is_null($effect1)) {
                    $merged[$key] = $set2[$key];
                } elseif (is_null($effect2)) {
                    $merged[$key] = $set1[$key];
                }
            }
        }

        return $merged;
    }

    /**
     * Determine correct access option effect
     *
     * There can be two possible types of the access settings: straight boolean and
     * array with "enabled" flag. If provided key is not a part of the access options,
     * the null is returned, otherwise boolean true of false.
     *
     * @param array  $opts
     * @param string $key
     *
     * @return null|boolean
     *
     * @access protected
     * @version 6.0.0
     * @deprecated 7.0.0
     */
    protected function computeAccessOptionEffect($opts, $key)
    {
        $effect = null; // nothing is defined

        if (isset($opts[$key])) {
            $effect = is_array($opts[$key]) ? $opts[$key]['enabled'] : $opts[$key];
        }

        return $effect;
    }

    /**
     * Get single instance of itself
     *
     * @return AAM_Core_Gateway
     *
     * @access public
     * @version 7.0.0
     */
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}