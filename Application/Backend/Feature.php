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
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature {

    /**
     * Collection of features
     *
     * @var array
     *
     * @access private
     * @static
     */
    static private $_features = array();

    /**
     * Register UI Feature
     *
     * @param stdClass $feature
     *
     * @return boolean
     *
     * @access public
     * @static
     */
    public static function registerFeature(stdClass $feature) {
        $response = false;

        // Determine correct AAM UI capability
        if (empty($feature->capability)){
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

        // Determine that current user has enough level to manage requested subject
        $allowed = AAM_Backend_Subject::getInstance()->isAllowedToManage();
        
        if ($show && $allowed && current_user_can($cap)) {
            self::$_features[] = $feature;
            $response = true;
        }

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
     */
    protected static function isVisible($options) {
        $count = 0;
        
        foreach(explode(',', $options) as $option) {
            $count += AAM_Core_Config::get($option, true);
        }
        
        return ($count > 0);
    }

    /**
     * Initiate the Controller
     *
     * @param stdClass $feature
     *
     * @return stdClass
     *
     * @access public
     * @static
     */
    public static function initView(stdClass $feature){
        if (is_string($feature->view)){
            $feature->view = new $feature->view(AAM_Backend_Subject::getInstance());
        }

        return $feature;
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
     * @static
     */
    public static function retrieveList($type) {
        $response = array();
        
        $subject = AAM_Backend_Subject::getInstance()->getUID();
        foreach (self::$_features as $feature) {
            $ftype = (!empty($feature->type) ? $feature->type : 'main'); //TODO - legacy Nov 2018
            if ($ftype === $type 
                    && (empty($feature->subjects) || in_array($subject, $feature->subjects, true))) {
                $response[] = self::initView($feature);
            }
        }
        usort($response, 'AAM_Backend_Feature::reorder');

        return $response;
    }

    /**
     * Order list of features
     *
     * Reorganize the list based on "position" attribute
     *
     * @param array $features
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function reorder($feature_a, $feature_b){
        $pos_a = (empty($feature_a->position) ? 9999 : $feature_a->position);
        $pos_b = (empty($feature_b->position) ? 9999 : $feature_b->position);

        if ($pos_a === $pos_b){
            $response = 0;
        } else {
            $response = ($pos_a < $pos_b ? -1 : 1);
        }

        return $response;
    }

}