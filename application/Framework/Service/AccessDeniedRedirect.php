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
 * @package AAM
 * @version 7.0.0
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
    const ALLOWED_AREAS = [
        'frontend',
        'backend',
        'api'
    ];

    /**
     * Get the access denied redirect
     *
     * @param string $area
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_redirect($area)
    {
        try {
            if (!in_array($area, self::ALLOWED_AREAS, true)) {
                throw new InvalidArgumentException('Invalid area argument');
            }

            $preferences = $this->_get_container()->get_preferences();

            if (!empty($preferences[$area])) {
                $result = $preferences[$area];
            } else {
                $result = [ 'type' => 'default' ];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Set the access denied redirect
     *
     * Note! This method does not validate incoming redirect model. Valid incoming
     * data:
     *
     * {
     *    "type": "string",
     *    "page_slug": "string",
     *    "page_id": "numeric",
     *    "url": "string",
     *    "callback": "string",
     *    "message": "string",
     *    "http_status_code": "numeric"
     * }
     *
     * @param string $area     Redirect area: frontend, backend or api
     * @param array  $redirect Redirect settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function set_redirect($area, array $redirect)
    {
        try {
            if (!in_array($area, self::ALLOWED_AREAS, true)) {
                throw new InvalidArgumentException('The area argument is invalid');
            }

            // Sanitize the incoming redirect data
            $sanitized = AAM_Framework_Manager::_()->redirect->sanitize_redirect(
                $redirect,
                self::ALLOWED_REDIRECT_TYPES
            );

            if (!$this->_get_container()->set_preference($area, $sanitized)) {
                throw new RuntimeException('Failed to persist settings');
            } else {
                $result = $this->get_redirect($area);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset the redirect rule
     *
     * @param string $area
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($area = null)
    {
        try {
            $container = $this->_get_container();

            if (empty($area)) {
                $result = $container->reset();
            } else {
                $preferences = $container->get_preferences(true);

                if (array_key_exists($area, $preferences)) {
                    unset($preferences[$area]);

                    $result = $container->set_preferences($preferences);
                } else {
                    $result = true;
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if access denied redirect preferences are customized
     *
     * @return bool
     *
     * @access public
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
     * Get access denied preference container
     *
     * @return AAM_Framework_Preference_Interface
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_container()
    {
        return $this->_get_access_level()->get_preference(
            AAM_Framework_Type_Preference::ACCESS_DENIED_REDIRECT
        );
    }

}