<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * WordPress API manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Main_Policy extends AAM_Backend_Feature_Abstract {
    
    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        $allowed = AAM_Backend_Subject::getInstance()->isAllowedToManage();
        if (!$allowed || !current_user_can('aam_manage_policy')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_policy'));
        }
    }

    /**
     * 
     * @return type
     */
    public function getTable() {
        return wp_json_encode($this->retrievePolicies());
    }
    
    /**
     * Install policy
     * 
     * @return string
     * 
     * @access public
     * @since  v5.7.3
     */
    public function install() {
        $package = (object) AAM_Core_Request::post('package');
        
        if (!empty($package->content)) {
            $json = base64_decode($package->content);
            
            $result = wp_insert_post(array(
                'post_author'  => get_current_user_id(),
                'post_content' => $json,
                'post_title'   => $package->title,
                'post_excerpt' => $package->description,
                'post_status'  => 'publish',
                'post_type'    => 'aam_policy'
            ));
            
            if (!is_wp_error($result)) {
                $response = array('status' => 'success');
            } else {
                $response = array(
                    'status' => 'failure', 'reason' => $result->get_error_message()
                );
            }
        } else {
            $response = array(
                'status' => 'failure', 
                'reason' => __('Failed to fetch policy. Please try again.', AAM_KEY)
            );
        }
        
        return wp_json_encode($response);
    }

    /**
     * Save post properties
     * 
     * @return string
     * 
     * @access public
     */
    public function save() {
        $subject = AAM_Backend_Subject::getInstance();
        $id      = AAM_Core_Request::post('id');
        $effect  = AAM_Core_Request::post('effect');
        
        $action = (!empty($effect) ? 'attach' : 'detach');
        
        // Verify that current user can perform following action
        if (AAM_Core_Policy_Factory::get()->canTogglePolicy($id, $action)) {
            //clear cache
            AAM_Core_API::clearCache();

            $result = $subject->save($id, $effect, 'policy');
        } else {
            $result = false;
        }

        return wp_json_encode(array(
            'status'  => ($result ? 'success' : 'failure')
        ));
    }
    
    /**
     * 
     * @return type
     */
    public function reset() {
        return AAM_Backend_Subject::getInstance()->resetObject('policy');
    }
    
    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/policy.phtml';
    }
    
    /**
     * Check inheritance status
     * 
     * Check if menu settings are overwritten
     * 
     * @return boolean
     * 
     * @access protected
     */
    protected function isOverwritten() {
        $object = AAM_Backend_Subject::getInstance()->getObject('policy');
        
        return $object->isOverwritten();
    }
    
    /**
     * 
     * @return type
     */
    protected function retrievePolicies() {
        $search = trim(AAM_Core_Request::request('search.value'));
        
        $list = get_posts(array(
            'post_type'   => 'aam_policy',
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        $response = array(
            'recordsTotal'    => count($list),
            'recordsFiltered' => count($list),
            'draw'            => AAM_Core_Request::request('draw'),
            'data'            => array(),
        );
        
        foreach($list as $record) {
            $policy = json_decode($record->post_content);
            
            if ($policy) {
                $response['data'][] = array(
                    $record->ID,
                    $this->buildTitle($record),
                    $this->buildActionList($record),
                    get_edit_post_link($record->ID, 'link')
                );
            }
        }
        
        return $response;
    }
    
    /**
     * 
     * @param type $record
     * @return string
     */
    protected function buildTitle($record) {
        $title  = (!empty($record->post_title) ? $record->post_title : __('(no title)'));
        $title .= '<br/>';
        
        if (isset($record->post_excerpt)) {
            $title .= '<small>' . esc_js($record->post_excerpt) . '</small>';
        }
        
        return $title;
    }
    
    /**
     * 
     * @param type $record
     * @return type
     */
    protected function buildActionList($record) {
        //'assign,edit,clone,delete'
        $subject = AAM_Backend_Subject::getInstance();
        $policy  = $subject->getObject('policy');
        $post    = $subject->getObject('post', $record->ID);
        
        $action  = $policy->has($record->ID) ? 'detach' : 'attach';
        $prefix  = AAM_Core_Policy_Factory::get()->canTogglePolicy($record->ID, $action) ? '' : 'no-';
        
        $actions = array(
            $policy->has($record->ID) ? "{$prefix}detach" : "{$prefix}attach",
            $post->has('backend.edit') ? 'no-edit' : 'edit'
        );
        
        return implode(',', $actions);
    }

    /**
     * Register Menu feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'policy',
            'position'   => 2,
            'title'      => __('Access Policies', AAM_KEY) . '<span class="badge">NEW</span>',
            'capability' => 'aam_manage_policy',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID, 
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}