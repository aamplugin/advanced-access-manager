<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Login Redirect preferences
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Preference_LoginRedirect
implements AAM_Framework_Preference_Interface
{

    use AAM_Framework_Preference_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Preference::LOGIN_REDIRECT;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result  = [];
        $manager = AAM_Framework_Manager::_();

        // Fetch list of statements for the resource Toolbar
        $param = $manager->policies($this->get_access_level())->param(
            'redirect:on:login'
        );

        if (!empty($param) && is_array($param)) {
            $result = $manager->policy->convert_statement_redirect($param);
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}