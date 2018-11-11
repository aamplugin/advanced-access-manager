<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * URI object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Uri extends AAM_Core_Object {

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
        
        $option = $this->getSubject()->readOption('uri');
        
        if (empty($option)) {
            $option = $this->getSubject()->inheritFromParent('uri');
        } else {
            $this->setOverwritten(true);
        }
        
        $this->setOption($option);
    }
    
    /**
     * 
     * @param type $uri
     * 
     * @return null|array
     */
    public function findMatch($s, $params = array()) {
        $match   = null;
        $options = $this->getOption();
        
        if (!empty($options)) {
            foreach($options as $rule) {
                $uri   = wp_parse_url($rule['uri']);
                $out   = array();

                if (!empty($uri['query'])) {
                    parse_str($uri['query'], $out);
                }
                
                $regex = '@^' . preg_quote($uri['path']) . '$@';
                
                if (apply_filters('aam-uri-match-filter', preg_match($regex, $s), $uri, $s)
                        && (empty($out) || count(array_intersect_assoc($params, $out)) === count($out))) {
                    $match = $rule;
                    break;
                }
            }
        }
        
        return $match;
    }

    /**
     * Save menu option
     * 
     * @return bool
     * 
     * @access public
     */
    public function save($id, $uri, $type, $action = null) {
        $option = $this->getOption();
        $option[$id] = array(
            'uri'    => $uri,
            'type'   => $type,
            'action' => $action
        );
        $this->setOption($option);
        
        return $this->getSubject()->updateOption($this->getOption(), 'uri');
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
        
        return $this->getSubject()->updateOption($this->getOption(), 'uri');
    }
    
    /**
     * Reset default settings
     * 
     * @return bool
     * 
     * @access public
     */
    public function reset() {
        return $this->getSubject()->deleteOption('uri');
    }

}