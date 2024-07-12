<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service 404 Redirect manager
 *
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.12 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.26
 */
class AAM_Framework_Service_NotFoundRedirect
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Array of allowed HTTP status codes
     *
     * @version 6.9.26
     */
    const HTTP_STATUS_CODES = array(
        'default'          => null,
        'page_redirect'    => array('3xx'),
        'url_redirect'     => array('3xx'),
        'login_redirect'   => null,
        'trigger_callback' => array('3xx', '4xx', '5xx')
    );

    /**
     * Array of default HTTP status codes
     *
     * @version 6.9.26
     */
    const HTTP_DEFAULT_STATUS_CODES = array(
        'default'          => null,
        'page_redirect'    => 307,
        'url_redirect'     => 307,
        'login_redirect'   => null,
        'trigger_callback' => 404
    );

    /**
     * Get the 404 redirect
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_redirect($inline_context = null)
    {
        try {
            $resource = $this->_get_resource($inline_context, true);

            $result = $this->_prepare_redirect(
                $resource->get_settings(),
                !$resource->is_overwritten()
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Set the 404 redirect
     *
     * @param array $redirect       Redirect settings
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function set_redirect(array $redirect, $inline_context = null)
    {
        try {
            // Validating that incoming data is correct and normalize is for storage
            $data    = $this->_validate_redirect($redirect);
            $resource = $this->_get_resource($inline_context);

            if (!$resource->set_explicit_settings($data)) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->_prepare_redirect(
                $resource->get_explicit_settings(), false
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset the redirect rule
     *
     * @param array $inline_context Runtime context
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($inline_context = null)
    {
        try {
            if ($this->_get_resource($inline_context)->reset()) {
                $result = $this->get_redirect($inline_context);
            } else {
                throw new RuntimeException('Failed to reset settings');
            }
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
    private function _prepare_redirect($settings, $is_inherited = false)
    {
        $response = [
            'type' => $settings['404.redirect.type']
        ];

        if ($response['type'] === 'page_redirect') {
            $response['redirect_page_id'] = intval(
                $settings['404.redirect.page']
            );
        } elseif ($response['type'] === 'url_redirect') {
            $response['redirect_url'] = $settings['404.redirect.url'];
        } elseif ($response['type'] === 'trigger_callback') {
            $response['callback'] = $settings['404.redirect.callback'];
        }

        $response['is_inherited'] = $is_inherited;

        return $response;
    }

    /**
     * Validate and normalize the incoming redirect data
     *
     * @param array $rule Incoming rule's data
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _validate_redirect(array $rule)
    {
        $result = [
            '404.redirect.type' => $rule['type']
        ];

        if ($rule['type'] === 'page_redirect') {
            if (isset($rule['redirect_page_id'])) {
                $page_id = intval($rule['redirect_page_id']);
            } else {
                $page_id = 0;
            }

            if ($page_id === 0) {
                throw new InvalidArgumentException(
                    'The `redirect_page_id` is required'
                );
            } else {
                $result['404.redirect.page'] = $page_id;
            }
        } elseif ($rule['type'] === 'url_redirect') {
            if (isset($rule['redirect_url'])) {
                $redirect_url = wp_validate_redirect($rule['redirect_url']);
            } else {
                $redirect_url = null;
            }

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid `redirect_url` is required'
                );
            } else {
                $result['404.redirect.url'] = $redirect_url;
            }
        } elseif ($rule['type'] === 'trigger_callback') {
            if (isset($rule['callback']) && is_callable($rule['callback'], true)) {
                $result['404.redirect.callback'] = $rule['callback'];
            } else {
                throw new InvalidArgumentException(
                    'The valid `callback` is required'
                );
            }
        }

        return $result;
    }

    /**
     * Get object
     *
     * @param array $inline_context
     *
     * @return AAM_Framework_Resource_NotFoundRedirect
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource($inline_context)
    {
        return $this->_get_access_level($inline_context)->get_resource(
            AAM_Framework_Type_Resource::NOT_FOUND_REDIRECT
        );
    }

}