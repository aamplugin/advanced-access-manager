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
 * @since 6.2.1 Added new label "Policy is not assigned to anybody"
 * @since 6.2.0 Added couple new labels
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.2.1
 */
class AAM_Backend_View_Localization
{

    /**
     * Get localization array
     *
     * @return array
     *
     * @since 6.2.1 Added new label "Policy is not assigned to anybody"
     * @since 6.2.0 Added couple new labels
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.1
     */
    public static function get()
    {
        return array(
            'Search Capability' => __('Search Capability', AAM_KEY),
            '_TOTAL_ capability(s)' => __('_TOTAL_ capability(s)', AAM_KEY),
            'Saving...' => __('Saving...', AAM_KEY),
            'Execute Migration' => __('Execute Migration', AAM_KEY),
            'Failed to add new capability' => __('Failed to add new capability', AAM_KEY),
            'Application error' => __('Application error', AAM_KEY),
            'Add Capability' => __('Add Capability', AAM_KEY),
            'Update Capability' => __('Update Capability', AAM_KEY),
            'Show Menu' => __('Show Menu', AAM_KEY),
            'Restrict Menu' => __('Restrict Menu', AAM_KEY),
            'Failed to retrieve mataboxes' => __('Failed to retrieve mataboxes', AAM_KEY),
            'Search' => __('Search', AAM_KEY),
            '_TOTAL_ object(s)' => __('_TOTAL_ object(s)', AAM_KEY),
            'Failed' => __('Failed', AAM_KEY),
            'Loading...' => __('Loading...', AAM_KEY),
            'No role' => __('No role', AAM_KEY),
            'Create New Role' => __('Create New Role', AAM_KEY),
            'Search Role' => __('Search Role', AAM_KEY),
            '_TOTAL_ role(s)' => __('_TOTAL_ role(s)', AAM_KEY),
            'Create' => __('Create', AAM_KEY),
            'Users' => __('Users', AAM_KEY),
            'Failed to add new role' => __('Failed to add new role', AAM_KEY),
            'Add Role' => __('Add Role', AAM_KEY),
            'Failed to update role' => __('Failed to update role', AAM_KEY),
            'Update' => __('Update', AAM_KEY),
            'Reset' => __('Reset', AAM_KEY),
            'Resetting...' => __('Resetting...', AAM_KEY),
            'Deleting...' => __('Deleting...', AAM_KEY),
            'Failed to delete role' => __('Failed to delete role', AAM_KEY),
            'Delete Role' => __('Delete Role', AAM_KEY),
            'Failed to lock user' => __('Failed to lock user', AAM_KEY),
            'Search user' => __('Search user', AAM_KEY),
            'Counter was reset successfully' => __('Counter was reset successfully', AAM_KEY),
            '_TOTAL_ user(s)' => __('_TOTAL_ user(s)', AAM_KEY),
            'Create New User' => __('Create New User', AAM_KEY),
            'Role' => __('Role', AAM_KEY),
            'Message has been sent' => __('Message has been sent', AAM_KEY),
            'Download Exported Settings' => __('Download Exported Settings', AAM_KEY),
            'All Users, Roles and Visitor' => __('All Users, Roles and Visitor', AAM_KEY),
            'Failed to apply policy changes' => __('Failed to apply policy changes', AAM_KEY),
            'Attach Policy To Visitors' => __('Attach Policy To Visitors', AAM_KEY),
            'Detach Policy From Visitors' => __('Detach Policy From Visitors', AAM_KEY),
            'Generating URL...' => __('Generating URL...', AAM_KEY),
            'Anonymous' => __('Anonymous', AAM_KEY),
            'Processing...' => __('Processing...', AAM_KEY),
            'Loading roles...' => __('Loading roles...', AAM_KEY),
            'Failed to generate JWT token' => __('Failed to generate JWT token', AAM_KEY),
            'Failed to process request' => __('Failed to process request', AAM_KEY),
            'Current user' => __('Current user', AAM_KEY),
            'Current role' => __('Current role', AAM_KEY),
            'Manage Access' => __('Manage Access', AAM_KEY),
            'Filter by role' => __('Filter by role', AAM_KEY),
            'Edit' => __('Edit', AAM_KEY),
            'Save' => __('Save', AAM_KEY),
            'Manage role' => __('Manage role', AAM_KEY),
            'Edit role' => __('Edit role', AAM_KEY),
            'Delete role' => __('Delete role', AAM_KEY),
            'Clone role' => __('Clone role', AAM_KEY),
            'Manage user' => __('Manage user', AAM_KEY),
            'Edit user' => __('Edit user', AAM_KEY),
            'Lock user' => __('Lock user', AAM_KEY),
            'Unlock user' => __('Unlock user', AAM_KEY),
            'WordPress core does not allow to grant this capability' => __('WordPress core does not allow to grant this capability', AAM_KEY),
            'Detach Policy From Everybody' => __('Detach Policy From Everybody', AAM_KEY),
            'Attach Policy To Everybody' => __('Attach Policy To Everybody', AAM_KEY),
            'Search Policy' => __('Search Policy', AAM_KEY),
            '_TOTAL_ Policies' => __('_TOTAL_ Policies', AAM_KEY),
            'Apply Policy' => __('Apply Policy', AAM_KEY),
            'Revoke Policy' => __('Revoke Policy', AAM_KEY),
            'Edit Policy' => __('Edit Policy', AAM_KEY),
            'Uncheck to allow' => __('Uncheck to allow', AAM_KEY),
            'Check to restrict' => __('Check to restrict', AAM_KEY),
            'Uncheck to show' => __('Uncheck to show', AAM_KEY),
            'Check to hide' => __('Check to hide', AAM_KEY),
            'Initialize' => __('Initialize', AAM_KEY),
            'No capabilities' => __('No capabilities', AAM_KEY),
            'Post Type' => __('Post Type', AAM_KEY),
            'Hierarchical Taxonomy' => __('Hierarchical Taxonomy', AAM_KEY),
            'Hierarchical Term' => __('Hierarchical Term', AAM_KEY),
            'Tag Taxonomy' => __('Tag Taxonomy', AAM_KEY),
            'Tag' => __('Tag', AAM_KEY),
            'Customized Settings' => __('Customized Settings', AAM_KEY),
            'Parent' => __('Parent', AAM_KEY),
            'Drill-Down' => __('Drill-Down', AAM_KEY),
            '_TOTAL_ route(s)' => __('_TOTAL_ route(s)', AAM_KEY),
            'No API endpoints found. You might have APIs disabled.' => __('No API endpoints found. You might have APIs disabled.', AAM_KEY),
            'Nothing to show' => __('Nothing to show', AAM_KEY),
            'Failed to save URI rule' => __('Failed to save URI rule', AAM_KEY),
            'Failed to delete URI rule' => __('Failed to delete URI rule', AAM_KEY),
            '_TOTAL_ URI(s)' => __('_TOTAL_ URI(s)', AAM_KEY),
            'Edit Rule' => __('Edit Rule', AAM_KEY),
            'Delete Rule' => __('Delete Rule', AAM_KEY),
            'Denied' => __('Denied', AAM_KEY),
            'Redirected' => __('Redirected', AAM_KEY),
            'Callback' => __('Callback', AAM_KEY),
            'Allowed' => __('Allowed', AAM_KEY),
            'Generating token...' => __('Generating token...', AAM_KEY),
            '_TOTAL_ token(s)' => __('_TOTAL_ token(s)', AAM_KEY),
            'No JWT tokens have been generated.' => __('No JWT tokens have been generated.', AAM_KEY),
            'Delete Token' => __('Delete Token', AAM_KEY),
            'View Token' => __('View Token', AAM_KEY),
            'Creating...' => __('Creating...', AAM_KEY),
            'Search Service' => __('Search Service', AAM_KEY),
            '_TOTAL_ service(s)' => __('_TOTAL_ service(s)', AAM_KEY),
            'Enabled' => __('Enabled', AAM_KEY),
            'Disabled' => __('Disabled', AAM_KEY),
            'All settings has been cleared successfully' => __('All settings has been cleared successfully', AAM_KEY),
            'Clear' => __('Clear', AAM_KEY),
            'Select Role' => __('Select Role', AAM_KEY),
            'Policy is not assigned to anybody' => __('Policy is not assigned to anybody', AAM_KEY),
            'Data has been saved to clipboard' => __('Data has been saved to clipboard', AAM_KEY),
            'Failed to save data to clipboard' => __('Failed to save data to clipboard', AAM_KEY),
            'Operation completed successfully' => __('Operation completed successfully', AAM_KEY),
            'Unexpected application error' => __('Unexpected application error', AAM_KEY)
        );
    }

}