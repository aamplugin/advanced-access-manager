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
 * Admin toolbar object
 *
 * @package AAM
 * @version 6.0.0
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
     * @version 6.0.0
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption('toolbar');

        $this->determineOverwritten($option);

        // Trigger custom functionality that may populate the menu options. For
        // example, this hooks is used by Access Policy service
        if (empty($option)) {
            $option = apply_filters(
                'aam_toolbar_object_option_filter', $option, $this
            );
        }

        $this->setOption(is_array($option) ? $option : array());
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
     * @access public
     * @version 6.0.0
     */
    public function isHidden($item, $both = false)
    {
        $options = $this->getOption();

        // Step #1. Check if toolbar item is directly restricted
        $direct = !empty($options[$item]);

        // Step #2. Check if whole branch is restricted
        $branch = ($both && !empty($options['toolbar-' . $item]));

        return $direct || $branch;
    }

}