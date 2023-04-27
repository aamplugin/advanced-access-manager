<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service role manage
 *
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/275
 * @since 6.9.6  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.10
 */
class AAM_Framework_Service_Roles
{

    /**
     * Single instance of itself
     *
     * @var AAM_Framework_Service_Role
     *
     * @access private
     * @static
     * @version 6.9.6
     */
    private static $_instance = null;

    /**
     * User index
     *
     * The value is generated with `count_users` core function
     *
     * @var array
     *
     * @access private
     * @version 6.9.6
     */
    private $_user_index = null;

    /**
     * WP core roles object
     *
     * @var WP_Roles
     *
     * @access private
     * @version 6.9.6
     */
    private $_wp_roles = null;

    /**
     * Instantiate the service
     *
     * @return void
     *
     * @access protected
     * @version 6.9.6
     */
    protected function __construct() {}

    /**
     * Return list of roles
     *
     * @return array Array of AAM_Framework_Proxy_Role
     *
     * @access public
     * @version 6.9.6
     */
    public function get_all_roles()
    {
        $response = array();
        $roles    = $this->get_wp_roles();

        foreach($roles->role_objects as $role) {
            array_push($response, new AAM_Framework_Proxy_Role(
                $roles->role_names[$role->name],
                $role
            ));
        }

        return $response;
    }

    /**
     * Get list of editable roles
     *
     * @return array Array of AAM_Framework_Proxy_Role
     *
     * @access public
     * @version 6.9.6
     */
    public function get_editable_roles()
    {
        $response = array();
        $roles    = $this->get_wp_roles();

        if (function_exists('get_editable_roles')) {
            $all = get_editable_roles();
        } else {
            $all = apply_filters('editable_roles', $roles->roles);
        }

        foreach(array_keys($all) as $slug) {
            array_push($response, new AAM_Framework_Proxy_Role(
                $roles->role_names[$slug],
                $roles->get_role($slug)
            ));
        }

        return $response;
    }

    /**
     * Get role by slug
     *
     * @param string  $slug     Unique role slug (aka ID)
     * @param boolean $editable optional Return role only if editable by current user
     *
     * @return AAM_Framework_Proxy_Role|null
     *
     * @access public
     * @throws UnderflowException
     * @version 6.9.6
     */
    public function get_role_by_slug($slug, $editable = true)
    {
        if ($editable === true) {
            $all = $this->get_editable_roles();
        } else {
            $all = $this->get_all_roles();
        }

        $match = array_filter($all, function($role) use ($slug) {
            return $role->slug === $slug;
        });

        if (count($match) === 0) {
            throw new UnderflowException(
                "Role '{$slug}' does not exist or is not editable"
            );
        }

        return array_shift($match);
    }

    /**
     * Create new role
     *
     * The method sanitizes and validates all the input values before creating a new
     * role. Additionally, verifies that there is no slug overlap with any existing
     * roles. In case of any validation issues, the method throws the
     * InvalidArgumentException exception.
     *
     * The only required argument is `$displayName`. If `$slug` is not provided, the
     * random number is generated with `uniqid` function.
     *
     * @param string $display_name Role name
     * @param string $slug         optional Role slug
     * @param array  $capabilities optional Array of capabilities
     *
     * @return AAM_Framework_Proxy_Role
     * @throws InvalidArgumentException
     *
     * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/275
     * @since 6.9.6  Initial implementation of the method
     *
     * @access public
     * @version 6.9.10
     */
    public function create_role(
        $display_name, $slug = null, array $capabilities = array()
    ) {
        $role  = null;
        $name  = sanitize_text_field($display_name);
        $roles = $this->get_wp_roles();

        if (is_string($name) && strlen($name) > 0) {
            // Verify that if role slug is provided and it is valid
            if (is_string($slug) && strlen($slug) > 0) {
                $slug = sanitize_key($slug);

                if (strlen($slug) === 0) {
                    throw new InvalidArgumentException('Role slug is invalid');
                }
            } else {
                // First, try to normalize the roles name into slug and if nothing,
                // then generate the random number
                $slug = str_replace(' ', '_', sanitize_key($name));
                $slug = empty($slug) ? strtolower(uniqid()) : $slug;
            }

            if ($roles->is_role($slug)) {
                throw new DomainException("Role '{$slug}' already exists");
            }

            // Sanitize the list of capabilities and make sure that the list contains
            // unique caps

            $caps = array_unique(array_map(function($cap) {
                $result = sanitize_key($cap);

                if (!is_string($result) || strlen($result) === 0) {
                    throw new InvalidArgumentException(
                        "Capability '{$cap}' is invalid"
                    );
                }

                return $result;
            }, $capabilities));

            // Creating new role
            $role = new AAM_Framework_Proxy_Role(
                $name,
                $roles->add_role($slug, $name, array_fill_keys($caps, true))
            );
        } else {
            throw new InvalidArgumentException('Role name is invalid');
        }

        return $role;
    }

