<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * JWT manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Main_Jwt extends AAM_Backend_Feature_Abstract {
    
    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        $allowed = AAM_Backend_Subject::getInstance()->isAllowedToManage();
        if (!$allowed || !current_user_can('aam_manage_jwt')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_jwt'));
        }
    }
    
    /**
     * 
     * @return type
     */
    public function getTable() {
        return wp_json_encode($this->retrieveList());
    }

    /**
     * 
     * @return type
     */
    public function generate() {
        $user        = AAM_Backend_Subject::getInstance()->get();
        $expires     = filter_input(INPUT_POST, 'expires');
        $refreshable = filter_input(INPUT_POST, 'refreshable', FILTER_VALIDATE_BOOLEAN);

        try {
            $max = AAM::getUser()->getMaxLevel();
            if ($max >= AAM_Core_API::maxLevel($user->allcaps)) {
                $issuer = new AAM_Core_Jwt_Issuer();
                $jwt =  $issuer->issueToken(
                    array(
                        'userId'      => $user->ID, 
                        'revocable'   => true, 
                        'refreshable' => $refreshable
                    ), 
                    $expires
                );
                $result = array(
                    'status' => 'success',
                    'jwt'    => $jwt->token
                );
            } else {
                throw new Exception('User ID has higher level than current user');
            }
        } catch (Exception $ex) {
            $result = array('status' => 'failure', 'reason' => $ex->getMessage());
        }
        
        return wp_json_encode($result);
    }

    /**
     * 
     * @return type
     */
    public function save() {
        $user   = AAM_Backend_Subject::getInstance()->get();
        $token  = filter_input(INPUT_POST, 'token');
        $claims = AAM_Core_Jwt_Issuer::extractTokenClaims($token);

        $result = AAM_Core_Jwt_Manager::getInstance()->registerToken(
            $user->ID, 
            $token
        );

        if ($result) {
            $response = array('status' => 'success');
        } else {
            $response = array(
                'status' => 'failure', 
                'reason' => __('Failed to register JWT token', AAM_KEY)
            );
        }

        return wp_json_encode($response);
    }
    
    /**
     * 
     * @return type
     */
    public function delete() {
        $user  = AAM_Backend_Subject::getInstance()->get();
        $token = filter_input(INPUT_POST, 'token');
        $result = AAM_Core_Jwt_Manager::getInstance()->revokeToken($user->ID, $token);

        if ($result) {
            $response = array('status' => 'success');
        } else {
            $response = array(
                'status' => 'failure', 
                'reason' => __('Failed to revoke JWT token', AAM_KEY)
            );
        }

       return wp_json_encode($response);
    }

    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/jwt.phtml';
    }
    
    /**
     * 
     * @return type
     */
    protected function retrieveList() {
        $tokens = AAM_Core_Jwt_Manager::getInstance()->getTokenRegistry(
            AAM_Backend_Subject::getInstance()->get()->ID
        );

        $response = array(
            'recordsTotal'    => count($tokens),
            'recordsFiltered' => count($tokens),
            'draw'            => AAM_Core_Request::request('draw'),
            'data'            => array(),
        );

        $issuer = new AAM_Core_Jwt_Issuer();

        foreach($tokens as $token) {
            try {
                $claims = $issuer->validateToken($token);
            } catch(Exception $e) {
                $claims = $issuer->extractTokenClaims($token);
                $claims->status = 'invalid';
            }
            
            $response['data'][] = array(
                $token,
                add_query_arg('aam-jwt', $token, site_url()),
                $claims->status,
                $claims->exp,
                'view,delete'
            );
        }
        
        return $response;
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
            'uid'        => 'jwt',
            'position'   => 65,
            'title'      => __('JWT Tokens', AAM_KEY) . '<span class="badge">NEW</span>',
            'capability' => 'aam_manage_jwt',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_User::UID
            ),
            'option'     => 'core.settings.jwtAuthentication',
            'view'       => __CLASS__
        ));
    }

}