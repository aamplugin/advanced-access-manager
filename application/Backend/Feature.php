<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Backend Feature
 *
 * This class is used to hold the list of all registered UI features with few neat
 * methods to manipulate it.
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature
{

    /**
     * Collection of features
     *
     * @var array
     *
     * @access private
     * @version 6.0.0
     */
    static private $_features = array();

    /**
     * Register UI Feature
     *
     * @param object $feature
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public static function registerFeature($feature)
    {
        $response     = false;
        $access_level = AAM_Backend_AccessLevel::get_instance();

        // Determine correct AAM UI capability
        if (empty($feature->capability)) {
            $cap = 'aam_manager';
        } else {
            $cap = $feature->capability;
        }

        // Determine if minimum required options are enabled
        if (isset($feature->option)) {
            $show = self::isVisible($feature->option);
        } else {
            $show = true;
        }

        if (is_array($cap)) {
            $can = count(array_filter($cap, 'current_user_can')) > 0;
        } else {
            $can = current_user_can($cap);
        }

        if ($show && $can) {
            if (is_object($feature->view)) {
                self::$_features[get_class($feature->view)] = $feature;
            } elseif (!is_a($feature->view, Closure::class)) {
                self::$_features[$feature->view] = $feature;
                // Initialize view manage so it can register any necessary hooks
                $feature->view = new $feature->view($access_level);
            }

            $response = true;
        }

        return $response;
    }

    /**
     * Get feature view manager
     *
     * @param string $id
     *
     * @return object
     *
     * @access public
     * @version 6.0.0
     */
    public static function getFeatureView($id)
    {
        if (self::isFeatureRegistered($id)) {
            $view = self::$_features[$id]->view;
        } else {
            $view = null;
        }

        return $view;
    }

    /**
     * Check if feature is registered
     *
     * @param string $id
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public static function isFeatureRegistered($id)
    {
        return array_key_exists($id, self::$_features);
    }

    /**
     * Retrieve list of features
     *
     * Retrieve sorted list of featured based on current subject
     *
     * @param string $type
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public static function retrieveList($type)
    {
        $response     = [];
        $access_level = AAM_Backend_AccessLevel::get_instance()->get_access_level();

        foreach (self::$_features as $feature) {
            if ($feature->type === $type) {
                if (empty($feature->access_levels)
                    || in_array($access_level->type, $feature->access_levels, true)
                ) {
                    $response[] = self::initView($feature);
                }
            }
        }

        usort($response, function($feature_a, $feature_b) {
            $pos_a = (empty($feature_a->position) ? 9999 : $feature_a->position);
            $pos_b = (empty($feature_b->position) ? 9999 : $feature_b->position);

            if ($pos_a === $pos_b) {
                $response = 0;
            } else {
                $response = ($pos_a < $pos_b ? -1 : 1);
            }

            return $response;
        });

        return $response;
    }

    /**
     * Check if feature is visible
     *
     * There is a way to show/hide feature based on the option. For example some
     * features should be visible only when Backend Access options is enabled.
     *
     * @param string $options
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function isVisible($options)
    {
        $count = 0;

        foreach (explode(',', $options) as $option) {
            $count += AAM::api()->config->get($option, true);
        }

        return ($count > 0);
    }

    /**
     * Initiate the view controller
     *
     * @param object $feature
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function initView($feature)
    {
        if (is_string($feature->view)) {
            $feature->view = new $feature->view(
                AAM_Backend_AccessLevel::get_instance()
            );
        }

        return $feature;
    }

}