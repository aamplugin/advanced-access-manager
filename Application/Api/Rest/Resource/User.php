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
 * AAM RESTful API Users Resource
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Api_Rest_Resource_User
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        add_filter('rest_user_query', array($this, 'userQuery'));
    }

    /**
     * Authorize user actions
     *
     * @return null
     *
     * @access public
     * @version 6.0.0
     */
    public function authorize()
    {
        return null;
    }

    /**
     * Alter user select query
     *
     * @param array $args
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function userQuery($args)
    {
        //current user max level
        $max     = AAM::getUser()->getMaxLevel();
        $exclude = isset($args['role__not_in']) ? $args['role__not_in'] : array();
        $roles   = AAM_Core_API::getRoles();

        foreach ($roles->role_objects as $id => $role) {
            if (AAM_Core_API::maxLevel($role->capabilities) > $max) {
                $exclude[] = $id;
            }
        }

        $args['role__not_in'] = $exclude;

        return $args;
    }

}