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
 * @since 6.9.32 https://github.com/aamplugin/advanced-access-manager/issues/390
 * @since 6.9.11 https://github.com/aamplugin/advanced-access-manager/issues/279
 * @since 6.2.2  Fixed bug with settings inheritance from the Default subject
 * @since 6.0.2  Enhanced stability of the code
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.32
 */
class AAM_Core_Subject_User extends AAM_Core_Subject
{

    /**
     * Subject UID: USER
     *
     * @version 6.0.0
     */
    const UID = 'user';

    /**
     * User expiration DB option
     *
     * @version 6.0.0
     */
    const EXPIRATION_OPTION = 'aam_user_expiration';

    /**
     * Parent role
     *
     * @var AAM_Core_Subject_Role
     *
     * @access private
     * @version 6.0.0
     */
    private $_parent = null;

    /**
     * Max user level
     *
     * @var int
     *
     * @access private
     * @version 6.0.0
     */
    private $_maxLevel = null;

    /**
     * Constructor
     *
     * @param int $id
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function __construct($id)
    {
        // Set subject Id
        $this->setId(intval($id));

        // Retrieve underlining WP core principal
        $this->setPrincipal($this->retrievePrincipal());
    }

    /**
     * Initialize user subject
     *
     * @return AAM_Core_Subject_User
     *
     * @access public
     * @version 6.0.0
     */
    public function initialize()
    {
        // Initialize current user. This hook is used by Access Policy service to
        // mutate the capability and role lists for current user
        do_action('aam_initialize_user_action', $this);

        return $this;
    }

    /**
     * Get user capabilities
     *
     * This method also filters out any capability that is a role
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getCapabilities()
    {
        $caps = $this->caps;

        foreach (array_keys($caps) as $cap) {
            if (wp_roles()->is_role($cap)) {
                unset($caps[$cap]);
            }
        }

        return $caps;
    }

    /**
     * Check if user has a capability
     *
     * @param string $capability
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function hasCapability($capability)
    {
        return user_can($this->getPrincipal(), $capability);
    }

    /**
     * Add capability
     *
     * @param string  $capability
     * @param boolean $grant
     *
     * @return boolean Always return true
     *
     * @access public
     * @version 6.0.0
     */
    public function addCapability($capability, $grant = true)
    {
        $this->add_cap($capability, $grant);

        return true;
    }

    /**
     * Remove capability
     *
     * @param string $capability
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function removeCapability($capability)
    {
        $this->remove_cap($capability);

        return true;
    }

    /**
     * @inheritDoc
     *
     * @since 6.2.2 Fixed bug where user did not inherit settings from default if
     *              user has not roles
     * @since 6.0.0 Initial implementation of the method
     *
     * @version 6.2.2
     */
    public function getParent()
    {
        if (is_null($this->_parent)) {
            $roles = $this->roles;
            $base  = array_shift($roles);

            if ($base) {
                $this->_parent = new AAM_Core_Subject_Role($base);

                $multi = AAM::api()->configs()->get_config(
                    'core.settings.multiSubject'
                );

                if ($multi && count($roles)) {
                    $siblings = array();
                    foreach ($roles as $role) {
                        $siblings[] = new AAM_Core_Subject_Role($role);
                    }
                    $this->_parent->setSiblings($siblings);
                }
            } else {
                $this->_parent = AAM::api()->getDefault();
            }
        }

        return $this->_parent;
    }

    /**
     * @inheritDoc
     * @version 6.0.0
     */
    public function getName()
    {
        $display = $this->display_name;

        return ($display ? $display : $this->user_nicename);
    }

    /**
     * Get max user level
     *
     * @return int
     *
     * @access public
     * @version 6.0.0
     */
    public function getMaxLevel()
    {
        if (is_null($this->_maxLevel)) {
            $this->_maxLevel = AAM_Core_API::maxLevel($this->allcaps);
        }

        return $this->_maxLevel;
    }

    /**
     * Retrieve WP core user principal
     *
     * @return WP_User
     *
     * @access protected
     * @version 6.0.0
     */
    protected function retrievePrincipal()
    {
        if ($this->getId() === get_current_user_id()) {
            $subject = wp_get_current_user();
        } else {
            $subject = new WP_User($this->getId());
        }

        return $subject;
    }

}