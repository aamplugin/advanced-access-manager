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
        
        if (!empty($option)) {
            $this->setOverwritten(true);
        }
        
        if (empty($option)) {
            $stms = AAM_Core_Policy_Factory::get($subject)->find("/^URI:/i");

            foreach($stms as $key => $stm) {
                $chunks = explode(':', $key);
                $effect = ($stm['Effect'] === 'deny' ? 1 : 0);
                $type   = $stm['Effect'];
                $destination = null;
                
                if ($effect === 1 && !empty($stm['Metadata']['Redirect'])) {
                    $type = strtolower($stm['Metadata']['Redirect']['Type']);
                    
                    switch($type) {
                        case 'message':
                            $destination = $stm['Metadata']['Redirect']['Message'];
                            break;
                        
                        case 'page':
                            if (isset($stm['Metadata']['Redirect']['Id'])) {
                                $destination = intval($stm['Metadata']['Redirect']['Id']);
                            } elseif (isset($stm['Metadata']['Redirect']['Slug'])) {
                                $page = $post = get_page_by_path(
                                   $stm['Metadata']['Redirect']['Slug'], OBJECT
                                );
                                $destination = (is_a($page, 'WP_Post') ? $page->ID : 0);
                            }
                            break;
                            
                        case 'url':
                            $destination = filter_var(
                                    $stm['Metadata']['Redirect']['URL'], 
                                    FILTER_VALIDATE_URL
                            );
                            if (empty($destination)) {
                                $type = 'message';
                                $destination = "Invalid URL: [{$stm['Metadata']['Redirect']['URL']}]";
                            }
                            break;
                        
                        case 'callback':
                            $destination = $stm['Metadata']['Redirect']['Callback'];
                            break;
                    }
                }
                
                $option[crc32($chunks[1] . $type. $destination)] = array(
                    'uri'    => $chunks[1],
                    'type'   => $type,
                    'action' => $destination
                );
            }
        }

        if (empty($option)) {
            $option = $this->getSubject()->inheritFromParent('uri');
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
    
    /**
     * 
     * @param type $external
     * @return type
     */
    public function mergeOption($external) {
        return array_merge($external, $this->getOption());
    }

}