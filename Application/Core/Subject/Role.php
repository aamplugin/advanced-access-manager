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
     * @return WP_Role|null
     *
     * @access protected
     * @version 6.0.0
     */
    protected function retrievePrincipal()
    {
        $roles = AAM_Core_API::getRoles();

        if (isset($roles->roles[$this->getId()])) {
            $role       = $roles->get_role($this->getId());
            $this->name = $roles->roles[$this->getId()]['name'];
        } else {
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
        $status = false;
        $roles  = AAM_Core_API::getRoles();

        $count = count_users();
        $stats = $count['avail_roles'];

        if (empty($stats[$this->getId()])) {
            $roles->remove_role($this->getId());
            $status = true;
        }

        return $status;
    }

    /**
     * Update role name
     *
     * @param string $name
     * @param string $slug
     *
     * @return boolean
     *
     * @since 6.4.0 Enhancement https://github.com/aamplugin/advanced-access-manager/issues/72
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.4.0
     */
    public function update($name, $slug = null)
    {
        $roles = AAM_Core_API::getRoles();

        if ($name) {
            if (!empty($slug) && ($slug !== $this->getId())) {
                $stats = count_users()['avail_roles'];
                $n = (isset($stats[$this->getId()]) ? $stats[$this->getId()] : 0);

                if ($n === 0) {
                    $new_roles = array();

                    foreach($roles->roles as $id => $data) {
                        if ($id === $this->getId()) {
                            $new_roles[$slug] = $data; // Replace role and preserve order
                        } else {
                            $new_roles[$id] = $data;
                        }
                    }

                    $roles->roles = $new_roles;
                    $this->setId($slug); // New role's id
                }
            }

            $roles->roles[$this->getId()]['name'] = $name;
            $status = AAM_Core_API::updateOption($roles->role_key, $roles->roles);
        } else {
            $status = false;
        }

        return $status;
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
     * @param string $cap
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function hasCapability($cap)
    {
        // If capability is the same as role ID, then capability exists
        if ($cap === $this->getId()) {
            $has = true;
        } else {
            $has = $this->has_cap($cap);
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

}