<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM WP_User proxy
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Proxy_User implements AAM_Framework_Proxy_Interface
{

    /**
     * User status: ACTIVE
     *
     * @version 7.0.0
     */
    const STATUS_ACTIVE = 'active';

    /**
     * User status: INACTIVE
     *
     * @version 7.0.0
     */
    const STATUS_INACTIVE = 'inactive';

    /**
     * Array of allowed user statuses
     *
     * @var array
     *
     * @version 7.0.0
     */
    const ALLOWED_USER_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE
    ];

    /**
     * Array of allowed expiration triggers
     *
     * @var array
     *
     * @version 7.0.0
     */
    const ALLOWED_EXPIRATION_TRIGGERS = [
        'logout',
        'change_role',
        'lock'
    ];

    /**
     * Original user object
     *
     * @var WP_User
     * @version 7.0.0
     */
    private $_user;

    /**
     * User status
     *
     * @var string
     * @access private
     * @version 7.0.0
     */
    private $_status = self::STATUS_ACTIVE;

    /**
     * Expiration date-time
     *
     * @var DateTime
     * @access private
     *
     * @version 7.0.0
     */
    private $_expires_at = null;

    /**
     * Action to trigger when user access expires
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_expiration_trigger = null;

    /**
     * Constructor
     *
     * Two additional attributes are initialized: status and expiration trigger.
     * The user's status is global and if user is locked, they are locked from all
     * sites in multisite setup. This is due to the fact that user can login only
     * once if WP.
     *
     * However, the expiration trigger is localized to a specific site.
     *
     * @param WP_User $user User core object
     *
     * @return void
     *
     * @access public
     * @since 7.0.0
     */
    public function __construct(WP_User $user)
    {
        $this->_user = $user;

        // Init user expiration state
        $this->_init_user_expiration();

        // Init user status
        $this->_init_user_status();
    }

    /**
     * @inheritDoc
     *
     * @return WP_User
     */
    public function get_core_instance()
    {
        return $this->_user;
    }

    /**
     * Update user attributes
     *
     * @param array $data
     *
     * @return AAM_Framework_Proxy_User
     * @access public
     *
     * @version 7.0.0
     */
    public function update($data)
    {
        // Verifying the expiration date & trigger, if defined
        if (isset($data['expiration'])) {
            $expiration = [
                'expires' => is_numeric($data['expiration']['expires_at']) ?
                    $data['expiration']['expires_at']
                    :
                    DateTime::createFromFormat(
                        DateTime::RFC3339, $data['expiration']['expires_at']
                    )->getTimestamp()
            ];

            // Parse the trigger
            if (empty($data['expiration']['trigger'])) {
                $expiration['action'] = 'logout';
            } elseif (is_array($data['expiration']['trigger'])) {
                $expiration['action'] = $data['expiration']['trigger']['type'];
            } elseif (is_string($data['expiration']['trigger'])) {
                $expiration['action'] = $data['expiration']['trigger'];
            }

            // Additionally, if trigger is change_role, capture the targeting
            // role
            if ($expiration['action'] === 'change_role') {
                $expiration['meta'] = $data['expiration']['trigger']['to_role'];
            }

            // Update the expiration attribute but do not check if it was saved
            // successfully because if you are trying to save the same value, it will
            // return false
            update_user_option($this->ID, 'aam_user_expiration', $expiration);

            // Reinitialize user expiration state
            $this->_init_user_expiration();
        }

        if (isset($data['status'])) {
            if ($data['status'] === self::STATUS_INACTIVE) {
                add_user_meta($this->ID, 'aam_user_status', 'locked');
            } else {
                delete_user_meta($this->ID, 'aam_user_status');
            }

            // Reinitialize user's status
            $this->_init_user_status();
        }

        if (isset($data['add_caps'])) {
            foreach($data['add_caps'] as $capability) {
                $this->add_cap($capability);
            }
        }

        if (isset($data['deprive_caps'])) {
            foreach($data['deprive_caps'] as $capability) {
                $this->add_cap($capability, false);
            }
        }

        if (isset($data['remove_caps'])) {
            foreach($data['remove_caps'] as $capability) {
                $this->remove_cap($capability);
            }
        }

        return $this;
    }

    /**
     * Reset user attributes
     *
     * @param string|array|null $attributes
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($attributes = null)
    {
        if (is_null($attributes)) {
            $attributes = [ 'expiration', 'status' ];
        } elseif (is_string($attributes)) {
            $attributes = [ $attributes ];
        }

        // Reset user expiration
        if (in_array('expiration', $attributes, true)) {
            delete_user_option($this->ID, 'aam_user_expiration');
            $this->_init_user_expiration();
        }

        // Reset user status
        if (in_array('status', $attributes, true)) {
            delete_user_meta($this->ID, 'aam_user_status');
            $this->_init_user_status();
        }
    }

    /**
     * Check if user is active
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_user_active()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user's access is expired
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_user_access_expired()
    {
        $result = false;

        if ($this->expires_at !== null) {
            $now    = new DateTime('now', new DateTimeZone('UTC'));
            $result = $this->expires_at->getTimestamp() < $now->getTimestamp();
        }

        return $result;
    }

    /**
     * Proxy method to the original object
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     * @access public
     *
     * @since 7.0.0
     */
    public function __call($name, $arguments)
    {
        $response = null;

        if (method_exists($this->_user, $name)) {
            $response = call_user_func_array(
                array($this->_user, $name), $arguments
            );
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'WP_User does not have method defined',
                AAM_VERSION
            );
        }

        return $response;
    }

    /**
     * Proxy property retrieval to the original object
     *
     * @param string $name
     *
     * @return mixed
     * @access public
     *
     * @since 7.0.0
     */
    public function __get($name)
    {
        $response = null;

        if (property_exists($this, "_{$name}")) {
            $response = $this->{"_{$name}"};
        } else {
            $response = $this->_user->{$name};
        }

        return $response;
    }

    /**
     * Proxy property setting to the original object
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     * @access public
     *
     * @since 7.0.0
     */
    public function __set($name, $value)
    {
        $this->_user->{$name} = $value;
    }

    /**
     * Init user expiration state
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _init_user_expiration()
    {
        $expiration = get_user_option('aam_user_expiration', $this->ID);

        if (!empty($expiration)) {
            $this->_expires_at = new DateTime(
                '@' . $expiration['expires'], new DateTimeZone('UTC')
            );

            // Determine trigger type and additional attributes for the trigger
            // (if applicable)
            $action = isset($expiration['action']) ? $expiration['action'] : 'lock';

            $trigger = [
                'type' => $action
            ];

            // The "change-role" is a legacy setting
            if (in_array($action, ['change-role', 'change_role'], true)) {
                $trigger['to_role'] = $expiration['meta'];
            }

            $this->_expiration_trigger = $trigger;
        } else {
            $this->_expires_at         = null;
            $this->_expiration_trigger = null;
        }
    }

    /**
     * Initialize user's status
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _init_user_status()
    {
        // Get user status
        $status = get_user_meta($this->ID, 'aam_user_status', true);

        if ($status === 'locked') {
            $this->_status = self::STATUS_INACTIVE;
        } else {
            $this->_status = self::STATUS_ACTIVE;
        }
    }

}