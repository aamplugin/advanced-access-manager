<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM shortcode strategy for content
 * 
 * Shortcode strategy to manage access to the parts of post's content
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Shortcode_Strategy_Content implements AAM_Shortcode_Strategy_Interface {
    
    /**
     *
     * @var type 
     */
    protected $args;
    
    /**
     *
     * @var type 
     */
    protected $content;
    
    /**
     * Initialize shortcode decorator
     * 
     * Expecting attributes in $args are:
     *   "hide"     => comma-separated list of role and user IDs to hide content
     *   "show"     => comma-separated list of role and user IDs to show content
     *   "limit"    => comma-separated list of role and user IDs to limit content
     *   "message"  => message to show if "limit" is defined
     *   "callback" => callback function that returns message if "limit" is defined
     * 
     * @param type $args
     * @param type $content
     */
    public function __construct($args, $content) {
        $this->args    = $args;
        $this->content = do_shortcode($content);
    }
    
    /**
     * Process short-code
     * 
     * @return string
     * 
     * @access public
     */
    public function run() {
        //prepare user
        if (get_current_user_id()) {
            $user = array((string)AAM::getUser()->ID, AAM::getUser()->roles[0]);
        } else {
            $user = array('visitor');
        }
        
        $show  = $this->getAccess('show');
        $limit = $this->getAccess('limit');
        $hide  = $this->getAccess('hide');
        $msg   = $this->getMessage();
        
        if (!empty($this->args['callback'])) {
            $content = call_user_func($this->args['callback'], $this);
        } else {
            $content = $this->content;
            
            //#1. Check if content is restricted for current user
            if (in_array('all', $hide, true) || $this->check($user, $hide)) {
                $content = '';
            }

            //#2. Check if content is limited for current user
            if (in_array('all', $limit, true) || $this->check($user, $limit)) {
                $content = do_shortcode($msg);
            }

            //#3. Check if content is allowed for current user
            if ($this->check($user, $show)) {
                $content = $this->content;
            }
        }
        
        return $content;
    }
    
    /**
     * 
     * @param type $user
     * @param type $conditions
     * @return type
     */
    protected function check($user, $conditions) {
        $match = false;
        $auth  = get_current_user_id();
        
        foreach($conditions as $condition) {
            if (($condition === 'authenticated') && $auth) {
                $match = true;
            } else if (preg_match('/^[\d*-]+\.[\d*-]+[\d\.*-]*[\d\.*-]*$/', $condition)) {
                $match = $this->checkIP(
                        $condition, AAM_Core_Request::server('REMOTE_ADDR')
                );
            } else {
                $match = in_array($condition, $user, true);
            }
            
            if ($match) {
                break;
            }
        }
        
        return $match;
    }
    
    /**
     * 
     * @param type $ip
     * @param type $userIp
     * @return boolean
     */
    protected function checkIP($ip, $userIp) {
        $match = true;
        
        $ipSplit  = preg_split('/[\.:]/', $ip);
        $uipSplit = preg_split('/[\.:]/', $userIp);
        
        foreach($ipSplit as $i => $group) {
            if (strpos($group, '-') !== false) { //range
                list($start, $end) = explode('-', $group);
                if ($uipSplit[$i] < $start || $uipSplit[$i] > $end) {
                    $match = false;
                    break;
                }
            } elseif ($group !== '*') {
                if ($group !== $uipSplit[$i]) {
                    $match = false;
                    break;
                }
            }
        }
        
        return $match;
    }
    
    /**
     * 
     * @return type
     */
    public function getAccess($type) {
        $access = (isset($this->args[$type]) ? $this->args[$type] : null);
        
        return array_map('trim', explode(',', $access));
    }
    
    /**
     * 
     * @return type
     */
    public function getMessage() {
        return isset($this->args['message']) ? $this->args['message'] : null;
    }
    
    /**
     * 
     * @return type
     */
    public function getArgs() {
        return $this->args;
    }
    
    /**
     * 
     * @return type
     */
    public function getContent() {
        return $this->content;
    }
    
}