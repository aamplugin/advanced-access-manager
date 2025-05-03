<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * JS localization for AAM backend UI
 *
 * @since 6.8.1 https://github.com/aamplugin/advanced-access-manager/issues/199
 * @since 6.2.1 Added new label "Policy is not assigned to anybody"
 * @since 6.2.0 Added couple new labels
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.8.1
 */
class AAM_Backend_View_Localization
{

    /**
     * Get localization array
     *
     * @return array
     *
     * @since 6.8.1 https://github.com/aamplugin/advanced-access-manager/issues/199
     * @since 6.2.1 Added new label "Policy is not assigned to anybody"
     * @since 6.2.0 Added couple new labels
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.8.1
     */
    public static function get()
    {
        return array(
            'Search Capability' => __('Search Capability', 'advanced-access-manager'),
            '_TOTAL_ capability(s)' => __('_TOTAL_ capability(s)', 'advanced-access-manager'),
            'Saving...' => __('Saving...', 'advanced-access-manager'),
            'Execute Migration' => __('Execute Migration', 'advanced-access-manager'),
            'Failed to add new capability' => __('Failed to add new capability', 'advanced-access-manager'),
            'Application error' => __('Application error', 'advanced-access-manager'),
            'Add Capability' => __('Add Capability', 'advanced-access-manager'),
            'Update Capability' => __('Update Capability', 'advanced-access-manager'),
            'Show Menu' => __('Show Menu', 'advanced-access-manager'),
            'Restrict Menu' => __('Restrict Menu', 'advanced-access-manager'),
            'Failed to retrieve mataboxes' => __('Failed to retrieve mataboxes', 'advanced-access-manager'),
            'Search' => __('Search', 'advanced-access-manager'),
            '_TOTAL_ object(s)' => __('_TOTAL_ object(s)', 'advanced-access-manager'),
            'Failed' => __('Failed', 'advanced-access-manager'),
            'Loading...' => __('Loading...', 'advanced-access-manager'),
            'No role' => __('No role', 'advanced-access-manager'),
            'Create New Role' => __('Create New Role', 'advanced-access-manager'),
            'Search role' => __('Search role', 'advanced-access-manager'),
            '_TOTAL_ role(s)' => __('_TOTAL_ role(s)', 'advanced-access-manager'),
            'Create' => __('Create', 'advanced-access-manager'),
            'Users' => __('Users', 'advanced-access-manager'),
            'Failed to add new role' => __('Failed to add new role', 'advanced-access-manager'),
            'Add Role' => __('Add Role', 'advanced-access-manager'),
            'Failed to update role' => __('Failed to update role', 'advanced-access-manager'),
            'Update' => __('Update', 'advanced-access-manager'),
            'Reset' => __('Reset', 'advanced-access-manager'),
            'Resetting...' => __('Resetting...', 'advanced-access-manager'),
            'Deleting...' => __('Deleting...', 'advanced-access-manager'),
            'Failed to delete role' => __('Failed to delete role', 'advanced-access-manager'),
            'Delete Role' => __('Delete Role', 'advanced-access-manager'),
            'Failed to lock user' => __('Failed to lock user', 'advanced-access-manager'),
            'Search user' => __('Search user', 'advanced-access-manager'),
            'Counter was reset successfully' => __('Counter was reset successfully', 'advanced-access-manager'),
            '_TOTAL_ user(s)' => __('_TOTAL_ user(s)', 'advanced-access-manager'),
            'Create New User' => __('Create New User', 'advanced-access-manager'),
            'Role' => __('Role', 'advanced-access-manager'),
            'Message has been sent' => __('Message has been sent', 'advanced-access-manager'),
            'Download Exported Settings' => __('Download Exported Settings', 'advanced-access-manager'),
            'All Users, Roles and Visitor' => __('All Users, Roles and Visitor', 'advanced-access-manager'),
            'Failed to apply policy changes' => __('Failed to apply policy changes', 'advanced-access-manager'),
            'Attach Policy To Visitors' => __('Attach Policy To Visitors', 'advanced-access-manager'),
            'Detach Policy From Visitors' => __('Detach Policy From Visitors', 'advanced-access-manager'),
            'Generating URL...' => __('Generating URL...', 'advanced-access-manager'),
            'Anonymous' => __('Anonymous', 'advanced-access-manager'),
            'Processing...' => __('Processing...', 'advanced-access-manager'),
            'Loading roles...' => __('Loading roles...', 'advanced-access-manager'),
            'Failed to generate JWT token' => __('Failed to generate JWT token', 'advanced-access-manager'),
            'Failed to process request' => __('Failed to process request', 'advanced-access-manager'),
            'Current user' => __('Current user', 'advanced-access-manager'),
            'Current role' => __('Current role', 'advanced-access-manager'),
            'Manage Access' => __('Manage Access', 'advanced-access-manager'),
            'Filter by role' => __('Filter by role', 'advanced-access-manager'),
            'Edit' => __('Edit', 'advanced-access-manager'),
            'Save' => __('Save', 'advanced-access-manager'),
            'Manage role' => __('Manage role', 'advanced-access-manager'),
            'Edit role' => __('Edit role', 'advanced-access-manager'),
            'Delete role' => __('Delete role', 'advanced-access-manager'),
            'Clone role' => __('Clone role', 'advanced-access-manager'),
            'Manage user' => __('Manage user', 'advanced-access-manager'),
            'Edit user' => __('Edit user', 'advanced-access-manager'),
            'Lock user' => __('Lock user', 'advanced-access-manager'),
            'Unlock user' => __('Unlock user', 'advanced-access-manager'),
            'WordPress core does not allow to grant this capability' => __('WordPress core does not allow to grant this capability', 'advanced-access-manager'),
            'Detach Policy From Everybody' => __('Detach Policy From Everybody', 'advanced-access-manager'),
            'Attach Policy To Everybody' => __('Attach Policy To Everybody', 'advanced-access-manager'),
            'Search Policy' => __('Search Policy', 'advanced-access-manager'),
            '_TOTAL_ Policies' => __('_TOTAL_ Policies', 'advanced-access-manager'),
            'Apply Policy' => __('Apply Policy', 'advanced-access-manager'),
            'Revoke Policy' => __('Revoke Policy', 'advanced-access-manager'),
            'Edit Policy' => __('Edit Policy', 'advanced-access-manager'),
            'Uncheck to allow' => __('Uncheck to allow', 'advanced-access-manager'),
            'Check to restrict' => __('Check to restrict', 'advanced-access-manager'),
            'Uncheck to show' => __('Uncheck to show', 'advanced-access-manager'),
            'Check to hide' => __('Check to hide', 'advanced-access-manager'),
            'Initialize' => __('Initialize', 'advanced-access-manager'),
            'No capabilities' => __('No capabilities', 'advanced-access-manager'),
            'Post Type' => __('Post Type', 'advanced-access-manager'),
            'Hierarchical Taxonomy' => __('Hierarchical Taxonomy', 'advanced-access-manager'),
            'Hierarchical Term' => __('Hierarchical Term', 'advanced-access-manager'),
            'Tag Taxonomy' => __('Tag Taxonomy', 'advanced-access-manager'),
            'Tag' => __('Tag', 'advanced-access-manager'),
            'Customized Settings' => __('Customized Settings', 'advanced-access-manager'),
            'Parent' => __('Parent', 'advanced-access-manager'),
            'Drill-Down' => __('Drill-Down', 'advanced-access-manager'),
            '_TOTAL_ route(s)' => __('_TOTAL_ route(s)', 'advanced-access-manager'),
            'No API endpoints found. You might have APIs disabled.' => __('No API endpoints found. You might have APIs disabled.', 'advanced-access-manager'),
            'Nothing to show' => __('Nothing to show', 'advanced-access-manager'),
            'Failed to save URI rule' => __('Failed to save URI rule', 'advanced-access-manager'),
            'Failed to delete URI rule' => __('Failed to delete URI rule', 'advanced-access-manager'),
            '_TOTAL_ URI(s)' => __('_TOTAL_ URI(s)', 'advanced-access-manager'),
            'Edit Rule' => __('Edit Rule', 'advanced-access-manager'),
            'Delete Rule' => __('Delete Rule', 'advanced-access-manager'),
            'Denied' => __('Denied', 'advanced-access-manager'),
            'Redirected' => __('Redirected', 'advanced-access-manager'),
            'Callback' => __('Callback', 'advanced-access-manager'),
            'Allowed' => __('Allowed', 'advanced-access-manager'),
            'Generating token...' => __('Generating token...', 'advanced-access-manager'),
            '_TOTAL_ token(s)' => __('_TOTAL_ token(s)', 'advanced-access-manager'),
            'No JWT tokens have been generated.' => __('No JWT tokens have been generated.', 'advanced-access-manager'),
            'Delete Token' => __('Delete Token', 'advanced-access-manager'),
            'View Token' => __('View Token', 'advanced-access-manager'),
            'Creating...' => __('Creating...', 'advanced-access-manager'),
            'Search Service' => __('Search Service', 'advanced-access-manager'),
            '_TOTAL_ service(s)' => __('_TOTAL_ service(s)', 'advanced-access-manager'),
            'Enabled' => __('Enabled', 'advanced-access-manager'),
            'Disabled' => __('Disabled', 'advanced-access-manager'),
            'All settings has been cleared successfully' => __('All settings has been cleared successfully', 'advanced-access-manager'),
            'Clear' => __('Clear', 'advanced-access-manager'),
            'Select Role' => __('Select Role', 'advanced-access-manager'),
            'Policy is not assigned to anybody' => __('Policy is not assigned to anybody', 'advanced-access-manager'),
            'Data has been saved to clipboard' => __('Data has been saved to clipboard', 'advanced-access-manager'),
            'Failed to save data to clipboard' => __('Failed to save data to clipboard', 'advanced-access-manager'),
            'Operation completed successfully' => __('Operation completed successfully', 'advanced-access-manager'),
            'Unexpected application error' => __('Unexpected application error', 'advanced-access-manager')
        );
    }

}