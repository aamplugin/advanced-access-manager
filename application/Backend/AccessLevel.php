<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend access level instance
 *
 * Currently managed access level. Based on the HTTP request data, define which
 * access level is currently managed with AAM UI.
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_AccessLevel
{

    /**
     * Single instance of itself
     *
     * @var AAM_Backend_AccessLevel
     * @access private
     *
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * Access Level
     *
     * @var AAM_Framework_AccessLevel_Interface
     * @access private
     *
     * @version 7.0.0
     */
    private $_access_level = null;

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        $access_level_type = strtolower(AAM::api()->misc->get(
            $_POST, 'access_level', ''
        ));

        if ($access_level_type === AAM_Framework_Type_AccessLevel::ROLE) {
            $access_level_id = AAM::api()->misc->get($_POST, 'role_id');
        } elseif ($access_level_type === AAM_Framework_Type_AccessLevel::USER) {
            $access_level_id = AAM::api()->misc->get($_POST, 'user_id');
        } else {
            $access_level_id = null;
        }

        if (empty($access_level_type)) {
            $al = $this->_get_last_managed_access_level();

            if (!empty($al)) {
                $access_level_type = $al['type'];
                $access_level_id   = !empty($al['id']) ? $al['id']: null;
            }
        } else { // Persist the last managed access level
            AAM::api()->cache->set(
                'managed_access_level_by_' . get_current_user_id(),
                [
                    'type' => $access_level_type,
                    'id'   => $access_level_id
                ],
                2592000 // 30 days
            );
        }

        if ($access_level_type) {
            $this->_init_access_level($access_level_type, $access_level_id);
        } else {
            $this->_init_fallback_access_level();
        }
    }

    /**
     * Check if current access level is role
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_role()
    {
        return $this->_access_level->type === AAM_Framework_Type_AccessLevel::ROLE;
    }

    /**
     * Check if current access level is user
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_user()
    {
        return $this->_access_level->type === AAM_Framework_Type_AccessLevel::USER;
    }

    /**
     * Check if current access level is visitor
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_visitor()
    {
        return $this->_access_level->type === AAM_Framework_Type_AccessLevel::VISITOR;
    }

    /**
     * Check if current access level is default
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_default()
    {
        return $this->_access_level->type === AAM_Framework_Type_AccessLevel::ALL;
    }

    /**
     * Get last managed access level
     *
     * @return array|null
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_last_managed_access_level()
    {
        $level = AAM::api()->cache->get(
            'managed_access_level_by_' . get_current_user_id()
        );

        if (!is_null($level)) {
            // Verifying that access level exists and is accessible
            if ($level['type'] === 'role') {
                if (!AAM::api()->roles->is_editable_role($level['id'])) {
                    $level = null;
                }
            } elseif ($level['type'] === 'user') {
                $user = apply_filters('aam_get_user', get_user_by('id', $level['id']));

                if ($user === false
                    || is_wp_error($user)
                    || !current_user_can('edit_user', $user->ID)
                ) {
                    $level = null;
                }
            }
        }

        return $level;
    }

    /**
     * Initialize requested access level
     *
     * @param string     $type
     * @param int|string $id
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _init_access_level($type, $id = null)
    {
        $this->_access_level = AAM::api()->access_levels->get($type, $id);
    }

    /**
     * Initialize fallback access level
     *
     * Based on user permissions, pick the first available access level that current
     * user can manage
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _init_fallback_access_level()
    {
        if (current_user_can('aam_manage_roles')) {
            $roles = array_keys(get_editable_roles());
            $this->_init_access_level(
                AAM_Framework_Type_AccessLevel::ROLE, array_pop($roles)
            );
        } elseif (current_user_can('aam_manage_users')) {
            $this->_init_access_level(
                AAM_Framework_Type_AccessLevel::USER, get_current_user_id()
            );
        } elseif (current_user_can('aam_manage_visitors')) {
            $this->_init_access_level(AAM_Framework_Type_AccessLevel::VISITOR);
        } elseif (current_user_can('aam_manage_default')) {
            $this->_init_access_level(AAM_Framework_Type_AccessLevel::ALL);
        } else {
            AAM::api()->redirect->do_redirect([
                'type'    => 'custom_message',
                'message' => __('You do not have permission to manage any access levels', 'advanced-access-manager')
            ]);
        }
    }

    /**
     * Get access level property
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function __get($name)
    {
        return $this->_access_level->$name;
    }

    /**
     * Call access level method
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->_access_level, $name), $args);
    }

    /**
     * Get AAM core subject
     *
     * @return AAM_Framework_AccessLevel_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function get_access_level()
    {
        return $this->_access_level;
    }

    /**
     * Bootstrap the object
     *
     * @return AAM_Backend_AccessLevel
     * @access public
     *
     * @version 7.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Get single instance of itself
     *
     * @return AAM_Backend_AccessLevel
     * @access public
     *
     * @version 7.0.0
     */
    public static function get_instance()
    {
        return self::bootstrap();
    }

}