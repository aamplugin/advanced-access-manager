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
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/271
 * @since 6.9.6  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.10
 */
class AAM_Framework_Proxy_Role
{

    /**
     * Role unique slug (aka ID)
     *
     * @var string
     * @since 6.9.6
     */
    private $_slug;

    /**
     * Role display name
     *
     * @var string
     * @since 6.9.6
     */
    private $_display_name;

    /**
     * Original role object
     *
     * @var WP_Role
     * @since 6.9.6
     */
    private $_role;

    /**
     * Constructor
     *
     * @param string  $name Role display name
     * @param WP_Role $role Role core object
     *
     * @return void
     *
     * @access public
     * @since 6.9.6
     */
    public function __construct($name, WP_Role $role)
    {
        $this->set_display_name($name);
        $this->set_slug($role->name);

        $this->_role = $role;
    }

    /**
     * Set slug
     *
     * The method also sanitizes the input value with `sanitize_key` core function
     *
     * @param string $slug Unique role slug (aka ID)
     *
     * @return void
     *
     * @access public
     * @throws InvalidArgumentException
     *
     * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/271
     * @since 6.9.6  Initial implementation of the method
     *
     * @version 6.9.10
     */
    public function set_slug($slug)
    {
        if (!is_string($slug) || strlen($slug) === 0) {
            throw new InvalidArgumentException('Invalid slug');
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
     *
     * @access public
     * @throws InvalidArgumentException
     * @since 6.9.6
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
     *
     * @access public
     * @throws InvalidArgumentException
     * @since 6.9.6
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
            $this->_role->remove_cap($sanitized);
        } elseif (isset($this->_role->capabilities[$sanitized])) {
            unset($this->_role->capabilities[$sanitized]);
        }
    }

    /**
     * Return role attributes as array
     *
     * @return array
     *
     * @access public
     * @since 6.9.6
     */
    public function to_array()
    {
        return array(
            'slug'         => $this->_slug,
            'name'         => $this->_display_name,
            'capabilities' => $this->_role->capabilities
        );
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
     * @since 6.9.6
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
     *
     * @access public
     * @since 6.9.6
     */
    public function __get($name)
    {
        $response = null;

        if (property_exists($this, "_{$name}")) {
            $response = $this->{"_{$name}"};
        } elseif (property_exists($this->_role, $name)) {
            $response = $this->_role->{$name};
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
     *
     * @access public
     * @since 6.9.6
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

}