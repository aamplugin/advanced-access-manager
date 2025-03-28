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
 * @version 7.0.0
 */
class AAM_Audit_XmlRpcEndpointCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'xml_rpc_endpoint';

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
        $issues   = [];
        $response = [ 'is_completed' => true ];

        try {
            array_push($issues, ...self::_check_endpoint_accessability());
        } catch (Exception $e) {
            array_push($failure, self::_format_issue(
                'APPLICATION_ERROR',
                [
                    'message' => $e->getMessage()
                ],
                'error'
            ));
        }

        if (count($issues) > 0) {
            $response['issues'] = $issues;
        }

        // Determine final status for the check
        self::_determine_check_status($response);

        return $response;
    }

    /**
     * Get a collection of error messages for current step
     *
     * @return array
     * @access private
     * @static
     *
     * @version 7.0.0
     */
    private static function _get_message_templates()
    {
        return [
            'OPEN_XMLRPC_ENDPOINT' => __(
                'Detected open to anonymous users XML-RPC endpoint',
                'advanced-access-manager'
            ),
            'ENABLED_XMLRPC' => __(
                'The XML-RPC API is enabled',
                'advanced-access-manager'
            )
        ];
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
    private static function _check_endpoint_accessability()
    {
        $response = [];

        $visitor = AAM::api()->getVisitor();

        // Check if API route "/xmlrpc.php" is enabled
        $api_url_enabled = !$visitor->getObject('uri')->findMatch('/xmlrpc.php');

        if ($api_url_enabled) {
            array_push($response, self::_format_issue('OPEN_XMLRPC_ENDPOINT'));
        }

        // Check if XML-PRC API is enabled
        $api_enabled = apply_filters('xmlrpc_enabled', true);

        if ($api_enabled) {
            array_push($response, self::_format_issue(
                'ENABLED_XMLRPC',
                [],
                'warning'
            ));
        }

        return $response;
    }

}