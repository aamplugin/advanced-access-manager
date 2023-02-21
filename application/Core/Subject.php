<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract subject class
 *
 * Subject is a user or thing that invokes WordPress resources like posts, menus,
 * URIs, etc. In other words, subject is the abstract access and security layer that
 * contains set of options that define how end user or visitor access a requested
 * resource.
 *
 * Subjects are related in the hierarchical way where "Default" subject supersede all
 * other subjects and access & security settings are propagated down the tree.
 *
 * Subject sibling is thing that is located on the same hierarchical level and access
 * settings get merged based on predefined preference. The example of sibling is a
 * user that has two or more roles. In this case the first role is primary while all
 * other roles are siblings to it.
 *
 * Subject principal is underlying WordPress core user or role. Not all Subjects have
 * principals (e.g. Visitor or Default).
 *
 * @since 6.9.6 https://github.com/aamplugin/advanced-access-manager/issues/249
 * @since 6.7.0 https://github.com/aamplugin/advanced-access-manager/issues/152
 * @since 6.3.2 Added new hook `aam_initialized_{$type}_object_filter`
 * @since 6.1.0 Fixed bug with incorrectly managed internal cache
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.6
 */
abstract class AAM_Core_Subject
{

    /**
     * Subject ID
     *
     * Whether it is User ID or Role ID
     *
     * @var string|int
     *
     * @access private
     * @version 6.0.0
     */
    private $_id = null;

    /**
     * WordPres core principal
     *
     * It can be WP_User or AAM_Framework_Proxy_Role, based on what class has
     * been used
     *
     * @var AAM_Framework_Proxy_Role|WP_User
     *
     * @access private
     * @version 6.0.0
     */
    private $_principal;

    /**
     * Principal's siblings
     *
     * For example this is quite typical for the multi-roles
     *
     * @var array
     *
     * @access private
     * @version 6.0.0
     */
    private $_siblings = array();

    /**
     * List of Objects to be access controlled for current subject
     *
     * All access control objects like Admin Menu, Metaboxes, Posts etc
     *
     * @var array
     *
     * @access private
     * @version 6.0.0
     */
    private $_objects = array();

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
        $response  = null;
        $principal = $this->getPrincipal();

