<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service Access Denied Redirect manager
 *
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/359
 * @since 6.9.22 https://github.com/aamplugin/advanced-access-manager/issues/346
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/322
 * @since 6.9.14 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
 */
class AAM_Framework_Service_AccessDeniedRedirect
implements
    AAM_Framework_Service_Interface
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * List of allowed rule types
     *
     * @version 7.0.0
     */
    const ALLOWED_REDIRECT_TYPES = [
        'default',
        'custom_message',
        'page_redirect',
        'url_redirect',
        'trigger_callback',
        'login_redirect'
    ];

    /**
     * Allowed redirect areas
     *
     * @version 7.0.0
     */
    const ALLOWED_REDIRECT_AREAS = [
        'frontend',
        'backend'
    ];

    /**
     * Get the access denied redirect
     *
     * @param string $area
     * @param array  $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 6.9.14
     */
    public function get_redirect($area = null, $inline_context = null)
    {
        try {
            $preference  = $this->get_preference(true, $inline_context);
            $redirects = $this->_prepare_redirects(
                $preference->get_settings(),
                !$preference->is_overwritten()
            );

            if (!empty($area)) {
                $result = $redirects[$area];
            } else {
                $result = array_values($redirects);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Set the access denied redirect
     *
     * @param string $area           Redirect area
     * @param array  $incoming_data  Redirect settings
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.14
     */
    public function set_redirect($area, array $incoming_data, $inline_context = null)
    {
        try {
            $preference = $this->get_preference(false, $inline_context);
            $redirect   = $this->_convert_to_redirect($incoming_data);
            $result     = $preference->set_explicit_setting($area, $redirect);

            if (!$result) {
                throw new RuntimeException('Failed to persist settings');
            } else {
                $result = $preference->get_setting($area);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset the redirect rule
     *
     * @param string $area
     * @param array  $inline_context Runtime context
     *
     * @return boolean
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
     * @since 6.9.14 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function reset($area = null, $inline_context = null)
    {
        try {
            $preference = $this->get_preference(false, $inline_context);

            if (empty($area)) {
                $success = $preference->reset();
            } else {
                $settings = $preference->get_explicit_settings();

                if (array_key_exists($area, $settings)) {
                    unset($settings[$area]);
                }

                $success = $preference->set_explicit_settings($settings);
            }

            if ($success) {
                $result = $this->get_redirect($area, $inline_context);
            } else {
                throw new RuntimeException('Failed to reset settings');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get preference resource
     *
     * @param boolean $reload
     * @param array   $inline_context
     *
     * @return AAM_Framework_Resource_AccessDeniedRedirect
     *
     * @access public
     * @version 7.0.0
     */
    public function get_preference($reload = false, $inline_context = null) {
        try {
            $result = $this->_get_access_level($inline_context)->get_preference(
                AAM_Framework_Type_Resource::ACCESS_DENIED_REDIRECT, $reload
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Normalize and prepare the redirect details
     *
     * @param array $settings
     * @param bool  $is_inherited
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_redirects($settings, $is_inherited = false)
    {
        $result = [];

        foreach([ 'frontend', 'backend' ] as $area) {
            if (array_key_exists($area, $settings)) {
                $result[$area] = $this->_prepare_redirect(
                    $settings[$area], $is_inherited
                );
            } else {
                $result[$area] = $this->_prepare_redirect([], $is_inherited);
            }
        }

        return $result;
    }

    /**
     * Prepare an individual redirect
     *
     * @param string $area
     * @param array  $settings
     * @param bool   $is_inherited
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_redirect($settings, $is_inherited)
    {
        return array_merge(
            [ 'type' => 'default' ],
            $settings,
            [ 'is_inherited' => $is_inherited ]
        );
    }

    /**
     * Validate and prepare redirect data
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_to_redirect($data)
    {
        // First, let's validate tha the rule type is correct
        if (!in_array($data['type'], self::ALLOWED_REDIRECT_TYPES, true)) {
            throw new InvalidArgumentException('The valid `type` is required');
        }

        $result = [
            'type' => $data['type']
        ];

        if ($data['type'] === 'custom_message') {
            $message = wp_kses_post($data['message']);

            if (empty($message)) {
                throw new InvalidArgumentException('The `message` is required');
            } else {
                $result['message'] = $message;
            }
        } elseif ($data['type'] === 'page_redirect') {
            $page_id = intval($data['redirect_page_id']);

            if ($page_id === 0) {
                throw new InvalidArgumentException(
                    'The `redirect_page_id` is required'
                );
            } else {
                $result['redirect_page_id'] = $page_id;
            }
        } elseif ($data['type'] === 'url_redirect') {
            $redirect_url = wp_validate_redirect($data['redirect_url']);

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid `redirect_url` is required'
                );
            } else {
                $result['redirect_url'] = $redirect_url;
            }
        } elseif ($data['type'] === 'trigger_callback') {
            if (!is_callable($data['callback'], true)) {
                throw new InvalidArgumentException(
                    'The valid `callback` is required'
                );
            } else {
                $result['callback'] = $data['callback'];
            }
        }

        if (!empty($data['http_status_code'])) {
            $code = intval($data['http_status_code']);

            if ($code >= 300) {
                $result['http_status_code'] = $code;
            }
        }

        return $result;
    }

}