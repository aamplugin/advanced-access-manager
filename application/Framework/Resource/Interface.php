<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Interface for all resources that hold permissions
 *
 * Resource that is classified as such that contains permissions, have slightly
 * different way to handle its data, especially when it comes to merge permissions.
 * 
 * * @method AAM_Framework_Service_Urls urls(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_ApiRoutes api_routes(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Abilities abilities(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_McpServers mcp_servers(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_McpTools mcp_tools(mixed $access_level = null, array $settings = [])
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
 * @property string $type
 *
 * @package AAM
 * @version 7.0.0
 */
interface AAM_Framework_Resource_Interface
{

    /**
     * Get access level this resource is tight to
     *
     * @return AAM_Framework_AccessLevel_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function get_access_level();

    /**
     * Get raw permissions
     *
     * If $resource_identifier is not provided, this method returns the collection of
     * all explicitly defined permissions
     *
     * @param mixed $resource_identifier [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_permissions($resource_identifier = null);

    /**
     * Set raw resource permissions
     *
     * @param array $permissions
     * @param mixed $resource_identifier [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set_permissions(array $permissions, $resource_identifier = null);

    /**
     * Set raw resource permission
     *
     * @param mixed  $resource_identifier
     * @param string $permission_key
     * @param mixed  $permission
     * @param mixed  ...$args             [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set_permission(
        $resource_identifier,
        $permission_key,
        $permission,
        ...$args
    );

    /**
     * Get raw resource permission
     *
     * @param mixed  $resource_identifier
     * @param string $permission_key
     *
     * @return array|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_permission($resource_identifier, $permission_key);

    /**
     * Remove a single permission
     *
     * @param mixed  $resource_identifier
     * @param string $permission_key
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function remove_permission(
        $resource_identifier,
        $permission_key
    );

    /**
     * Check if resource settings are overwritten
     *
     * @param mixed $resource_identifier [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_customized($resource_identifier = null);

    /**
     * Reset all explicitly defined settings to default
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($resource_identifier = null);

}