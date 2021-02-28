<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

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
     * JWT token claim(s) dispatch to WP Network sites
     *
     * @param string $token
     * @param array $params
     *
     * @return object
     *
     * @since 6.1.0 Enriched error response with more details
     * @since 6.0.4 Making sure that JWT expiration is checked with UTC timezone
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function adminUserNetworkDispatch($token, $params)
    {
        try {
            $response = $this->validateToken($token);

            if($response->isValid) {

                $wpUser = get_user_by('email', $params['email']);
                if($wpUser) {
                    $this->checkUsersBlogs($wpUser, $params);
                } else {
                    $wpUser = $this->createUserAndBlogs($params);
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

    protected function createUserAndBlogs($params)
    {
        $WP_User = null;

        $this->checkUsersBlogs($WP_User, $params);

        return $WP_User;
    }
}
