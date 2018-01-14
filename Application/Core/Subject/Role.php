<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Role subject
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Subject_Role extends AAM_Core_Subject {

    /**
     * Subject UID: ROLE
     */
    const UID = 'role';

    /**
     * Retrieve Role based on ID
     *
     * @return WP_Role|null
     *
     * @access protected
     */
    protected function retrieveSubject() {
        $roles = AAM_Core_API::getRoles();
        $role = $roles->get_role($this->getId());

        if (!is_null($role) && isset($role->capabilities)) {
            //add role capability as role id, weird WordPress behavior
            //example is administrator capability
            $role->capabilities[$this->getId()] = true;
        }

        return $role;
    }

    /**
     * Delete User Role 
     *
     * @return boolean
     *
     * @access public
     */
    public function delete() {
        $status = false;
        $roles = AAM_Core_API::getRoles();

        if ($this->getId() !== 'administrator') {
            $count = count_users();
            $stats = $count['avail_roles'];

            if (empty($stats[$this->getId()])) {
                $roles->remove_role($this->getId());
                $status = true;
            }
        }

        return $status;
    }

    /**
     * Update role name
     * 
     * @param string $name
     * 
     * @return boolean
     * 
     * @access public
     */
    public function update($name) {
        $roles = AAM_Core_API::getRoles();
        if ($name) {
            $roles->roles[$this->getId()]['name'] = $name;
            $status = AAM_Core_API::updateOption($roles->role_key, $roles->roles);
        } else {
            $status = false;
        }

        return $status;
    }

    /**
     * Remove Capability
     *
     * @param string  $capability
     *
     * @return boolean
     *
     * @access public
     */
    public function removeCapability($capability) {
        $this->getSubject()->add_cap($capability, false);
        
        return true;
    }

    /**
     * Check if Subject has capability
     *
     * Keep compatible with WordPress core
     *
     * @param string $capability
     *
     * @return boolean
     *
     * @access public
     */
    public function addCapability($capability) {
        $this->getSubject()->add_cap($capability, true);
        
        return true;
    }

    /**
     * Get role's capabilities
     * 
     * @return array
     * 
     * @access public
     */
    public function getCapabilities() {
        return $this->getSubject()->capabilities;
    }

    /**
     *
     * @param type $value
     * @param type $object
     * @param type $object_id
     * @return type
     */
    public function updateOption($value, $object, $object_id = 0) {
        return AAM_Core_API::updateOption(
                        $this->getOptionName($object, $object_id), $value
        );
    }

    /**
     *
     * @param type $object
     * @param type $object_id
     * @param type $default
     * @return type
     */
    public function readOption($object, $object_id = 0, $default = null) {
        return AAM_Core_API::getOption(
                        $this->getOptionName($object, $object_id), $default
        );
    }

    /**
     *
     * @param type $object
     * @param type $id
     * @return string
     */
    public function getOptionName($object, $id) {
        $name = "aam_{$object}" . ($id ? "_{$id}_" : '_');
        $name .= self::UID . '_' . $this->getId();

        return $name;
    }

    /**
     *
     * @return type
     */
    public function getUID() {
        return self::UID;
    }

    /**
     * @inheritdoc
     */
    public function getParent() {
        return apply_filters(
                'aam-parent-role-filter', 
                AAM_Core_Subject_Default::getInstance(), 
                $this
        );
    }
    
    /**
     * 
     * @return type
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * 
     * @return type
     */
    public function getMaxLevel() {
        return AAM_Core_API::maxLevel($this->capabilities);
    }
    
    /**
     * 
     * @return boolean
     */
    public function isRole() {
        return true;
    }
    
}