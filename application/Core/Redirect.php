<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Core AAM redirect handler
 *
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.8.5  https://github.com/aamplugin/advanced-access-manager/issues/214
 * @since 6.4.3  https://github.com/aamplugin/advanced-access-manager/issues/94
 * @since 6.0.5  Fixed bug where URL redirect was incorrectly validating destination
 *               URL
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.26
 */
class AAM_Core_Redirect
{

    /**
     * Collection of redirect types
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected static $redirectTypes = array(
        'login'    => __CLASS__ . '::doLoginRedirect',
        'page'     => __CLASS__ . '::doPageRedirect',
        'message'  => __CLASS__ . '::printMessage',
        'default'  => __CLASS__ . '::printMessage',
        'url'      => __CLASS__ . '::doUrlRedirect',
        'callback' => __CLASS__ . '::triggerCallback'
    );

    /**
     * Execute redirect
     *
     * @param string  $type
     * @param array   $metadata
     * @param boolean $halt
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function execute($type, $metadata, $halt = false)
    {
        if (array_key_exists($type, self::$redirectTypes)) {
            call_user_func(self::$redirectTypes[$type], $metadata);
        }

        // Halt the execution. Redirect should carry user away if this is not
        // a CLI execution (e.g. Unit Test)
        if (php_sapi_name() !== 'cli' && ($halt === true)) {
            exit;
        }
    }

    /**
     * Display WP Die message
     *
     * @param array $meta
     *
     * @return void
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.26
     */
    public static function printMessage($meta)
    {
        $title   = !empty($meta['title']) ? $meta['title'] : __('Access Denied', AAM_KEY);
        $message = !empty($meta['message']) ? $meta['message'] : $title;

        wp_die($message, $title, array(
            'exit'     => defined('AAM_UNITTEST_RUNNING') ? false : true,
            'response' => isset($meta['status']) ? $meta['status'] : 401
        ));
    }

    /**
     * Redirect to the login page
     *
     * @return void
     *
     * @since 6.8.5 https://github.com/aamplugin/advanced-access-manager/issues/214
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.8.5
     */
    public static function doLoginRedirect()
    {
        wp_safe_redirect(add_query_arg(
            array('reason' => 'restricted'),
            wp_login_url(AAM_Core_Request::server('REQUEST_URI'))
        ));

        // Halt the execution. Redirect should carry user away if this is not
        // a CLI execution (e.g. Unit Test)
        if (php_sapi_name() !== 'cli') {
            exit;
        }
    }

    /**
     * Redirect to the existing page
     *
     * @param array $meta
     *
     * @return void
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.8.5  https://github.com/aamplugin/advanced-access-manager/issues/214
     * @since 6.4.3  https://github.com/aamplugin/advanced-access-manager/issues/94
     * @since 6.1.1  Defining default redirect code `307` if none provided
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.26
     */
    public static function doPageRedirect($meta)
    {
        $current = AAM_Core_API::getCurrentPost();
        $dest    = isset($meta['page']) ? $meta['page'] : null;
        $code    = !empty($meta['status']) ? $meta['status'] : 307;

        if (!empty($dest) && (empty($current) || ($current->ID !== intval($dest)))) {
            wp_safe_redirect(get_page_link($dest), $code);

            // Halt the execution. Redirect should carry user away if this is not
            // a CLI execution (e.g. Unit Test)
            if (php_sapi_name() !== 'cli') {
                exit;
            }
        }
    }

    /**
     * Redirect safely to any URL
     *
     * @param array $meta
     *
     * @return void
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.8.5  https://github.com/aamplugin/advanced-access-manager/issues/214
     * @since 6.4.3  https://github.com/aamplugin/advanced-access-manager/issues/94
     * @since 6.0.5  Fixed bug where destination URL was not properly checked against
     *               current page URI
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.26
     */
    public static function doUrlRedirect($meta)
    {
        $dest = isset($meta['url']) ? $meta['url'] : null;
        $code = !empty($meta['status']) ? $meta['status'] : 307;

        if ($dest !== AAM_Core_Request::server('REQUEST_URI')) {
            wp_safe_redirect($dest, $code);

            // Halt the execution. Redirect should carry user away if this is not
            // a CLI execution (e.g. Unit Test)
            if (php_sapi_name() !== 'cli') {
                exit;
            }
        }
    }

    /**
     * Trigger callback function that will handle redirect
     *
     * @param array $meta
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function triggerCallback($meta)
    {
        if (is_callable($meta['callback'])) {
            call_user_func($meta['callback']);
        }
    }

}