    /**
     * Update role attributes
     *
     * @param AAM_Framework_Proxy_Role $role
     *
     * @return boolean
     *
     * @access public
     * @throws DomainException
     * @version 6.9.6
     */
    public function update_role(AAM_Framework_Proxy_Role $role)
    {
        $roles = $this->get_wp_roles();

        // If role's slug changed, verify that there is no overlap with existing
        // roles and current role does not have any users assigned to it. Otherwise
        // reject update
        if ($role->slug !== $role->name) {
            // Making sure that user can change role's slug.
            if ($this->get_role_user_count($role) > 0) {
                throw new DomainException('Cannot update slug for role with users');
            }

            // Taking exactly the same position in the list of roles
            $new_list = array();

            foreach($roles->roles as $slug => $props) {
                if ($slug === $role->name) {
                    $new_list[$role->slug] = array(
                        'name'         => $role->display_name,
                        'capabilities' => $role->capabilities
                    );
                } else {
                    $new_list[$slug] = $props;
                }
            }

            $roles->roles = $new_list;
        } else { // Otherwise only update the attributes like display name and caps
            $roles->roles[$role->slug] = array(
                'name'         => $role->display_name,
                'capabilities' => $role->capabilities
            );
        }

        return update_option($roles->role_key, $roles->roles);
    }

    /**
     * Delete existing role
     *
     * @param AAM_Framework_Proxy_Role $role
     *
     * @return boolean
     *
     * @access public
     * @throws DomainException
     * @version 6.9.6
     */
    public function delete_role(AAM_Framework_Proxy_Role $role)
    {
        $roles = $this->get_wp_roles();

        // Verifying that role has not users assigned. Otherwise reject
        if ($this->get_role_user_count($role) > 0) {
            throw new DomainException('Cannot delete role with users');
        }

        $this->get_wp_roles()->remove_role($role->slug);

        return !$roles->is_role($role->slug);
    }

    /**
     * Get approximate number of users assigned to role
     *
     * @param AAM_Framework_Proxy_Role $role
     *
     * @return int
     *
     * @access public
     * @version 6.9.6
     */
    public function get_role_user_count(AAM_Framework_Proxy_Role $role)
    {
        if (is_null($this->_user_index)) {
            $this->_user_index = count_users();
        }

        $avail = $this->_user_index['avail_roles'];

        return isset($avail[$role->name]) ? $avail[$role->name] : 0;
    }

    /**
     * Get the list of WordPress roles as WP_Roles object
     *
     * @return WP_Roles
     *
     * @access public
     * @version 6.9.6
     */
    public function get_wp_roles()
    {
        global $wp_roles;

        if (is_null($this->_wp_roles)) {
            if (function_exists('wp_roles')) {
                $this->_wp_roles = wp_roles();
            } elseif (isset($wp_roles)) {
                $this->_wp_roles = $wp_roles;
            } else {
                $this->_wp_roles = new WP_Roles();
            }
        }

        return $this->_wp_roles;
    }

    /**
     * Proxy method to the WP_Roles
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @access public
     * @version 6.9.6
     */
    public function __call($name, $arguments)
    {
        $response = null;
        $roles    = $this->get_wp_roles();

        if (method_exists($roles, $name)) {
            $response = call_user_func_array(array($roles, $name), $arguments);
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'WP_Roles does not have method defined',
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
     * @version 6.9.6
     */
    public function __get($name)
    {
        $response = null;
        $roles    = $this->get_wp_roles();

        if (property_exists($roles, $name)) {
            $response = $roles->{$name};
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'WP_Roles does not have property defined',
                AAM_VERSION
            );
        }

        return $response;
    }

    /**
     * Bootstrap the role service
     *
     * @return AAM_Framework_Service_Role
     *
     * @access public
     * @static
     * @version 6.9.6
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}