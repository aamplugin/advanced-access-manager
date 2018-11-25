<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Policy object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Policy extends AAM_Core_Object {

    /**
     *
     * @var type 
     */
    protected $resources = array();
    
    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     *
     * @return void
     *
     * @access public
     */
    public function __construct(AAM_Core_Subject $subject) {
        parent::__construct($subject);
        
        $parent = $this->getSubject()->inheritFromParent('policy');
        if(empty($parent)) {
            $parent = array();
        }
        
        $option = $this->getSubject()->readOption('policy');
        if (empty($option)) {
            $option = array();
        } else {
            $this->setOverwritten(true);
        }
        
        foreach($option as $key => $value) {
            $parent[$key] = $value; //override
        }
        
        $this->setOption($parent);
    }
    
    /**
     * 
     */
    public function load() {
       $resources = AAM::api()->getUser()->getObject('cache')->get('policy', 0, null);
        
        if (is_null($resources)) {
            $statements = array();
            
            // Step #1. Extract all statements
            foreach($this->getOption() as $id => $effect) {
                if ($effect) {
                    $policy = get_post($id);
                    
                    if (is_a($policy, 'WP_Post')) {
                        $obj = json_decode($policy->post_content, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $statements = array_merge(
                                $statements, $this->extractStatements($obj)
                            );
                        }
                    }
                }
            }

            // Step #2. Merge all statements
            $resources = array();

            foreach($statements as $statement) {
                if (isset($statement['Resource'])) {
                    $actions = (array)(!empty($statement['Action']) ? $statement['Action'] : '');
                    
                    foreach((array) $statement['Resource'] as $resource) {
                        foreach($actions as $action) {
                            $id = strtolower(
                                $resource . (!empty($action) ? ":{$action}" : '')
                            );

                            if (!isset($resources[$id])) {
                                $resources[$id] = $statement;
                            } elseif (empty($resources[$id]['Enforce'])) {
                                $resources[$id] = $this->mergeStatements(
                                    $resources[$id], $statement
                                );
                            }

                            // cleanup
                            if (isset($resources[$id]['Resource'])) { unset($resources[$id]['Resource']); }
                            if (isset($resources[$id]['Action'])) { unset($resources[$id]['Action']); }
                        }
                    }
                }
            }
            
            AAM::api()->getUser()->getObject('cache')->add('policy', 0, $resources);
        }
        
        $this->resources = $resources;
    }
    
    /**
     * 
     * @param type $policy
     * @return type
     */
    protected function extractStatements($policy) {
        $statements = array();
        
        if (isset($policy['Statement'])) {
            if (is_array($policy['Statement'])) {
                $statements = $policy['Statement'];
            } else {
                $statements = array($policy['Statement']);
            }
        }
        
        // normalize each statement
        foreach(array('Action', 'Condition') as $prop) {
            foreach($statements as $i => $statement) {
                if (isset($statement[$prop])) {
                    $statements[$i][$prop] = (array) $statement[$prop];
                }
            }
        }
        
        return $statements;
    }
    
    /**
     * 
     * @param type $left
     * @param type $right
     * @return type
     */
    protected function mergeStatements($left, $right) {
        if (isset($right['Resource'])) {
            unset($right['Resource']);
        }
        
        $merged = array_merge($left, $right);
        
        if (!isset($merged['Effect'])) {
            $merged['Effect'] = 'deny';
        }
     
        return $merged;
    }
    
    /**
     * Save menu option
     * 
     * @return bool
     * 
     * @access public
     */
    public function save($id, $effect) {
        $option      = $this->getOption();
        $option[$id] = intval($effect);
        
        $this->setOption($option);
        
        return $this->getSubject()->updateOption($this->getOption(), 'policy');
    }
    
    /**
     * 
     * @param type $id
     */
    public function has($id) {
        $option = $this->getOption();
        
        return !empty($option[$id]);
    }
    
    /**
     * 
     * @param type $resource
     * @return type
     */
    public function isAllowed($resource, $action = null) {
        $allowed = null;
        
        $id = strtolower($resource . (!empty($action) ? ":{$action}" : ''));
        
        if (isset($this->resources[$id])) {
            $allowed = ($this->resources[$id]['Effect'] === 'allow');
        }
        
        return $allowed;
    }
    
    /**
     * 
     * @param type $id
     * 
     * @return type
     */
    public function delete($id) {
        $option = $this->getOption();
        if (isset($option[$id])) {
            unset($option[$id]);
        }
        $this->setOption($option);
        
        return $this->getSubject()->updateOption($this->getOption(), 'policy');
    }
    
    /**
     * Reset default settings
     * 
     * @return bool
     * 
     * @access public
     */
    public function reset() {
        //clear cache
        AAM_Core_API::clearCache();
        
        return $this->getSubject()->deleteOption('policy');
    }

}