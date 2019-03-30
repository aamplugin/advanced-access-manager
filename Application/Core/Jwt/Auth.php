<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM JWT Authentication handler
 * 
 * @package AAM
 * @author  Vasyl Martyniuk <vasyl@vasyltech.com>
 * @since   v5.9.2
 */
class AAM_Core_Jwt_Auth {

    /**
     * Authenticate user with username and password
     * 
     * @param string $username
     * @param string $password
     * 
     * @return stdClass
     * 
     * @access public
     */
    public function authenticateWithCredentials($username, $password) {
        $response = array('error' => true);

        // try to authenticate user with provided credentials
        try {
            $result = AAM_Core_Login::getInstance()->execute(
                array(
                    'user_login'    => $username,
                    'user_password' => $password
                ), 
                false
            );
        } catch (Exception $ex) {
            $result = array(
                'status' => 'failure',
                'reason' => $ex->getMessage(),
            );
        }

        if ($result['status'] === 'success') { // generate token
            try {
                $response = array(
                    'status' => 'success',
                    'user'   => $result['user']
                );
            } catch (Exception $ex) {
                $response['reason'] = $ex->getMessage();
            }
        } else {
            $response['reason'] = $result['reason'];
        }
        
        return (object) $response;
    }
    
}