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
 * @version 7.0.0
 */
class AAM_Restful_SecurityAudit
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_trigger_audit'
    ];

    /**
     * Single instance of itself
     *
     * @var AAM_Restful_SecurityAudit
     *
     * @access private
     * @static
     *
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Create new support message
            $this->_register_route('/audit', [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'run_step'),
                'args'     => array(
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
            ], self::PERMISSIONS, false);

            // Get complete report
            $this->_register_route('/audit/report', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'generate_report')
            ], self::PERMISSIONS, false);

            // Share complete report
            $this->_register_route('/audit/summary', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'prepare_summary')
            ], self::PERMISSIONS, false);
        });
    }

    /**
     * Run the current step
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function run_step(WP_REST_Request $request)
    {
        try {
            $response = AAM_Service_SecurityAudit::get_instance()->execute(
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
     * @access public
     *
     * @version 7.0.0
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
     * Prepare executive audit summary
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function prepare_summary()
    {
        $repository = AAM_Addon_Repository::get_instance();

        // Step #1. Prepare the audit report
        $payload = json_encode([
            'license'  => $repository->get_premium_license_key(),
            'instance' => wp_hash('aam', 'nonce'),
            'report'   => $this->_generate_shareable_results()
        ]);

        // Step #2. Upload the report
        $result = wp_remote_post('https://api.aamportal.com/audit/summary', [
            'body'        => $payload,
            'timeout'     => 30,
            'data_format' => 'body',
            'headers'     => [
                'Content-Type' => 'application/json'
            ]
        ]);

        // print_r($result);

        // Get HTTP code
        $http_code = wp_remote_retrieve_response_code($result);

        // Check for errors in the response. This is hard error handling
        if (is_wp_error($result)) {
            throw new RuntimeException(esc_js($result->get_error_message()));
        }

        // Get the response from the server
        $result = json_decode(wp_remote_retrieve_body($result), true);

        // Store the copy of the executive summary, but only if success
        if ($http_code === 200) {
            AAM::api()->db->write(
                AAM_Service_SecurityAudit::DB_SUMMARY_OPTION,
                $result,
                false
            );
        }

        // Prepare the response to UI
        $response = [
            'status'=> $http_code == 200 ? 'success' : 'failure'
        ];

        if ($http_code === 200) {
            $response['results'] = $result;
        } elseif (!empty($result['reason'])) {
            $response['reason'] = $result['reason'];
        } else {
            $response['reason'] = __(
                'Hm, something went wrong. Please try again later.',
                'advanced-access-manager'
            );
        }

        return rest_ensure_response($response);
    }

    /**
     * Generate CSV version of the report
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _generate_csv_report()
    {
        $service = AAM_Service_SecurityAudit::get_instance();

        // Open output buffer for CSV content & set header
        $report = fopen('php://output', 'w');
        fputcsv($report, [ 'Issue', 'Type', 'Category' ]);

        $data   = $service->read();
        $checks = $service->get_steps();

        foreach($data as $check_id => $check_result) {
            $check    = $checks[$check_id];
            $executor = $checks[$check_id]['executor'];

            if (!empty($check_result['issues'])) {
                foreach($check_result['issues'] as $issue) {
                    fputcsv($report, [
                        call_user_func("{$executor}::issue_to_message", $issue),
                        $issue['type'],
                        isset($check['category']) ? $check['category'] : $check_id
                    ]);
                }
            }
        }

        // Close output buffer
        fclose($report);
    }

    /**
     * Prepare shareable audit results
     *
     * Aggregating data and removing unnecessary information
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _generate_shareable_results()
    {
        $results = [];
        $service = AAM_Service_SecurityAudit::get_instance();
        $checks  = $service->get_steps();

        foreach($service->read() as $check => $data) {
            if (!empty($data['is_completed']) && !empty($data['issues'])) {
                $executor  = $checks[$check]['executor'];
                $shareable = call_user_func(
                    "{$executor}::issues_to_shareable", $data
                );

                if (!empty($shareable)) {
                    $results[$check] = $shareable;
                }
            }
        }

        return [
            'results' => $results,
            'plugins' => $this->_get_plugin_list()
        ];
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
                'plugin_path' => $plugin_path
            ];
        }

        return $result;
    }

    /**
     * Generate JSON version of the report
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _generate_json_report()
    {
        $report  = [];
        $service = AAM_Service_SecurityAudit::get_instance();
        $data    = $service->read();
        $checks  = $service->get_steps();

        foreach($data as $check_id => $check_result) {
            $check = $checks[$check_id];
            $exec  = $checks[$check_id]['executor'];

            if (!empty($check_result['issues'])) {
                foreach($check_result['issues'] as $issue) {
                    $msg = call_user_func("{$exec}::issue_to_message", $issue);
                    $cat = isset($check['category']) ? $check['category'] : $check_id;

                    array_push($report, [
                        'issue'    => $msg,
                        'type'     => $issue['type'],
                        'category' => $cat
                    ]);
                }
            }
        }

        return $report;
    }

}