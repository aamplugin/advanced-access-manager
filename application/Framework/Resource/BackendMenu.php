<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend Menu resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_BackendMenu implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::BACKEND_MENU;

    /**
     * Check is menu or submenu is restricted
     *
     * @param string $slug [Optional]
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_restricted($slug = null)
    {
        $result = null;
        $slug   = is_null($slug) ? $this->_internal_id : $slug;

        if (empty($slug)) {
            throw new InvalidArgumentException(
                'The Backend Menu resource has to be initialized with valid menu slug'
            );
        }

        // The default dashboard landing page is always excluded
        if ($slug !== 'index.php') {
            if (array_key_exists($slug, $this->_permissions)) {
                $result = $this->_permissions[$slug]['effect'] === 'deny';
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        // Fetch list of statements for the resource BackendMenu
        $list = AAM_Framework_Manager::_()->policies(
            $this->get_access_level()
        )->statements('BackendMenu:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            // Extracting backend menu item ID
            $parsed = explode(':', $stm['Resource']);

            if (!empty($parsed[1])) {
                $permissions = array_replace([
                    $parsed[1] => [
                        'effect' => $effect !== 'allow' ? 'deny' : 'allow'
                    ]
                ], $permissions);
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}