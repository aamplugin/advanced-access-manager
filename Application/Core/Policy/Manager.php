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
     * Policy core object
     * 
     * @var AAM_Core_Object_Policy
     * 
     * @access protected 
     */
    protected $policyObject;
    
    /**
     * Current subject
     * 
     * @var AAM_Core_Subject
     * 
     * @access protected 
     */
    protected $subject;
    
    /**
     * Parsed policy tree
     * 
     * @var array
     * 
     * @access protected 
     */
    protected $tree = null;
    
    /**
     * Constructor
     * 
     * @access protected
     * 
     * @return void
     */
    public function __construct(AAM_Core_Subject $subject) {
        $this->policyObject = $subject->getObject('policy');
        $this->subject      = $subject;
    }
    
    /**
     * Call policy object public methods
     * 
     * @param string $name
     * @param array  $args
     * 
     * @return mixed
     * 
     * @access public
     */
    public function __call($name, $args) {
        $result = null;
        
        if (method_exists($this->policyObject, $name)) {
            $result = call_user_func_array(array($this->policyObject, $name), $args);
        }
        
        return $result;
    }
    
    /**
     * Find all the matching policies
     * 
     * @param string  $s      RegEx
     * @param array   $args   Inline arguments
     * @param bool    $single Single record only - the last record
     * 
     * @return array
     * 
     * @access public
     */
    public function find($s, $args = array(), $single = false) {
        $statements = array();
        $tree       = $this->preparePolicyTree();
        
        foreach($tree['Statement'] as $key => $stm) {
            if (preg_match($s, $key) && $this->isApplicable($stm, $args)) {
                $statements[$this->strToLower($key)] = $stm;
            }
        }
        
        return ($single ? end($statements) : $statements);
    }
    
    /**
     * Check if specified action is allowed for resource
     * 
     * This method is working with "Statement" array.
     * 
     * @param string $resource Resource name
     * @param array  $args     Args that will be injected during condition evaluation
     * 
     * @return boolean|null
     * 
     * @access public
     */
    public function isAllowed($resource, $args = array()) {
        $allowed = null;
        $tree    = $this->preparePolicyTree();
        $id      = $this->strToLower($resource);
        
        if (isset($tree['Statement'][$id])) {
            $stm = $tree['Statement'][$id];
            
            if ($this->isApplicable($stm, $args)) {
                $effect  = strtolower($stm['Effect']);
                $allowed = ($effect === 'allow');
            }
        }
        
        return $allowed;
    }

    /**
     * Convert string to lowercase
     *
     * @param string $str
     * 
     * @return string
     * 
     * @access protected
     */
    protected function strToLower($str) {
        if (function_exists('mb_strtolower')) {
            $result = mb_strtolower($str);
        } else {
            $result = strtolower($str);
        }

        return $result;
    }

    /**
     * Determine if resource is the boundary
     * 
     * The Boundary is type of resource that is denied and is enforced so no other
     * statements can override it. For example edit_posts capability can be boundary
     * for any statement that user Role resource
     *
     * @param string $resource
     * @param array  $args
     * 
     * @return boolean
     * 
     * @access public
     */
    public function isBoundary($resource, $args = array()) {
        $denied = false;
        $tree   = $this->preparePolicyTree();
        $id     = $this->strToLower($resource);
        
        if (isset($tree['Statement'][$id])) {
            $stm = $tree['Statement'][$id];
            
            if ($this->isApplicable($stm, $args)) {
                $effect  = strtolower($stm['Effect']);
                $denied = ($effect === 'deny' && !empty($stm['Enforce']));
            }
        }
        
        return $denied;
    }
    
    /**
     * Get Policy Param
     * 
     * @param string $name
     * @param array  $args
     * 
     * @return mixed
     * 
     * @access public
     */
    public function getParam($id, $args = array()) {
        $value = null;

        if (isset($this->tree['Param'][$id])) {
            $param = $this->tree['Param'][$id];
            
            if ($this->isApplicable($param, $args)) {
                $value = $param['Value'];
            }
        }
        
        return $value;
    }
    
    /**
     * Check if current subject can toggle specific policy
     * 
     * Verify that policy can be attached/detached
     * 
     * @param int    $id     Policy ID
     * @param string $action Either "attach" or "detach"
     * 
     * @return bool
     * 
     * @access public
     * @since  v5.9
     */
    public function canTogglePolicy($id, $action) {
        $post = get_post($id);
            
        // Verify that current user can perform following action
        $stm = $this->find(
            "/^post:{$post->post_type}:({$post->post_name}|{$post->ID}):{$action}/i",
            array('post' => $post),
            true
        );

        return (empty($stm['Effect']) || $stm['Effect'] === 'allow');
    }
    
    /**
     * Check if policy block is applicable
     * 
     * @param array $block
     * @param array $args
     * 
     * @return boolean
     * 
     * @access protected
     */
    protected function isApplicable($block, $args) {
        $result = true;
        
        if (!empty($block['Condition']) && !is_scalar($block['Condition'])) {
            $result = AAM_Core_Policy_Condition::getInstance()->evaluate(
                $block['Condition'], $args
            );
        }
        
        return $result;
    }
   
    /**
     * Prepare policy tree
     * 
     * This is the lazy load for the policy tree. If tree has not been initialized,
     * trigger the process of parsing and merging statements and settings.
     * 
     * @return array
     * 
     * @access protected
     */
    protected function preparePolicyTree() {
        if (is_null($this->tree)) {
            $cache = $this->subject->getObject('cache')->get('policyTree');
            
            if (empty($cache)) {
                $this->tree = array(
                    'Statement' => array(),
                    'Param'     => array()
                );

                foreach($this->policyObject->getOption() as $id => $effect) {
                    if (!empty($effect)) { // Load policy only if it is attached
                        $this->extendTree(
                            $this->tree, $this->parsePolicy(get_post($id))
                        );
                    }
                }
                
                $this->subject->getObject('cache')->add('policyTree', 0, $this->tree);
            } else {
                $this->tree = $cache;
            }
        }
        
        return $this->tree;
    }
    
    /**
     * Parse policy post and extract Statements and Params
     * 
     * @param WP_Post $policy
     * 
     * @return array
     * 
     * @access protected
     */
    protected function parsePolicy($policy) {
        $tree = array('Statement' => array(), 'Param' => array());
        // Only parse if policy is valid WP post and is published (active)
        if (is_a($policy, 'WP_Post') && ($policy->post_status === 'publish')) {
            $val = json_decode($policy->post_content, true);
            
            // Do not load the policy if any errors
            if (json_last_error() === JSON_ERROR_NONE) {
                $tree = array(
                    'Statement' => isset($val['Statement']) ? (array) $val['Statement'] : array(),
                    'Param'     => isset($val['Param']) ? (array) $val['Param'] : array(),
                );
            }
        }
        
        return $tree;
    }
    
    /**
     * Extend tree with additional statements and params
     * 
     * @param array &$tree
     * @param array $addition
     * 
     * @return array
     * 
     * @access protected
     */
    protected function extendTree(&$tree, $addition) {
        // Step #1. If there are any statements, let's index them by resource:action
        // and insert into the list of statements
        foreach($addition['Statement'] as $stm) {
            $list = (isset($stm['Resource']) ? (array) $stm['Resource'] : array());
            $acts = (isset($stm['Action']) ? (array) $stm['Action'] : array(''));
            
            foreach($list as $res) {
                foreach($acts as $act) {
                    $id = $this->strToLower($res . (!empty($act) ? ":{$act}" : ''));
                    
                    if (!isset($tree['Statement'][$id]) || empty($tree['Statement'][$id]['Enforce'])) {
                        $tree['Statement'][$id] = $this->removeKeys($stm, array('Resource', 'Action'));
                    }
                }
            }
        }
        
        // Step #2. If there are any params, let's index them and insert into the list
        foreach($addition['Param'] as $param) {
            $id = (isset($param['Key']) ? $param['Key'] : '__none');
            
            if (!isset($tree['Param'][$id]) || empty($tree['Param'][$id]['Enforce'])) {
                $tree['Param'][$id] = $this->removeKeys($param, array('Key'));
            }
        }
    }
    
    /**
     * Remove unnecessary keys from array
     * 
     * @param array $arr
     * @param array $keys
     * 
     * @return array
     * 
     * @access private
     */
    private function removeKeys($arr, $keys) {
        foreach($keys as $key) {
            if (isset($arr[$key])) {
                unset($arr[$key]);
            }
        }
        
        return $arr;
    }
    
}