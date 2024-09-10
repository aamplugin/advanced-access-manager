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
    AAM_Framework_Resource_Interface,
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
     * @param string $slug
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($menu_slug, $parent_menu_slug = null)
    {
        $result = null;

        // Decode URL in case of any special characters like &amp;
        $slug = $this->_normalize_menu_slug($menu_slug);

        if (is_string($parent_menu_slug)) {
            $parent_slug = $this->_normalize_menu_slug($parent_menu_slug);
        } else {
            $parent_slug = null;
        }

        // The default dashboard landing page is always excluded
        if ($slug !== 'index.php') {
            if (array_key_exists($slug, $this->_permissions)) {
                $permission = $this->_permissions[$slug];

                // If parent menu slug is not provided, we are checking access to the
                // entire menu branch
                if (is_null($parent_slug)) {
                    $result = $permission['effect'] === 'deny'
                                && !empty($permission['is_top_level']);
                } else { // Otherwise we are checking permission only for submenu
                    $result = $permission['effect'] === 'deny'
                                && empty($permission['is_top_level']);
                }
            } elseif (array_key_exists($parent_slug, $this->_permissions)) {
                // Inherit settings from the parent menu, if provided
                $result = $this->_permissions[$parent_slug]['effect'] === 'deny';
            }
        } else {
            $result = false;
        }

        return apply_filters(
            'aam_backend_menu_is_restricted_filter',
            $result,
            $this,
            $slug,
            $parent_slug
        );
    }

    /**
     * Normalize the menu slug
     *
     * Ensuring consistency
     *
     * @param string $slug
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _normalize_menu_slug($slug)
    {
        // Decode URL in case of any special characters like &amp;
        $slug = strtolower(htmlspecialchars_decode($slug));

        // The customize.php is funky
        if (strpos($slug, 'customize.php') === 0) {
            $slug = 'customize.php';
        }

        return $slug;
    }

}