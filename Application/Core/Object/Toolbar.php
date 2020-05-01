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
 * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/105
 * @since 6.2.2 Added support for the new `aam_toolbar_is_hidden_filter` filter
 * @since 6.1.0 Fixed bug with incorrectly halted inheritance mechanism
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.5.0
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
     * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/105
     * @since 6.1.0 Fixed bug with incorrectly halted inheritance mechanism
     * @since 6.0.0 Initial implementation of the method
     *
     * @version 6.5.0
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption('toolbar');

        $this->determineOverwritten($option);

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
     * @since 6.2.2 Added `aam_toolbar_is_hidden_filter` filter
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.2
     */
    public function isHidden($item, $both = false)
    {
        $options = $this->getOption();
        $item    = strtolower($item);

        // Step #1. Check if toolbar item is directly restricted
        $direct = !empty($options[$item]);

        // Step #2. Check if whole branch is restricted
        $branch = ($both && !empty($options['toolbar-' . $item]));

        return apply_filters(
            'aam_toolbar_is_hidden_filter', $direct || $branch, $item, $this
        );
    }

}