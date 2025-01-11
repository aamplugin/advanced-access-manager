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
    const TYPE = AAM_Framework_Type_Resource::CAPABILITY;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result  = [];
        $manager = AAM_Framework_Manager::_();
        $service = $manager->policies($this->get_access_level());

        foreach($service->statements('Capability:*') as $stm) {
            $bits   = explode(':', $stm['Resource']);
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            $result = array_replace([
                $bits[1] => [ 'effect' => $effect ]
            ], $result);
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}