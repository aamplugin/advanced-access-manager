<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core API gateway
 *
 * @since 6.9.6 https://github.com/aamplugin/advanced-access-manager/issues/249
 * @since 6.1.0 Significant improvement of the inheritance mechanism
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.6
 */
final class AAM_Core_Gateway
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * Prevent from fatal errors
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function __call($name, $arguments)
    {
        _doing_it_wrong(
            __CLASS__ . '::' . __METHOD__,
            "The method {$name} is not defined in the AAM API",
            AAM_VERSION
        );
    }

    /**
     * Get AAM configuration option
     *
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function getConfig($option, $default = null)
    {
        return AAM_Core_Config::get($option, $default);
    }

    /**
     * Update AAM configuration option
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function updateConfig($option, $value)
    {
        return AAM_Core_Config::set($option, $value);
    }

    /**
     * Delete AAM configuration option
     *
     * @param string $option
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function deleteConfig($option)
    {
        return AAM_Core_Config::delete($option);
    }

    /**
     * Get user
     *
     * If no $id specified, current user will be returned
     *
     * @param int $id
     *
     * @return AAM_Core_Subject
     *
     * @access public
     * @version 6.0.0
     */
    public function getUser($id = null)
    {
        if (!empty($id)) {
            $user = new AAM_Core_Subject_User($id);
            $user->initialize();
        } else {
            $user = AAM::getUser();
        }

        return $user;
    }

    /**
     * Get role subject
     *
     * @param string $id
     *
     * @return AAM_Core_Subject_Role
     *
     * @access public
     * @version 6.0.0
     */
    public function getRole($id)
    {
        return new AAM_Core_Subject_Role($id);
    }

    /**
     * Get visitor subject
     *
     * @return AAM_Core_Subject_Visitor
     *
     * @access public
     * @version 6.0.0
     */
    public function getVisitor()
    {
        if (is_user_logged_in()) {
            $visitor = new AAM_Core_Subject_Visitor();
        } else {
            $visitor = AAM::getUser();
        }

        return $visitor;
    }

    /**
     * Get default subject
     *
     * @return AAM_Core_Subject_Default
     *
     * @access public
     * @version 6.0.0
     */
    public function getDefault()
    {
        return AAM_Core_Subject_Default::getInstance();
    }

    /**
     * Log any critical message
     *
     * @param string $message
     * @param string $markers...
     *
     * @access public
     * @version 6.0.0
     */
    public function log()
    {
        call_user_func_array('AAM_Core_Console::add', func_get_args());
    }

    /**
     * Prepare Access Policy manager but only if service is enabled
     *
     * @param AAM_Core_Subject $subject
     * @param boolean          $skipInheritance
     *
     * @return AAM_Core_Policy_Manager|null
     *
     * @since 6.1.0 Added $skipInheritance flag to insure proper settings inheritance
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function getAccessPolicyManager(
        AAM_Core_Subject $subject = null, $skipInheritance = false
    ) {
        if (is_null($subject)) {
            $subject = AAM::getUser();
        }

        if (AAM_Core_Config::get(AAM_Service_AccessPolicy::FEATURE_FLAG, true)) {
            $manager = AAM_Core_Policy_Factory::get($subject, $skipInheritance);
        } else {
            $manager = null;
        }

        return $manager;
    }

    /**
     * Reset all AAM settings and configurations
     *
     * @return void
     *
     * @access public
     *
     * @version 6.9.6
     */
    public function reset()
    {
        AAM_Core_API::clearSettings();
    }

    /**
     * Merge two set of access settings into one
     *
     * The merging method also takes in consideration the access settings preference
     * defined in ConfigPress
     *
     * @param array  $set1
     * @param array  $set2
     * @param string $objectType
     * @param string $preference
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function mergeSettings($set1, $set2, $objectType, $preference = null)
    {
        $merged   = array();

        // If preference is not explicitly defined, fetch it from the AAM configs
        if (is_null($preference)) {
            $preference = $this->getConfig(
                "core.settings.{$objectType}.merge.preference",
                'deny'
            );
        }

        // first get the complete list of unique keys
        $keys = array_keys($set1);
        foreach (array_keys($set2) as $key) {
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
            $effect1 = $this->computeAccessOptionEffect($set1, $key);
            $effect2 = $this->computeAccessOptionEffect($set2, $key);
            $effect  = ($preference === 'deny');

            // Access Option is either boolean true or array with "enabled" key
            // set as boolean true
            if ($effect1 === $effect2) { // both equal
                $merged[$key] = $set2[$key];
            } elseif ($effect1 === $effect) { // set1 matches preference
                $merged[$key] = $set1[$key];
            } elseif ($effect2 === $effect) { // set2 matches preference
                $merged[$key] = $set2[$key];
            } else {
                if ($preference === 'allow') {
                    $option = isset($set2[$key]) ? $set2[$key] : $set1[$key];
                    if (is_array($option)) {
                        $option['enabled'] = false;
                    } else {
                        $option = false;
                    }
                    $merged[$key] = $option;
                } elseif (is_null($effect1)) {
                    $merged[$key] = $set2[$key];
                } elseif (is_null($effect2)) {
                    $merged[$key] = $set1[$key];
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
     * @param array  $opts
     * @param string $key
     *
     * @return null|boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function computeAccessOptionEffect($opts, $key)
    {
        $effect = null; // nothing is defined

        if (isset($opts[$key])) {
            $effect = is_array($opts[$key]) ? $opts[$key]['enabled'] : $opts[$key];
        }

        return $effect;
    }

}