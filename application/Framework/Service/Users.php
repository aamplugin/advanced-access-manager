<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service user manager
 *
 * @package AAM
 * @version 6.9.32
 */
class AAM_Framework_Service_Users
{

    /**
     * Single instance of itself
     *
     * @var AAM_Framework_Service_Users
     *
     * @access private
     * @static
     * @version 6.9.32
     */
    private static $_instance = null;

    /**
     * Instantiate the service
     *
     * @return void
     *
     * @access protected
     * @version 6.9.32
     */
    protected function __construct() {}

    /**
     * Return paginated list of user
     *
     * @return array Array of AAM_Framework_Proxy_User
     *
     * @access public
     * @version 6.9.32
     */
    public function get_users(array $args = [])
    {
        $args = array_merge([
            'blog_id'        => get_current_blog_id(),
            'fields'         => 'all',
            'number'         => 10,
            'offset'         => 0,
            'search'         => '',
            'result_type'    => 'full',
            'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
            'orderby'        => 'display_name'
        ], $args);

        $query    = new WP_User_Query($args);
        $response = [];

        if ($args['result_type'] !== 'summary') {
            $response['list'] = [];

            foreach ($query->get_results() as $user) {
                array_push($response['list'], $this->_prepare_user($user));
            }
        }

        if (in_array($args['result_type'], ['full', 'summary'], true)) {
            $response['summary'] = [
                'total_count'    => count_users()['total_users'],
                'filtered_count' => $query->get_total()
            ];
        }

        return $response;
    }

    /**
     * Get user by its identifier
     *
     * The identifier can be either ID, user_login or user_email.
     *
     * @param int|string $identifier
     * @param boolean    $return_proxy
     *
     * @return array|AAM_Framework_Proxy_User
     *
     * @access public
     * @version 6.9.32
     * @throws Exception
     */
    public function get_user($identifier, $return_proxy = true)
    {
        if (is_numeric($identifier)) { // Get user by ID
            $user = get_user_by('id', $identifier);
        } elseif (is_string($identifier) && $identifier !== '*') {
            if (strpos($identifier, '@') > 0) { // Email?
                $user = get_user_by('email', $identifier);
            } else {
                $user = get_user_by('login', $identifier);
            }
        } else {
            $user = null;
        }

        // Do additional validation to ensure that current user can manage this
        // user.
        // TODO: This is a legacy "User Level Filter" service and it can be removed
        // down the road
        $user = apply_filters('aam_get_user', $user);

        if (is_wp_error($user)) {
            throw new DomainException($user->get_error_message());
        } elseif (!is_a($user, 'WP_User')) {
            throw new Exception("User with identifier {$identifier} does not exist");
        }

        // Final check before returning user data
        if (!current_user_can('aam_list_user', $user->ID)) {
            throw new DomainException(
                "User with identifier {$identifier} does not exist"
            );
        }

        return $return_proxy ? $user : $this->_prepare_user($user);
    }

    /**
     * Update user
     *
     * @param int|string $identifier
     * @param array      $data
     * @param boolean    $return_proxy
     *
     * @return array|AAM_Framework_Proxy_User
     *
     * @access public
     * @version 6.9.32
     */
    public function update_user($identifier, $data, $return_proxy = true)
    {
        $user = $this->get_user($identifier);

        // Verifying the expiration date & trigger, if defined
        if (isset($data['expires_at'])) {
            $expiration = [
                'expires' => DateTime::createFromFormat(
                    DateTime::RFC3339, $data['expires_at']
                )->getTimestamp()
            ];

            // Parse the trigger
            if (is_array($data['trigger'])) {
                $expiration['action'] = $data['trigger']['type'];
            } else {
                $expiration['action'] = $data['trigger'];
            }

            // Additionally, if trigger is change_role, capture the targeting role
            if ($expiration['action'] === 'change_role') {
                $expiration['meta'] = $data['trigger']['to_role'];
            }

            // Update the expiration attribute
            update_user_option($user->ID, 'aam_user_expiration', $expiration);
        }

        if (isset($data['status'])) {
            if ($data['status'] === AAM_Framework_Proxy_User::STATUS_INACTIVE) {
                add_user_meta($user->ID, 'aam_user_status', 'locked');
            } else {
                delete_user_meta($user->ID, 'aam_user_status');
            }
        }

        return $this->get_user($identifier, $return_proxy);
    }

    /**
     * Reset user
     *
     * @param int|string $identifier
     * @param boolean    $return_proxy
     *
     * @return array|AAM_Framework_Proxy_User
     *
     * @access public
     * @version 6.9.32
     */
    public function reset_user($identifier, $return_proxy = true)
    {
        $user = $this->get_user($identifier);

        delete_user_option($user->ID, 'aam_user_expiration');

        do_action('aam_reset_user_action', $user);

        return $this->get_user($identifier, $return_proxy);
    }

    /**
     * Prepare user data
     *
     * @param WP_User $wp_user
     *
     * @return array
     *
     * @access private
     * @version 6.9.32
     */
    private function _prepare_user(WP_User $wp_user)
    {
        // Take WordPress core user and convert it to AAM User Proxy so we can have
        // more enhanced view on the user
        $user = new AAM_Framework_Proxy_User($wp_user);

        $response = [
            'id'                    => $user->ID,
            'user_login'            => $user->user_login,
            'display_name'          => $user->display_name,
            'user_level'            => intval($user->user_level),
            'roles'                 => $this->_prepare_user_roles($user->roles),
            'assigned_capabilities' => $user->caps,
            'all_capabilities'      => $user->allcaps,
            'status'                => $user->status
        ];

        $expires_at = $user->expires_at;

        if (!empty($expires_at)) {
            $response['expiration'] = [
                'expires_at'           => $user->expires_at->format(DateTime::RFC3339),
                'expires_at_timestamp' => $user->expires_at->getTimestamp(),
                'trigger'              => $user->expiration_trigger
            ];
        }

        return apply_filters('aam_get_user_filter', $response, $user);
    }

    /**
     * Prepare list of roles
     *
     * @param array $roles
     *
     * @return array
     *
     * @access private
     * @version 6.9.32
     */
    private function _prepare_user_roles($roles)
    {
        $response = array();

        $names = AAM_Framework_Manager::roles()->get_names();

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (array_key_exists($role, $names)) {
                    $response[] = translate_user_role($names[$role]);
                }
            }
        }

        return $response;
    }

    /**
     * Bootstrap the role service
     *
     * @return AAM_Framework_Service_Users
     *
     * @access public
     * @static
     * @version 6.9.32
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}