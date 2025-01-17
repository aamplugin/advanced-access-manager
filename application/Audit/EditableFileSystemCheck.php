<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Check if file system is editable
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Audit_EditableFileSystemCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Run the check
     *
     * @return array
     *
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function run()
    {
        $issues = [];
        $response = [ 'is_completed' => true ];

        try {
            array_push($issues, ...self::_check_file_system_permissions());
        } catch (Exception $e) {
            array_push($failure, self::_format_issue(sprintf(
                __('Unexpected application error: %s', AAM_KEY),
                $e->getMessage()
            ), 'APPLICATION_ERROR', 'error'));
        }

        if (count($issues) > 0) {
            $response['issues'] = $issues;
        }

        // Determine final status for the check
        self::_determine_check_status($response);

        return $response;
    }

    /**
     * Detect empty roles
     *
     * @return array
     *
     * @access private
     * @static
     *
     * @version 7.0.0
     */
    private static function _check_file_system_permissions()
    {
        $response = [];

        if (!defined( 'DISALLOW_FILE_EDIT' )
            || !constant('DISALLOW_FILE_EDIT')
            || !wp_is_file_mod_allowed('capability_edit_themes')
        ) {
            array_push($response, self::_format_issue(
                __('Detected potentially writable file system', AAM_KEY),
                'WRITABLE_FS',
                'warning'
            ));
        }

        return $response;
    }

}