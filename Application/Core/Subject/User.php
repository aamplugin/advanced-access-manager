<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * User subject
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Subject_User extends AAM_Core_Subject {

    /**
     * Subject UID: USER
     */
    const UID = 'user';

    /**
     * AAM Capability Key
     *
     * It is very important to have all user capability changes be stored in
     * separate options from the wp_capabilities usermeta cause if AAM is not
     * active as a plugin, it reverts back to the default WordPress settings
     */
    const AAM_CAPKEY = 'aam_capability';
    
    /**
     *
     * @var type 
     */
    protected $parent = null;
    
    /**
     * 
     * @param type $id
     */
    public function __construct($id) {
        parent::__construct($id);
        
        if (get_current_user_id() == $id) {
            //check if user is locked
            if ($this->user_status == 1) {
                wp_logout();
            }

            //check if user's role expired
            $expire = get_user_option('aam-role-expires');
            if ($expire && ($expire <= time())) {
                $this->restoreRoles();
            }
        }
    }
    
    /**
     * Block User
     *
     * @return boolean
     *
     * @access public
     * @global wpdb $wpdb
     */
    public function block() {
        global $wpdb;

        $response = false;
        if (current_user_can('edit_users')) {
            $status = ($this->getSubject()->user_status ? 0 : 1);
            $result = $wpdb->update(
                    $wpdb->users, 
                    array('user_status' => $status), 
                    array('ID' => $this->getId())
            );
            if ($result) {
                $this->getSubject()->user_status = $status;
                clean_user_cache($this->getSubject());
                $response = true;
            }
        }

        return $response;
    }
    
    /**
     * 
     */
    public function restoreRoles() {
        $roles = get_user_option('aam-original-roles');
        
        //remove curren roles
        foreach((array) $this->roles as $role) {
            $this->remove_role($role);
        }
        
        //add original roles
        foreach(($roles ? $roles : array('subscriber')) as $role) {
            $this->add_role($role);
        }
            
        //delete options
        delete_user_option($this->getId(), 'aam-role-expires');
        delete_user_option($this->getId(), 'aam-original-roles');
    }

    /**
     * Retrieve User based on ID
     *
     * @return WP_Role
     *
     * @access protected
     */
    protected function retrieveSubject() {
        $subject = new WP_User($this->getId());

        //retrieve aam capabilities if are not retrieved yet
        $caps = get_user_option(self::AAM_CAPKEY, $this->getId());
        if (is_array($caps)) {
            $caps    = array_merge($subject->caps, $caps);
            $allcaps = array_merge($subject->allcaps, $caps);
            
            //reset the user capabilities
            $subject->allcaps = $allcaps;
            $subject->caps    = $caps;
            
            if (wp_get_current_user()->ID == $subject->ID) {
                wp_get_current_user()->allcaps = $allcaps;
                wp_get_current_user()->caps    = $caps;
            }
        }
        
        return $subject;
    }

    /**
     * Get user capabilities
     * 
     * @return array
     * 
     * @access public
     */
    public function getCapabilities() {
        return $this->getSubject()->allcaps;
    }

    /**
     * Check if user has a capability
     *
     * @param string $capability
     *
     * @return boolean
     *
     * @access public
     */
    public function hasCapability($capability) {
        return user_can($this->getSubject(), $capability);
    }

    /**
     * Add capability
     * 
     * @param string $capability
     *
     * @return boolean
     *
     * @access public
     */
    public function addCapability($capability) {
        return $this->updateCapability($capability, true);
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
        return $this->updateCapability($capability, false);
    }

    /**
     * Update User's Capability Set
     *
     * @param string  $capability
     * @param boolean $grand
     *
     * @return boolean
     *
     * @access public
     */
    public function updateCapability($capability, $grand) {
        //update capability
        $caps = $this->getSubject()->caps;
        $caps[$capability] = $grand;
        
        //save and return the result of operation
        return update_user_option($this->getId(), self::AAM_CAPKEY, $caps);
    }

    /**
     * Undocumented function
     *
     * @param string $object
     * @return void
     */
    public function resetObject($object) {
        if ($object == 'capability') {
            $result = delete_user_option($this->getId(), self::AAM_CAPKEY);
        } else {
            $result = $this->deleteOption($object);
        }

        return $result;
    }
    
    /**
     * Update user's option
     * 
     * @param mixed  $value
     * @param string $object
     * @param string $id
     * 
     * @return boolean
     * 
     * @access public
     */
    public function updateOption($value, $object, $id = 0) {
        return update_user_option(
                $this->getId(), $this->getOptionName($object, $id), $value
        );
    }

    /**
     * Read user's option
     * 
     * @param string $object
     * @param string $id
     *
     * @return mixed
     * 
     * @access public
     */
    public function readOption($object, $id = '') {
        return get_user_option(
                $this->getOptionName($object, $id), $this->getId()
        );
    }
    
    /**
     * Read user's option
     * 
     * @param string $object
     * @param string $id
     *
     * @return mixed
     * 
     * @access public
     */
    public function deleteOption($object, $id = 0) {
        return delete_user_option(
                $this->getId(), $this->getOptionName($object, $id)
        );
    }

    /**
     * @inheritdoc
     */
    public function getParent() {
        if (is_null($this->parent)) {
            //try to get this option from the User's Role
            $roles  = $this->getSubject()->roles;
            //first user role is counted only. AAM does not support multi-roles
            $parent = array_shift($roles);

            //in case of multisite & current user does not belong to the site
            if ($parent) {
                $this->parent = new AAM_Core_Subject_Role($parent);
            } else {
                $this->parent = null;
            }
        }

        return $this->parent;
    }

    /**
     * Prepare option's name
     *
     * @param string     $object
     * @param string|int $id
     *
     * @return string
     *
     * @access public
     */
    public function getOptionName($object, $id) {
        return "aam_{$object}" . ($id ? "_{$id}" : '');
    }
    
    /**
     * Get Subject UID
     *
     * @return string
     *
     * @access public
     */
    public function getUID() {
        return self::UID;
    }
    
    /**
     * 
     * @return type
     */
    public function getName() {
        $display = $this->display_name;
        
        return ($display ? $display : $this->user_nicename);
    }

    /**
     * 
     * @return type
     */
    public function getMaxLevel() {
        return AAM_Core_API::maxLevel($this->allcaps);
    }
    
    /**
     * 
     * @return boolean
     */
    public function isUser() {
        return true;
    }
    
}