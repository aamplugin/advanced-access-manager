<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Base trait to represents AAM preference concept
 *
 * @package AAM
 * @version 7.0.0
 */
trait AAM_Framework_Preference_BaseTrait
{

    /**
     * Reference to the access level
     *
     * @var AAM_Framework_AccessLevel_Interface
     * @access private
     *
     * @version 7.0.0
     */
    private $_access_level = null;

    /**
     * Collection of extended methods
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_extended_methods = [];

    /**
     * Preferences
     *
     * Array of final preferences. The final preferences are those that have been
     * properly inherited and merged.
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_preferences = [];

    /**
     * Explicit preferences (not inherited from parent access level)
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_explicit_preferences = [];

    /**
     * Constructor
     *
     * Initialize the preference container
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function __construct($access_level)
    {
        $this->_access_level = $access_level;

        // Extend access level with more methods
        $closures = apply_filters(
            'aam_framework_preference_methods_filter',
            $this->_extended_methods,
            $this
        );

        if (is_array($closures)) {
            foreach($closures as $name => $closure) {
                $closures[$name] = $closure->bindTo($this, $this);
            }

            $this->_extended_methods = $closures;
        }

        // Read explicitly defined settings from DB
        $preferences = AAM_Framework_Manager::_()->settings(
            $access_level
        )->get_setting($this->_get_settings_ns(), []);

        if (!is_array($preferences)) { // Deal with corrupted data
            $preferences = [];
        } else {
            $preferences = $this->_add_sys_attributes($preferences);
        }

        $this->_explicit_preferences = $preferences;

        // JSON Access Policy is deeply embedded in the framework, thus take it into
        // consideration during resource initialization
        if (AAM_Framework_Manager::_()->config->get('service.policies.enabled', true)) {
            $preferences = array_replace(
                $this->_add_sys_attributes($this->_apply_policy()),
                $preferences
            );
        }

        // Allow other implementations to influence set of preferences
        $preferences = apply_filters(
            'aam_init_preferences_filter',
            $preferences,
            $this
        );

        // Trigger inheritance mechanism
        $this->_preferences = array_replace(
            $this->_add_sys_attributes(
                $this->_inherit_from_parent(),
                [ '__inherited' => true ]
            ),
            $preferences
        );
    }

    /**
     * Get access level this resource is tight to
     *
     * @return AAM_Framework_AccessLevel_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function get_access_level()
    {
        return $this->_access_level;
    }

    /**
     * @inheritDoc
     */
    public function is_customized()
    {
        return !empty($this->_explicit_preferences);
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->_explicit_preferences = [];

        return AAM_Framework_Manager::_()->settings(
            $this->get_access_level()
        )->delete_setting($this->_get_settings_ns());
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->_preferences;
    }

    /**
     * @inheritDoc
     */
    public function set(array $preferences)
    {
        $this->_explicit_preferences = $preferences;
        $this->_preferences          = array_replace(
            $this->_preferences,
            $preferences
        );

        // Store changes in DB
        return AAM_Framework_Manager::_()->settings(
            $this->get_access_level()
        )->set_setting($this->_get_settings_ns(), $preferences);
    }

    /**
     * Get settings namespace
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_settings_ns()
    {
        return constant('static::TYPE');
    }

    /**
     * Inherit preferences from parent access level (if any)
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _inherit_from_parent()
    {
        $parent = $this->get_access_level()->get_parent();
        $result = [];

        if (is_a($parent, AAM_Framework_AccessLevel_Interface::class)) {
            if ($parent->has_siblings()) {
                $siblings = $parent->get_siblings();
            } else {
                $siblings = [];
            }

            // Getting preference from the parent access level
            $result  = $parent->get_preference(constant('static::TYPE'))->get();
            $manager = AAM_Framework_Manager::_();

            foreach ($siblings as $sibling) {
                $sibling_preferences = $sibling->get_preference(
                    constant('static::TYPE')
                )->get();

                $result = $manager->misc->merge_preferences(
                    $sibling_preferences,
                    $result
                );
            }
        }

        return $result;
    }

    /**
     * Apply preferences extracted from policies
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _apply_policy()
    {
        return apply_filters('aam_apply_policy_filter', [], $this);
    }

    /**
     * Add some system attributes to each permission
     *
     * @param array $data
     * @param array $additional [Optional]
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _add_sys_attributes($data, $additional = [])
    {
        if (!empty($data)) {
            $acl    = $this->get_access_level();
            $acl_id = $acl->get_id();

            $to_merge = [ '__access_level' => $acl::TYPE ];

            if (!empty($acl_id)) {
                $to_merge['__access_level_id'] = $acl_id;
            }

            $result = array_merge($data, $to_merge, $additional);
        } else {
            $result = [];
        }

        return $result;
    }

}