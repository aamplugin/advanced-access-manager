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
class AAM_Framework_Resource_Toolbar implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * Resource alias
     *
     * @version 7.0.0
     */
    const TYPE = AAM_Framework_Type_Resource::TOOLBAR;

    /**
     * Check whether the toolbar item is hidden or not
     *
     * This method check if menu item is explicitly hidden or its parent menu is.
     * Additionally, it uses the "aam_toolbar_is_hidden_filter" filter to allow
     * third-party implementation to influence the decision
     *
     * @param string $item_slug
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden($item_slug)
    {
        $item   = strtolower($item_slug);
        $parent = $this->get_parent_item($item);

        // If there is a direct setting for given item, use it and ignore everything
        // else
        if (array_key_exists($item, $this->_settings)) {
            $restricted = !empty($this->_settings[$item]);
        } elseif (array_key_exists('toolbar-' . $item, $this->_settings)) {
            $restricted = !empty($this->_settings['toolbar-' . $item]);
        } elseif ($parent !== null) { // Get access controls from the parent
            $restricted = $this->is_hidden($parent);
        } else {
            $restricted = null;
        }

        return apply_filters(
            'aam_toolbar_is_hidden_filter',
            $restricted,
            $item,
            $this
        );
    }

    /**
     * Get parent menu item
     *
     * @param string $item
     *
     * @return null|string
     *
     * @access public
     * @version 7.0.0
     */
    private function get_parent_item($item)
    {
        $parent = null;
        $cache  = AAM_Service_Toolbar::getInstance()->getToolbarCache();

        if (is_array($cache)) {
            foreach($cache as $branch) {
                foreach($branch['children'] as $child) {
                    if ($child['id'] === $item) {
                        $parent = $branch['id'];
                    }

                    if ($parent !== null) {
                        break;
                    }
                }
            }
        }

        return $parent;
    }

}