<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service Login Redirect manager
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_LoginRedirect implements AAM_Framework_Service_Interface
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
        'trigger_callback'
    ];

    /**
     * Get the login redirect
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_redirect()
    {
        try {
            $result = $this->_get_container()->get_preferences();

            if (empty($result)) {
                $result = [ 'type' => 'default' ];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Set the login redirect
     *
     * @param array $redirect Redirect settings
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function set_redirect(array $redirect)
    {
        try {
            // Validating that incoming data is correct and normalize is for storage
            $sanitized = $this->redirect->sanitize_redirect(
                $redirect,
                self::ALLOWED_REDIRECT_TYPES
            );

            if (!$this->_get_container()->set_preferences($sanitized)) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_redirect();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset the redirect rule
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function reset()
    {
        try {
            $result = $this->_get_container()->reset();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if login redirect preferences are customized
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_customized()
    {
        try {
            $result = $this->_get_container()->is_customized();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get login redirect preference container
     *
     * @return AAM_Framework_Preference_LoginRedirect
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_container()
    {
        return $this->_get_access_level()->get_preference(
            AAM_Framework_Type_Preference::LOGIN_REDIRECT
        );
    }

}