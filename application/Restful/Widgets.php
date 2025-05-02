<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Widgets service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_Widgets
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_widgets'
    ];

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
            // Get the list of all widgets grouped by screen
            $this->_register_route('/widgets', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_items'),
                'args'     => array(
                    'area' => array(
                        'description' => 'Website area',
                        'type'        => 'string',
                        'enum'        => [ 'dashboard', 'frontend' ]
                    )
                )
            ], self::PERMISSIONS);

            // Get a widget
            $this->_register_route('/widget/(?P<slug>[\w]+)', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_item'),
                'args'     => array(
                    'slug' => array(
                        'description' => 'Widget unique slug',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ], self::PERMISSIONS);

            // Update a widget's permission
            $this->_register_route('/widget/(?P<slug>[\w]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_item_permission'),
                'args'     => array(
                    'slug' => array(
                        'description' => 'Widget unique slug',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'effect' => array(
                        'description' => 'Either metabox is restricted or not',
                        'type'        => 'string',
                        'default'     => 'deny',
                        'enum'        => [ 'allow', 'deny' ]
                    )
                )
            ], self::PERMISSIONS);

            // Delete a widget's permission
            $this->_register_route('/widget/(?P<slug>[\w]+)', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_item_permission'),
                'args'     => array(
                    'slug' => array(
                        'description' => 'Widget unique slug',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ], self::PERMISSIONS);

            // Reset all or specific screen permissions
            $this->_register_route('/widgets', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_permissions'),
                'args'     => array(
                    'area' => array(
                        'description' => 'Website area',
                        'type'        => 'string',
                        'enum'        => [ 'dashboard', 'frontend' ]
                    )
                )
            ], self::PERMISSIONS);
        });
    }

    /**
     * Get a list of widgets grouped by website area
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_items(WP_REST_Request $request)
    {
        try {
            $area   = $request->get_param('area');
            $result = AAM_Service_Widgets::get_instance()->get_widget_list(
                $this->_get_service($request)
            );

            if (!empty($area)) {
                $result = array_values(
                    array_filter($result, function($c) use ($area) {
                        return $c['area'] === $area;
                    })
                );
            }

            return $result;
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a widget by ID
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_item(WP_REST_Request $request)
    {
        try {
            $result = $this->_get_widget_by_slug(
                $request->get_param('slug'),
                $this->_get_service($request)
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update widget permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function update_item_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $slug    = $request->get_param('slug');

            if ($request->get_param('effect') === 'allow') {
                $service->allow($slug);
            } else {
                $service->deny($slug);
            }

            $result = $this->_get_widget_by_slug($slug, $service);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete widget permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_item_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [
                'success' => $service->reset($request->get_param('slug'))
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [ 'success' => $service->reset() ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Widgets
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->widgets(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }

    /**
     * Get a widget by slug
     *
     * @param string                        $slug
     * @param AAM_Framework_Service_Widgets $service
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_widget_by_slug($slug, $service)
    {
        $match = array_filter(
            AAM_Service_Widgets::get_instance()->get_widget_list($service),
            function($w) use ($slug) { return $w['slug'] === $slug; }
        );

        if (empty($match)) {
            throw new OutOfRangeException(sprintf(
                'Widget with slug %s does not exist or is not indexed', esc_js($slug)
            ));
        } else {
            $result = array_shift($match);
        }

        return $result;
    }

}