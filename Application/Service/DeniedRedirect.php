<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * Access Denied Redirect service
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Service_DeniedRedirect
{

    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.denied-redirect.enabled';

    /**
     * Default wp_die handler
     *
     * @var callback
     *
     * @access private
     * @version 6.0.0
     */
    private $_defaultHandler;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Redirect::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Access Denied Redirect', AAM_KEY),
                    'description' => __('Manage the default access denied redirect when access gets denied for any protected website resource. The service hooks into the WordPress core wp_die function and redirect any frontend or backend denied requests accordingly.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 25);
        }

        // Hook that initialize the AAM UI part of the service
        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Set original wp_die handler
     *
     * @param callback $handler
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function setDefaultHandler($handler)
    {
        $this->_defaultHandler = $handler;
    }

    /**
     * Get original wp_die handler
     *
     * @return callback
     *
     * @access public
     * @version 6.0.0
     */
    public function getDefaultHandler()
    {
        return $this->_defaultHandler;
    }

    /**
     * Initialize Access Denied Redirect hooks
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function initializeHooks()
    {
        add_filter('wp_die_handler', function($handler) {
            $service = AAM_Service_DeniedRedirect::getInstance();
            $service->setDefaultHandler($handler);

            return array($service, 'processDie');
        }, PHP_INT_MAX - 1);
    }

    /**
     * WP Die custom handler
     *
     * @param string $message
     * @param string $title
     * @param array  $args
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function processDie($message, $title = '', $args = array())
    {
        if ($title === 'aam_access_denied') {
            $method = AAM_Core_Request::server('REQUEST_METHOD');
            $isApi  = (defined('REST_REQUEST') && REST_REQUEST);

            if (($method !== 'POST') && !$isApi) {
                if (is_admin()) {
                    $area = 'backend';
                } else {
                    $area = 'frontend';
                }

                $object = AAM::getUser()->getObject('redirect');
                $type   = $object->get("{$area}.redirect.type", 'default');

                AAM_Core_Redirect::execute(
                    $type,
                    array(
                        $type  => $object->get("{$area}.redirect.{$type}"),
                        'args' => $args
                    )
                );
            } else {
                call_user_func($this->getDefaultHandler(), $message, '', $args);
            }
        } else {
            call_user_func($this->getDefaultHandler(), $message, $title, $args);
        }

        if (!empty($args['exit'])) {
            exit; // Halt the execution
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_DeniedRedirect::bootstrap();
}