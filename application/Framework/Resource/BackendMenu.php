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
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted()
    {
        $result = null;

        if (empty($this->_internal_id)) {
            throw new InvalidArgumentException(
                'The Backend Menu resource has to be initialized with valid menu slug'
            );
        }

        // The default dashboard landing page is always excluded
        if ($this->_internal_id !== 'index.php') {
            if (array_key_exists($this->_internal_id, $this->_permissions)) {
                $result = $this->_permissions[$this->_internal_id]['effect'] === 'deny';
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
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : null;

            // If effect is defined, move forward with the rest
            if (!empty($effect)) {
                // Extracting backend menu item ID
                $parsed = explode(':', $stm['Resource']);

                if (!empty($parsed[1])) {
                    $permissions = array_replace([
                        $parsed[1] => [
                            'effect' => $effect
                        ]
                    ], $permissions);
                }
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}