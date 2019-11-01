<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * User subject
 *
 * @package AAM
 * @version 6.0.0
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
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function initialize()
    {
        // Initialize current user. This hook is used by Access Policy service to
        // mutate the capability and role lists for current user
        do_action('aam_initialize_user_action', $this);
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
        $caps  = $this->caps;
        $roles = AAM_Core_API::getRoles();

        foreach (array_keys($caps) as $cap) {
            if ($roles->is_role($cap)) {
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
     * @version 6.0.0
     */
    public function getParent()
    {
        if (is_null($this->_parent)) {
            $roles = $this->roles;
            $base  = array_shift($roles);

            if ($base) {
                $this->_parent = new AAM_Core_Subject_Role($base);

                $multi = AAM::api()->getConfig('core.settings.multiSubject', false);

                if ($multi && count($roles)) {
                    $siblings = array();
                    foreach ($roles as $role) {
                        $siblings[] = new AAM_Core_Subject_Role($role);
                    }
                    $this->_parent->setSiblings($siblings);
                }
            } else {
                $this->_parent = false;
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

    /**
     * Validate current authenticated user status
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function validateStatus()
    {
        $status = $this->checkUserExpiration();

        if ($status !== true) {
            $this->resetUserExpiration();

            // Trigger specified action
            switch ($status['action']) {
                case 'change-role':
                    $this->set_role(''); // First reset all roles
                    foreach ((array) $status->meta as $role) {
                        $this->add_role($role);
                    }
                    break;

                case 'delete':
                    require_once(ABSPATH . 'wp-admin/includes/user.php');
                    wp_delete_user(
                        $this->getId(),
                        AAM_Core_Config::get('core.reasign.ownership.user')
                    );
                    // Finally logout

                case 'logout':
                    wp_logout();
                    break;

                default:
                    do_action('aam_process_inactive_user_action', $status, $this);
                    break;
            }
        }
    }

    /**
     * Set user expiration meta
     *
     * @param array $settings
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function setUserExpiration($settings)
    {
        if (array_key_exists('action', $settings) === false) {
            $settings['action'] = 'logout';
        }

        return update_user_option(
            $this->getId(), self::EXPIRATION_OPTION, $settings
        ) !== false;
    }

    /**
     * Get user expiration data
     *
     * @return array|null
     *
     * @access public
     * @version 6.0.0
     */
    public function getUserExpiration()
    {
        $response = get_user_option(self::EXPIRATION_OPTION, $this->getId());

        if (!empty($response)) {
            $response['expires'] = new DateTime(
                '@' . $response['expires'], new DateTimeZone('UTC')
            );
        }

        return $response;
    }

    /**
     * Reset user expiration meta
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function resetUserExpiration()
    {
        return delete_user_option($this->getId(), self::EXPIRATION_OPTION);
    }

    /**
     * Check if user account is expired
     *
     * @return array|bool
     *
     * @access protected
     * @version 6.0.0
     */
    protected function checkUserExpiration()
    {
        $status     = true;
        $expiration = $this->getUserExpiration();

        if (!empty($expiration)) {
            $compare  = new DateTime('now', new DateTimeZone('UTC'));

            if ($expiration['expires']->getTimestamp() <= $compare->getTimestamp()) {
                $status = $expiration;
            }
        }

        return $status;
    }

}