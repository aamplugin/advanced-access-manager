<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * WordPress ability access resource.
 *
 * @package AAM
 * @version 8.0.0
 */
class AAM_Framework_Resource_Ability implements AAM_Framework_Resource_Interface
{
    
    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::ABILITY;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        foreach ($this->policies()->statements('Ability:*') as $stm) {
            $ability = substr($stm['Resource'], 8);
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            if (isset($stm['Namespace'])) {
                $namespace    = rawurldecode($stm['Namespace']);
                $ability_name = rawurldecode($ability);
            } else {
                $parts        = explode('/', $ability);
                $namespace    = isset($parts[0]) ? $parts[0] : null;
                $ability_name = isset($parts[1]) ? $parts[1] : null;
            }

            if (!empty($namespace) && !empty($ability_name)) {
                $result[$ability_name . '|' . $namespace] = [
                    'access' => [
                        'effect' => $effect === 'allow' ? 'allow' : 'deny'
                    ]
                ];
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

    /**
     * @inheritDoc
     */
    private function _get_resource_id($identifier)
    {
        $result = $identifier->name;

        if (!empty($identifier->namespace)) {
            $result .= '|' . $identifier->namespace;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    private function _get_resource_identifier($id)
    {
        $parts = explode('|', $id);

        return (object) [
            'name'      => $parts[0],
            'namespace' => !empty($parts[1]) ? $parts[1] : null
        ];
    }

}