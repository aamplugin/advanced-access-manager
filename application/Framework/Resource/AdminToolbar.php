<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Admin Toolbar (aka Toolbar) resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_AdminToolbar
implements
    AAM_Framework_Resource_Interface,
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::TOOLBAR;

    /**
     * Check whether the toolbar item is hidden or not
     *
     * This method check if toolbar item is hidden or its parent item is.
     * Additionally, it uses the "aam_admin_toolbar_is_hidden_filter" filter to allow
     * third-party implementation to influence the decision
     *
     * @param string $item_id
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden($item_id)
    {
        // If there is a direct setting for given item, use it and ignore everything
        // else
        if (array_key_exists($item_id, $this->_permissions)) {
            $hidden = $this->_permissions[$item_id]['effect'] !== 'allow';
        } else {
            $hidden = null;
        }

        return apply_filters(
            'aam_admin_toolbar_is_hidden_filter',
            $hidden,
            $this,
            $item_id
        );
    }

}