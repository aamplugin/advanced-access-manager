<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Role subject
 *
 * @since 6.4.0 Added the ability to change role's slug
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.4.0
 */
class AAM_Core_Subject_Role extends AAM_Core_Subject
{

    /**
     * Subject UID: ROLE
     *
     * @version 6.0.0
     */
    const UID = 'role';

    /**
     * Role name
     *
     * Fix the bug that is in the way WP_Roles is initialized
     *
     * @var string
     * @version 6.0.0
     */
    protected $name;

    /**
     * Parent role's subject
     *
     * @var AAM_Core_Subject
     *
     * @access private
     * @version 6.0.0
     */
    private $_parent = null;

    /**
     * Constructor
     *
     * @param string $id
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function __construct($id)
    {
        // Set subject Id
        $this->setId($id);

        // Retrieve underlining WP core principal
        $this->setPrincipal($this->retrievePrincipal());
    }

    /**
     * Retrieve WP core role
     *
     * @return AAM_Framework_Proxy_Role|null
     *
     * @access protected
     * @version 6.0.0
     */
    protected function retrievePrincipal()
    {
        try {
            $role = AAM_Framework_Manager::roles()->get_role_by_slug(
                $this->getId(), false
            );
            $this->name = $role->display_name;
        } catch (Exception $_) {
            $role = null;
        }

        return $role;
    }

    /**
     * Delete role
     *
     * Role is not going to be deleted if there is at least one user assigned to it
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function delete()
    {
        $result = false;

        try {
            $role   = AAM_Framework_Manager::roles()->get_role_by_slug($this->getId());
            $result = AAM_Framework_Manager::roles()->delete_role($role);
        } catch (Exception $_) {
            // Do nothing
        }

        return $result;
    }

    /**
     * Update role name
     *
     * @param string $name
     * @param string $slug
     *
     * @return boolean
     *
     * @since 6.4.0 https://github.com/aamplugin/advanced-access-manager/issues/72
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.4.0
     */
    public function update($name, $slug = null)
    {
        $role = $this->getPrincipal();

        // Setting new attributes
        $role->set_display_name($name);

        // If new slug is defined set it as well
        if (is_string($slug)) {
            $role->set_slug($slug);
        }

        $result = false;

        try {
            $result = AAM_Framework_Manager::roles()->update_role($role);
        } catch (Exception $_) {
            // Do nothing
        }

        return $result;
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
     * Add capability
     *
     * @param string  $capability
     * @param boolean $grant
     *
     * @return boolean
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
     * Get role capabilities
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }

    /**
     * Check if role has capability
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
        // If capability is the same as role ID, then capability exists
        if ($capability === $this->getId()) {
            $has = true;
        } else {
            $has = $this->has_cap($capability);
        }

        return $has;
    }

    /**
     * @inheritDoc
     * @version 6.0.0
     */
    public function getParent()
    {
        if (is_null($this->_parent)) {
            $this->_parent = apply_filters(
                'aam_parent_role_filter',
                AAM_Core_Subject_Default::getInstance(),
                $this
            );
        }

        return $this->_parent;
    }

    /**
     * @inheritDoc
     * @version 6.0.0
     */
    public function getName()
    {
        return translate_user_role($this->name);
    }

   /**
     * Get max role user level
     *
     * @return int
     *
     * @access public
     * @version 6.0.0
     */
    public function getMaxLevel()
    {
        return AAM_Core_API::maxLevel($this->capabilities);
    }

    /**
     * Fallback for any principal native methods
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->getPrincipal(), $name), $args);
    }

}