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
class AAM_Framework_Utility_Redirect implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Do redirect
     *
     * @param array $redirect
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function do_redirect(array $redirect)
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
                $this->to_redirect_url($redirect, '/'),
                $status_code ? $status_code : 307
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
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function do_access_denied_redirect()
    {
        $handler = apply_filters('aam_access_denied_redirect_handler_filter', null);

        if (is_null($handler)) {
            wp_die(
                __('The access is denied.', 'advanced-access-manager'),
                __('Access Denied', 'advanced-access-manager'),
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
     * @param string $default  [Optional]
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function to_redirect_url($redirect, $default = '/')
    {
        $result = null;

        if ($redirect['type'] === 'login_redirect') {
            $result = add_query_arg([ 'reason' => 'restricted' ], wp_login_url(
                AAM_Framework_Manager::_()->misc->get($_SERVER, 'REQUEST_URI')
            ));
        } elseif ($redirect['type'] === 'page_redirect') {
            if (!empty($redirect['redirect_page_id'])) {
                $result = get_page_link($redirect['redirect_page_id']);
            } elseif (!empty($redirect['redirect_page_slug'])) {
                $result = get_page_link(
                    get_page_by_path($redirect['redirect_page_slug'])
                );
            }
        } elseif ($redirect['type'] === 'url_redirect') {
            $result = AAM_Framework_Manager::_()->misc->sanitize_url(
                $redirect['redirect_url']
            );
        } elseif ($redirect['type'] === 'trigger_callback'
            && is_callable($redirect['callback'])
        ) {
            $result = AAM_Framework_Manager::_()->misc->sanitize_url(
                call_user_func($redirect['callback'])
            );
        }

        return $result ? $result : $default;
    }

    /**
     * Sanitize the redirect data
     *
     * @param array $redirect
     * @param array $allowed_types [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function sanitize_redirect(array $redirect, $allowed_types = [])
    {
        // First, let's validate tha the rule type is correct
        if (!in_array($redirect['type'], $allowed_types, true)) {
            throw new InvalidArgumentException('The redirect type is not valid');
        }

        $result = [ 'type' => $redirect['type'] ];

        if ($redirect['type'] === 'custom_message') {
            $message = wp_kses_post($redirect['message']);

            if (empty($message)) {
                throw new InvalidArgumentException('The custom message is required');
            } else {
                $result['message'] = $message;
            }
        } elseif ($redirect['type'] === 'page_redirect') {
            if (array_key_exists('redirect_page_id', $redirect)) {
                $attribute = 'redirect_page_id';
                $value     = intval($redirect['redirect_page_id']);
            } elseif(array_key_exists('redirect_page_slug', $redirect)) {
                $attribute = 'redirect_page_slug';
                $value     = trim($redirect['redirect_page_slug']);
            } else {
                throw new InvalidArgumentException(
                    'Redirected page ID or slug is not provided'
                );
            }

            $result[$attribute] = $value;
        } elseif ($redirect['type'] === 'url_redirect') {
            $redirect_url = AAM_Framework_Manager::_()->misc->sanitize_url(
                $redirect['redirect_url']
            );

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid redirect URL is required'
                );
            } else {
                $result['redirect_url'] = $redirect_url;
            }
        } elseif ($redirect['type'] === 'trigger_callback') {
            if (!is_callable($redirect['callback'], true)) {
                throw new InvalidArgumentException(
                    'The valid callback is required'
                );
            } else {
                $result['callback'] = $redirect['callback'];
            }
        }

        if (!empty($redirect['http_status_code'])) {
            $type  = $redirect['type'];
            $code  = intval($redirect['http_status_code']);
            $valid = false;

            if (in_array($type, ['default', 'custom_message'], true)) {
                $valid = ($code >= 400 && $code <= 599);
            } elseif (in_array($type, ['page_redirect', 'url_redirect'], true)) {
                $valid = $code >= 300 && $code <= 399;
            } elseif ($type === 'trigger_callback') {
                $valid = $code >= 300 && $code <= 599;
            }

            if ($valid) {
                $result['http_status_code'] = $code;
            } else {
                throw new InvalidArgumentException(
                    'The HTTP status code is not valid for given redirect type'
                );
            }
        }

        return $result;
    }

}