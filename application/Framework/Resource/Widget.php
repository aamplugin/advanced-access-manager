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
    const TYPE = AAM_Framework_Type_Resource::WIDGET;

    /**
     * Check whether the widget is hidden or not
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

        if (empty($slug)) {
            throw new InvalidArgumentException(
                'Non-empty widget slug has to be provided'
            );
        }

        if (array_key_exists($slug, $this->_permissions)) {
            $result = $this->_permissions[$slug]['effect'] !== 'allow';
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        // Fetch list of statements for the resource Widget
        $list = AAM_Framework_Manager::_()->policies(
            $this->get_access_level()
        )->statements('Widget:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            // Extracting widget slug
            $parsed = explode(':', $stm['Resource']);

            if (!empty($parsed[1])) {
                // Determining correct internal resource id
                if (array_key_exists('Area', $stm)) {
                    $id = strtolower($stm['Area']) . '_' . $parsed[1];
                } else {
                    $id = $parsed[1];
                }

                $permissions = array_replace([
                    $id => [
                        'effect' => $effect !== 'allow' ? 'deny' : 'allow'
                    ]
                ], $permissions);
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}