<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Metaboxes resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Metabox implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::METABOX;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        // Fetch list of statements for the resource Metabox
        $list = AAM_Framework_Manager::_()->policies(
            $this->get_access_level()
        )->statements('Metabox:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            // Extracting metabox slug
            $parsed = explode(':', $stm['Resource']);

            if (!empty($parsed[1])) {
                // Determining correct internal resource id
                if (array_key_exists('ScreenId', $stm)) {
                    $id = strtolower($stm['ScreenId']) . '_' . $parsed[1];
                } else {
                    $id = $parsed[1];
                }

                $result = array_replace([
                    $id => [
                        'effect' => $effect !== 'allow' ? 'deny' : 'allow'
                    ]
                ], $result);
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}