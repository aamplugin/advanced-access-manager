<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Metaboxes & Widgets (aka Component) resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Component
implements
    AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::COMPONENT;

    /**
     * Check whether the component is hidden or not
     *
     * This method check if component is hidden.
     *
     * @param string $screen_id
     * @param string $component_id
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden($screen_id, $component_id)
    {
        // TODO: Legacy, refactor and remove the "widgets" that represent
        // "frontend" screen
        $screen = $screen_id === 'frontend' ? 'widgets' : $screen_id;
        $id     = strtolower("{$screen}|{$component_id}");

        if (array_key_exists($id, $this->_settings)) {
            $result = !empty($this->_settings[$id]);
        } else {
            $result = null;
        }

        return apply_filters(
            'aam_metabox_is_hidden_filter',
            $result,
            $screen_id,
            $component_id,
            $this
        );
    }

}