<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * URL resource
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Url implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::URL;

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        $manager = AAM_Framework_Manager::_();

        // Fetch list of statements for the resource Url
        $list = $manager->policies($this->get_access_level())->statements('Url:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';
            $parsed = explode(':', $stm['Resource'], 2);

            if (!empty($parsed[1])) {
                $url         = $manager->misc->sanitize_url($parsed[1]);
                $permissions = array_merge([
                    $url => [
                        'effect'   => $effect !== 'allow' ? 'deny' : 'allow',
                        'redirect' => $manager->policy->convert_statement_redirect(
                            $stm
                        )
                    ]
                ], $permissions);
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}