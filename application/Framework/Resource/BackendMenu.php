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
class AAM_Framework_Resource_BackendMenu
implements
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::BACKEND_MENU;

    /**
     * Check is menu or submenu is restricted
     *
     * @param string $menu_slug
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($menu_slug)
    {
        $result = null;

        // The default dashboard landing page is always excluded
        if ($menu_slug !== 'index.php') {
            if (array_key_exists($menu_slug, $this->_permissions)) {
                $result = $this->_permissions[$menu_slug]['effect'] === 'deny';
            }
        } else {
            $result = false;
        }

        return $result;
    }

}