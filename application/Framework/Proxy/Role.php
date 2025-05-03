<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM WP_Role proxy
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Proxy_Role implements AAM_Framework_Proxy_Interface
{

    /**
     * Role unique slug (aka ID)
     *
     * @var string
     * @since 7.0.0
     */
    private $_slug;

    /**
     * Role display name
     *
     * @var string
     * @since 7.0.0
     */
    private $_display_name;

    /**
     * Original role object
     *
     * @var WP_Role
     * @since 7.0.0
     */
    private $_role;

    /**
     * User index
     *
     * The value is generated with `count_users` core function and is static so
     * it can be shared with all instances of the role proxy
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private static $_user_index = null;

    /**
     * Constructor
     *
     * @param string  $name Role display name
     * @param WP_Role $role Role core object
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function __construct($name, WP_Role $role)
    {
        $this->set_display_name($name);

        // Covering the scenario when role name is just a number
        $this->_slug = (string) $role->name;
        $this->_role = $role;
    }

    /**
     * @inheritDoc
     *
     * @return WP_Role
     */
    public function get_core_instance()
    {
        return $this->_role;
    }

    /**
     * Update role
     *
     * @param array $attributes
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function update(array $attributes = [])
    {
        // Setting new slug if provided and it does not match the original slug
        if (!empty($attributes['slug']) && $attributes['slug'] !== $this->_slug) {
            // Keep the old slug. We'll use it later to place role in exactly the
            // same spot on the list of roles
            $old_slug = $this->_slug;

            $this->set_slug(sanitize_key($attributes['slug']));
        }

        // Set new display name if provided
        if (!empty($attributes['name'])) {
            $this->set_display_name($attributes['name']);
        }

        // Adding the list of capabilities
        if (isset($attributes['add_caps'])) {
            foreach($attributes['add_caps'] as $capability) {
                $this->_role->add_cap($capability, true);
            }
        }

        // Depriving the list of capabilities
        if (isset($attributes['deprive_caps'])) {
            foreach($attributes['deprive_caps'] as $capability) {
                $this->_role->add_cap($capability, false);
            }
        }

        // Removing the list of capabilities
        if (isset($attributes['remove_caps'])) {
            foreach($attributes['remove_caps'] as $capability) {
                $this->_role->remove_cap($capability);
            }
        }

        $roles = wp_roles()->roles;

        // If slug was updated, then replace the old role with new role and retain
        // the position
        if (!empty($old_slug)) {
            // Taking exactly the same position in the list of roles
            $new_list = array();

            foreach($roles as $slug => $props) {
                if ($slug === $old_slug) {
                    $new_list[$this->_slug] = array(
                        'name'         => $this->_display_name,
                        'capabilities' => $this->capabilities
                    );
                } else {
                    $new_list[$slug] = $props;
                }
            }

            $roles = $new_list;
        } else { // Otherwise only update the attributes like display name and caps
            $roles[$this->_slug] = array(
                'name'         => $this->_display_name,
                'capabilities' => $this->capabilities
            );
        }

        wp_roles()->roles = $roles;

        update_option(wp_roles()->role_key, $roles);

        // Always return true because the update_options may return false if you
        // try to save the same attributes twice
        return true;
    }

    /**
     * Set slug
     *
     * The method also sanitizes the input value with `sanitize_key` core function.
     * Additionally it prevents from changing slug if role has at least one user
     * assigned to it.
     *
     * @param string $slug Unique role slug (aka ID)
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function set_slug($slug)
    {
        if (!is_string($slug) || strlen($slug) === 0) {
            throw new InvalidArgumentException('Invalid slug');
        } elseif ($this->user_count > 0) {
            throw new LogicException(
                'Cannot update slug for role with users'
            );
        } elseif (wp_roles()->is_role($slug)) {
            throw new LogicException(
                'There is already a role with the same slug'
            );
        }

        $this->_slug = $slug;
    }

    /**
     * Set display name
     *
     * The method also sanitizes the input value with `sanitize_text_field` core
     * function
     *
     * @param string $display_name Role name (aka display name)
     *
     * @return void
     * @access public
     *
     * @since 7.0.0
     */
    public function set_display_name($display_name)
    {
        $name = sanitize_text_field($display_name);

        if (!is_string($name) || strlen($name) === 0) {
            throw new InvalidArgumentException('Invalid display name');
        }

        $this->_display_name = $name;
    }

    /**
     * Grant capability to role
     *
     * @param string  $capability       Capability slug
     * @param boolean $save_immediately Wether save in DB immediately or not
     *
     * @return void
     * @access public
     *
     * @since 7.0.0
     */
    public function add_capability($capability, $save_immediately = false)
    {
        $sanitized = trim($capability);

        if (!is_string($sanitized) || strlen($sanitized) === 0) {
            throw new InvalidArgumentException(sprintf(
                "Capability '%s' is invalid", esc_js($capability)
            ));
        }

        if ($save_immediately === true) {
            $this->_role->add_cap($sanitized, true);
        } else {
            $this->_role->capabilities[$sanitized] = true;
        }
    }

    /**
     * Deprive capability from role
     *
     * @param string  $capability       Capability slug
     * @param boolean $save_immediately Wether save in DB immediately or not
     *
     * @return void
     * @access public
     *
     * @since 7.0.0
     */
    public function remove_capability($capability, $save_immediately = false)
    {
        $sanitized = trim($capability);

        if (!is_string($sanitized) || strlen($sanitized) === 0) {
            throw new InvalidArgumentException(sprintf(
                "Capability '%s' is invalid", esc_js($capability)
            ));
        }

        if ($save_immediately === true) {
            $this->_role->add_cap($sanitized, false);
        } elseif (isset($this->_role->capabilities[$sanitized])) {
            $this->_role->capabilities[$sanitized] = false;
        }
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
     * @version 7.0.0
     */
    public function __call($name, $arguments)
    {
        $response = null;

        if (method_exists($this->_role, $name)) {
            $response = call_user_func_array(array($this->_role, $name), $arguments);
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'WP_Role does not have method defined',
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
     * @version 7.0.0
     */
    public function __get($name)
    {
        $response = null;

        if (property_exists($this, "_{$name}")) {
            $response = $this->{"_{$name}"};
        } elseif (property_exists($this->_role, $name)) {
            $response = $this->_role->{$name};
        } elseif ($name === 'user_count') { // Lazy load this property
            $response = $this->_get_user_count();
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'WP_Role does not have property defined',
                AAM_VERSION
            );
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
     * @version 7.0.0
     */
    public function __set($name, $value)
    {
        if (property_exists($this->_role, $name)) {
            $this->_role->{$name} = $value;
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'WP_Role does not have property defined',
                AAM_VERSION
            );
        }
    }

    /**
     * Get user count for the current role
     *
     * @return int
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_user_count()
    {
        if (is_null(self::$_user_index)) {
            self::$_user_index = count_users();
        }

        $avail = self::$_user_index['avail_roles'];

        return isset($avail[$this->_slug]) ? $avail[$this->_slug] : 0;
    }

}