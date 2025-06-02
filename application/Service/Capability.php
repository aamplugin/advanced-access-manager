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
 * @version 7.0.0
 */
class AAM_Service_Capability
{
    use AAM_Service_BaseTrait;

    /**
     * Default configurations
     *
     * @version 7.0.0
     */
    const DEFAULT_CONFIG = [
        'service.capability.edit_caps' => true
    ];

    /**
     * List of capabilities with their descriptions
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_capabilities = [];

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if (empty($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);

        // Register RESTful API endpoints
        AAM_Restful_Capability::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Initialize service hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_Capability::register();
            });
        }

        // Capability descriptions hooks
        add_filter(
            'aam_capability_description_filter',
            [ $this, 'get_cap_description' ],
            10,
            2
        );

        // Initialize the list of capabilities with descriptions
        $this->_capabilities = [
            'switch_themes' => __('Allows a user to change the active theme of a website, altering its overall design and layout.', 'advanced-access-manager'),
            'edit_themes' => __('Enables a user to directly modify the code of theme files, allowing for customization and adjustments to the website\'s appearance and functionality.', 'advanced-access-manager'),
            'edit_theme_options' => __('Permits a user to access and modify theme settings through the WordPress Customizer, enabling personalized adjustments to the site\'s appearance and functionality without altering code.', 'advanced-access-manager'),
            'install_themes' => __('Allows a user to add new themes to a website from the WordPress Theme Directory or by uploading theme files directly.', 'advanced-access-manager'),
            'activate_plugins' => __('Enables a user to activate or deactivate plugins, thereby controlling the addition or removal of specific functionalities on the website.', 'advanced-access-manager'),
            'edit_plugins' => __('Allows a user to directly modify the code of installed plugin files, enabling custom changes and enhancements to the site\'s functionality.', 'advanced-access-manager'),
            'install_plugins' => __('Allows a user to add new plugins to a website, expanding its functionality by integrating additional features and tools.', 'advanced-access-manager'),
            'edit_users' => __('Allows a user to modify the profiles and settings of existing users, including their roles, personal information, and permissions.', 'advanced-access-manager'),
            'edit_files' => __('Allows a user to edit files in the theme or plugin editor', 'advanced-access-manager'),
            'manage_options' => __('Allows a user to manage all site options and settings', 'advanced-access-manager'),
            'moderate_comments' => __('Allows a user to moderate comments and manage their status', 'advanced-access-manager'),
            'manage_categories' => __('Allows a user to manage and edit categories for posts', 'advanced-access-manager'),
            'manage_links' => __('Allows a user to manage and edit links in the blogroll', 'advanced-access-manager'),
            'upload_files' => __('Allows a user to upload files to the media library', 'advanced-access-manager'),
            'import' => __('Allows a user to import content from external sources', 'advanced-access-manager'),
            'unfiltered_html' => __('Allows a user to post unfiltered HTML content', 'advanced-access-manager'),
            'edit_posts' => __('Allows a user to edit posts created by the user', 'advanced-access-manager'),
            'edit_others_posts' => __('Allows a user to edit posts created by other users', 'advanced-access-manager'),
            'edit_published_posts' => __('Allows a user to edit posts that are already published', 'advanced-access-manager'),
            'publish_posts' => __('Allows a user to publish new posts', 'advanced-access-manager'),
            'edit_pages' => __('Allows a user to edit pages on the site', 'advanced-access-manager'),
            'read' => __('Allows a user to read and view site content', 'advanced-access-manager'),
            'publish_pages' => __('Publish pages on the site', 'advanced-access-manager'),
            'edit_others_pages' => __('Edit pages created by other users', 'advanced-access-manager'),
            'edit_published_pages' => __('Edit pages that are already published', 'advanced-access-manager'),
            'delete_pages' => __('Delete pages', 'advanced-access-manager'),
            'delete_others_pages' => __('Delete pages created by other users', 'advanced-access-manager'),
            'delete_published_pages' => __('Delete pages that are already published', 'advanced-access-manager'),
            'delete_posts' => __('Delete posts', 'advanced-access-manager'),
            'delete_others_posts' => __('Delete posts created by other users', 'advanced-access-manager'),
            'delete_published_posts' => __('Delete posts that are already published', 'advanced-access-manager'),
            'delete_private_posts' => __('Delete private posts', 'advanced-access-manager'),
            'edit_private_posts' => __('Edit private posts', 'advanced-access-manager'),
            'read_private_posts' => __('Read private posts', 'advanced-access-manager'),
            'delete_private_pages' => __('Delete private pages', 'advanced-access-manager'),
            'edit_private_pages' => __('Edit private pages', 'advanced-access-manager'),
            'read_private_pages' => __('Read private pages', 'advanced-access-manager'),
            'delete_users' => __('Delete users', 'advanced-access-manager'),
            'create_users' => __('Create new users', 'advanced-access-manager'),
            'unfiltered_upload' => __('Upload files without filtering', 'advanced-access-manager'),
            'edit_dashboard' => __('Access and edit the dashboard', 'advanced-access-manager'),
            'customize' => __('Customize site appearance and options', 'advanced-access-manager'),
            'delete_site' => __('Delete the entire site', 'advanced-access-manager'),
            'update_plugins' => __('Update installed plugins', 'advanced-access-manager'),
            'delete_plugins' => __('Delete installed plugins', 'advanced-access-manager'),
            'update_themes' => __('Update installed themes', 'advanced-access-manager'),
            'update_core' => __('Update WordPress core', 'advanced-access-manager'),
            'list_users' => __('View list of all users', 'advanced-access-manager'),
            'remove_users' => __('Remove users from the site', 'advanced-access-manager'),
            'add_users' => __('Add new users to the site', 'advanced-access-manager'),
            'promote_users' => __('Promote users to higher roles', 'advanced-access-manager'),
            'delete_themes' => __('Delete installed themes', 'advanced-access-manager'),
            'export' => __('Export data from the site', 'advanced-access-manager'),
            'edit_comment' => __('Edit comments left on the site', 'advanced-access-manager'),
            'create_sites' => __('Create new sites in a multisite network', 'advanced-access-manager'),
            'delete_sites' => __('Delete sites in a multisite network', 'advanced-access-manager'),
            'manage_network' => __('Manage the entire network of sites', 'advanced-access-manager'),
            'manage_sites' => __('Manage individual sites in a multisite network', 'advanced-access-manager'),
            'manage_network_users' => __('Manage users across the entire network', 'advanced-access-manager'),
            'manage_network_themes' => __('Manage themes across the entire network', 'advanced-access-manager'),
            'manage_network_options' => __('Manage network-wide options and settings', 'advanced-access-manager'),
            'manage_network_plugins' => __('Manage plugins across the entire network', 'advanced-access-manager'),
            'upload_plugins' => __('Upload plugins to the site', 'advanced-access-manager'),
            'upload_themes' => __('Upload themes to the site', 'advanced-access-manager'),
            'upgrade_network' => __('Upgrade the entire network of sites', 'advanced-access-manager'),
            'setup_network' => __('Set up and configure a multisite network', 'advanced-access-manager'),
            'level_0' => __('Read only user level. Typically the Subscriber role.', 'advanced-access-manager'),
            'level_1' => __('Limited access level. Typically the Contributor role.', 'advanced-access-manager'),
            'level_2' => __('Author role access level', 'advanced-access-manager'),
            'level_3' => __('No specific meaning.', 'advanced-access-manager'),
            'level_4' => __('No specific meaning.', 'advanced-access-manager'),
            'level_5' => __('No specific meaning.', 'advanced-access-manager'),
            'level_6' => __('No specific meaning.', 'advanced-access-manager'),
            'level_7' => __('Editor access level.', 'advanced-access-manager'),
            'level_8' => __('No specific meaning.', 'advanced-access-manager'),
            'level_9' => __('No specific meaning.', 'advanced-access-manager'),
            'level_10' => __('The highest level capabilities. Typically the Administrator role.', 'advanced-access-manager')
        ];
    }

    /**
     * Get capability description
     *
     * @param string $description
     * @param string $slug
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function get_cap_description($description, $slug)
    {
        if (empty($description) && isset($this->_capabilities[$slug])) {
            $description = $this->_capabilities[$slug];
        }

        return $description;
    }

}