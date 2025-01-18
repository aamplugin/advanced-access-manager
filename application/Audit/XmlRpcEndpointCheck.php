<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Check if XML-RPC endpoint is enabled
 *
 * @package AAM
 * @version 6.9.40
 */
class AAM_Audit_XmlRpcEndpointCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Run the check
     *
     * @return array
     *
     * @access public
     * @static
     * @version 6.9.40
     */
    public static function run()
    {
        $issues   = [];
        $response = [ 'is_completed' => true ];

        try {
            array_push($issues, ...self::_check_endpoint_accessability());
        } catch (Exception $e) {
            array_push($issues, self::_format_issue(sprintf(
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
     * @version 6.9.40
     */
    private static function _check_endpoint_accessability()
    {
        $response = [];

        $visitor = AAM::api()->getVisitor();

        // Check if API route "/xmlrpc.php" is enabled
        $api_url_enabled = !$visitor->getObject('uri')->findMatch('/xmlrpc.php');

        if ($api_url_enabled) {
            array_push($response, self::_format_issue(
                __('Detected open to unauthenticated users XML-RPC endpoint', AAM_KEY),
                'OPEN_XMLRPC_ENDPOINT'
            ));
        }

        // Check if XML-PRC API is enabled
        $api_enabled = apply_filters('xmlrpc_enabled', true);

        if ($api_enabled) {
            array_push($response, self::_format_issue(
                __('The XML-RPC API is enabled', AAM_KEY),
                'ENABLED_XMLRPC',
                'warning'
            ));
        }

        return $response;
    }

}