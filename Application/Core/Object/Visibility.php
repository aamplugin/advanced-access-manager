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
 * Post visibility object
 *
 * @package AAM
 * @version 6.0.0
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
     * @version 6.0.0
     */
    protected function initialize()
    {
        $posts = $this->getSubject()->readOption(AAM_Core_Object_Post::OBJECT_TYPE);

        foreach ($posts as $id => $settings) {
            $this->pushOptions('post', $id, $settings);
        }

        if ($this->suppressFilters() === false) {
            // Initialize post visibility option. This hooks is used by Access Policy
            // service as well as Plus Package to populate visibility list
            do_action('aam_visibility_object_init_action', $this);
        }
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
     * @access public
     * @version 6.0.0
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

        if (!isset($option[$object][$id])) {
            $option[$object][$id] = $filtered;
        } else {
            $option[$object][$id] = array_replace($filtered, $option[$object][$id]);
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
     * @access public
     * @version 6.0.0
     */
    public function getSegment($segment)
    {
        $option = $this->getOption();

        return (isset($option[$segment]) ? $option[$segment] : array());
    }

    /**
     * Merge visibility settings
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
            $options, $this->getOption(), AAM_Core_Object_Post::OBJECT_TYPE
        );
    }

}