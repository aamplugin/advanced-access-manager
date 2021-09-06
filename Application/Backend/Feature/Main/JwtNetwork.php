<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * AAM JWT Network backend service
 *
 * @package AAM
 * @version 6.7.0
 */
class AAM_Backend_Feature_Main_JwtNetwork  extends AAM_Backend_Feature_Abstract
{

    /**
     * HTML template to render
     *
     * @version 6.7.0
     */
    const TEMPLATE = 'service/jwtnetwork.php';

    /**
     * Register network service
     *
     * @return void
     *
     * @access public
     * @version 6.7.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'jwtnetwork',
            'position'   => 99,
            'title'      => __('JWT Multisite Support', AAM_KEY),
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Default::UID,
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID
            ),
            'view'       => __CLASS__
        ));
    }

    /**
     * Get list of claimed Network Sites
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function getTable()
    {
        return wp_json_encode($this->retrieveList());
    }

    /**
     * Retrieve list of registered JWT tokens
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function retrieveList()
    {
        $tokens = AAM_Service_Jwt::getInstance()->getTokenRegistry(
            AAM_Backend_Subject::getInstance()->ID
        );

        $response = array(
            'recordsTotal'    => count($tokens),
            'recordsFiltered' => count($tokens),
            'draw'            => AAM_Core_Request::request('draw'),
            'data'            => array(),
        );

        $issuer = AAM_Core_Jwt_Issuer::getInstance();

        foreach ($tokens as $token) {
            $claims  = $issuer->validateToken($token);

            if ($claims->isValid) {
                $expires = new DateTime('@' . $claims->exp, new DateTimeZone('UTC'));
                $details = $expires->format('m/d/Y, H:i O');
            } else {
                $details = __('Token is no longer valid', AAM_KEY);
            }

            $response['data'][] = array(
                $token,
                add_query_arg('aam-jwt', $token, site_url()),
                $claims->isValid,
                $details,
                'view,delete'
            );
        }

        return $response;
    }

    /**
     * Delete role in the network
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function delete()
    {
        $user   = AAM_Backend_Subject::getInstance();
        $token  = filter_input(INPUT_POST, 'token');
        $result = AAM_Service_JwtNetwork::getInstance()->revokeUserSite($user->ID, $token);

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
}