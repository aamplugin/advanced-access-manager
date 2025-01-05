<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend Menu resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_BackendMenu
implements AAM_Framework_Resource_Interface, ArrayAccess
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::BACKEND_MENU;

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        // Fetch list of statements for the resource BackendMenu
        $list = AAM_Framework_Manager::_()->policies(
            $this->get_access_level()
        )->statements('BackendMenu:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            // Extracting backend menu item ID
            $parsed = explode(':', $stm['Resource']);

            if (!empty($parsed[1])) {
                $permissions = array_replace([
                    $parsed[1] => [
                        'effect' => $effect !== 'allow' ? 'deny' : 'allow'
                    ]
                ], $permissions);
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}