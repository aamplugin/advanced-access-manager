<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post object
 *
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
 * @since 6.1.0  Removed support for the $suppressFilters flag
 * @since 6.0.1  Added new method isDefined that is used to determine if access option
 *               is defined
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.31
 */
class AAM_Core_Object_Post extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'post';

    /**
     * WP Post object
     *
     * @var WP_Post
     *
     * @access private
     * @version 6.0.0
     */
    private $_post = null;

    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     * @param WP_Post|Int      $post
     *
     * @return void
     *
     * @since 6.1.0 Removed support for the $suppressFilters flag
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function __construct(AAM_Core_Subject $subject, $post)
    {
        $this->setSubject($subject);

        // Make sure that we are dealing with WP_Post object
        // This is done to remove redundant calls to the database on the backend view
        if (is_a($post, 'WP_Post')) {
            $this->setPost($post);
        } elseif (is_numeric($post)) {
            $this->setPost(get_post($post));
        }

        // Making sure that we actually have post, otherwise just initiate with dummy
        if (is_a($this->getPost(), 'WP_Post')) {
            $this->setId($this->getPost()->ID);
        } else {
            $this->setPost(new WP_Post((object) array('ID' => 0)));
            $this->setId(0);
        }

        $this->initialize();
    }

    /**
     * Get WP post property
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
        $post = $this->getPost();

        return (property_exists($post, $name) ? $post->$name : null);
    }

    /**
     * @inheritDoc
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
     * @since 6.1.0  Removed support for the $suppressFilters flag
     * @since 6.0.0  Initial implementation of the method
     *
     * @version 6.9.31
     */
    protected function initialize()
    {
        // Read direct access settings - those that are explicitly defined for the
        // post
        $option = $this->getSubject()->readOption(
            self::OBJECT_TYPE, $this->ID . '|' . $this->post_type
        );

        $this->setExplicitOption($option);

        // Trigger custom functionality that may populate the post access options
        // after initial setup. Typically is used by third party functionality and
        // premium AAM plugins.
        $this->setOption(
            apply_filters('aam_post_object_option_filter', $option, $this)
        );
    }

    /**
     * Set Post
     *
     * @param WP_Post|stdClass $post
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function setPost($post)
    {
        $this->_post = $post;
    }

    /**
     * Check if particular access property is enabled
     *
     * Examples of such a access property is "restricted", "hidden", etc.
     *
     * @param string $property
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function is($property)
    {
        $result = false;
        $option = $this->getOption();

        if (array_key_exists($property, $option)) {
            if (is_bool($option[$property])) {
                $result = $option[$property];
            } else {
                $result = !empty($option[$property]['enabled']);
            }
        }

        return $result;
    }

    /**
     * Determine if property is defined
     *
     * This is useful for managing access to commenting system
     *
     * @param string $property
     *
     * @return boolean
     *
     * @access public
     * @see AAM_Service_Content::initializeHooks
     *
     * @version 6.0.1
     */
    public function isDefined($property)
    {
        return array_key_exists($property, $this->getOption());
    }

    /**
     * Check if particular action is allowed
     *
     * This is alias for the AAM_Core_Object_Post::is($property) method and is used
     * only to improve code readability. Example of such action is "edit", "publish",
     * etc.
     *
     * @param string $property
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isAllowedTo($property)
    {
        // Normalize some names to improve code verbosity
        $lower_case = strtolower($property);

        if (in_array($lower_case, array('read', 'access', 'view', 'see'))) {
            $property = 'restricted';
        } elseif ($lower_case === 'list') {
            $property = 'hidden';
        } else if (in_array($lower_case, array('list_to_others', 'list_others'))) {
            $property = 'hidden_others';
        }

        return !$this->is($property);
    }

    /**
     * Check if particular access option is enabled
     *
     * This is alias for the AAM_Core_Object_Post::is($property) method and is used
     * only to improve code readability. Example of such action is "teaser",
     * "origin", etc.
     *
     * @param string $property
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function has($property)
    {
        return $this->is($property);
    }

    /**
     * Get WP Post
     *
     * @return WP_Post
     *
     * @access public
     * @version 6.0.0
     */
    public function getPost()
    {
        return $this->_post;
    }

    /**
     * Save access settings
     *
     * @return boolean
     *
     * @since 6.1.0 Using explicit options to store settings
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function save()
    {
        return $this->getSubject()->updateOption(
            $this->getExplicitOption(),
            self::OBJECT_TYPE,
            $this->ID . '|' . $this->post_type
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
            self::OBJECT_TYPE, $this->ID . '|' . $this->post_type
        );
    }

}