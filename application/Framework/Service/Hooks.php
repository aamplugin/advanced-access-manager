<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service Hooks manager
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Hooks implements AAM_Framework_Service_Interface
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get all defined hooks
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_hooks()
    {
        try {
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Deny specific hook
     *
     * @param string         $hook
     * @param integer|string $priority [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($hook, $priority = 10)
    {
        try {
            $result = $this->_update_permissions($hook, $priority, 'deny');
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow specific hook
     *
     * @param string         $hook
     * @param integer|string $priority [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($hook, $priority = 10)
    {
        try {
            $result = $this->_update_permissions($hook, $priority, 'allow');
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alter specific hook's return value
     *
     * @param string         $hook
     * @param mixed          $return
     * @param integer|string $priority [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function alter($hook, $return, $priority = 10)
    {
        try {
            $result = $this->_update_permissions($hook, $priority, 'alter', $return);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alter specific hook's return value by merging it with additional array
     *
     * @param string         $hook
     * @param array          $data
     * @param integer|string $priority [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function merge($hook, array $data, $priority = 10)
    {
        try {
            $result = $this->_update_permissions($hook, $priority, 'merge', $data);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Remove all registered callback function for given hook and return given value
     * instead
     *
     * @param string         $hook
     * @param mixed          $value
     * @param integer|string $priority [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function replace($hook, $value, $priority = 10)
    {
        try {
            $result = $this->_update_permissions($hook, $priority, 'replace', $value);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset permissions
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function reset()
    {
        try {
            $result = $this->_get_resource()->reset();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if permissions are customized
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_customized()
    {
        try {
            $result = $this->_get_resource()->is_customized();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Update hook permission
     *
     * @param string     $hook
     * @param string|int $priority
     * @param string     $effect
     * @param mixed      $response
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _update_permissions($hook, $priority, $effect, $response = null)
    {
        $resource   = $this->_get_resource();
        $permission = [
            "{$hook}|{$priority}" => [
                'effect' => strtolower($effect)
            ]
        ];

        if (!is_null($response)) {
            $permission["{$hook}|{$priority}"]['response'] = $response;
        }

        return $resource->set_permissions(array_replace(
            $resource->get_permissions(true),
            $permission
        ));
    }

    /**
     * Get hooks resource
     *
     * @return AAM_Framework_Resource_Hook
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::HOOK
        );
    }

}