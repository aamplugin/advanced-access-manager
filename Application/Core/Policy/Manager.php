<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core policy manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @since AAM v5.7.2
 */
final class AAM_Core_Policy_Manager {
    
    /**
     * Single instance of itself
     * 
     * @var AAM_Core_Policy_Manager 
     * 
     * @access private
     * @static
     */
    private static $_instance = null;
    
    /**
     * Policy core object
     * 
     * @var AAM_Core_Object_Policy
     * 
     * @access protected 
     */
    protected $policyObject;
    
    /**
     * Constructor
     * 
     * @access protected
     * 
     * @return void
     */
    protected function __construct() {
        $this->policyObject = AAM::getUser()->getObject('policy');
    }
    
    /**
     * Find all the matching policies
     * 
     * @param string           $s       RegEx
     * @param AAM_Core_Subject $subject Subject to search for
     * 
     * @return array
     * 
     * @access public
     */
    public function find($s, AAM_Core_Subject $subject = null) {
        $statements = array();
        
        // Get list of policies
        if (is_null($subject)) {
            $policies = $this->policyObject;
        } else {
            $policies = $subject->getObject('policy');
        }
        
        foreach($policies->getResources($subject) as $key => $stm) {
            if (preg_match($s, $key)) {
                $statements[strtolower($key)] = $stm;
            }
        }
        
        return $statements;
    }
    
    /**
     * Get single instance of itself
     * 
     * @return AAM_Core_Policy_Manager
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * Load the policy manager
     * 
     * @return void
     * 
     * @access public
     * @static
     */
    public static function bootstrap() {
        self::getInstance();
    }
}