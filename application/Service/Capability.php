<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Capability service
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Service_Capability
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the feature
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.capability.enabled';

    /**
     * Default configurations
     *
     * @version 6.9.34
     */
    const DEFAULT_CONFIG = [
        'core.service.capability.enabled' => true,
        'core.settings.editCapabilities'  => true
    ];

    /**
     * List of capabilities with their descriptions
     *
     * @var array
     *
     * @access private
     * @version 6.9.33
     */
    private $_capabilities = [];

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if (is_null($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);

        $enabled = AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Capability::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function($services) {
                $services[] = array(
                    'title'       => __('Capabilities', AAM_KEY),
                    'description' => __('Manage list of all the registered with WordPress core capabilities for any role or individual user. The service allows to create new or update and delete existing capabilities. Very powerful set of tools for more advanced user/role access management.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 15);
        }

        if ($enabled) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize service hooks
     *
     * @return void
     *
     * @access protected
     * @version 6.9.33
     */
    protected function initializeHooks()
    {
        // Register RESTful API endpoints
        AAM_Restful_CapabilityService::bootstrap();

        // Capability descriptions hooks
        add_filter(
            'aam_capability_description_filter',
            [ $this, 'get_cap_description' ],
            10,
            2
        );

        // Initialize the list of capabilities with descriptions
        $this->_capabilities = [
            'switch_themes' => __('Allows a user to change the active theme of a website, altering its overall design and layout.', AAM_KEY),
            'edit_themes' => __('Enables a user to directly modify the code of theme files, allowing for customization and adjustments to the website\'s appearance and functionality.', AAM_KEY),
            'edit_theme_options' => __('Permits a user to access and modify theme settings through the WordPress Customizer, enabling personalized adjustments to the site\'s appearance and functionality without altering code.', AAM_KEY),
            'install_themes' => __('Allows a user to add new themes to a website from the WordPress Theme Directory or by uploading theme files directly.', AAM_KEY),
            'activate_plugins' => __('Enables a user to activate or deactivate plugins, thereby controlling the addition or removal of specific functionalities on the website.', AAM_KEY),
            'edit_plugins' => __('Allows a user to directly modify the code of installed plugin files, enabling custom changes and enhancements to the site\'s functionality.', AAM_KEY),
            'install_plugins' => __('Allows a user to add new plugins to a website, expanding its functionality by integrating additional features and tools.', AAM_KEY),
            'edit_users' => __('Allows a user to modify the profiles and settings of existing users, including their roles, personal information, and permissions.', AAM_KEY),
            'edit_files' => __('Allows a user to edit files in the theme or plugin editor', AAM_KEY),
            'manage_options' => __('Allows a user to manage all site options and settings', AAM_KEY),
            'moderate_comments' => __('Allows a user to moderate comments and manage their status', AAM_KEY),
            'manage_categories' => __('Allows a user to manage and edit categories for posts', AAM_KEY),
            'manage_links' => __('Allows a user to manage and edit links in the blogroll', AAM_KEY),
            'upload_files' => __('Allows a user to upload files to the media library', AAM_KEY),
            'import' => __('Allows a user to import content from external sources', AAM_KEY),
            'unfiltered_html' => __('Allows a user to post unfiltered HTML content', AAM_KEY),
            'edit_posts' => __('Allows a user to edit posts created by the user', AAM_KEY),
            'edit_others_posts' => __('Allows a user to edit posts created by other users', AAM_KEY),
            'edit_published_posts' => __('Allows a user to edit posts that are already published', AAM_KEY),
            'publish_posts' => __('Allows a user to publish new posts', AAM_KEY),
            'edit_pages' => __('Allows a user to edit pages on the site', AAM_KEY),
            'read' => __('Allows a user to read and view site content', AAM_KEY),
            'publish_pages' => __('Publish pages on the site', AAM_KEY),
            'edit_others_pages' => __('Edit pages created by other users', AAM_KEY),
            'edit_published_pages' => __('Edit pages that are already published', AAM_KEY),
            'delete_pages' => __('Delete pages', AAM_KEY),
            'delete_others_pages' => __('Delete pages created by other users', AAM_KEY),
            'delete_published_pages' => __('Delete pages that are already published', AAM_KEY),
            'delete_posts' => __('Delete posts', AAM_KEY),
            'delete_others_posts' => __('Delete posts created by other users', AAM_KEY),
            'delete_published_posts' => __('Delete posts that are already published', AAM_KEY),
            'delete_private_posts' => __('Delete private posts', AAM_KEY),
            'edit_private_posts' => __('Edit private posts', AAM_KEY),
            'read_private_posts' => __('Read private posts', AAM_KEY),
            'delete_private_pages' => __('Delete private pages', AAM_KEY),
            'edit_private_pages' => __('Edit private pages', AAM_KEY),
            'read_private_pages' => __('Read private pages', AAM_KEY),
            'delete_users' => __('Delete users', AAM_KEY),
            'create_users' => __('Create new users', AAM_KEY),
            'unfiltered_upload' => __('Upload files without filtering', AAM_KEY),
            'edit_dashboard' => __('Access and edit the dashboard', AAM_KEY),
            'customize' => __('Customize site appearance and options', AAM_KEY),
            'delete_site' => __('Delete the entire site', AAM_KEY),
            'update_plugins' => __('Update installed plugins', AAM_KEY),
            'delete_plugins' => __('Delete installed plugins', AAM_KEY),
            'update_themes' => __('Update installed themes', AAM_KEY),
            'update_core' => __('Update WordPress core', AAM_KEY),
            'list_users' => __('View list of all users', AAM_KEY),
            'remove_users' => __('Remove users from the site', AAM_KEY),
            'add_users' => __('Add new users to the site', AAM_KEY),
            'promote_users' => __('Promote users to higher roles', AAM_KEY),
            'delete_themes' => __('Delete installed themes', AAM_KEY),
            'export' => __('Export data from the site', AAM_KEY),
            'edit_comment' => __('Edit comments left on the site', AAM_KEY),
            'create_sites' => __('Create new sites in a multisite network', AAM_KEY),
            'delete_sites' => __('Delete sites in a multisite network', AAM_KEY),
            'manage_network' => __('Manage the entire network of sites', AAM_KEY),
            'manage_sites' => __('Manage individual sites in a multisite network', AAM_KEY),
            'manage_network_users' => __('Manage users across the entire network', AAM_KEY),
            'manage_network_themes' => __('Manage themes across the entire network', AAM_KEY),
            'manage_network_options' => __('Manage network-wide options and settings', AAM_KEY),
            'manage_network_plugins' => __('Manage plugins across the entire network', AAM_KEY),
            'upload_plugins' => __('Upload plugins to the site', AAM_KEY),
            'upload_themes' => __('Upload themes to the site', AAM_KEY),
            'upgrade_network' => __('Upgrade the entire network of sites', AAM_KEY),
            'setup_network' => __('Set up and configure a multisite network', AAM_KEY),
            'level_0' => __('Read only user level. Typically the Subscriber role.', AAM_KEY),
            'level_1' => __('Limited access level. Typically the Contributor role.', AAM_KEY),
            'level_2' => __('Author role access level', AAM_KEY),
            'level_3' => __('No specific meaning.', AAM_KEY),
            'level_4' => __('No specific meaning.', AAM_KEY),
            'level_5' => __('No specific meaning.', AAM_KEY),
            'level_6' => __('No specific meaning.', AAM_KEY),
            'level_7' => __('Editor access level.', AAM_KEY),
            'level_8' => __('No specific meaning.', AAM_KEY),
            'level_9' => __('No specific meaning.', AAM_KEY),
            'level_10' => __('The highest level capabilities. Typically the Administrator role.', AAM_KEY)
        ];
    }

    /**
     * Get capability description
     *
     * @param string $description
     * @param string $slug
     *
     * @return string
     *
     * @access public
     * @version 6.9.33
     */
    public function get_cap_description($description, $slug)
    {
        if (empty($description) && isset($this->_capabilities[$slug])) {
            $description = $this->_capabilities[$slug];
        }

        return $description;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Capability::bootstrap();
}