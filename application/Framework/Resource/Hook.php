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
    const TYPE = AAM_Framework_Type_Resource::HOOK;

    /**
     * Check is hook is restricted
     *
     * @param string $slug [Optional]
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($slug = null)
    {
        $result = null;
        $slug   = empty($slug) ? $this->_internal_id : $slug;

        if ($slug) {
            throw new InvalidArgumentException(
                'The Hook resource has to be initialized with valid slug'
            );
        }

        if (array_key_exists($slug, $this->_permissions)) {
            $result = $this->_permissions[$slug]['effect'] === 'deny';
        }

        return $result;
    }

    /**
     * Check is hook's return value is modified
     *
     * @param string $slug [Optional]
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_modified($slug = null)
    {
        $result = null;
        $slug   = empty($slug) ? $this->_internal_id : $slug;

        if ($slug) {
            throw new InvalidArgumentException(
                'The Hook resource has to be initialized with valid slug'
            );
        }

        if (array_key_exists($slug, $this->_permissions)) {
            $result = !in_array(
                $this->_permissions[$slug]['effect'],
                [ 'deny', 'allow' ],
                true
            );
        }

        return $result;
    }

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