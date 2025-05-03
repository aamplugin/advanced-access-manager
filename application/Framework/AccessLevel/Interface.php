<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access level interface
 *
 * All access levels should implement this interface. Though, it is not enforced, it
 * is strongly recommended to adhere to this interface and periodically check if it
 * changed.
 *
 * @method AAM_Framework_Service_Urls urls(array $settings = [])
 * @method AAM_Framework_Service_ApiRoutes api_routes(array $settings = [])
 * @method AAM_Framework_Service_Jwts jwts(array $settings = [])
 * @method AAM_Framework_Service_LoginRedirect login_redirect(array $settings = [])
 * @method AAM_Framework_Service_LogoutRedirect logout_redirect(array $settings = [])
 * @method AAM_Framework_Service_NotFoundRedirect not_found_redirect(array $settings = [])
 * @method AAM_Framework_Service_BackendMenu backend_menu(array $settings = [])
 * @method AAM_Framework_Service_AdminToolbar admin_toolbar(array $settings = [])
 * @method AAM_Framework_Service_Metaboxes metaboxes(array $settings = [])
 * @method AAM_Framework_Service_Widgets widgets(array $settings = [])
 * @method AAM_Framework_Service_AccessDeniedRedirect access_denied_redirect(array $settings = [])
 * @method AAM_Framework_Service_Roles roles(array $settings = [])
 * @method AAM_Framework_Service_Users users(array $settings = [])
 * @method AAM_Framework_Service_Posts posts(array $settings = [])
 * @method AAM_Framework_Service_Terms terms(array $settings = [])
 * @method AAM_Framework_Service_PostTypes post_types(array $settings = [])
 * @method AAM_Framework_Service_Taxonomies taxonomies(array $settings = [])
 * @method AAM_Framework_Service_Capabilities capabilities(array $settings = [])
 * @method AAM_Framework_Service_Capabilities caps(array $settings = [])
 * @method AAM_Framework_Service_Settings settings(array $settings = [])
 * @method AAM_Framework_Service_Policies policies(array $settings = [])
 * @method AAM_Framework_Service_Hooks hooks(array $settings = [])
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
interface AAM_Framework_AccessLevel_Interface
{

    /**
     * Proxy methods to WordPress core instance
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     * @access public
     *
     * @since 7.0.0
     */
    public function __call($name, $arguments);

    /**
     * Get resource by its type and internal ID
     *
     * @param string  $resource_type
     * @param boolean $reload        [Optional]
     *
     * @return AAM_Framework_Resource_Interface|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_resource($resource_type, $reload = null);

    /**
     * Get preference container
     *
     * @param string  $preference_type
     * @param boolean $reload          Optional]
     *
     * @return AAM_Framework_Preference_Interface|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_preference($preference_type, $reload = null);

    /**
     * Get access level ID
     *
     * @return string|int|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_id();

    /**
     * Get access level display name
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function get_display_name();

    /**
     * Add new siblings to the collection
     *
     * @param AAM_Framework_AccessLevel_Interface $sibling
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function add_sibling(AAM_Framework_AccessLevel_Interface $sibling);

    /**
     * Check if there are any siblings
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function has_siblings();

    /**
     * Get all siblings
     *
     * @return array<AAM_Framework_AccessLevel_Interface>
     * @access public
     *
     * @version 7.0.0
     */
    public function get_siblings();

    /**
     * Retrieve parent access level
     *
     * Returns null, if there is no parent access level
     *
     * @return AAM_Framework_AccessLevel_Interface|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_parent();

}