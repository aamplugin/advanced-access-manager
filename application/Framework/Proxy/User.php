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
 * @version 6.9.32
 */
class AAM_Framework_Proxy_User
{

    /**
     * User status: ACTIVE
     *
     * @version 6.9.32
     */
    const STATUS_ACTIVE = 'active';

    /**
     * User status: INACTIVE
     *
     * @version 6.9.32
     */
    const STATUS_INACTIVE = 'inactive';

    /**
     * Array of allowed user statuses
     *
     * @var array
     *
     * @version 6.9.32
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
     * @version 6.9.32
     */
    const ALLOWED_EXPIRATION_TRIGGERS = [
        'logout',
        'delete',
        'change_role',
        'lock'
    ];

    /**
     * Original user object
     *
     * @var WP_User
     * @version 6.9.32
     */
    private $_wp_user;

    /**
     * User status
     *
     * @var string
     * @access private
     * @version 6.9.32
     */
    private $_status = self::STATUS_ACTIVE;

    /**
     * Expiration date-time
     *
     * @var DateTime
     * @access private
     *
     * @version 6.9.32
     */
    private $_expires_at = null;

    /**
     * Action to trigger when user access expires
     *
     * @var array
     *
     * @access private
     * @version 6.9.32
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
     * @since 6.9.32
     */
    public function __construct(WP_User $user)
    {
        $this->_wp_user = $user;

        // Init user expiration state
        $this->_init_user_expiration();

        // Init user status
        $this->_init_user_status();
    }

    /**
     * Update user attributes
     *
     * @param array $data
     *
     * @return AAM_Framework_Proxy_User
     *
     * @access public
     * @version 6.9.33
     * @throws RuntimeException
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

        if (isset($data['remove_caps'])) {
            foreach($data['remove_caps'] as $capability) {
                // Note! Yes, adding capability to ensure that user will not inherit
                // this capability from their parent role(s)
                $this->add_cap($capability, false);
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
     *
     * @access public
     * @version 6.9.33
     */
    public function reset($attributes = null)
    {
        if (is_null($attributes)) {
            $attributes = ['expiration', 'status'];
        } elseif (is_array($attributes)) {
            $attributes = [$attributes];
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
     *
     * @access public
     * @version 6.9.33
     */
    public function is_user_active()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user's access is expired
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.33
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
     * Grant capability to user
     *
     * @param string  $capability       Capability slug
     * @param boolean $save_immediately Wether save in DB immediately or not
     *
     * @return void
     *
     * @access public
     * @throws InvalidArgumentException
     * @since 6.9.6
     */
    public function add_capability($capability, $save_immediately = false)
    {
        $sanitized = sanitize_key($capability);

        if (!is_string($sanitized) || strlen($sanitized) === 0) {
            throw new InvalidArgumentException(
                "Capability '{$capability}' is invalid"
            );
        }

        if ($save_immediately === true) {
            $this->_wp_user->add_cap($sanitized, true);
        } else {
            $this->_wp_user->caps[$sanitized] = true;
        }
    }

    /**
     * Deprive capability from a user
     *
     * @param string  $capability       Capability slug
     * @param boolean $save_immediately Wether save in DB immediately or not
     *
     * @return void
     *
     * @access public
     * @throws InvalidArgumentException
     * @since 6.9.32
     */
    public function remove_capability($capability, $save_immediately = false)
    {
        $sanitized = sanitize_key($capability);

        if (!is_string($sanitized) || strlen($sanitized) === 0) {
            throw new InvalidArgumentException(
                "Capability '{$capability}' is invalid"
            );
        }

        if ($save_immediately === true) {
            $this->_wp_user->remove_cap($sanitized);
        } elseif (isset($this->_wp_user->capabilities[$sanitized])) {
            unset($this->_wp_user->caps[$sanitized]);
        }
    }

    /**
     * Return user attributes as array
     *
     * @return array
     *
     * @access public
     * @since 6.9.32
     */
    public function to_array()
    {
        return $this->_wp_user->data;
    }

    /**
     * Proxy method to the original object
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @access public
     * @since 6.9.32
     */
    public function __call($name, $arguments)
    {
        $response = null;

        if (method_exists($this->_wp_user, $name)) {
            $response = call_user_func_array(
                array($this->_wp_user, $name), $arguments
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
     *
     * @access public
     * @since 6.9.32
     */
    public function __get($name)
    {
        $response = null;

        if (property_exists($this, "_{$name}")) {
            $response = $this->{"_{$name}"};
        } else {
            $response = $this->_wp_user->{$name};
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
     *
     * @access public
     * @since 6.9.32
     */
    public function __set($name, $value)
    {
        $this->_wp_user->{$name} = $value;
    }

    /**
     * Get WordPress core user object
     *
     * @return WP_User
     *
     * @access public
     * @version 6.9.33
     */
    public function get_wp_user()
    {
        return $this->_wp_user;
    }

    /**
     * Init user expiration state
     *
     * @return void
     *
     * @access private
     * @version 6.9.33
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
     *
     * @access private
     * @version 6.9.33
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