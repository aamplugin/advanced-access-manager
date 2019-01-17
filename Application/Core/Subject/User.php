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
    protected $aamCaps = array();
    
    /**
     *
     * @var type 
     */
    protected $parent = null;
    
    /**
     *
     * @var type 
     */
    protected $maxLevel = null;
    
    /**
     * 
     * @param type $id
     */
    public function __construct($id = '') {
        parent::__construct($id);
        
        // Retrieve user capabilities set with AAM
        $aamCaps = get_user_option(self::AAM_CAPKEY, $id);
        
        if (is_array($aamCaps)) {
            $this->aamCaps = $aamCaps;
        }
    }
    
    /**
     * 
     */
    public function validateUserStatus() {
        //check if user is blocked
        if ($this->user_status === 1) {
            wp_logout();
        }
            
        //check if user is expired
        $expired = get_user_meta($this->ID, 'aam_user_expiration', true);
        if (!empty($expired)) {
            $parts   = explode('|', $expired);
            
            // Set time
            // TODO: Remove in Jan 2020
            if (preg_match('/^[\d]{4}-/', $parts[0])) {
                $expires = DateTime::createFromFormat('Y-m-d H:i:s', $parts[0]);
            } else {
                $expires = DateTime::createFromFormat('m/d/Y, H:i O', $parts[0]);
            }
            
            $compare = new DateTime();
            //TODO - PHP Warning:  DateTime::setTimezone(): Can only do this for zones with ID for now in
            @$compare->setTimezone($expires->getTimezone());
            
            if ($expires <= $compare) {
                $this->triggerExpiredUserAction($parts);
            }
        }

        //check if user's role expired
        $roleExpire = get_user_option('aam-role-expires', $this->ID);
        if ($roleExpire && ($roleExpire <= time())) {
            $this->restoreRoles();
        }
        
        //finally check if session tracking is enabled and if so, check if used
        //has to be logged out
        if (AAM::api()->getConfig('core.session.tracking', false)) {
            $ttl = AAM::api()->getConfig(
                    "core.session.user.{$this->ID}.ttl",
                    AAM::api()->getConfig("core.session.user.ttl", null)
            );

            if (!empty($ttl)) {
                $timestamp = get_user_meta(
                    $this->ID, 'aam-authenticated-timestamp', true
                );
                
                if ($timestamp && ($timestamp + intval($ttl) <= time())) {
                    delete_user_meta($this->ID, 'aam-authenticated-timestamp');
                    wp_logout();
                }
            }
        }
    }
    
    /**
     * Expire user
     * 
     * @param array $config
     * 
     * @return void
     * 
     * @access 
     */
    public function triggerExpiredUserAction($config) {
        switch($config[1]) {
            case 'lock':
                $this->block();
                break;
            
            case 'logout':
                wp_logout();
                break;
            
            case 'change-role':
                if (AAM_Core_API::getRoles()->is_role($config[2])) {
                    $this->getSubject()->set_role($config[2]);
                    delete_user_option($this->getSubject()->ID, 'aam_user_expiration');
                }
                break;

            case 'delete':
                require_once(ABSPATH . 'wp-admin/includes/user.php' );
                wp_delete_user(
                    $this->getId(), AAM_Core_Config::get('core.reasign.ownership.user')
                );
                wp_logout();
                break;

            default:
                break;
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

        $status = ($this->getSubject()->user_status ? 0 : 1);
        $result = $wpdb->update(
                $wpdb->users, 
                array('user_status' => $status), 
                array('ID' => $this->getId())
        );
        
        if ($result) {
            $this->getSubject()->user_status = $status;
            clean_user_cache($this->getSubject());
        }

        return $result;
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
        if ($this->getId() === get_current_user_id()) {
            $subject = wp_get_current_user();
        } else {
            $subject = new WP_User($this->getId());
        }
        
        return $subject;
    }
    
    /**
     * 
     */
    public function loadCapabilities() {
        $subject = $this->getSubject();
        
        // Retrieve all capabilities set in Access Policy
        // Load Capabilities from the policy
        $stms = AAM_Core_Policy_Manager::getInstance()->find("/^Capability:/i");

        $policyCaps = array();
        
        foreach($stms as $key => $stm) {
            $chunks = explode(':', $key);
            if (count($chunks) === 2) {
                $policyCaps[$chunks[1]] = ($stm['Effect'] === 'allow' ? 1 : 0);
            }
        }
        
        //reset the user capabilities
        $subject->allcaps = array_merge($subject->allcaps, $policyCaps,  $this->aamCaps);
        $subject->caps    = array_merge($subject->caps, $policyCaps,  $this->aamCaps);
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
        // Priority #1: capability that has been explicitely set
        if (isset($this->aamCaps[$capability])) {
            $result = !empty($this->aamCaps[$capability]);
        } else {
            // Priority #2: capability that has been defined in policy
            // Override by policy if is set
            $stm = AAM::api()->getPolicyManager()->find(
                    "/^Capability:{$capability}$/i", $this
            );
            if (!empty($stm)) {
                $val = end($stm);
                $result = ($val['Effect'] === 'allow' ? 1 : 0);
            } else {
                $result = user_can($this->getSubject(), $capability);
            }
        }
        
        return $result;
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
        if ($object === 'capability') {
            $result = delete_user_option($this->getId(), self::AAM_CAPKEY);
        } else {
            $result = parent::resetObject($object);
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
            $base   = array_shift($roles);
            
            if ($base) {
                $this->parent = new AAM_Core_Subject_Role($base);
                
                // if user has more than one role that set subject as multi
                if (AAM::api()->getConfig('core.settings.multiSubject', false) 
                        && count($roles)) {
                    $siblings = array();
                    foreach($roles as $role) {
                        $siblings[] = new AAM_Core_Subject_Role($role);
                    }
                    $this->parent->setSiblings($siblings);
                }
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
        if (is_null($this->maxLevel)) {
            $this->maxLevel = AAM_Core_API::maxLevel($this->allcaps);
        }
        
        return $this->maxLevel;
    }
    
}