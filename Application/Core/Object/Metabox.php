<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Metabox object
 *
 * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/105
 * @since 6.2.2 Added `aam_metabox_is_hidden_filter` filter
 * @since 6.0.0 Initial implementation of the method
 *
 * @package AAM
 * @version 6.5.0
 */
class AAM_Core_Object_Metabox extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'metabox';

    /**
     * @inheritdoc
     *
     * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/105
     * @since 6.0.0 Initial implementation of the method
     *
     * @version 6.5.0
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption(self::OBJECT_TYPE);

        $this->determineOverwritten($option);

        // Trigger custom functionality that may populate the menu options. For
        // example, this hooks is used by Access Policy service
        $option = apply_filters('aam_metabox_object_option_filter', $option, $this);

        // Making sure that all menu keys are lowercase
        $normalized = array();
        foreach($option as $key => $val) {
            $normalized[strtolower($key)] = $val;
        }

        $this->setOption(is_array($normalized) ? $normalized : array());
    }

    /**
     * Check if metabox or widget is visible
     *
     * @param string $screen
     * @param string $metaboxId
     *
     * @return boolean
     *
     * @since 6.2.2 Added `aam_metabox_is_hidden_filter` filter
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.2
     */
    public function isHidden($screen, $metaboxId)
    {
        $option = $this->getOption();
        $id     = strtolower("{$screen}|{$metaboxId}");

        return apply_filters(
            'aam_metabox_is_hidden_filter',
            !empty($option[$id]),
            $screen,
            $metaboxId,
            $this
        );
    }

}