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
class AAM_Framework_Resource_Hook
implements AAM_Framework_Resource_Interface, ArrayAccess
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::HOOK;

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        // Fetch list of statements for the resource Hook
        $list = AAM_Framework_Manager::_()->policies(
            $this->get_access_level()
        )->statements('Hook:*');

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
                        $permission['response'] = $stm['MergeWith'];
                    } else {
                        $permission['response'] = [];
                    }
                } elseif (array_key_exists('Return', $stm)) {
                    $permission['response'] = $stm['Return'];
                }

                $permissions = array_replace([
                    "{$hook}|{$priority}" => $permission
                ], $permissions);
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}