<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Base trait to represents AAM resource preference concept
 *
 * @package AAM
 * @version 7.0.0
 */
trait AAM_Framework_Resource_PreferenceTrait
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * Constructor
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __construct(
        AAM_Framework_AccessLevel_Interface $access_level
    ) {
        $this->_access_level = $access_level;

        // Initialize resource settings & extend with additional methods
        $this->initialize_resource($access_level);

        if (method_exists($this, 'initialize_hook')) {
            $this->initialize_hook();
        };
    }

    /**
     * Proxy methods to WordPress core instance
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @access public
     * @since 7.0.0
     */
    public function __call($name, $arguments)
    {
        $response = null;

        if (array_key_exists($name, $this->_extended_methods)) {
            $response = call_user_func_array(
                $this->_extended_methods[$name], $arguments
            );
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'Method does not exist',
                AAM_VERSION
            );
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function get_internal_id($serialize = false)
    {
        return null;
    }

    /**
     * Merge incoming settings
     *
     * @param array $incoming_settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function merge_settings($incoming_settings)
    {
        return array_merge_recursive($incoming_settings, $this->_settings);
    }

}