<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service to manager subjects
 *
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/388
 * @since 6.9.9  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.31
 */
class AAM_Framework_Service_Subject
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Cached instantiated subjects
     *
     * @var array
     *
     * @access private
     * @version 6.9.31
     */
    private $_cache = [];

    /**
     * Determine subject based on access level and ID
     *
     * @param string $access_level
     * @param mixed  $id
     *
     * @return AAM_Core_Subject
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/388
     * @since 6.9.9  Initial implementation of the method
     *
     * @access public
     * @version 6.9.31
     */
    public function get($access_level, $id = null, $reload = false)
    {
        try {
            if ($access_level === AAM_Core_Subject_Role::UID) {
                $cache_key = "role:{$id}";

                if (!isset($this->_cache[$cache_key]) || $reload) {
                    $this->_cache[$cache_key] = $this->get_role($id);
                }
            } elseif ($access_level === AAM_Core_Subject_User::UID) {
                $cache_key = "user:{$id}";

                if (!isset($this->_cache[$cache_key]) || $reload) {
                    $this->_cache[$cache_key] = $this->get_user(intval($id));
                }
            } elseif ($access_level === AAM_Core_Subject_Visitor::UID) {
                $cache_key = "visitor";

                if (!isset($this->_cache[$cache_key]) || $reload) {
                    $this->_cache[$cache_key] = $this->get_visitor();
                }
            } elseif ($access_level === AAM_Core_Subject_Default::UID) {
                $cache_key = "default";

                if (!isset($this->_cache[$cache_key]) || $reload) {
                    $this->_cache[$cache_key] = $this->get_default();
                }
            } else {
                throw new BadMethodCallException('Unsupported access_level');
            }

            $result = $this->_cache[$cache_key];
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get role subject
     *
     * @param string $id Role ID (aka slug)
     *
     * @return AAM_Core_Subject_Role
     *
     * @access public
     * @version 6.9.9
     */
    public function get_role($id)
    {
        try {
            $result = new AAM_Core_Subject_Role($id);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get user subject
     *
     * @param int $id User ID
     *
     * @return AAM_Core_Subject_User
     *
     * @access public
     * @version 6.9.9
     */
    public function get_user($id)
    {
        try {
            $result = new AAM_Core_Subject_User($id);
            $result->initialize();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get visitor subject
     *
     * @return AAM_Core_Subject_Visitor
     *
     * @access public
     * @version 6.9.9
     */
    public function get_visitor()
    {
        try {
            $result = new AAM_Core_Subject_Visitor();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get default subject
     *
     * @return AAM_Core_Subject_Default
     *
     * @access public
     * @version 6.9.9
     */
    public function get_default()
    {
        try {
            $result = AAM_Core_Subject_Default::getInstance();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

}