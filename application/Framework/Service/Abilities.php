<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Ability service
 * 
 * @version 8.0.0
 */
class AAM_Framework_Service_Abilities
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Deny access to the ability
     * 
     * @param string $name
     * 
     * @return bool|WP_Error
     * @access public
     *
     * @version 8.0.0
     */
    public function deny($name)
    {
        try {
            $result = $this->_update_item_permission(
                $this->_normalize_resource_identifier($name), true
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow access to the ability
     * 
     * @param string $name
     * 
     * @return bool|WP_Error
     * @access public
     *
     * @version 8.0.0
     */
    public function allow($name)
    {
        try {
            $result = $this->_update_item_permission(
                $this->_normalize_resource_identifier($name), false
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset access controls
     * 
     * @param string $name
     * 
     * @return bool
     * @access public
     * 
     * @version 8.0.0
     */
    public function reset($name = null)
    {
        try {
            $resource = $this->_get_resource();

            if (is_null($name)) {
                $result = $resource->reset();
            } else {
                $result = $resource->remove_permission(
                    $this->_normalize_resource_identifier($name), 'access'
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if ability is restricted
     *
     * @param string $name
     *
     * @return boolean
     * @access public
     *
     * @version 8.0.0
     */
    public function is_denied($name)
    {
        try {
            $result     = null;
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($name);
            $permission = $resource->get_permission($identifier, 'access');

            // Step #1. Determine if ability is explicitly restricted
            if (!empty($permission)) {
                $result = $permission['effect'] !== 'allow';
            }

            // Step #2. Allow third-party implementation to influence the decision
            $result = apply_filters(
                'aam_ability_is_denied_filter',
                $result,
                $identifier,
                $resource
            );

            // Prepare the final answer
            $result = is_bool($result) ? $result : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if ability is allowed
     *
     * @param mixed $name
     *
     * @return boolean
     * @access public
     *
     * @version 8.0.0
     */
    public function is_allowed($name)
    {
        $result = $this->is_denied($name);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Update existing item permission
     *
     * @param string $id
     * @param bool   $is_denied [Optional]
     *
     * @return array
     * @access private
     *
     * @version 8.0.0
     */
    private function _update_item_permission($id, $is_denied = true)
    {
        return $this->_get_resource()->set_permission(
            $id,
            'access',
            $is_denied
        );
    }

    /**
     * Convert ability name into a resource identifier
     *
     * @param string $name
     *
     * @return object
     * @access private
     *
     * @version 8.0.0
     */
    private function _normalize_resource_identifier($name)
    {
        $parts = explode('/', $name);

        return (object) [
            'name'      => isset($parts[1]) ? $parts[1] : $parts[0],
            'namespace' => isset($parts[1]) ? $parts[0] : null
       ];
    }

    /**
     * Get resource
     *
     * @return AAM_Framework_Resource_Ability
     * @access private
     *
     * @version 8.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::ABILITY
        );
    }

}