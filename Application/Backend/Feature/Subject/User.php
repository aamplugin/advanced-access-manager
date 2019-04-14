<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * User view manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Subject_User {
    
    /**
     * Construct
     */
    public function __construct() {
        if (!current_user_can('aam_manage_users')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_users'));
        }
    }
    
    /**
     * Retrieve list of users
     * 
     * Based on filters, get list of users
     * 
     * @return string JSON encoded list of users
     * 
     * @access public
     */
    public function getTable() {
        $response = array(
            'draw'  => AAM_Core_Request::request('draw'),
            'data'  => array()
        );
        
        //get total number of users
        $total  = count_users();
        $result = $this->query();

        $response['recordsTotal']    = $total['total_users'];
        $response['recordsFiltered'] = $result->get_total();

        foreach ($result->get_results() as $row) {
            $user = new AAM_Core_Subject_User($row->ID);
            $user->initialize(true);
            $response['data'][] = $this->prepareRow($user);
        }

        return wp_json_encode($response);
    }
    
    /**
     * Save user expiration
     * 
     * @return string
     * 
     * @access public
     */
    public function saveExpiration() {
        $response = array(
            'status' => 'failure',
            'reason' => __('Operation is not permitted', AAM_KEY)
        );
        
        $userId  = filter_input(INPUT_POST, 'user');
        $expires = filter_input(INPUT_POST, 'expires');
        $action  = filter_input(INPUT_POST, 'after');
        $role    = filter_input(INPUT_POST, 'role');
        $jwt     = filter_input(INPUT_POST, 'jwt');
        
        if (current_user_can('edit_users')) {
            if ($userId != get_current_user_id()) {
                if ($this->isAllowed(new AAM_Core_Subject_User($userId))) {
                    $this->updateUserExpiration($userId, $expires, $action, $role, $jwt);
                    $response = array('status' => 'success');
                }
            } else {
                $response['reason'] = __('You cannot set expiration to yourself', AAM_KEY);
            }
        }
        
        return wp_json_encode($response);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function resetExpiration() {
        $response = array(
            'status' => 'failure',
            'reason' => __('Operation is not permitted', AAM_KEY)
        );
        
        $userId  = filter_input(INPUT_POST, 'user');
        
        if (current_user_can('edit_users')) {
            if ($userId != get_current_user_id()) {
                if ($this->isAllowed(new AAM_Core_Subject_User($userId))) {
                    $meta = get_user_meta($userId, 'aam_user_expiration', true);
                    
                    if (!empty($meta)) {
                        $parts = explode('|', $meta);
                        if (!empty($parts[3])) {
                            AAM_Core_Jwt_Manager::getInstance()->revokeToken(
                                $userId, $parts[3]
                            );
                        }
                    }
                    $result   = delete_user_meta($userId, 'aam_user_expiration');
                    $response = array(
                        'status' => $result ? 'success' : 'failure'
                    );
                }
            } else {
                $response['reason'] = __('You cannot manager expiration to yourself', AAM_KEY);
            }
        }
        
        return wp_json_encode($response);
    }
    
    /**
     * 
     * @return type
     */
    public function switchToUser() {
        $response = array(
                'status' => 'failure', 
                'reason' => 'You are not allowed to switch to this user'
        );
        
        if (current_user_can('aam_switch_users')) { 
            $user = AAM_Backend_Subject::getInstance()->get();

            if ($this->isAllowed($user)) {
                AAM_Core_API::updateOption(
                        'aam-user-switch-' . $user->ID, get_current_user_id()
                );
                
                // Making sure that user that we are switching too is not logged in
                // already. Reported by https://github.com/KenAer
                $sessions = WP_Session_Tokens::get_instance($user->ID);
                if (count($sessions->get_all()) >= 1) {
                    $sessions->destroy_all();
                }
                
                // If there is jwt token in cookie, make sure it is deleted otherwise
                // user technically will never be switched
                if (AAM_Core_Request::cookie('aam-jwt')) {
                    setcookie(
                        'aam-jwt', 
                        '', 
                        time() - YEAR_IN_SECONDS,
                        '/', 
                        parse_url(get_bloginfo('url'), PHP_URL_HOST), 
                        is_ssl()
                    );
                }

                wp_clear_auth_cookie();
                wp_set_auth_cookie( $user->ID, true );
                wp_set_current_user( $user->ID );

                $response = array('status' => 'success', 'redirect' => admin_url());
            }
        }
        
        return wp_json_encode($response);
    }
    
    /**
     * Query database for list of users
     * 
     * Based on filters and settings get the list of users from database
     * 
     * @return \WP_User_Query
     * 
     * @access public
     */
    public function query() {
        $search = trim(AAM_Core_Request::request('search.value'));
        $role   = trim(AAM_Core_Request::request('role'));
        
        $args = array(
            'blog_id' => get_current_blog_id(),
            'fields'  => 'all',
            'number'  => AAM_Core_Request::request('length'),
            'offset'  => AAM_Core_Request::request('start'),
            'search'  => ($search ? $search . '*' : ''),
            'search_columns' => array(
                'user_login', 'user_email', 'display_name'
            ),
            'orderby' => 'display_name',
            'order'   => $this->getOrderDirection()
        );
        
        if (!empty($role)) {
            $args['role__in'] = $role;
        }

        return new WP_User_Query($args);
    }
    
    /**
     * 
     * @return type
     */
    protected function getOrderDirection() {
        $dir   = 'asc';
        $order = AAM_Core_Request::post('order.0');
        
        if (!empty($order['column']) && ($order['column'] === '2')) {
            $dir = !empty($order['dir']) ? $order['dir'] : 'asc';
        }
        
        return strtoupper($dir);
    }

    /**
     * Block user
     * 
     * @return string
     * 
     * @access public
     */
    public function block() {
        $result = false;
        
        if (current_user_can('aam_toggle_users') && current_user_can('edit_users')) {
            $subject = AAM_Backend_Subject::getInstance();

            if ($this->isAllowed($subject->get())) {
                //user is not allowed to lock himself
                if (intval($subject->getId()) !== get_current_user_id()) {
                    $result = $subject->block();
                }
            }
        }

        return wp_json_encode(array('status' => ($result ? 'success' : 'failure')));
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function generateJwt() {
        if (current_user_can('aam_manage_jwt')) {
            $user    = AAM_Backend_Subject::getInstance()->get();
            $expires = filter_input(INPUT_POST, 'expires');
            $trigger = filter_input(INPUT_POST, 'trigger', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

            try {
                $max = AAM::getUser()->getMaxLevel();
                if ($max >= AAM_Core_API::maxLevel($user->allcaps)) {
                    $issuer = new AAM_Core_Jwt_Issuer();
                    $jwt =  $issuer->issueToken(
                        array(
                            'userId'      => $user->ID, 
                            'revocable'   => true, 
                            'refreshable' => false,
                            'trigger'     => $trigger
                        ), 
                        $expires
                    );
                    AAM_Core_Jwt_Manager::getInstance()->registerToken($user->ID, $jwt->token);
                    $result = array(
                        'status' => 'success',
                        'jwt'    => $jwt->token
                    );
                } else {
                    $result = array('status' => 'failure', 'reason' => 'User ID has higher level than current user');
                }
            } catch (Exception $ex) {
                $result = array('status' => 'failure', 'reason' => $ex->getMessage());
            }
        } else {
            $result = array('status' => 'failure', 'reason' => 'You are not allowed to manage JWT tokens');
        }
        
        return wp_json_encode($result);
    }
    
    /**
     * Prepare row
     * 
     * @param AAM_Core_Subject_User $user
     * 
     * @return array
     * 
     * @access protected
     */
    protected function prepareRow(AAM_Core_Subject_User $user) {
        return array(
            $user->ID,
            implode(', ', $this->getUserRoles($user->roles)),
            ($user->display_name ? $user->display_name : $user->user_nicename),
            implode(',', $this->prepareRowActions($user)),
            AAM_Core_API::maxLevel($user->getMaxLevel()),
            $this->getUserExpiration($user)
        );
    }
    
    /**
     * Get list of user roles
     * 
     * @param array $roles
     * 
     * @return array
     * 
     * @access protected
     */
    protected function getUserRoles($roles) {
        $response = array();
        
        $names = AAM_Core_API::getRoles()->get_names();
        
        if (is_array($roles)) {
            foreach($roles as $role) {
                if (array_key_exists($role, $names)) {
                    $response[] = translate_user_role($names[$role]);
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Prepare user row actions
     * 
     * @param WP_User $user
     * 
     * @return array
     * 
     * @access protected
     */
    protected function prepareRowActions(AAM_Core_Subject_User $user) {
        if ($this->isAllowed($user) || ($user->ID === get_current_user_id())) {
            $ui = AAM_Core_Request::post('ui', 'main');
            $id = AAM_Core_Request::post('id');
        
            if ($ui === 'principal') {
                $object = $user->getObject('policy');
                $actions = array(($object->has($id) ? 'detach' : 'attach'));
            } else {
                $actions = array('manage');

                if (AAM_Core_Config::get('core.settings.secureLogin', true) 
                        && current_user_can('aam_toggle_users')) {
                    $actions[] = ($user->user_status ? 'unlock' : 'lock');
                }

                if (current_user_can('edit_users')) {
                    $actions[] = 'edit';
                } else {
                    $actions[] = 'no-edit';
                }

                if (current_user_can('aam_switch_users')) {
                    $actions[] = 'switch';
                } else {
                    $actions[] = 'no-switch';
                }
            }
        } else {
            $actions = array();
        }
        
        return $actions;
    }
    
    /**
     * Update user expiration
     * 
     * @param int    $user
     * @param string $expires
     * @param string $action
     * @param string $role
     * 
     * @return bool
     * 
     * @access protected
     */
    protected function updateUserExpiration($user, $expires, $action, $role = '', $jwt = '') {
        update_user_meta(
            $user, 
            'aam_user_expiration',
            $expires . "|" . ($action ? $action : 'delete') . '|' . $role . '|' . $jwt
        );
    }
    
    /**
     * Get user expiration
     * 
     * @param WP_User $user
     * 
     * @return string
     * 
     * @access protected
     */
    protected function getUserExpiration(AAM_Core_Subject_User $user) {
        return get_user_meta($user->ID, 'aam_user_expiration', true);
    }
    
    /**
     * Check max user allowance
     * 
     * @param AAM_Core_Subject_User $user
     * 
     * @return boolean
     * 
     * @access protected
     */
    protected function isAllowed(AAM_Core_Subject_User $user) {
        $sameLevel = false;
        if (AAM_Core_API::capabilityExists('manage_same_user_level')) {
            $sameLevel = current_user_can('manage_same_user_level');
        } else {
            $sameLevel = current_user_can('administrator');
        }

        $userMaxLevel    = AAM::api()->getUser()->getMaxLevel();
        $subjectMaxLevel = $user->getMaxLevel();

        if ($sameLevel) {
            $allowed = $userMaxLevel >= $subjectMaxLevel;
        } else {
            $allowed = $userMaxLevel > $subjectMaxLevel;
        }
        
        return $allowed;
    }

}