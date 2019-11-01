<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * Menu object
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Core_Object_Menu extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'menu';

    /**
     * @inheritdoc
     * @version 6.0.0
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption(self::OBJECT_TYPE);

        $this->determineOverwritten($option);

        // Trigger custom functionality that may populate the menu options. For
        // example, this hooks is used by Access Policy service
        $option = apply_filters('aam_menu_object_option_filter', $option, $this);

        $this->setOption(is_array($option) ? $option : array());
    }

    /**
     * Check is menu or submenu is restricted
     *
     * @param string  $menu
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isRestricted($menu)
    {
        // Decode URL in case of any special characters like &amp;
        $decoded = htmlspecialchars_decode($menu);

        $options = $this->getOption();
        $parent  = $this->getParentMenu($decoded);

        // Step #1. Check if menu is directly restricted
        $direct = !empty($options[$decoded]);

        // Step #2. Check if whole branch is restricted
        $branch = !empty($options['menu-' . $decoded]);

        // Step #3. Check if dynamic submenu is restricted because of whole branch
        $indirect = ($parent && (!empty($options['menu-' . $parent])));

        return apply_filters(
            'aam_admin_menu_is_restricted_filter',
            $direct || $branch || $indirect,
            $decoded,
            $this
        );
    }

    /**
     * Get parent menu
     *
     * @param string $search
     *
     * @return string|null
     *
     * @access protected
     * @global array $submenu
     * @version 6.0.0
     */
    protected function getParentMenu($search)
    {
        global $submenu;

        $result = null;

        if (is_array($submenu)) {
            foreach ($submenu as $parent => $subs) {
                foreach ($subs as $sub) {
                    if ($sub[2] === $search) {
                        $result = $parent;
                        break;
                    }
                }

                if ($result !== null) {
                    break;
                }
            }
        }

        return $result;
    }

}