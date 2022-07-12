<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

include_once ABSPATH . 'wp-includes/rest-api/endpoints/class-wp-rest-users-controller.php';
include_once ABSPATH . 'wp-admin/includes/ms.php';

/**
 * AAM JWT NetworkDispatch
 *
 * @since 6.7.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.7.2
 */
class AAM_Core_Jwt_NetworkDispatch
    extends AAM_Core_Jwt_Issuer
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * JWT admin token dispatch to WP Network sites
     *
     * @param string $token
     * @param array $params
     * @param WP_REST_Request $request
     *
     * @return object
     *
     * @since 6.7.0 Initial implementation of the method
     *
     * @access public
     */
    public function adminUserNetworkDispatch($token, $params, $request)
    {
        try {
            $response = $this->validateToken($token);
            if($response->isValid) {

                $response->wpUserExists = false;
                $response->wpUserInSync = false;
                $wpUser = get_user_by('email', $params['email']);
                if($wpUser) {
                    $response->wpUserExists = true;

                    $this->checkUsersBlogs($wpUser, $params);

                    if(array_key_exists('user_id_sync', $params) && $wpUser->ID === $params['user_id_sync']) {
                        $response->wpUserInSync = true;
                    }

                } else {
                    $wpUser = $this->createUserAndBlogs($params, $request);
                    $response->wpUserInSync = true;
                }

                $response->hasDispatched = true;
                $response->wpUser = $wpUser;
            } else {
                $response->hasDispatched = false;
            }

        } catch (Exception $ex) {
            $status = $ex->getCode();
            $response = array(
                'hasDispatched' => false,
                'reason' => $ex->getMessage(),
                'status' => (!empty($status) ? $status : 400)
            );
        }

        return (object)$response;
    }

    /**
     * JWT admin token remove WP Network user
     *
     * @param string $token
     * @param array $params
     * @param WP_REST_Request $request
     *
     * @return object
     *
     * @since 670.0 Initial implementation of the method
     *
     * @access public
     */
    public function adminUserRemove($token, $params, $request)
    {
        try {
            $response = $this->validateToken($token);
            if($response->isValid) {

                $blogs = get_sites();

                $wpUser = get_user_by('id', $params['id']);
                if($wpUser instanceof WP_User) {
                    /** @var WP_Site $WP_Site */
                    foreach ($blogs as $WP_Site) {

                        switch_to_blog($WP_Site->blog_id);

                        if(is_user_member_of_blog($wpUser->ID, $WP_Site->blog_id)) {
                            remove_user_from_blog($wpUser->ID, $WP_Site->blog_id);
                        }

                    }

                    wpmu_delete_user($wpUser->ID);

                    $response->wasRemoved = true;
                    $response->wpUser = $wpUser;
                }
            } else {
                $response->wasRemoved = false;
            }

        } catch (Exception $ex) {
            $status = $ex->getCode();
            $response = array(
                'wasRemoved' => false,
                'reason' => $ex->getMessage(),
                'status' => (!empty($status) ? $status : 400)
            );
        }

        return (object)$response;
    }

    /**
     * @param WP_User $WP_User
     * @param array $params
     */
    protected function checkUsersBlogs(WP_User $WP_User, $params)
    {
        $blogs = get_sites();

        $site_ids = array();
        $site_urls = array();
        $site_roles = array();

        foreach ($blogs as $WP_Site) {

            switch_to_blog($WP_Site->blog_id);

            $pattern = "/https?:\/\/{$WP_Site->domain}/";
            $matches = preg_grep($pattern, $params['sites']);

            if($matches) {

                $index = array_key_first($matches);
                $site_ids[] = $WP_Site->blog_id;
                $site_urls[] = $matches[$index];
                $site_roles[] = $params['site_roles'][$index];

            } else if(is_user_member_of_blog($WP_User->ID, $WP_Site->site_id)) {

                remove_user_from_blog($WP_User->ID, $WP_Site->site_id, 0);

            }
        }

        foreach ($site_ids as $index => $blog_id) {

            switch_to_blog($blog_id);

            if(!is_user_member_of_blog($WP_User->ID, $blog_id)) {

                $result = add_user_to_blog($blog_id, $WP_User->ID, $site_roles[$index]);
                if(is_wp_error($result)) {
                    die(print_r($result, true));
                }
            }

        }

        restore_current_blog();
    }

    /**
     * @param array $params
     * @param WP_REST_Request $request
     *
     * @return false|WP_User
     */
    protected function createUserAndBlogs($params, $request)
    {
        $usersController = new WP_REST_Users_Controller();
        $response = $usersController->create_item($request);
        $userArr = $response->data;
        $WP_User = get_user_by('id', $userArr['id']);

        $this->checkUsersBlogs($WP_User, $params);

        $WP_User = get_user_by('id', $userArr['id']);

        return $WP_User;
    }
}
