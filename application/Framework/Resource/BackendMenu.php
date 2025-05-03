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
class AAM_Framework_Resource_BackendMenu implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::BACKEND_MENU;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        // Fetch list of statements for the resource BackendMenu
        $list = $this->policies()->statements('BackendMenu:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            // Extracting backend menu item ID
            $parsed = explode(':', $stm['Resource']);

            if (!empty($parsed[1])) {
                $result = array_replace([
                    $parsed[1] => [
                        'access' => [
                            'effect' => $effect !== 'allow' ? 'deny' : 'allow'
                        ]
                    ]
                ], $result);
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}