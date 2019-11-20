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
 * User view manager
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Subject_User
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Access capability for the user manager service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_users';

    /**
     * Retrieve list of users
     *
     * Based on filters, get list of users
     *
     * @return string JSON encoded list of users
     *
     * @access public
     * @version 6.0.0
     */
    public function getTable()
    {
        $response = array(
            'draw'  => $this->getFromRequest('draw'),
            'data'  => array()
        );

        //get total number of users
        $total  = count_users();
        $result = $this->query();

        $response['recordsTotal']    = $total['total_users'];
        $response['recordsFiltered'] = $result->get_total();

        foreach ($result->get_results() as $row) {
            $response['data'][] = $this->prepareRow(
                AAM::api()->getUser($row->ID)
            );
        }

        return wp_json_encode($response);
    }

    /**
     * Additional layer for method authorization
     *
     * This is used to control if user is allowed to perform certain AJAX action for
     * provided user
     *
     * @param string $method
     * @param array  $args
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function __call($method, $args)
    {
        $response = array(
            'status' => 'failure', 'reason' => __('Unauthorized operation', AAM_KEY)
        );

        if (method_exists($this, "_{$method}")) {
            $user_id  = $this->getFromPost('user');

            if (current_user_can('aam_manager') && current_user_can('edit_users')) {
                if ($user_id != get_current_user_id()) {
                    if ($this->isAllowed($user_id)) {
                        $response = call_user_func(array($this, "_{$method}"));
                    }
                } else {
                    $response['reason'] = __('Cannot manage yourself', AAM_KEY);
                }
            }
        } else {
            _doing_it_wrong(
                __CLASS__ . '::' . $method,
                'User Manager does not have this method defined',
                AAM_VERSION
            );
        }

        return wp_json_encode($response);
    }

    /**
     * Prepare individual user row
     *
     * @param AAM_Core_Subject_User $user
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareRow(AAM_Core_Subject_User $user)
    {
        $attributes = array();
        $expiration = get_user_option(
            AAM_Core_Subject_User::EXPIRATION_OPTION, $user->ID
        );

        if (!empty($expiration)) {
            $expires = new DateTime(
                '@' . $expiration['expires'], new DateTimeZone('UTC')
            );

            $attributes[] = $expires->format('m/d/Y, H:i O');
            $attributes[] = $expiration['action'];
            $attributes[] = (!empty($expiration['meta']) ? $expiration['meta'] : null);
        }

        return array(
            $user->ID,
            implode(', ', $this->prepareUserRoles($user->roles)),
            $user->getName(),
            implode(',', $this->prepareRowActions($user)),
            AAM_Core_API::maxLevel($user->getMaxLevel()),
            implode('|', $attributes)
        );
    }

    /**
     * Prepare the list of user roles
     *
     * @param array $roles
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareUserRoles($roles)
    {
        $response = array();

        $names = AAM_Core_API::getRoles()->get_names();

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
     * Prepare user row actions
     *
     * @param AAM_Core_Subject_User $user
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareRowActions(AAM_Core_Subject_User $user)
    {
        $allowed = $this->isAllowed($user);
        $actions = array();

        if ($allowed) {
            $actions = apply_filters(
                'aam_user_row_actions_filter',
                array(
                    'manage',
                    current_user_can('edit_users') ? 'edit' : 'no-edit'
                ),
                $user
            );
        }

        return $actions;
    }

    /**
     * Save user expiration
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _saveExpiration()
    {
        $userId  = $this->getFromPost('user');
        $action  = $this->getFromPost('after');
        $role    = $this->getFromPost('role');
        $expires = new DateTime('@' . $this->getFromPost('expires'));

        $result = AAM::api()->getUser($userId)->setUserExpiration(array(
            'expires' => $expires->getTimestamp(),
            'action'  => $action,
            'meta'    => (!empty($role) ? $role : null)
        ));

        if ($result) {
            $response = array('status' => 'success');
        } else {
            $response = array(
                'status' => 'failure',
                'reason' => __('Unexpected application error', AAM_KEY)
            );
        }

        return $response;
    }

    /**
     * Reset user expiration settings
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _resetExpiration()
    {
        $userId = $this->getFromPost('user');
        $result = AAM::api()->getUser($userId)->resetExpiration();

        if ($result) {
            $response = array('status' => 'success');
        } else {
            $response = array(
                'status' => 'failure',
                'reason' => __('Unexpected application error', AAM_KEY)
            );
        }

        return $response;
    }

    /**
     * Query database for list of users
     *
     * Based on filters and settings get the list of users from database
     *
     * @return \WP_User_Query
     *
     * @access protected
     * @version 6.0.0
     */
    protected function query()
    {
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
     * Get user list order direction
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getOrderDirection()
    {
        $dir   = 'asc';
        $order = AAM_Core_Request::post('order.0');

        if (!empty($order['column']) && (intval($order['column']) === 2)) {
            $dir = !empty($order['dir']) ? $order['dir'] : 'asc';
        }

        return strtoupper($dir);
    }

    /**
     * Check is current user is allowed to manage requested user
     *
     * @param int|AAM_Core_Subject_User $user
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function isAllowed($user)
    {
        if (is_numeric($user)) {
            $user = AAM::api()->getUser($user);
        }

        return apply_filters(
            'aam_user_can_manage_level_filter', true, $user->getMaxLevel()
        );
    }

    /**
     * Register User UI feature
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'subject',
            'view'       => __CLASS__
        ));
    }

}