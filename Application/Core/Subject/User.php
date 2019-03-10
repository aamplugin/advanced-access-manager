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
     * List of all user specific capabilities
     * 
     * @var array
     * 
     * @access protected 
     */
    protected $aamCaps = array();
    
    /**
     * Parent subject
     * 
     * @var AAM_Core_Subject
     * 
     * @access protected 
     */
    protected $parent = null;
    
    /**
     * Max user level
     * 
     * @var int
     * 
     * @access protected 
     */
    protected $maxLevel = null;
    
    /**
     * Constructor
     * 
     * @param int $id
     * 
     * @return void
     * 
     * @access public
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
    public function initialize() {
        $subject = $this->getSubject();
        $manager = AAM_Core_Policy_Factory::get($this);
        
        // Retrieve all capabilities set in Access Policy
        // Load Capabilities from the policy
        $policyCaps = array();
        
        foreach($manager->find("/^Capability:[\w]+/i") as $key => $stm) {
            $chunks = explode(':', $key);
            $policyCaps[$chunks[1]] = ($stm['Effect'] === 'allow' ? 1 : 0);
        }
        
        // Load Roles from the policy
        $roles    = (array) $subject->roles;
        $allRoles = AAM_Core_API::getRoles();
        $roleCaps = array();
        
        foreach($manager->find("/^Role:/i") as $key => $stm) {
            $chunks = explode(':', $key);
            
            if ($stm['Effect'] === 'allow') {
                if (!in_array($chunks[1], $roles, true)) {
                    if ($allRoles->is_role($chunks[1])) {
                        $roleCaps   = array_merge($roleCaps, $allRoles->get_role($chunks[1])->capabilities);
                        $roleCaps[] = $chunks[1];
                    }
                    $roles[] = $chunks[1];
                }
            } elseif (in_array($chunks[1], $roles, true)) {
                // Make sure that we delete all instances of the role
                foreach($roles as $i => $role){ 
                    if ($role === $chunks[1]) {
                        unset($roles[$i]);
                    }
                }
            }
        }
        
        //reset the user capabilities
        $subject->allcaps = array_merge($subject->allcaps, $roleCaps, $policyCaps,  $this->aamCaps);
        $subject->caps    = array_merge($subject->caps,  $this->aamCaps);

        //make sure that no capabilities are going outside of define boundary
        $subject->allcaps = $this->applyCapabilityBoundaries($manager, $subject->allcaps);
        $subject->caps = $this->applyCapabilityBoundaries($manager, $subject->caps);

        // also delete all capabilities that are assigned to denied role ONLY
        // $diff contains the list of roles that were denied for user
        $diff = array_diff_key( $subject->roles, $roles);

        // prepare the list of capabilities that potentially should be removed from
        // user
        $removeCaps = array();
        foreach($diff as $role) {
            $removeCaps = array_merge($removeCaps, $allRoles->get_role($role)->capabilities);
        }

        // prepare the list of capabilities that should still be assigned to user
        $keepCaps = array();
        foreach($roles as $role) {
            $keepCaps = array_merge($keepCaps, $allRoles->get_role($role)->capabilities);
        }

        foreach(array_keys($removeCaps) as $key) {
            if (!array_key_exists($key, $keepCaps)) {
                unset($subject->allcaps[$key]);
                if (isset($subject->caps[$key])) { unset($subject->caps[$key]); }
            }
        }

        $subject->roles = $roles;
    }

    /**
     * Check if any of the capabilities going out of the defined boundary
     *
     * @param AAM_Core_Policy_Manager $manager
     * @param array                    $caps
     * 
     * @return array
     * 
     * @access protected
     */
    protected function applyCapabilityBoundaries($manager, $caps) {
        $final = array();

        foreach($caps as $key => $effect) {
            if ($manager->isBoundary("Capability:{$key}") === false) {
                $final[$key] = $effect;
            }
        }

        return $final;
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
            $parts = explode('|', $expired);
            
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
            
            if ($expires->getTimestamp() <= $compare->getTimestamp()) {
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