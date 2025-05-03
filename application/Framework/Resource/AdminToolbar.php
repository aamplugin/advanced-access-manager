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
class AAM_Framework_Resource_AdminToolbar implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::TOOLBAR;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        // Fetch list of statements for the resource Toolbar
        $list = $this->policies()->statements('Toolbar:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            // Extracting toolbar ID
            $parsed = explode(':', $stm['Resource']);

            if (!empty($parsed[1])) {
                $result = array_merge([
                    $parsed[1] => [
                        'list' => [
                            'effect' => $effect !== 'allow' ? 'deny' : 'allow'
                        ]
                    ]
                ], $result);
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}