        // Make sure that method is callable
        if (method_exists($principal, $name)) {
            $response = call_user_func_array(array($principal, $name), $args);
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'Subject does not have method defined',
                AAM_VERSION
            );
        }

        return $response;
    }

    /**
     * Fallback for the principal native properties
     *
     * @param string $name
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function __get($name)
    {
        return $this->getPrincipal()->$name;
    }

    /**
     * Fallback for the principal native properties
     *
     * @param string $name
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function __set($name, $value)
    {
        $principal = $this->getPrincipal();
        $principal->$name = $value;
    }

    /**
     * Set subject ID
     *
     * @param string|int
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * Get subject ID
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Get subject name
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    abstract public function getName();

    /**
     * Get maximum subject User level
     *
     * @return int
     *
     * @access public
     * @version 6.0.0
     */
    public function getMaxLevel()
    {
        return 0;
    }

    /**
     * Get WP core principal
     *
     * @return AAM_Framework_Proxy_Role|WP_User
     *
     * @access public
     * @version 6.0.0
     */
    public function getPrincipal()
    {
        return $this->_principal;
    }

    /**
     * Set WP core principal
     *
     * @param AAM_Framework_Proxy_Role|WP_User $principal
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function setPrincipal($principal)
    {
        $this->_principal = $principal;
    }

    /**
     * Get subject siblings
     *
     * @param array $siblings
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function setSiblings(array $siblings)
    {
        $this->_siblings = $siblings;
    }

    /**
     * Check if subject has siblings
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function hasSiblings()
    {
        return (count($this->_siblings) > 0);
    }

    /**
     * Get list of subject siblings
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getSiblings()
    {
        return $this->_siblings;
    }

    /**
     * Default placeholder for the verifying capability
     *
     * @param string $cap
     *
     * @return boolean
     *
     * @access public
     * @since 6.9.1
     */
    public function hasCapability($cap)
    {
        return false;
    }

    /**
     * Get AAM core object
     *
     * This method will instantiate requested AAM core object with pre-populated
     * access settings for the subject that requested the object.
     *
     * @param string  $type
     * @param mixed   $id
     * @param boolean $skipInheritance
     *
     * @return AAM_Core_Object
     *
     * @since 6.3.2 Added new hook `aam_initialized_{$type}_object_filter` to solve
     *              https://github.com/aamplugin/advanced-access-manager/issues/52
     * @since 6.1.0 Fixed the bug where initialize object was not cached correctly
     *              due to $skipInheritance flag
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.2
     */
    public function getObject($type, $id = null, $skipInheritance = false)
    {
        $suffix = ($skipInheritance ? '_direct' : '_full');

        // Check if there is an object with specified ID
        if (!isset($this->_objects[$type . $id . $suffix])) {
            $class_name = 'AAM_Core_Object_' . ucfirst($type);

            // If requested object is part of the core, instantiate it
            if (class_exists($class_name)) {
                $object = new $class_name($this, $id, $skipInheritance);
            } else {
                $object = apply_filters(
                    'aam_object_filter', null, $this, $type, $id, $skipInheritance
                );
            }

            if (is_a($object, 'AAM_Core_Object')) {
                // Kick in the inheritance chain if needed
                if ($skipInheritance === false) {
                    $this->inheritFromParent($object);
                }

                // Finally cache the object
                $this->_objects[$type . $id . $suffix] = apply_filters(
                    "aam_initialized_{$type}_object_filter", $object
                );
            }
        } else {
            $object = $this->_objects[$type . $id . $suffix];
        }

        return $object;
    }

    /**
     * Inherit access settings for provided object from the parent subject(s)
     *
     * @param AAM_Core_Object $object
     *
     * @return array
     *
     * @since 6.7.0 https://github.com/aamplugin/advanced-access-manager/issues/152
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.7.0
     */
    protected function inheritFromParent(AAM_Core_Object $object)
    {
        $subject = $this->getParent();

        if (is_a($subject, 'AAM_Core_Subject')) {
            $option = $subject->getObject(
                $object::OBJECT_TYPE,
                $object->getId()
            )->getOption();

            // Merge access settings if multi-roles option is enabled
            $multi = AAM::api()->getConfig('core.settings.multiSubject', false);

            if ($multi && $subject->hasSiblings()) {
                foreach ($subject->getSiblings() as $sibling) {
                    $option = $sibling->getObject(
                        $object::OBJECT_TYPE,
                        $object->getId()
                    )->mergeOption(
                        $option
                    );
                }
            }

            // Merge access settings while reading hierarchical chain
            $option = array_replace_recursive($option, $object->getOption());

            // Finally set the option for provided object
            $object->setOption($option);
        }

        return $object->getOption();
    }

    /**
     * Retrieve parent subject
     *
     * If there is no parent subject, return null
     *
     * @return AAM_Core_Subject|null
     *
     * @access public
     * @version 6.0.0
     */
    abstract public function getParent();

    /**
     * Update subject access option
     *
     * @param mixed  $value
     * @param string $object
     * @param mixed  $id
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function updateOption($value, $object, $id = null)
    {
        return AAM_Core_AccessSettings::getInstance()->set(
            $this->getOptionName($object, $id), $value
        )->save();
    }

    /**
     * Read subject access option
     *
     * @param string $object
     * @param mixed  $id
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function readOption($object, $id = null)
    {
        return AAM_Core_AccessSettings::getInstance()->get(
            $this->getOptionName($object, $id)
        );
    }

    /**
     * Delete subject access option
     *
     * @param string $object
     * @param mixed  $id
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function deleteOption($object, $id = null)
    {
        return AAM_Core_AccessSettings::getInstance()->delete(
            $this->getOptionName($object, $id)
        )->save();
    }

    /**
     * Compute access option name based on object type
     *
     * @param string $object
     * @param mixed  $id
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    public function getOptionName($object, $id)
    {
        $subjectId = $this->getId();

        $name  = static::UID . ($subjectId ? ".{$subjectId}" : '') . '.';
        $name .= $object . ($id ? ".{$id}" : '');

        return $name;
    }

    /**
     * Reset object cache
     *
     * Subject caches all instantiated object for performance reasons
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function flushCache()
    {
        $this->_objects = array();
    }

    /**
     * Reset settings
     *
     * @return boolean
     *
     * @access public
     * @since 6.9.6
     */
    public function reset()
    {
        $id = static::UID;

        if ($this->getId() !== null) {
            $id .= '.' . $this->getId();
        }

        return AAM_Core_AccessSettings::getInstance()->delete($id)->save();
    }

}