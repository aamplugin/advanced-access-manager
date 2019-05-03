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
                $code   = null;
                
                if ($effect === 1 && !empty($stm['Metadata']['Redirect'])) {
                    $redirect = $stm['Metadata']['Redirect'];
                    $type     = strtolower($redirect['Type']);
                    $code     = isset($redirect['Code']) ? $redirect['Code'] : 307;
                    
                    switch($type) {
                        case 'message':
                            $destination = $redirect['Message'];
                            break;
                            
                        case 'page':
                            if (isset($redirect['Id'])) {
                                $destination = intval($redirect['Id']);
                            } elseif (isset($redirect['Slug'])) {
                                $page = get_page_by_path($redirect['Slug'], OBJECT);
                                $destination = (is_a($page, 'WP_Post') ? $page->ID : 0);
                            }
                            break;

                        case 'url':
                            $destination = filter_var(
                                $redirect['URL'], 
                                FILTER_VALIDATE_URL
                            );
                            if (empty($destination)) {
                                $type = 'message';
                                $destination = "Invalid URL: [{$redirect['URL']}]";
                            }
                            break;
                        
                        case 'callback':
                            $destination = $redirect['Callback'];
                            break;
                    }
                }
                
                $option[crc32($chunks[1] . $type. $destination)] = array(
                    'uri'    => $chunks[1],
                    'type'   => $type,
                    'action' => $destination,
                    'code'   => $code
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

                // normalize the search and target URIs
                $s           = rtrim($s,  '/');
                $uri['path'] = rtrim((isset($uri['path']) ? $uri['path'] : ''), '/');
                
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
    public function save($id, $uri, $type, $action = null, $code = 307) {
        $option = $this->getOption();
        $option[$id] = array(
            'uri'    => $uri,
            'type'   => $type,
            'action' => $action,
            'code'   => $code
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
     * @param array $external
     * 
     * @return type
     */
    public function mergeOption($external) {
        $combined = array_merge($external, $this->getOption());
        $merged   = array();
        
        $preference = AAM::api()->getConfig(
            "core.settings.uri.merge.preference", 'deny'
        );
        
        foreach($combined as $key => $options) {
            // If merging preference is "deny" and at least one of the access
            // settings is checked, then final merged array will have it set
            // to checked
            if (!isset($merged[$options['uri']])) {
                $merged[$key] = $options;
            } else {
                if (($preference === 'deny') && ($options['type'] !== 'allow')) {
                    $merged[$key] = $options;
                    break;
                } elseif ($preference === 'allow' && ($options['type'] === 'allow')) {
                    $merged[$key] = $options;
                    break;
                }
            }
        }

        return $merged;
    }

}