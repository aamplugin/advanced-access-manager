<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Reusable elements for each service
 *
 * @since 6.4.0 Enhancement https://github.com/aamplugin/advanced-access-manager/issues/71
 * @since 6.0.0 Initial implementation of the service
 *
 * @package AAM
 * @version 6.4.0
 */
trait AAM_Core_Contract_ServiceTrait
{

    /**
     * Single instance of itself
     *
     * @var object
     *
     * @access protected
     * @version 6.0.0
     */
    protected static $instance = null;

    /**
     * Register service to be fetched
     *
     * @return null|object
     *
     * @access protected
     * @version 6.4.0
     */
    protected function registerService()
    {
        add_filter('aam_get_service_filter', function($service, $alias) {
            if (empty($service) && ($alias === self::SERVICE_ALIAS)) {
                $service = $this;
            }

            return $service;
        }, 10, 2);
    }

    /**
     * Bootstrap the service
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
    }

    /**
     * Get single instance of itself
     *
     * @return object
     *
     * @access public
     * @version 6.0.0
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::bootstrap();
        }

        return self::$instance;
    }

}