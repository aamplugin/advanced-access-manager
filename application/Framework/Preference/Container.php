<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Common preference container
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Preference_Container
implements AAM_Framework_Preference_Interface
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
     * Preference namespace
     *
     * The preferences' namespace
     *
     * @var string
     *
     * @access private
     * @version 7.0.0
     */
    private $_ns = null;

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
     * Resource preferences
     *
     * Array of final preferences. The final preferences are those that have been
     * properly inherited and merged.
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_preferences = [];

    /**
     * Explicit preferences (not inherited from parent access level)
     *
     * When resource is initialized, it already contains the final set of preferences,
     * inherited from the parent access levels. This property contains preferences
     * that are explicitly defined for current resource.
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_explicit_preferences = [];

    /**
     * Constructor
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @para
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __construct(
        AAM_Framework_AccessLevel_Interface $access_level, $ns
    ) {
        $this->_access_level = $access_level;
        $this->_ns           = $ns;

        // Making sure that namespace is defined
        if (empty($ns) || !is_string($ns)) {
            throw new InvalidArgumentException(
                'Preference namespace has to be provided'
            );
        }

        // Initialize preference container & extend with additional methods
        $this->initialize_container($access_level);

        if (method_exists($this, 'initialize_hook')) {
            $this->initialize_hook();
        };
    }

    /**
     * Initialize the container and extend it with additional methods
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_container(
        AAM_Framework_AccessLevel_Interface $access_level
    ) {
        // Read explicitly defined settings from DB
        $settings = AAM::api()->settings([
            'access_level' => $access_level
        ])->get_setting($this->get_ns(), []);

        if (!empty($settings)) {
            $this->_explicit_preferences = $settings;
        }

        // Allow other implementations to modify defined settings
        $this->_preferences = apply_filters(
            'aam_initialize_preference_filter',
            $settings,
            $this
        );

        // Extend access level with more methods
        $closures = apply_filters(
            'aam_framework_preference_methods_filter',
            [],
            $this
        );

        if (is_array($closures)) {
            foreach ($closures as $name => $closure) {
                $closures[$name] = $closure->bindTo($this, $this);
            }

            $this->_extended_methods = $closures;
        }
    }

    /**
     * @inheritDoc
     */
    public function get_access_level()
    {
        return $this->_access_level;
    }

    /**
     * @inheritDoc
     */
    public function get_ns()
    {
        return $this->_ns;
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
                $this->_extended_methods[$name],
                $arguments
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
    public function get_preferences($explicit_only = false)
    {
        if ($explicit_only) {
            $result = $this->_explicit_preferences;
        } else {
            $result = $this->_preferences;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function set_preferences(array $preferences, $explicit_only = true)
    {
        if ($explicit_only) {
            // First, set the explicit preferences
            $this->_explicit_preferences = $preferences;

            // Overriding the final set of preferences
            $this->_preferences = array_merge($this->_preferences, $preferences);

            // Store changes in DB
            $result = AAM::api()->settings([
                'access_level' => $this->get_access_level()
            ])->set_setting($this->get_ns(), $preferences);
        } else {
            $this->_preferences = $preferences;
            $result             = true;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function get_preference($preference, $default = null)
    {
        if (array_key_exists($preference, $this->_preferences)) {
            $result = $this->_preferences[$preference];
        } else {
            $result = $default;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function set_preference($preference, $value)
    {
        return $this->set_preferences(array_merge(
            $this->_explicit_preferences,
            [ $preference => $value ]
        ));
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

        return AAM::api()->settings([
            'access_level' => $this->get_access_level()
        ])->delete_setting($this->get_ns());
    }

}