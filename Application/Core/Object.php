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
 * Abstract object class
 *
 * @package AAM
 * @version 6.0.0
 */
abstract class AAM_Core_Object
{

    /**
     * Core object slug
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = null;

    /**
     * Subject
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
     * @var mixed
     *
     * @access private
     * @version 6.0.0
     */
    private $_id = null;

    /**
     * Object options
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
     * Suppress any filters that may alter option
     *
     * This is used to suppress the inheritance chain that invokes when object has
     * hierarchical relationships.
     *
     * @var boolean
     *
     * @access private
     * @version 6.0.0
     */
    private $_suppressFilters = false;

    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     * @param mixed            $id
     * @param boolean          $setSuppressFilters
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function __construct(
        AAM_Core_Subject $subject, $id = null, $suppressFilters = false
    ) {
        $this->setSubject($subject);
        $this->setId($id);
        $this->setSuppressFilters($suppressFilters);

        $this->initialize();
    }

    /**
     * Initialize access settings
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
     * @param string $function
     * @param array  $args
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function __call($function, $args)
    {
        _doing_it_wrong(
            $function,
            sprintf(__('AAM object function %s is not defined', AAM_KEY), $function),
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
     * @version 5.0.0
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
     * @access public
     * @version 6.0.0
     */
    public function updateOptionItem($item, $value)
    {
        $option = $this->getOption();

        if (isset($option[$item]) && is_array($option[$item])) {
            $option[$item] = array_replace_recursive($option[$item], $value);
        } else {
            $option[$item] = $value;
        }

        $this->setOption($option);

        return $this;
    }

    /**
     * Set overwritten flat
     *
     * @param array $option
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function determineOverwritten($option)
    {
        $this->_overwritten    = !empty($option);
        $this->_explicitOption = $option;
    }

    /**
     * Determine if access settings are set explicitly for current subject
     *
     * @param string $property
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isExplicit($property)
    {
        $option   = $this->_explicitOption;
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
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        return $this->getSubject()->updateOption(
            $this->getOption(),
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

    /**
     * Suppress filters flag
     *
     * @param boolean $setSuppressFilters
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function setSuppressFilters($setSuppressFilters)
    {
        $this->_suppressFilters = $setSuppressFilters;
    }

    /**
     * Get suppress filters flag
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function suppressFilters()
    {
        return $this->_suppressFilters;
    }

}