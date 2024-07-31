<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Base trait to represents AAM resource concept
 *
 * AAM Resource is a “container” with a set of settings for a given access level.
 * This is an important abstraction layer for the settings inheritance mechanism.
 * Depending on the type of resource, different merging algorithm is applied, however,
 * the general concept is simple - lower access level inherits settings from the
 * higher and has full liberty to override any inherited settings.
 *
 * @package AAM
 * @version 7.0.0
 */
trait AAM_Framework_Resource_BaseTrait
{

    /**
     * Reference to the access level
     *
     * @var AAM_Framework_AccessLevel_Interface
     *
     * @access private
     * @version 7.0.0
     */
    private $_access_level = null;

    /**
     * Collection of extended methods
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_extended_methods = [];

    /**
     * Resource settings
     *
     * Array of final settings. The final access settings are those that have been
     * properly inherited and merged.
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_settings = [];

    /**
     * Explicit settings (not inherited from parent access level)
     *
     * When resource is initialized, it already contains the final set of the settings,
     * inherited from the parent access levels. This properly contains access settings
     * that are explicitly defined for current resource.
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_explicit_settings = [];

    /**
     * Inherited settings from parent access level (if applicable)
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_inherited_settings = [];

    /**
     * Initialize the resource settings and extend it with additional methods
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_resource(
        AAM_Framework_AccessLevel_Interface $access_level
    ) {
        // Read explicitly defined settings from DB
        $settings = AAM_Framework_Manager::settings([
            'access_level' => $access_level
        ])->get_setting(constant('static::TYPE'), []);

        if (!empty($settings)) {
            $this->_explicit_settings = $settings;
        }

        // Allow other implementations to modify defined settings
        $this->_settings = apply_filters(
            'aam_init_' . constant('static::TYPE') . '_resource_settings_filter',
            $settings,
            $this
        );

        // Extend access level with more methods
        $closures = apply_filters('aam_framework_resource_methods_filter', [], $this);

        if (is_array($closures)) {
            foreach($closures as $name => $closure) {
                $closures[$name] = $closure->bindTo($this, $this);
            }

            $this->_extended_methods = $closures;
        }
    }

    /**
     * Get access level this resource is tight to
     *
     * @return AAM_Framework_AccessLevel_Interface
     *
     * @access public
     * @version 7.0.0
     */
    public function get_access_level()
    {
        return $this->_access_level;
    }

    /**
     * Get the collection of resource settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_settings()
    {
        return $this->_settings;
    }

    /**
     * Set resource settings
     *
     * @param array $settings
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function set_settings(array $settings)
    {
        $this->_settings = $settings;
    }

    /**
     * Get explicitly defined settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_explicit_settings()
    {
        return $this->_explicit_settings;
    }

    /**
     * Set explicit settings
     *
     * @param array $settings
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function set_explicit_settings(array $settings)
    {
        // First, settings the explicit settings
        $this->_explicit_settings = $settings;

        // Overriding the final set of settings
        $this->_settings = array_merge($this->_settings, $settings);

        // Store changes in DB
        return AAM_Framework_Manager::settings([
            'access_level' => $this->get_access_level()
        ])->set_setting(constant('static::TYPE'), $settings);
    }

    /**
     * Set one explicit setting
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function set_explicit_setting($key, $value)
    {
        $settings = $this->_explicit_settings;

        // Adding the setting
        $settings[$key] = $value;

        // Store the settings
        $result = $this->set_explicit_settings($settings);

        // Also override the final settings
        // TODO: This may not work correctly as it does not merge
        //       Entertain the idea of inherited_settings
        if ($result) {
            $this->_settings[$key] = $value;
        }

        return $result;
    }

    /**
     * Get setting by key
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function get_setting($key, $default = null)
    {
        if (array_key_exists($key, $this->_settings)) {
            $result = $this->_settings[$key];
        } else {
            $result = $default;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function is_overwritten($key = null)
    {
        if (is_null($key)) {
            $result = !empty($this->_explicit_settings);
        } else {
            $result = array_key_exists($key, $this->_explicit_settings);
        }

        return $result;
    }

    /**
     * Reset explicitly defined settings
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function reset()
    {
        $this->_explicit_settings = [];

        return AAM_Framework_Manager::settings([
            'access_level' => $this->get_access_level()
        ])->delete_setting(constant('static::TYPE'));
    }

}