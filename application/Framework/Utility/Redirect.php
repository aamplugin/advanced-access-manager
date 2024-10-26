<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM framework utilities
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_Utility_Redirect
{

    /**
     * Do redirect
     *
     * @param array $redirect
     *
     * @return void
     *
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function do_redirect(array $redirect)
    {
        // Determine redirect HTTP status code and use it if applicable for given
        // redirect type
        if (!empty($redirect['http_status_code'])) {
            $status_code = $redirect['http_status_code'];
        } else {
            $status_code = null;
        }

        if (in_array($redirect['type'], [
            'login_redirect',
            'page_redirect',
            'url_redirect',
            'trigger_callback'
        ], true)) {
            wp_safe_redirect(
                self::to_redirect_url($redirect, '/'),
                $status_code ? $status_code : 302
            );
        } elseif ($redirect['type'] === 'custom_message') {
            wp_die(
                $redirect['message'],
                '',
                apply_filters('aam_wp_die_args_filter', [
                    'exit'     => true,
                    'response' => $status_code ? $status_code : 401
                ])
            );
        } else {
            do_action('aam_deny_access_action', $redirect);
        }

        exit;
    }

    /**
     * Handle the access denied redirect
     *
     * This method will either show a generic "Access Denied" message through WP core
     * function wp_die or handle the actual redirect defined with the "Access Denied
     * Redirect" service (if enabled)
     *
     * @param string $message
     * @param string $title
     * @param int    $status_code
     *
     * @return void
     */
    public static function do_access_denied_redirect()
    {
        $handler = apply_filters('aam_access_denied_redirect_handler_filter', null);

        if (is_null($handler)) {
            wp_die(
                __('The access is denied.', AAM_KEY),
                __('Access Denied', AAM_KEY),
                apply_filters('aam_wp_die_args_filter', [
                    'exit'     => true,
                    'response' => 401
                ])
            );
        } else {
            call_user_func($handler);
        }

        exit;
    }

    /**
     * Convert redirect rule to URL
     *
     * @param array  $redirect
     * @param string $default
     *
     * @return string
     *
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function to_redirect_url($redirect, $default = '/')
    {
        $result = null;

        if ($redirect['type'] === 'login_redirect') {
            $result = add_query_arg(
                [ 'reason' => 'restricted' ],
                wp_login_url($_SERVER['REQUEST_URI'])
            );
        } elseif ($redirect['type'] === 'page_redirect') {
            $result = get_page_link($redirect['redirect_page_id']);
        } elseif ($redirect['type'] === 'url_redirect') {
            $result = AAM_Framework_Utility_Misc::sanitize_url(
                $redirect['redirect_url']
            );
        } elseif ($redirect['type'] === 'trigger_callback'
            && is_callable($redirect['callback'])
        ) {
            $result = AAM_Framework_Utility_Misc::sanitize_url(
                call_user_func($redirect['callback'])
            );
        }

        return $result ? $result : $default;
    }

}