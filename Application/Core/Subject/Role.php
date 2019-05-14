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
     * Role name
     * 
     * Fix the bug that is in the way WP_Roles is initialized
     *
     * @var string
     */
    protected $name;

    /**
     * Retrieve Role based on ID
     *
     * @return WP_Role|null
     *
     * @access protected
     */
    protected function retrieveSubject() {
        $wpRoles = AAM_Core_API::getRoles();

        if (isset($wpRoles->roles[$this->getId()])) {
            $role       = $wpRoles->get_role($this->getId());
            $this->name = $wpRoles->roles[$this->getId()]['name'];
        } else {
            $role = null;
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

        $count = count_users();
        $stats = $count['avail_roles'];

        if (empty($stats[$this->getId()])) {
            $roles->remove_role($this->getId());
            $status = true;
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
        $this->getSubject()->remove_cap($capability);
        
        return true;
    }

    /**
     * Check if Subject has capability
     *
     * Keep compatible with WordPress core
     *
     * @param string  $capability
     * @param boolean $grant
     *
     * @return boolean
     *
     * @access public
     */
    public function addCapability($capability, $grant = true) {
        $this->getSubject()->add_cap($capability, $grant);
        
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
     * Check if subject has capability
     * 
     * @param string $cap
     * 
     * @return boolean
     * 
     * @access public
     */
    public function hasCapability($cap) {
        // If capability is the same as role ID, then capability exists
        if ($cap === $this->getId()) {
            $has = true;
        } else {
            $has = $this->getSubject()->has_cap($cap);
        }
        
        // Override by policy if is set
        $manager = AAM::api()->getPolicyManager($this);
        
        if ($manager->isAllowed("Capability:{$cap}") === false) {
            $has = false;
        }
        
        return $has;
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
        return translate_user_role($this->name);
    }
    
    /**
     * 
     * @return type
     */
    public function getMaxLevel() {
        return AAM_Core_API::maxLevel($this->capabilities);
    }
    
}