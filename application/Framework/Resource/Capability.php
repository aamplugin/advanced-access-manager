<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Capability resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Capability implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::CAPABILITY;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result  = [];
        $service = $this->policies();

        foreach($service->statements('Capability:*') as $stm) {
            $bits   = explode(':', $stm['Resource']);
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            $result = array_replace([
                $bits[1] => [
                    'assume' => [
                        'effect' => $effect
                    ]
                ]
            ], $result);
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}