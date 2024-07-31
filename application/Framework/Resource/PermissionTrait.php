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
 * AAM Resource is a website resource that you manage access to for users, roles or
 * visitors. For example, it can be any website post, page, term, backend menu etc.
 *
 * On another hand, AAM Resource is a “container” with specific settings for any user,
 * role or visitor. For example login, logout redirect, default category or access
 * denied redirect rules.
 *
 * @package AAM
 * @version 7.0.0
 */
trait AAM_Framework_Resource_PermissionTrait
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * Resource internal identifier
     *
     * Some resource may have unique identifier like each post or term has unique
     * auto-incremented ID, or post type - unique slug. Other resource, like menu,
     * toolbar, do not have unique.
     *
     * @var int|string|array|null
     *
     * @access private
     * @version 7.0.0
     */
    private $_internal_id = null;

    /**
     * WordPress core instance of the resource
     *
     * @var mixed
     *
     * @access private
     * @version 7.0.0
     */
    private $_core_instance = null;

    /**
     * Constructor
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param mixed|null                          $internal_id
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __construct(
        AAM_Framework_AccessLevel_Interface $access_level, $internal_id = null
    ) {
        $this->_access_level = $access_level;
        $this->_internal_id  = $internal_id;

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
        } elseif (is_object($this->_core_instance)) {
            $response = call_user_func_array(
                array($this->_core_instance, $name), $arguments
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
     * Get a property of a core instance
     *
     * @param string $name
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function __get($name)
    {
        $response = null;

        if (is_object($this->_core_instance)
            && property_exists($this->_core_instance, $name)
        ) {
            $response = $this->_core_instance->{$name};
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'Property does not exist',
                AAM_VERSION
            );
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function get_internal_id($serialize = true)
    {
        if (is_array($this->_internal_id) && $serialize) {
            $result = implode('|', array_values($this->_internal_id));
        } else {
            $result = $this->_internal_id;
        }

        return $result;
    }

    /**
     * Return the list of all permissions
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permissions()
    {
        return $this->get_settings();
    }

    /**
     * Merge incoming settings
     *
     * Depending on the resource type, different strategies may be applied to merge
     * settings
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
        return $this->_merge_binary_settings($incoming_settings);
    }

    /**
     * Merge binary set of settings
     *
     * The binary set of settings are typically those that have a set with true/false
     * values. Some values may be complex values. The complex values are those that
     * have value as array. In this case there is an assumption that the "enabled"
     * property is set that indicated if value (aka rule) is enforced or not.
     *
     * @param array $incoming_settings
     * @param array $base_settings
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _merge_binary_settings(
        $incoming_settings, $base_settings = null
    ) {
        $merged = [];
        $config = AAM_Framework_Manager::configs();

        // If base settings are not specified, use resource's settings instead. This
        // is implemented this way because some resources may want to manipulate the
        // settings' set before merging (see identity governance resource for
        // example)
        $base_settings = is_null($base_settings) ? $this->_settings : $base_settings;

        // If preference is not explicitly defined, fetch it from the AAM configs
        $preference = $config->get_config(
            'core.settings.merge.preference'
        );

        $preference = $config->get_config(
            'core.settings.' . constant('static::TYPE') . '.merge.preference',
            $preference
        );

        // First get the complete list of unique keys
        $keys = array_keys($incoming_settings);
        foreach (array_keys($base_settings) as $key) {
            if (!in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        foreach ($keys as $key) {
            // There can be only two types of preferences: "deny" or "allow". Based
            // on that, choose access settings that have proper effect as following:
            //
            //   - If set1 and set2 have two different preferences, get the one that
            //     has correct preference;
            //   - If set1 and set2 have two the same preferences, choose the set2
            //   - If only set1 has access settings, use set1 as-is
            //   - If only set2 has access settings, use set2 as-is
            //   - If set1 and set2 have different effect than preference, choose
            //     set2
            $effect1 = $this->_get_setting_effect($incoming_settings, $key);
            $effect2 = $this->_get_setting_effect($base_settings, $key);
            $effect  = ($preference === 'deny');

            // Access Option is either boolean true or array with "enabled" key
            // set as boolean true
            if ($effect1 === $effect2) { // both equal
                $merged[$key] = $base_settings[$key];
            } elseif ($effect1 === $effect) { // set1 matches preference
                $merged[$key] = $incoming_settings[$key];
            } elseif ($effect2 === $effect) { // set2 matches preference
                $merged[$key] = $base_settings[$key];
            } else {
                if ($preference === 'allow') {
                    if (isset($base_settings[$key])) {
                        $option = $base_settings[$key];
                    } else {
                        $option = $incoming_settings[$key];
                    }

                    if (is_array($option)) {
                        $option['enabled'] = false;
                    } else {
                        $option = false;
                    }

                    $merged[$key] = $option;
                } elseif (is_null($effect1)) {
                    $merged[$key] = $base_settings[$key];
                } elseif (is_null($effect2)) {
                    $merged[$key] = $incoming_settings[$key];
                }
            }
        }

        return $merged;
    }

    /**
     * Determine correct access option effect
     *
     * There can be two possible types of the access settings: straight boolean and
     * array with "enabled" flag. If provided key is not a part of the access options,
     * the null is returned, otherwise boolean true of false.
     *
     * @param array  $settings
     * @param string $key
     *
     * @return null|boolean
     *
     * @access protected
     * @version 7.0.0
     */
    private function _get_setting_effect($settings, $key)
    {
        $effect = null; // nothing is defined

        if (array_key_exists($key, $settings)) {
            if (is_array($settings[$key]) && isset($settings[$key]['enabled'])) {
                $effect = !empty($settings[$key]['enabled']);
            } else {
                $effect = !empty($settings[$key]);
            }
        }

        return $effect;
    }

}