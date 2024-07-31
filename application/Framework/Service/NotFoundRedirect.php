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
implements
    AAM_Framework_Service_Interface
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * List of allowed redirect types
     *
     * @version 7.0.0
     */
    const ALLOWED_REDIRECT_TYPES = [
        'default',
        'page_redirect',
        'url_redirect',
        'trigger_callback',
        'login_redirect'
    ];

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
            $preference = $this->get_preference(true, $inline_context);
            $result     = $this->_prepare_redirect(
                $preference->get_settings(),
                !$preference->is_overwritten()
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Set the 404 redirect
     *
     * @param array $incoming_data  Redirect settings
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function set_redirect(array $incoming_data, $inline_context = null)
    {
        try {
            // Validating that incoming data is correct and normalize is for storage
            $preference = $this->get_preference(false, $inline_context);
            $data       = $this->_convert_to_redirect($incoming_data);

            if (!$preference->set_explicit_settings($data)) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->_prepare_redirect(
                $preference->get_explicit_settings(), false
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
            $this->get_preference(false, $inline_context)->reset();

            $result = $this->get_redirect($inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get Not Found Redirect preference resource
     *
     * @param boolean $reload
     * @param array   $inline_context
     *
     * @return AAM_Framework_Resource_NotFoundRedirect
     *
     * @access public
     * @version 7.0.0
     */
    public function get_preference($reload = false, $inline_context = null)
    {
        try {
            $result = $this->_get_access_level($inline_context)->get_preference(
                AAM_Framework_Type_Resource::NOT_FOUND_REDIRECT, $reload
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
    private function _prepare_redirect($settings, $is_inherited = false)
    {
        return array_merge(
            [ 'type' => 'default' ],
            $settings,
            [ 'is_inherited' => $is_inherited ]
        );
    }

    /**
     * Validate and normalize the incoming redirect data
     *
     * @param array $incoming_data
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_to_redirect(array $incoming_data)
    {
        // First, let's validate tha the rule type is correct
        if (!in_array($incoming_data['type'], self::ALLOWED_REDIRECT_TYPES, true)) {
            throw new InvalidArgumentException('The valid `type` is required');
        }

        $result = [
            'type' => $incoming_data['type']
        ];

        if ($incoming_data['type'] === 'page_redirect') {
            if (isset($incoming_data['redirect_page_id'])) {
                $page_id = intval($incoming_data['redirect_page_id']);
            } else {
                $page_id = 0;
            }

            if ($page_id === 0) {
                throw new InvalidArgumentException(
                    'The `redirect_page_id` is required'
                );
            } else {
                $result['redirect_page_id'] = $page_id;
            }
        } elseif ($incoming_data['type'] === 'url_redirect') {
            if (isset($incoming_data['redirect_url'])) {
                $redirect_url = wp_validate_redirect($incoming_data['redirect_url']);
            } else {
                $redirect_url = null;
            }

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid `redirect_url` is required'
                );
            } else {
                $result['redirect_url'] = $redirect_url;
            }
        } elseif ($incoming_data['type'] === 'trigger_callback') {
            if (isset($incoming_data['callback'])
                && is_callable($incoming_data['callback'], true)
            ) {
                $result['callback'] = $incoming_data['callback'];
            } else {
                throw new InvalidArgumentException(
                    'The valid `callback` is required'
                );
            }
        }

        return $result;
    }

}