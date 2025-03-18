<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the security audit service
 *
 * @package AAM
 * @version 6.9.40
 */
class AAM_Restful_SecurityAuditService
{

    use AAM_Restful_ServiceTrait;

    /**
     * The namespace for the collection of endpoints
     */
    const API_NAMESPACE = 'aam/v2';

    /**
     * Single instance of itself
     *
     * @var AAM_Restful_SecurityAuditService
     *
     * @access private
     * @static
     * @version 6.9.40
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.40
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Create new support message
            $this->_register_route('/service/audit', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'run_step'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_trigger_audit');
                },
                'args' => array(
                    'step' => array(
                        'description' => 'Security audit step',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'reset' => [
                        'description' => 'Wether reset already existing results or not',
                        'type'        => 'boolean',
                        'default'     => false
                    ]
                )
            ));

            // Get complete report
            $this->_register_route('/service/audit/report', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'generate_report'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_trigger_audit');
                }
            ));

            // Share complete report
            $this->_register_route('/service/audit/share', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'share_report'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_share_audit_results');
                },
                'args' => [
                    'email' => [
                        'description' => 'Email address for communication',
                        'type'        => 'string',
                        'required'    => true,
                        'validate_callback' => function($email) {
                            return filter_var($email, FILTER_VALIDATE_EMAIL);
                        }
                    ]
                ]
            ));
        });
    }

    /**
     * Run the current step
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.9.40
     */
    public function run_step(WP_REST_Request $request)
    {
        try {
            $response = AAM_Service_SecurityAudit::bootstrap()->execute(
                $request->get_param('step'),
                $request->get_param('reset')
            );
        } catch (Exception $ex) {
            $response = $this->_prepare_error_response($ex);
        }

        return rest_ensure_response($response);
    }

    /**
     * Generate report
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.9.40
     */
    public function generate_report(WP_REST_Request $request)
    {
        try {
            $report_type = $request->get_header('accept');

            if ($report_type === 'text/csv') {
                header('Content-Type: text/csv; charset=utf-8');

                $this->_generate_csv_report();

                $response = new WP_REST_Response(null, 200);
            } else {
                $response = rest_ensure_response($this->_generate_json_report());
            }

        } catch (Exception $ex) {
            $response = rest_ensure_response($this->_prepare_error_response($ex));
        }

        return $response;
    }

    /**
     * Share report
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function share_report(WP_REST_Request $request)
    {
        // Step #1. Let's get signed URL that we can use to upload the report
        $url = $this->_get_signed_url($request->get_param('email'));

        // Step #2. Prepare the audit report
        $report = json_encode([
            'roles'     => wp_roles()->roles,
            'users'     => count_users(),
            'results'   => $this->_generate_json_report(),
            'settings'  => AAM_Core_API::getOption(
                AAM_Core_AccessSettings::DB_OPTION, []
            ),
            'configs'   => AAM_Core_API::getOption(
                AAM_Framework_Service_Configs::DB_OPTION, []
            ),
            'configpress' => AAM_Core_API::getOption(
                AAM_Framework_Service_Configs::DB_CONFIGPRESS_OPTION, []
            ),
            'plugins'   => $this->_get_plugin_list()
        ]);

        // Step #3. Upload the report
        $response = wp_remote_request($url, [
            'method'    => 'PUT',
            'body'      => $report,
            'headers'   => [
                'Content-Type'   => 'application/octet-stream',
                'Content-Length' => strlen($report)
            ],
            'timeout'     => 15,
            'data_format' => 'body'
        ]);

        // Check for errors in the response
        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }

        $http_code = wp_remote_retrieve_response_code($response);

        return rest_ensure_response([
            'status' => $http_code == 200 ? 'success' : 'failure'
        ]);
    }

    /**
     * Get signed URL for report upload
     *
     * @param string $email
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_signed_url($email)
    {
        $result = wp_remote_get(add_query_arg([
            'email' => $email
        ], 'https://api.aamportal.com/upload/url'));

        if (is_wp_error($result)) {
            throw new RuntimeException('Failed to connect to the server');
        }

        $data = json_decode(wp_remote_retrieve_body($result), true);

        if (empty($data['url'])) {
            throw new RuntimeException('Failed to prepare report for upload');
        }

        return $data['url'];
    }

    /**
     * Get list of all installed plugins
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_plugin_list()
    {
        if (!function_exists('get_plugins')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        // Get all installed plugins
        $plugins = get_plugins();

        // Initialize an array to store the plugin information
        $result = [];

        // Loop through each plugin and check its status
        foreach ($plugins as $plugin_path => $plugin) {
            $result[] = [
                'name'        => $plugin['Name'],
                'version'     => $plugin['Version'],
                'is_active'   => is_plugin_active($plugin_path),
                'plugin_path' => $plugin_path,
                'description' => $plugin['Description']
            ];
        }

        return $result;
    }

    /**
     * Generate CSV version of the report
     *
     * @return void
     *
     * @access private
     * @version 6.9.40
     */
    private function _generate_csv_report()
    {
        $service = AAM_Service_SecurityAudit::bootstrap();

        // Open output buffer for CSV content & set header
        $report = fopen('php://output', 'w');
        fputcsv($report, [ 'Issue', 'Type', 'Category' ]);

        $data   = $service->read();
        $checks = $service->get_steps();

        foreach($data as $check_id => $check_result) {
            $check = $checks[$check_id];

            if (!empty($check_result['issues'])) {
                foreach($check_result['issues'] as $failure) {
                    fputcsv($report, [
                        $failure['reason'],
                        $failure['type'],
                        isset($check['category']) ? $check['category'] : $check_id
                    ]);
                }
            }
        }

        // Close output buffer
        fclose($report);
    }

    /**
     * Generate JSON version of the report
     *
     * @return string
     *
     * @access private
     * @version 6.9.40
     */
    private function _generate_json_report()
    {
        $report  = [];
        $service = AAM_Service_SecurityAudit::bootstrap();
        $data    = $service->read();
        $checks  = $service->get_steps();

        foreach($data as $check_id => $check_result) {
            $check = $checks[$check_id];

            if (!empty($check_result['issues'])) {
                foreach($check_result['issues'] as $failure) {
                    array_push($report, [
                        'issue'    => $failure['reason'],
                        'type'     => $failure['type'],
                        'category' => isset($check['category']) ? $check['category'] : $check_id
                    ]);
                }
            }
        }

        return $report;
    }

    /**
     * Register new RESTful route
     *
     * The method also applies the `aam_rest_route_args_filter` filter that allows
     * other processes to change the router definition
     *
     * @param string $route
     * @param array  $args
     *
     * @return void
     *
     * @access private
     * @version 6.9.40
     */
    private function _register_route($route, $args)
    {
        register_rest_route(
            self::API_NAMESPACE,
            $route,
            apply_filters(
                'aam_rest_route_args_filter', $args, $route, self::API_NAMESPACE
            )
        );
    }

}