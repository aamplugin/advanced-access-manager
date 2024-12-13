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
 * @package AAM
 * @version 7.0.0
 */
trait AAM_Core_Contract_ServiceTrait
{

    /**
     * Single instance of itself
     *
     * @var object
     *
     * @access protected
     * @version 7.0.0
     */
    protected static $instance = null;

    /**
     * Register action hook
     *
     * @param string   $name
     * @param callable $cb
     * @param integer  $priority
     * @param integer  $params
     * @param boolean  $exclude_super_admins
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _register_action(
        $name, $cb, $priority = 10, $params = 0, $exclude_super_admins = true
    ) {
        if ($exclude_super_admins) {
            add_action($name, function(...$args) use ($cb) {
                if (!AAM::api()->misc->is_super_admin()) {
                    call_user_func_array($cb, $args);
                }
            }, $priority, $params);
        } else {
            add_action($name, $cb, $priority, $params);
        }
    }

    /**
     * Register filter hook
     *
     * @param string   $name
     * @param callable $cb
     * @param integer  $priority
     * @param integer  $params
     * @param boolean  $exclude_super_admins
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _register_filter(
        $name, $cb, $priority = 10, $params = 0, $exclude_super_admins = true
    ) {
        if ($exclude_super_admins) {
            add_filter($name, function(...$args) use ($cb) {
                if (!AAM::api()->misc->is_super_admin()) {
                    $result = call_user_func_array($cb, $args);
                } else {
                    $result = isset($args[0]) ? $args[0] : null;
                }

                return $result;
            }, $priority, $params);
        } else {
            add_filter($name, $cb, $priority, $params);
        }
    }

    /**
     * Bootstrap the service
     *
     * @return object
     *
     * @param boolean $reload
     *
     * @access public
     * @version 7.0.0
     */
    public static function bootstrap($reload = false)
    {
        if (is_null(self::$instance) || $reload) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Get single instance of itself
     *
     * @return object
     *
     * @param boolean $reload
     *
     * @access public
     * @version 7.0.0
     */
    public static function get_instance($reload = false)
    {
        return self::bootstrap($reload);
    }

}