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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Main_Content extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the service
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_content';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'service/content.php';


    /**
     * Get access form with pre-populated data
     *
     * @param mixed  $resource_id
     * @param string $resource_type
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function render_content_access_form($resource_id, $resource_type)
    {
        $access_level = AAM_Backend_AccessLevel::get_instance();

        // Making sure we are dealing with correct resource ID
        if ($resource_type === AAM_Framework_Type_Resource::TERM) {
            // Get term
            $resource_identifier = get_term(
                intval($resource_id),
                AAM::api()->misc->get($_POST, 'taxonomy', '')
            );

            $post_type = AAM::api()->misc->get($_POST, 'post_type');

            if (!empty($post_type)) {
                $resource_identifier->post_type = $post_type;
            }
        } elseif ($resource_type === AAM_Framework_Type_Resource::POST) {
            $resource_identifier = get_post($resource_id);
        } elseif ($resource_type === AAM_Framework_Type_Resource::TAXONOMY) {
            $resource_identifier = get_taxonomy($resource_id);
        } elseif ($resource_type === AAM_Framework_Type_Resource::POST_TYPE) {
            $resource_identifier = get_post_type_object($resource_id);
        }

        $resource = $access_level->get_resource($resource_type);
        $args     = [
            'resource'            => $resource,
            'resource_identifier' => $resource_identifier,
            'resource_id'         => $resource_id,
            'access_controls'     => $this->_prepare_access_controls(
                $resource, $resource_identifier
            ),
            // TODO: Consider removing the Backend Access Level
            'access_level'        => AAM_Backend_AccessLevel::get_instance()
        ];

        // Do the SSR for the access form
        return apply_filters(
            "aam_{$resource_type}_access_form_filter",
            $this->_load_partial('content-access-form', (object) $args),
            (object) $args
        );
    }

    /**
     * Load dynamic template
     *
     * @param string $name
     * @param object $params
     *
     * @return string
     * @access public
     *
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
     * @param mixed                            $resource_identifier
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_access_controls($resource, $resource_identifier)
    {
        $result = [];

        if ($resource->type === AAM_Framework_Type_Resource::POST) {
            $result = $this->_prepare_post_access_controls(
                $resource, $resource_identifier
            );
        } else {
            $result = $this->_prepare_other_access_controls(
                $resource, $resource_identifier
            );
        }

        return $result;
    }

    /**
     * Prepare access controls for the post resource
     *
     * @param AAM_Framework_Resource_Post $resource
     * @param mixed                       $resource_identifier
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_post_access_controls($resource, $resource_identifier)
    {
        $list    = $resource->get_permission($resource_identifier, 'list');

        if (!empty($list) && $list['effect'] !== 'allow') {
            $on = !empty($list['on']) ? $list['on'] : [ 'frontend', 'backend', 'api' ];
        } else {
            $on = [];
        }

        $read    = $resource->get_permission($resource_identifier, 'read');
        $comment = $resource->get_permission($resource_identifier, 'comment');
        $edit    = $resource->get_permission($resource_identifier, 'edit');
        $publish = $resource->get_permission($resource_identifier, 'publish');
        $delete  = $resource->get_permission($resource_identifier, 'delete');

        return apply_filters('aam_ui_content_access_controls_filter', [
            'list' => array(
                'title'       => __('Hidden', 'advanced-access-manager'),
                'modal'       => 'modal_content_visibility',
                'is_denied'   => !empty($list) && $list['effect'] !== 'allow',
                'areas'       => $on,
                'customize'   => __('Customize visibility', 'advanced-access-manager'),
                'tooltip'     => sprintf(
                    __('Customize the visibility of "%s" separately for each section of your website. It\'s crucial to thoughtfully select which areas will have hidden content. For instance, you might choose to hide certain posts in the backend for content editors, while still allowing them to be visible on the frontend for general users.', 'advanced-access-manager'),
                    $resource_identifier->post_title
                ),
                'description' => sprintf(
                    __('Hide the "%s" from all menus, lists, and API responses. However, it remains accessible via a direct URL. Visibility can be customized for the frontend, backend and API areas independently.', 'advanced-access-manager'),
                    $resource_identifier->post_title
                ),
                'on' => [
                    'frontend' => sprintf(
                        __('Hide the "%s" on the website frontend', 'advanced-access-manager'),
                        $resource_identifier->post_title
                    ),
                    'backend' => sprintf(
                        __('Hide the "%s" in the backend (admin area)', 'advanced-access-manager'),
                        $resource_identifier->post_title
                    ),
                    'api' => sprintf(
                        __('Hide the "%s" in the RESTful API results', 'advanced-access-manager'),
                        $resource_identifier->post_title
                    )
                ]
            ),
            'read' => array(
                'title'       => __('Restricted', 'advanced-access-manager'),
                'modal'       => 'modal_content_restriction',
                'is_denied'   => !empty($read) && $read['effect'] !== 'allow',
                'customize'   => __('Customize direct access', 'advanced-access-manager'),
                'tooltip'     => sprintf(
                    __('Restrict direct access to read or download the "%s". This restriction can be customized with options such as setting an access expiration date, creating a password, redirecting to a different location, and more.', 'advanced-access-manager'),
                    $resource_identifier->post_title
                ),
                'description' => sprintf(
                    __('Restrict direct access to "%s". This restriction can be customized with options such as setting an access expiration date, creating a password, redirecting to a different location, and more.', 'advanced-access-manager'),
                    $resource_identifier->post_title
                )
            ),
            'comment' => array(
                'title'       => __('Leave Comments', 'advanced-access-manager'),
                'is_denied'   => !empty($comment) && $comment['effect'] !== 'allow',
                'description' => sprintf(
                    __('Limit the ability to leave comments on the "%s".', 'advanced-access-manager'),
                    $resource_identifier->post_title
                )
            ),
            'edit' => array(
                'title'       => __('Edit', 'advanced-access-manager'),
                'is_denied'   => !empty($edit) && $edit['effect'] !== 'allow',
                'description' => sprintf(
                    __('Disable the ability to edit "%s". Editing "%s" will be restricted both in the backend area and via the RESTful API.', 'advanced-access-manager'),
                    $resource_identifier->post_title,
                    $resource_identifier->post_title
                )
            ),
            'publish' => array(
                'title'       => __('Publish', 'advanced-access-manager'),
                'is_denied'   => !empty($publish) && $publish['effect'] !== 'allow',
                'description' => sprintf(
                    __('Manage the ability to publish draft "%s" or any updates to already published versions. If denied, a user will only be able to submit for review.', 'advanced-access-manager'),
                    $resource_identifier->post_title
                )
            ),
            'delete' => array(
                'title'       => __('Delete', 'advanced-access-manager'),
                'is_denied'   => !empty($delete) && $delete['effect'] !== 'allow',
                'description' => sprintf(
                    __('Disable the ability to delete "%s". Deletion will be restricted both in the backend area and via the RESTful API.', 'advanced-access-manager'),
                    $resource_identifier->post_title
                )
            )
        ], $resource, $resource_identifier);
    }

    /**
     * Prepare access controls for other resources
     *
     * @param AAM_Framework_Resource_Interface $resource
     * @param mixed                            $resource_identifier
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_other_access_controls($resource, $resource_identifier)
    {
        return apply_filters(
            'aam_ui_content_access_controls_filter',
            [],
            $resource,
            $resource_identifier
        );
    }

    /**
     * Register Posts & Pages service UI
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'post',
            'position'   => 20,
            'title'      => __('Posts & Terms', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }

}