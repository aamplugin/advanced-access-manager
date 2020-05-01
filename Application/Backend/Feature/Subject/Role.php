<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Role view manager
 *
 * @since 6.5.0 Implemented https://github.com/aamplugin/advanced-access-manager/issues/97
 * @since 6.4.0 Enhancement https://github.com/aamplugin/advanced-access-manager/issues/72
 * @since 6.1.0 Fixed bug with role creation process that caused PHP warning
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.5.0
 */
class AAM_Backend_Feature_Subject_Role
{

    /**
     * Capability that allows to manage roles
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_roles';

    /**
     * Get role list
     *
     * Prepare and return the list of roles for the table view
     *
     * @return string JSON Encoded role list
     *
     * @access public
     * @version 6.0.0
     */
    public function getTable()
    {
        //retrieve list of users
        $count = count_users();
        $stats = $count['avail_roles'];

        $filtered = $this->fetchRoleList();

        $response = array(
            'recordsTotal'    => count(get_editable_roles()),
            'recordsFiltered' => count($filtered),
            'draw'            => AAM_Core_Request::request('draw'),
            'data'            => array(),
        );

        foreach ($filtered as $id => $data) {
            $user_count = (isset($stats[$id]) ? $stats[$id] : 0);

            $response['data'][] = array(
                $id,
                $user_count,
                translate_user_role($data['name']),
                implode(',', $this->prepareRowActions($user_count, $id)),
                AAM_Core_API::maxLevel($data['capabilities'])
            );
        }

        return wp_json_encode(apply_filters('aam_get_role_list_filter', $response));
    }

    /**
     * Prepare the list of role actions
     *
     * @param int    $user_count
     * @param string $roleId
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareRowActions($user_count, $roleId)
    {
        $actions = array('manage');

        if (current_user_can('aam_edit_roles')) {
            $actions[] = 'edit';
        } else {
            $actions[] = 'no-edit';
        }
        if (current_user_can('aam_create_roles')) {
            $actions[] = 'clone';
        } else {
            $actions[] = 'no-clone';
        }
        if (current_user_can('aam_delete_roles') && !$user_count) {
            $actions[] = 'delete';
        } else {
            $actions[] = 'no-delete';
        }

        return apply_filters('aam_role_row_actions_filter', $actions, $roleId);
    }

    /**
     * Additional layer for method authorization
     *
     * This is used to control if user is allowed to perform certain AJAX action
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
            $response = call_user_func(array($this, "_{$method}"));
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
     * Get pure list of roles (without any meta info)
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _getList()
    {
        return apply_filters(
            'aam_get_role_list_filter', $this->fetchRoleList()
        );
    }

    /**
     * Fetch role list from the DB
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function fetchRoleList()
    {
        $response = array();

        // Filter by name
        $search  = trim(AAM_Core_Request::request('search.value'));
        $exclude = trim(AAM_Core_Request::request('exclude'));
        $roles   = get_editable_roles();

        foreach ($roles as $id => $role) {
            $match = preg_match('/^' . $search . '/i', $role['name']);
            if (($exclude !== $id) && (!$search || $match)) {
                $response[$id] = $role;
            }
        }

        return $response;
    }

    /**
     * Create new role
     *
     * @return array
     *
     * @since 6.5.0 Implemented https://github.com/aamplugin/advanced-access-manager/issues/97
     * @since 6.1.0 Fixed the PHP notice where `Undefined variable: parent`
     * @since 6.0.0 Initial implementation of the method
     *
     * @access private
     * @version 6.5.0
     */
    private function _create()
    {
        $response = array(
            'status' => 'failure', 'reason' => __('Unauthorized operation', AAM_KEY)
        );

        if (current_user_can('aam_create_roles')) {
            $name    = sanitize_text_field(filter_input(INPUT_POST, 'name'));
            $roles   = AAM_Core_API::getRoles();
            $role_id = sanitize_key(strtolower($name));
            $inherit = trim(filter_input(INPUT_POST, 'inherit'));
            $doClone = filter_input(INPUT_POST, 'clone', FILTER_VALIDATE_BOOLEAN);

            // If inherited role is set get capabilities from it
            if ($inherit) {
                $parent = $roles->get_role($inherit);
                $caps   = ($parent ? $parent->capabilities : array());

                // Also adding role's slug to the list of capabilities
                // https://github.com/aamplugin/advanced-access-manager/issues/97
                $caps[$inherit] = true;
            } else {
                $caps   = array();
                $parent = null;
            }

            if ($role = $roles->add_role($role_id, $name, $caps)) {
                $response = array(
                    'status' => 'success',
                    'role'   => array(
                        'id'    => $role_id,
                        'name'  => $name,
                        'level' => AAM_Core_API::maxLevel($caps)
                    )
                );

                // Clone settings if needed
                if ($doClone && !empty($parent)) {
                    $this->cloneSettings($role, $parent);
                }

                do_action('aam_post_add_role_action', $role, $parent);
            } else {
                $response['reason'] = __("Role {$name} already exists", AAM_KEY);
            }
        }

        return $response;
    }

    /**
     * Clone access settings
     *
     * @param object $role
     * @param object $parent
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function cloneSettings($role, $parent)
    {
        $settings = AAM_Core_AccessSettings::getInstance();

        // Clone the settings
        $settings->set("role.{$role->name}", $settings->get("role.{$parent->name}"));

        return $settings->save();
    }

    /**
     * Edit role name
     *
     * @return array
     *
     * @since 6.4.0 Enhancement https://github.com/aamplugin/advanced-access-manager/issues/72
     * @since 6.0.0 Initial implementation of the method
     *
     * @access private
     * @version 6.4.0
     */
    private function _edit()
    {
        if (current_user_can('aam_edit_roles')) {
            $role = AAM_Backend_Subject::getInstance();

            $role->update(
                esc_js(trim(filter_input(INPUT_POST, 'name'))),
                sanitize_key(filter_input(INPUT_POST, 'slug'))
            );

            do_action('aam_post_update_role_action', $role->getSubject());

            $response = array('status' => 'success');
        } else {
            $response = array(
                'status' => 'failure',
                'reason' => __('Unauthorized operation', AAM_KEY)
            );
        }

        return $response;
    }

    /**
     * Delete role
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _delete()
    {
        $response = array('status' => 'failure');

        if (current_user_can('aam_delete_roles')) {
            if (AAM_Backend_Subject::getInstance()->delete()) {
                $response['status'] = 'success';
            } else {
                $response['reason'] = __('Failed to delete the role', AAM_KEY);
            }
        } else {
            $response['reason'] = __('Unauthorized operation', AAM_KEY);
        }

        return $response;
    }

    /**
     * Register Role UI feature
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