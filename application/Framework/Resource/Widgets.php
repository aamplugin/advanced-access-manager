<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Widgets resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Widgets
implements
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::WIDGETS;

    /**
     * Check whether the metabox is hidden or not
     *
     * @param string $slug
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden($slug)
    {
        $lowercase_slug = strtolower($slug);

        if (array_key_exists($lowercase_slug, $this->_permissions)) {
            $result = $this->_permissions[$lowercase_slug]['effect'] !== 'allow';
        } else {
            $result = null;
        }

        return apply_filters(
            'aam_widget_is_hidden_filter', $result, $lowercase_slug, $this
        );
    }

}