<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Hook resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Hook implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::HOOK;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        // Fetch list of statements for the resource Hook
        $list = $this->policies()->statements('Hook:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            // Extracting hook attributes
            $parsed   = explode(':', $stm['Resource']);
            $hook     = !empty($parsed[1]) ? $parsed[1] : null;
            $priority = !empty($parsed[2]) ? intval($parsed[2]) : 10;

            if (!empty($hook)) {
                $permission = [
                    // Note! Here we are preserving the custom effects
                    'effect' => $effect !== 'deny' ? $effect : 'deny'
                ];

                if ($effect === 'merge') {
                    if (isset($stm['MergeWith']) && is_array($stm['MergeWith'])) {
                        $permission['return'] = $stm['MergeWith'];
                    } else {
                        $permission['return'] = [];
                    }
                } elseif (array_key_exists('Return', $stm)) {
                    $permission['return'] = $stm['Return'];
                }

                $result = array_replace([
                    "{$hook}|{$priority}" => [
                        'access' => $permission
                    ]
                ], $result);
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

    /**
     * @inheritDoc
     */
    private function _get_resource_id($identifier)
    {
        return $identifier->name . '|' . $identifier->priority;
    }

    /**
     * @inheritDoc
     *
     * @version 7.0.11
     */
    private function _get_resource_identifier($id)
    {
        list($name, $priority) = explode('|', $id);

        return (object) [
            'name'     => $name,
            'priority' => $priority
        ];
    }

}