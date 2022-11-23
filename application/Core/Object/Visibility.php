<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post visibility object
 *
 * @since 6.1.0 Refactored implementation to fix merging bugs and improve inheritance
 *              mechanism
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.1.0
 */
class AAM_Core_Object_Visibility extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'visibility';

    /**
     * List of properties that are responsible for visibility
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected $accessProperties = array();

    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     * @param mixed            $id
     *
     * @return void
     *
     * @since 6.1.0 Removed support for the $suppressFilters flag
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function __construct(AAM_Core_Subject $subject, $id = null)
    {
        $this->setSubject($subject);
        $this->setId($id);

        // Determine post access properties that are responsible for the post
        // visibility
        $this->accessProperties = apply_filters(
            'aam_visibility_options_filter', array('hidden')
        );

        // Initialize the object
        $this->initialize();
    }

    /**
     * @inheritDoc
     *
     * @since 6.1.0 Removed support for the $suppressFilters flag
     * @since 6.0.0 Initial implementation of the method
     *
     * @version 6.1.0
     */
    protected function initialize()
    {
        $posts = $this->getSubject()->readOption(AAM_Core_Object_Post::OBJECT_TYPE);

        foreach ($posts as $id => $settings) {
            $this->pushOptions('post', $id, $settings);
        }

        // Initialize post visibility option. This hooks is used by Access Policy
        // service as well as Complete Package to populate visibility list
        do_action('aam_visibility_object_init_action', $this);
    }

    /**
     * Push visibility option to the registry
     *
     * @param string $object
     * @param mixed  $id
     * @param array  $options
     *
     * @return array
     *
     * @since 6.1.0 Changed the way visibility options are indexed (used to be as
     *              multi-dimensional array and now it is key/value pairs)
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function pushOptions($object, $id, $options)
    {
        $option      = $this->getOption();
        $filtered    = array();

        foreach ($options as $key => $value) {
            if (in_array($key, $this->accessProperties, true)) {
                $filtered[$key] = $value;
            }
        }

        if (empty($filtered)) {
            $filtered = array_combine(
                $this->accessProperties,
                array_fill(0, count($this->accessProperties), false)
            );
        }

        if (!isset($option["{$object}/{$id}"])) {
            $option["{$object}/{$id}"] = $filtered;
        } else {
            $option["{$object}/{$id}"] = array_replace(
                $filtered, $option["{$object}/{$id}"]
            );
        }
        $this->setOption($option);

        return $filtered;
    }

    /**
     * Get visibility segment
     *
     * @param string $segment
     *
     * @return array
     *
     * @since 6.1.0 Changed the way visibility options are fetched (used to be as
     *              multi-dimensional array and now it is key/value pairs)
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function getSegment($segment)
    {
        $response = array();

        foreach($this->getOption() as $key => $value) {
            if (strpos($key, "{$segment}/") === 0) {
                $response[str_replace("{$segment}/", '', $key)] = $value;
            }
        }

        return $response;
    }

    /**
     * Merge visibility settings
     *
     * @param array $options
     *
     * @return array
     *
     * @since 6.1.0 Fixed bug with incorrectly merged settings for users with multiple
     *              roles
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function mergeOption($options)
    {
        $these_options = $this->getOption();
        $keys          = array_unique(array_merge(
            array_keys($options), array_keys($this->getOption())
        ));

        $merged = array();

        // Iterate over each unique key end merge settings accordingly
        foreach($keys as $key) {
            $merged[$key] = AAM::api()->mergeSettings(
                (isset($options[$key]) ? $options[$key] : array()),
                (isset($these_options[$key]) ? $these_options[$key] : array()),
                AAM_Core_Object_Post::OBJECT_TYPE
            );
        }

        return $merged;
    }

}