<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service Logout Redirect manager
 *
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.12 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.26
 */
class AAM_Framework_Service_LogoutRedirect
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get the logout redirect
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
            $result   = $this->_prepare_redirect(
                $resource->get_settings(),
                !$resource->is_overwritten()
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Set the logout redirect
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
            $resource = $this->_get_resource($inline_context);
            $settings = $resource->convert_to_redirect($redirect);

            if (!$resource->set_explicit_settings($settings)) {
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
        return array_merge(
            [ 'type' => 'default' ],
            $settings,
            [ 'is_inherited' => $is_inherited ]
        );
    }

    /**
     * Get object
     *
     * @param array $inline_context
     *
     * @return AAM_Framework_Resource_LogoutRedirect
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource($inline_context, $reload = false)
    {
        return $this->_get_access_level($inline_context)->get_resource(
            AAM_Framework_Type_Resource::LOGOUT_REDIRECT, null, $reload
        );
    }

}