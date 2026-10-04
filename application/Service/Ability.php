<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * WordPress Abilities access service.
 *
 * @package AAM
 * 
 * @version 8.0.0
 */
class AAM_Service_Ability
{
    use AAM_Service_BaseTrait;

    /**
     * Register WordPress ability checks and its REST API.
     *
     * @version 8.0.0
     */
    protected function __construct()
    {
        if (function_exists('wp_get_abilities')) {
            AAM_Restful_Ability::bootstrap();

            add_filter('wp_ability_permission_result', function($result, $name) {
                return $this->_filter_permission_result($result, $name);
            }, PHP_INT_MAX, 2);
        }
    }

    /** 
     * Keep AAM denials in the final permission result when core offers this hook. 
     * 
     * @param mixed  $permission
     * @param string $ability_name
     * 
     * @return mixed
     * @access private
     * 
     * @version 8.0.0
     * */
    public function _filter_permission_result($permission, $ability_name)
    {
        if ($permission === true) {
            $permission = AAM::api()->abilities()->is_denied($ability_name)
                ? AAM_Service_AccessDeniedRedirect::denied_error(
                    'aam_ability_denied',
                    __('This ability is restricted by AAM.', 'advanced-access-manager')
                )
                : true;
        }

        return $permission;
    }

}