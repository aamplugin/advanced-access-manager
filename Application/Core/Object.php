<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract class that represents AAM object concept
 *
 * AAM Object is a website resource that you manage access to for users, roles or
 * visitors. For example, it can be any website post, page, term, backend menu etc.
 *
 * On another hand, AAM Object is a “container” with specific settings for any user,
 * role or visitor. For example login, logout redirect, default category or access
 * denied redirect rules.
 *
 * @since 6.3.3 Change visibility level for the setExplicitOption method
 * @since 6.1.0 Significant improvement to the inheritance mechanism. Documented
 *              the class
 * @since 6.0.5 Added `getExplicitOption` method
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.3.3
 */
abstract class AAM_Core_Object
{

    /**
     * Core object slug
     *
     * The slug should be unique identifier for the type of object (e.g. menu, post)
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = null;

    /**
     * Subject
     *
     * Current subject access settings belong to
     *
     * @var AAM_Core_Subject
     *
     * @access private
     * @version 6.0.0
     */
    private $_subject = null;

    /**
     * Object Id
     *
     * Some objects may have unique identifier like each post or term has unique
     * auto-incremented ID, or post type - unique slug. Other objects, like menu,
     * toolbar, do not have unique.
     *
     * @var int|string|null
     *
     * @access private
     * @version 6.0.0
     */
    private $_id = null;

    /**
     * Object access options
     *
     * Array of access options or settings. Depending on object, the structure of
     * options may vary. Typically it is an associated array of key/value pairs,
     * however in some cases it is multi-dimensional array of settings.
     *
     * @var array
     *
     * @access private
     * @version 6.0.0
     */
    private $_option = array();

    /**
     * Explicit options (not inherited from parent subjects)
     *
     * When object is obtained through AAM_Core_Subject::getObject method, it already
     * contains the final set of the settings, inherited from the parent subjects.
     * This properly contains access settings that are explicitly defined for current
     * subject.
     *
     * @var array
     *
     * @access private
     * @version 6.0.0
     */
    private $_explicitOption = array();

    /**
     * Overwritten indicator
     *
     * If settings for specific object were detected before inheritance mechanism
     * kicked off, then it is considered overwritten
     *
     * @var boolean
     *
     * @access private
     * @version 6.0.0
     */
    private $_overwritten = false;

    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject Requested subject
     * @param mixed            $id      Object ID if applicable
     *
     * @return void
     *
     * @since 6.1.0 Removed $suppressFilters param
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.0.0
     */
    public function __construct(AAM_Core_Subject $subject, $id = null)
    {
        $this->setSubject($subject);
        $this->setId($id);
        $this->initialize();
    }

    /**
     * Initialize access options
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    abstract protected function initialize();

    /**
     * Fallback to avoid any issues with previous versions
     *
     * If DEBUG mode is enabled, the error message states that invoking method does
     * not exist
     *
     * @param string $function Invoking method
     * @param array  $args     Method's arguments
     *
     * @return void
     *
     * @since 6.1.0 Do not localize internal error message
     * @since 6.0.0 Initial implementation of the method
     *
     * @see _doing_it_wrong
     * @access public
     * @version 6.1.0
     */
    public function __call($function, $args)
    {
        _doing_it_wrong(
            $function,
            sprintf('AAM object function %s is not defined', $function),
            AAM_VERSION
        );
    }

    /**
     * Set current subject
     *
     * Either it is User, Role, Visitor or Default
     *
     * @param AAM_Core_Subject $subject
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function setSubject(AAM_Core_Subject $subject)
    {
        $this->_subject = $subject;
    }

    /**
     * Get current Subject
     *
     * @return AAM_Core_Subject
     *
     * @access public
     * @version 6.0.0
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * Set current object Id
     *
     * @param int|string $id
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
     * Get current object Id
     *
     * @return int|string
     *
     * @access public
     * @version 6.0.0
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set object options
     *
     * @param array $option
     *
     * @return AAM_Core_Object
     *
     * @access public
     * @version 6.0.0
     */
    public function setOption(array $option)
    {
        $this->_option = $option;

        return $this;
    }

    /**
     * Get object options
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getOption()
    {
        return $this->_option;
    }

    /**
     * Get specific access property
     *
     * @param string $property
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function get($property, $default = null)
    {
        $option = $this->getOption();

        $chunks = explode('.', $property);
        $value  = (isset($option[$chunks[0]]) ? $option[$chunks[0]] : null);

        foreach (array_slice($chunks, 1) as $chunk) {
            if (isset($value[$chunk])) {
                $value = $value[$chunk];
            } else {
                $value = $default;
                break;
            }
        }

        return (is_null($value) ? $default : $value);
    }

    /**
     * Merge options based on merging preferences
     *
     * @param array $options
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function mergeOption($options)
    {
        return AAM::api()->mergeSettings(
            $options,
            $this->getOption(),
            static::OBJECT_TYPE
        );
    }

    /**
     * Update single option item
     *
     * @param string $item
     * @param mixed  $value
     *
     * @return AAM_Core_Object
     *
     * @since 6.1.0 Using explicitOptions to add new access setting instead of
     *              final options
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function updateOptionItem($item, $value)
    {
        $option = $this->getExplicitOption();

        if (isset($option[$item]) && is_array($option[$item])) {
            $option[$item] = array_replace_recursive($option[$item], $value);
        } else {
            $option[$item] = $value;
        }

        // Override current set of final options to keep consistency
        $this->setOption(array_replace_recursive($this->getOption(), $option));

        $this->setExplicitOption($option);

        return $this;
    }

    /**
     * Set overwritten flat
     *
     * @param array $option
     *
     * @return void
     *
     * @since 6.1.0 Using explicitOptions to determine override flag instead of
     *              final options
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function determineOverwritten($option)
    {
        $this->_overwritten    = !empty($option);
        $this->setExplicitOption(is_array($option) ? $option : array());
    }

    /**
     * Determine if access settings are set explicitly for current subject
     *
     * @param string $property
     *
     * @return boolean
     *
     * @since 6.0.5 Changed the way explicit option is fetched
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.0.5
     */
    public function isExplicit($property)
    {
        $option   = $this->getExplicitOption();
        $explicit = true;

        $chunks = explode('.', $property);
        $value  = (isset($option[$chunks[0]]) ? $option[$chunks[0]] : null);

        foreach (array_slice($chunks, 1) as $chunk) {
            if (isset($value[$chunk])) {
                $value = $value[$chunk];
            } else {
                $explicit = false;
                break;
            }
        }

        return $explicit;
    }

    /**
     * Get explicit option
     *
     * @return array
     *
     * @access public
     * @version 6.0.5
     */
    public function getExplicitOption()
    {
        return $this->_explicitOption;
    }

    /**
     * Set explicit object option
     *
     * @param array $option
     *
     * @return void
     *
     * @since 6.3.3 Changed the method to be public
     * @since 6.1.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.3.3
     */
    public function setExplicitOption($option)
    {
        $this->_explicitOption = $option;
    }

    /**
     * Check if options are overwritten
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isOverwritten()
    {
        return $this->_overwritten;
    }

    /**
     * Save access settings
     *
     * @return boolean
     *
     * @since 6.1.0 Using explicitOptions to save access setting instead of
     *              final options
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function save()
    {
        return $this->getSubject()->updateOption(
            $this->getExplicitOption(),
            static::OBJECT_TYPE,
            $this->getId()
        );
    }

    /**
     * Reset access settings
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function reset()
    {
        return $this->getSubject()->deleteOption(
            static::OBJECT_TYPE,
            $this->getId()
        );
    }

}