<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend posts & terms service UI
 *
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/384
 * @since 6.9.29 https://github.com/aamplugin/advanced-access-manager/issues/375
 * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/363
 * @since 6.7.9  https://github.com/aamplugin/advanced-access-manager/issues/192
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/89
 *               https://github.com/aamplugin/advanced-access-manager/issues/108
 * @since 6.3.1  Fixed bug with incorrectly escaped passwords and teaser messages
 * @since 6.3.0  Fixed bug with PHP noticed that was triggered if password was not
 *               defined
 * @since 6.2.0  Added more granular control over the HIDDEN access option
 * @since 6.0.3  Allowed to manage access to ALL registered post types
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.31
 */
class AAM_Backend_Feature_Main_Content extends AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_ServiceTrait,
        AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_content';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/content.php';


    /**
     * Get access form with pre-populated data
     *
     * @param mixed  $resource_id
     * @param string $resource_type
     *
     * @return string
     *
     * @access public
     * @version 7.0.0
     */
    public function render_content_access_form($resource_id, $resource_type)
    {
        $access_level = AAM_Backend_AccessLevel::get_instance();

        // Making sure we are dealing with correct resource ID
        if ($resource_type === AAM_Framework_Type_Resource::TERM) {
            // Term ID is compound and can have up to 3 layer of controls
            $resource_id = [
                'id' => intval($resource_id)
            ];

            if (isset($_POST['taxonomy'])) { // Is taxonomy specified?
                $resource_id['taxonomy'] = trim($_POST['taxonomy']);
            }

            if (isset($_POST['post_type'])) { // Is post type specified?
                $resource_id['post_type'] = trim($_POST['post_type']);
            }
        }

        $resource = $access_level->get_resource($resource_type, $resource_id);
        $args     = [
            'resource'        => $resource,
            'access_controls' => $this->_prepare_access_controls($resource),
            // TODO: Consider removing the Backend Access Level
            'access_level'    => AAM_Backend_AccessLevel::get_instance()
        ];

        // Do the SSR for the access form
        return apply_filters(
            "aam_{$resource_type}_access_form_filter",
            $this->_load_partial('content-access-form', (object) $args),
            (object) $args
        );
    }

    /**
     * Determine if permission is denied
     *
     * @param string                      $permission
     * @param AAM_Framework_Resource_Post $resource
     *
     * @return boolean
     *
     * @access protected
     * @version 7.0.0
     */
    protected function is_permission_denied($permission, $resource)
    {
        $perms    = $resource->get_permissions();
        $settings = !empty($perms[$permission]) ? $perms[$permission] : null;

        return !empty($settings) && $settings['effect'] === 'deny';
    }

    /**
     * Get specific permission's settings
     *
     * @param string                           $permission
     * @param AAM_Framework_Resource_Interface $resource
     *
     * @return array
     *
     * @access protected
     * @version 7.0.0
     */
    protected function get_permission_settings($permission, $resource)
    {
        $permissions = $resource->get_permissions();

        return isset($permissions[$permission]) ? $permissions[$permission] : [];
    }

    /**
     * Load dynamic template
     *
     * @param string $name
     * @param object $params
     *
     * @return string
     *
     * @access public
     * @version 7.0.0
     */
    private function _load_partial($name, object $params)
    {
        ob_start();

        // Prepare the complete filepath
        $file_path = dirname(__DIR__) . '/../tmpl/partial/' . $name . '.php';

        require $file_path;
        $content = ob_get_contents();

        ob_end_clean();

        return $content;
    }

    /**
     * Prepare list of access controls for currently managed resource
     *
     * @param AAM_Framework_Resource_Interface $resource
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_access_controls($resource)
    {
        $result = [];

        if ($resource->type === AAM_Framework_Type_Resource::POST) {
            $result = $this->_prepare_post_access_controls($resource);
        } else {
            $result = $this->_prepare_other_access_controls($resource);
        }

        return $result;
    }

    /**
     * Prepare access controls for the post resource
     *
     * @param AAM_Framework_Resource_Post $resource
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_post_access_controls($resource)
    {
        $permissions = $resource->get_permissions();

        return apply_filters('aam_ui_content_access_controls_filter', [
            'list' => array(
                'title'       => __('Hidden', AAM_KEY),
                'modal'       => 'modal_content_visibility',
                'is_denied'   => $this->is_permission_denied('list', $resource),
                'areas'       => isset($permissions['list']) ? $permissions['list']['on'] : [],
                'customize'   => __('Customize visibility', AAM_KEY),
                'tooltip'     => sprintf(
                    __('Customize the visibility of "%s" separately for each section of your website. It\'s crucial to thoughtfully select which areas will have hidden content. For instance, you might choose to hide certain posts in the backend for content editors, while still allowing them to be visible on the frontend for general users.', AAM_KEY),
                    $resource->post_title
                ),
                'description' => sprintf(
                    __('Hide the "%s" from all menus, lists, and API responses. However, it remains accessible via a direct URL. Visibility can be customized for the frontend, backend and API areas independently.', AAM_KEY),
                    $resource->post_title
                ),
                'on' => [
                    'frontend' => sprintf(
                        __('Hide the "%s" on the website frontend', AAM_KEY),
                        $resource->post_title
                    ),
                    'backend' => sprintf(
                        __('Hide the "%s" in the backend (admin area)', AAM_KEY),
                        $resource->post_title
                    ),
                    'api' => sprintf(
                        __('Hide the "%s" in the RESTful API results', AAM_KEY),
                        $resource->post_title
                    )
                ]
            ),
            'read' => array(
                'title'       => __('Restricted', AAM_KEY),
                'modal'       => 'modal_content_restriction',
                'is_denied'   => $this->is_permission_denied('read', $resource),
                'customize'   => __('Customize direct access', AAM_KEY),
                'tooltip'     => sprintf(
                    __('Restrict direct access to read or download the "%s". This restriction can be customized with options such as setting an access expiration date, creating a password, redirecting to a different location, and more.', AAM_KEY),
                    $resource->post_title
                ),
                'description' => sprintf(
                    __('Restrict direct access to "%s". This restriction can be customized with options such as setting an access expiration date, creating a password, redirecting to a different location, and more.', AAM_KEY),
                    $resource->post_title
                )
            ),
            'comment' => array(
                'title'       => __('Leave Comments', AAM_KEY),
                'is_denied'   => $this->is_permission_denied('comment', $resource),
                'description' => sprintf(
                    __('Limit the ability to leave comments on the "%s".', AAM_KEY),
                    $resource->post_title
                )
            ),
            'edit' => array(
                'title'       => __('Edit', AAM_KEY),
                'is_denied'   => $this->is_permission_denied('edit', $resource),
                'description' => sprintf(
                    __('Disable the ability to edit "%s". Editing "%s" will be restricted both in the backend area and via the RESTful API.', AAM_KEY),
                    $resource->post_title,
                    $resource->post_title
                )
            ),
            'publish' => array(
                'title'       => __('Publish', AAM_KEY),
                'is_denied'   => $this->is_permission_denied('publish', $resource),
                'description' => sprintf(
                    __('Manage the ability to publish draft "%s" or any updates to already published versions. If denied, a user will only be able to submit for review.', AAM_KEY),
                    $resource->post_title
                )
            ),
            'delete' => array(
                'title'       => __('Delete', AAM_KEY),
                'is_denied'   => $this->is_permission_denied('delete', $resource),
                'description' => sprintf(
                    __('Disable the ability to delete "%s". Deletion will be restricted both in the backend area and via the RESTful API.', AAM_KEY),
                    $resource->post_title
                )
            )
        ], $resource);
    }

    /**
     * Prepare access controls for other resources
     *
     * @param AAM_Framework_Resource_Interface $resource
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_other_access_controls($resource)
    {
        return apply_filters(
            'aam_ui_content_access_controls_filter', [], $resource
        );
    }

    /**
     * Register Posts & Pages service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'post',
            'position'   => 20,
            'title'      => __('Posts & Terms', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }

}