<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Admin toolbar object
 *
 * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/302
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/105
 * @since 6.2.2  Added support for the new `aam_toolbar_is_hidden_filter` filter
 * @since 6.1.0  Fixed bug with incorrectly halted inheritance mechanism
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.33
 */
class AAM_Core_Object_Toolbar extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'toolbar';

    /**
     * @inheritdoc
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
     * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/105
     * @since 6.1.0  Fixed bug with incorrectly halted inheritance mechanism
     * @since 6.0.0  Initial implementation of the method
     *
     * @version 6.9.31
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption('toolbar');

        $this->setExplicitOption($option);

        // Trigger custom functionality that may populate the menu options. For
        // example, this hooks is used by Access Policy service
        $option = apply_filters('aam_toolbar_object_option_filter', $option, $this);

        // Making sure that all menu keys are lowercase
        $normalized = array();
        foreach($option as $key => $val) {
            $normalized[strtolower($key)] = $val;
        }

        $this->setOption(is_array($normalized) ? $normalized : array());
    }

    /**
     * Check is item defined
     *
     * Check if toolbar item defined in options based on the id
     *
     * @param string $item
     *
     * @return boolean
     *
     * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/302
     * @since 6.2.2  Added `aam_toolbar_is_hidden_filter` filter
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.33
     */
    public function isHidden($item)
    {
        $options = $this->getOption();
        $item    = strtolower($item);
        $parent  = $this->getParentMenu($item);

        // If there is a direct setting for given item, use it and ignore everything
        // else
        if (array_key_exists($item, $options)) {
            $restricted = !empty($options[$item]);
        } elseif (array_key_exists('toolbar-' . $item, $options)) {
            $restricted = !empty($options['toolbar-' . $item]);
        } elseif ($parent !== null) { // Get access controls from the parent
            $restricted = $this->isHidden($parent);
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
     * Get parent menu
     *
     * @param string $item
     *
     * @return null|string
     *
     * @access public
     * @version 6.9.13
     */
    public function getParentMenu($item)
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