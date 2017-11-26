<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Shortcode
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Shortcode_Factory {
    
    /**
     *
     * @var type 
     */
    protected $strategy = null;
    
    /**
     * Initialize shortcode factory
     * 
     * @param type $args
     * @param type $content
     */
    public function __construct($args, $content) {
        $context = !empty($args['context']) ? $args['context'] : 'content';
        
        $classname = 'AAM_Shortcode_Strategy_' . ucfirst($context);
        
        if (class_exists($classname)) {
            $this->strategy = new $classname($args, $content);
        } else {
            $this->strategy = apply_filters(
                    'aam-shortcode-filter', null, $context, $args, $content
            );
        }
    }
    
    /**
     * 
     * @return string
     */
    public function process() {
        if (is_a($this->strategy, 'AAM_Shortcode_Strategy_Interface')) {
            $content = $this->strategy->run();
        } else {
            $content = __('No valid strategy found for the given context', AAM_KEY);
        }
        
        return $content;
    }
    
}