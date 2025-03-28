<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Check if RESTful Auto-discovery endpoint is enabled
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Audit_RestfulAutoDiscoverEndpointCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'restful_auto_discover_endpoint';

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
            'REST_OPEN_DISCOVER_ENDPOINT' => __(
                'Detected open to anonymous users REST auto-discover endpoint',
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

        // Check if API route "/" is enabled
        $api_route_enabled = !$visitor->getObject('route')->isRestricted(
            'restful', '/', 'GET'
        );

        // Additionally check if the same endpoint is restricted with URL Access
        // service
        $parsed  = wp_parse_url(rest_url());
        $matched = $visitor->getObject('uri')->findMatch(wp_validate_redirect(
            empty($parsed['path']) ? '/' : $parsed['path']
        ));

        $url_enabled = empty($matched) || $matched['type'] === 'allow';

        // Verifying that auto-discover endpoint is disabled for visitors
        if ($url_enabled && $api_route_enabled) {
            array_push($response, self::_format_issue('REST_OPEN_DISCOVER_ENDPOINT'));
        }

        return $response;
    }

}