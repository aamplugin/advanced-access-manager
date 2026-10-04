<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Denied Redirect service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_AccessDeniedRedirect
{

    use AAM_Service_BaseTrait;

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
        // Register RESTful API endpoints
        AAM_Restful_AccessDeniedRedirect::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Initialize Access Denied Redirect hooks
     *
     * @return void
     * @access protected
     *
     * @version 8.0.0
     */
    protected function initialize_hooks()
    {
        add_action('aam_initialize_ui_action', function () {
            AAM_Backend_Feature_Main_AccessDeniedRedirect::register();
        });

        add_filter('rest_post_dispatch', [ $this, 'filter_rest_denial' ], PHP_INT_MAX, 3);

        add_action('aam_access_denied_redirect_handler_filter', function($handler) {
            if (is_null($handler)) {
                $handler = function() {
                    $service  = AAM::api()->access_denied_redirect();
                    $redirect = $service->get_redirect(
                        AAM::api()->misc->get_current_area()
                    );

                    if ($redirect['type'] === 'default') {
                        if (isset($redirect['http_status_code'])) {
                            $status_code = $redirect['http_status_code'];
                        } else {
                            $status_code = 401;
                        }

                        wp_die(
                            __('The access is denied.', 'advanced-access-manager'),
                            __('Access Denied', 'advanced-access-manager'),
                            apply_filters('aam_wp_die_args_filter', [
                                'exit'     => true,
                                'response' => $status_code
                            ])
                        );
                    } else {
                        AAM::api()->redirect->do_redirect($redirect);
                    }
                };
            }

            return $handler;
        });
    }

    /**
     * Apply the REST denial response settings to AAM access errors.
     *
     * @param WP_REST_Response $response
     * @param WP_REST_Server   $server
     * @param WP_REST_Request  $request
     *
     * @return WP_REST_Response
     * @access public
     * @version 8.0.0
     */
    public function filter_rest_denial($response, $server, $request)
    {
        $data = $response->get_data();

        if (!is_array($data) || !in_array($data['code'] ?? null, [
            'rest_access_denied', 'rest_unauthorized'
        ], true)) {
            return $response;
        }

        $rule = self::_get_api_denial_rule();

        if (($rule['type'] ?? null) === 'trigger_callback') {
            $error = new WP_Error(
                $data['code'],
                $data['message'] ?? '',
                isset($data['data']) && is_array($data['data'])
                    ? $data['data'] : [ 'status' => $response->get_status() ]
            );
            $custom = self::_run_api_callback($rule, $error);
            $converted = rest_convert_error_to_response($custom);
            $response->set_status($converted->get_status());
            $response->set_data($converted->get_data());

            return $response;
        }

        $settings = self::_get_api_denial_settings($rule);

        if (!empty($settings['message'])) {
            $data['message'] = $settings['message'];
        }

        if (!empty($settings['http_status_code'])) {
            $status = $settings['http_status_code'];
            $response->set_status($status);
            if (isset($data['data']) && is_array($data['data'])) {
                $data['data']['status'] = $status;
            }
        }

        $response->set_data($data);

        return $response;
    }

    /**
     * Create an Ability or MCP denial using the shared API response.
     *
     * @param string $code    WordPress error code
     * @param string $message Default error message
     * @param array  $data    Optional error data
     *
     * @return WP_Error
     * @access public
     * @static
     * @version 8.0.0
     */
    public static function denied_error($code, $message, array $data = [])
    {
        $rule     = self::_get_api_denial_rule();
        $settings = self::_get_api_denial_settings($rule);
        $error = new WP_Error(
            $code,
            !empty($settings['message']) ? $settings['message'] : $message,
            array_replace(
                [ 'status' => 403 ],
                $data,
                isset($settings['http_status_code'])
                    ? [ 'status' => $settings['http_status_code'] ] : []
            )
        );

        return ($rule['type'] ?? null) === 'trigger_callback'
            ? self::_run_api_callback($rule, $error) : $error;
    }

    /**
     * Read the configured API denial rule without browser redirect behavior.
     *
     * @return array
     * @access private
     * @static
     * @version 8.0.0
     */
    private static function _get_api_denial_rule()
    {
        if (!AAM::api()->config->get('service.access_denied_redirect.enabled', true)) {
            return [];
        }

        $rule = AAM::api()->access_denied_redirect()->get_redirect('api');

        return is_array($rule) && in_array($rule['type'] ?? null, [
            'default', 'custom_message', 'trigger_callback'
        ], true) ? $rule : [];
    }

    /**
     * Extract message and status settings for a standard API denial.
     *
     * @param array $rule API denial rule
     * @return array
     * @access private
     * @static
     * @version 8.0.0
     */
    private static function _get_api_denial_settings(array $rule)
    {
        $settings = [];
        if (($rule['type'] ?? null) === 'custom_message' && !empty($rule['message'])) {
            $settings['message'] = wp_strip_all_tags($rule['message']);
        }
        if (!empty($rule['http_status_code'])) {
            $settings['http_status_code'] = (int) $rule['http_status_code'];
        }

        return $settings;
    }

    /**
     * Let a registered callback replace an API denial with another WP_Error.
     *
     * @param array    $rule  API denial rule
     * @param WP_Error $error Original denial
     *
     * @return WP_Error
     * @access private
     * @static
     * @version 8.0.0
     */
    private static function _run_api_callback(array $rule, WP_Error $error)
    {
        if (!empty($rule['callback']) && is_callable($rule['callback'])) {
            try {
                $result = call_user_func($rule['callback'], $error);

                if ($result instanceof WP_Error) {
                    $data = $result->get_error_data();
                    $status = is_array($data) ? ($data['status'] ?? null) : null;

                    if (!is_int($status) || $status < 400 || $status > 599) {
                        return $error;
                    }

                    return $result;
                }
            } catch (Throwable $e) {
                // Keep the original denial if the callback cannot complete.
            }
        }

        return $error;
    }

}
