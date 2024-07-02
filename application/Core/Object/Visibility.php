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
 * @since 6.9.29 https://github.com/aamplugin/advanced-access-manager/issues/375
 * @since 6.9.23 https://github.com/aamplugin/advanced-access-manager/issues/347
 * @since 6.9.22 https://github.com/aamplugin/advanced-access-manager/issues/345
 * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/342
 * @since 6.1.0  Refactored implementation to fix merging bugs and improve inheritance
 *               mechanism
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.29
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
     * Align & Merge access controls
     *
     * This method makes sure that both data set have the same keys first and then
     * merges them
     *
     * @param array $incoming
     *
     * @return array
     *
     * @since 6.9.29 https://github.com/aamplugin/advanced-access-manager/issues/375
     * @since 6.9.21 Initial implementation of the method
     *
     * @access public
     * @version 6.9.29
     */
    public function mergeOption($incoming)
    {
        // The visibility object also passes the incoming object as the second
        // argument
        $object = func_get_arg(1);

        // Identifying all the keys that are missing in the $subject, however, present
        // in $this and align settings
        $base = $this->getOption();

        $diff = array_diff(array_keys($incoming), array_keys($base));

        if (count($diff)) {
            foreach($diff as $key) {
                $base[$key] = $this->getOptionByXpath($this, $key);
            }
        }

        $diff = array_diff(array_keys($base), array_keys($incoming));

        if (count($diff)) {
            foreach($diff as $key) {
                $incoming[$key] = $this->getOptionByXpath($object, $key);
            }
        }

        $merged = array();

        // Iterate over each unique key end merge settings accordingly
        foreach(array_keys($incoming) as $key) {
            $merged[$key] = AAM::api()->mergeSettings(
                (isset($incoming[$key]) ? $incoming[$key] : array()),
                (isset($base[$key]) ? $base[$key] : array()),
                AAM_Core_Object_Post::OBJECT_TYPE
            );
        }

        return $merged;
    }

    /**
     * Get set of access controls by path
     *
     * @param AAM_Core_Object $object
     * @param string          $xpath
     *
     * @return array
     *
     * @since 6.9.23 https://github.com/aamplugin/advanced-access-manager/issues/347
     * @since 6.9.22 https://github.com/aamplugin/advanced-access-manager/issues/345
     * @since 6.9.21 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.23
     */
    protected function getOptionByXPath($object, $xpath)
    {
        static $invocations = 0;

        $response = array();
        $option   = 'core.settings.' . $object::OBJECT_TYPE . '.mergeAlign.limit';
        $limit    = AAM::api()->configs()->get_config($option, 50);

        if ($invocations < $limit) {
            // First, let, parse the xpath and determine what object to fetch
            $parts = explode('/', $xpath);

            if ($parts[0] === 'post') {
                $attr = explode('|', $parts[1]);

                $response = $object->getSubject()->getObject(
                    'post', $attr[0]
                )->getOption();
            } else {
                $response = apply_filters(
                    'aam_visibility_get_object_by_key_filter',
                    $response,
                    $xpath,
                    $object
                );
            }

            $invocations++;
        } else {
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                'There are potentially too many access controls explicitly defined' .
                ' for Posts & Terms. Consider rethinking how you define access to ' .
                'content as scale or increase the merge-align limit',
                AAM_VERSION
            );
        }

        return $response;
    }

}