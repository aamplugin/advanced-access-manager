<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Widgets resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Widget implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::WIDGET;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        // Fetch list of statements for the resource Widget
        foreach($this->policies()->statements('Widget:*') as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            // Extracting widget slug
            $parsed = explode(':', $stm['Resource']);

            if (!empty($parsed[1])) {
                // Determining correct internal resource id
                if (array_key_exists('Area', $stm)) {
                    $id =  $parsed[1] . '|' . strtolower($stm['Area']);
                } else {
                    $id = $parsed[1];
                }

                $result = array_replace([
                    $id => [
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