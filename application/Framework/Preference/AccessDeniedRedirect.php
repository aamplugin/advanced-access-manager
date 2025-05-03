<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Denied Redirect preferences
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Preference_AccessDeniedRedirect
implements AAM_Framework_Preference_Interface, ArrayAccess
{

    use AAM_Framework_Preference_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Preference::ACCESS_DENIED_REDIRECT;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result  = [];
        $manager = AAM_Framework_Manager::_();

        // Fetch list of statements for the resource Toolbar
        $params = $manager->policies($this->get_access_level())->params(
            'redirect:on:access-denied:*'
        );

        foreach($params as $key => $value) {
            $bits = explode(':', $key);
            $area = $bits[3]; // Should be either frontend, backend or API

            if (is_array($value)) {
                $result[$area] = $manager->policy->convert_statement_redirect($value);
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}