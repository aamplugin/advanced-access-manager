<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Menu object
 *
 * @since 6.2.2 Added new filter `aam_backend_menu_is_restricted_filter` so it can
 *              be integrated with access policy wildcard
 * @since 6.0.0 Initial implementation of the method
 *
 * @package AAM
 * @version 6.2.2
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
     * @since 6.2.2 Added new filter `aam_backend_menu_is_restricted_filter`
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.2
     */
    public function isRestricted($menu)
    {
        // Decode URL in case of any special characters like &amp;
        $s = htmlspecialchars_decode($menu);

        if (!in_array($s, array('index.php', 'menu-index.php'))) {
            $options = $this->getOption();
            $parent  = $this->getParentMenu($s);

            // Step #1. Check if menu is directly restricted
            $direct = !empty($options[$s]);

            // Step #2. Check if whole branch is restricted
            $branch = !empty($options['menu-' . $s]);

            // Step #3. Check if dynamic submenu is restricted because of whole branch
            $indirect = ($parent && (!empty($options['menu-' . $parent])));

            $restricted = apply_filters(
                'aam_backend_menu_is_restricted_filter',
                $direct || $branch || $indirect,
                $s,
                $this
            );
        } else {
            $restricted = false;
        }

        return $restricted;
    }

    /**
     * Get parent menu
     *
     * @param string $search
     *
     * @return string|null
     *
     * @since 6.2.2 Made the method public
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @global array $submenu
     * @version 6.2.2
     */
    public function getParentMenu($search)
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