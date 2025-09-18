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
 * @method AAM_Framework_Service_Urls urls(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_ApiRoutes api_routes(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Jwts jwts(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_LoginRedirect login_redirect(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_LogoutRedirect logout_redirect(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_NotFoundRedirect not_found_redirect(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_BackendMenu backend_menu(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_AdminToolbar admin_toolbar(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Metaboxes metaboxes(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Widgets widgets(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_AccessDeniedRedirect access_denied_redirect(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Roles roles(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Users users(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Posts posts(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Terms terms(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_PostTypes post_types(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Taxonomies taxonomies(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Capabilities capabilities(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Capabilities caps(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Settings settings(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Policies policies(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Hooks hooks(mixed $access_level = null, array $settings = [])
 *
 * @property AAM_Framework_Utility_Cache $cache
 * @property AAM_Framework_Utility_ObjectCache $object_cache
 * @property AAM_Framework_Utility_Capabilities $caps
 * @property AAM_Framework_Utility_Capabilities $capabilities
 * @property AAM_Framework_Utility_Config $config
 * @property AAM_Framework_Utility_Misc $misc
 * @property AAM_Framework_Utility_Redirect $redirect
 * @property AAM_Framework_Utility_Roles $roles
 * @property AAM_Framework_Utility_Users $users
 * @property AAM_Framework_Utility_Db $db
 * @property AAM_Framework_Utility_AccessLevels $access_levels
 * @property AAM_Framework_Utility_Jwt $jwt
 * @property AAM_Framework_Utility_Policy $policy
 * @property AAM_Framework_Utility_Content $content
 * @property AAM_Framework_Utility_Rest $rest
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
     * @access private
     *
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @access protected
     * @return void
     *
     * @version 7.0.0
     */
    protected function __construct() {}

    /**
     * Prevent from fatal errors
     *
     * @param string $name
     * @param array  $args
     *
     * @return AAM_Framework_Service_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function __call($name, $args)
    {
        return AAM_Framework_Manager::_()->{$name}(...$args);
    }

    /**
     * Get utility instance
     *
     * @param string $name
     *
     * @return AAM_Framework_Utility_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function __get($name)
    {
        return $this->utility($name);
    }

    /**
     * Get user by their's identifier
     *
     * If no identifier provided, the current user will be return. If user is not
     * authenticated, the visitor access level will be returned.
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_AccessLevel_User|AAM_Framework_AccessLevel_Visitor
     * @access public
     *
     * @version 7.0.9
     */
    public function user($identifier = null)
    {
        if (is_null($identifier)) {
            $result = AAM::current_user();
        } elseif (!empty($identifier)) { // User ID can be 0 (zero)
            $result = $this->access_levels->get(
                AAM_Framework_Type_AccessLevel::USER, $identifier
            );
        } else {
            $result = $this->access_levels->get(
                AAM_Framework_Type_AccessLevel::VISITOR
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
     * @access public
     *
     * @version 7.0.0
     */
    public function role($role_slug)
    {
        return $this->access_levels->get(
            AAM_Framework_Type_AccessLevel::ROLE, $role_slug
        );
    }

    /**
     * Get visitor access level
     *
     * @return AAM_Framework_AccessLevel_Visitor
     * @access public
     *
     * @version 7.0.0
     */
    public function visitor()
    {
        return $this->access_levels->get(AAM_Framework_Type_AccessLevel::VISITOR);
    }

    /**
     * Get visitor access level
     *
     * @return AAM_Framework_AccessLevel_Visitor
     * @access public
     *
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
     * @access public
     *
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
     * @access public
     *
     * @version 7.0.0
     */
    public function default()
    {
        return $this->access_levels->get(AAM_Framework_Type_AccessLevel::DEFAULT);
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     * @access public
     *
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
     * @access public
     *
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
     * @access public
     *
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
     * @access public
     *
     * @version 7.0.0
     */
    public function any()
    {
        return $this->default();
    }

    /**
     * Return utility instance
     *
     * @param string $utility_name
     *
     * @return AAM_Framework_Utility_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function utility($utility_name)
    {
        return AAM_Framework_Manager::_()->{$utility_name};
    }

    /**
     * Setup the framework manager
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function setup($access_level)
    {
        AAM_Framework_Manager::setup($access_level);
    }

    /**
     * Get single instance of itself
     *
     * @return AAM_Core_Gateway
     * @access public
     *
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