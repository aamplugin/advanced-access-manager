<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Admin Toolbar (aka Toolbar) resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_AdminToolbar
implements AAM_Framework_Resource_Interface, ArrayAccess
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::TOOLBAR;

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        // Fetch list of statements for the resource Toolbar
        $list = AAM_Framework_Manager::_()->policies(
            $this->get_access_level()
        )->statements('Toolbar:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            // Extracting toolbar ID
            $parsed = explode(':', $stm['Resource']);

            if (!empty($parsed[1])) {
                $permissions = array_merge([
                    $parsed[1] => [
                        'effect' => $effect !== 'allow' ? 'deny' : 'allow'
                    ]
                ], $permissions);
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}