<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * API route object
 *
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/304
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/105
 * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/56
 * @since 6.1.0  Fixed bug with incorrectly halted inheritance mechanism
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.31
 */
class AAM_Core_Object_Route extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'route';

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
        $option = $this->getSubject()->readOption('route');

        $this->setExplicitOption($option);

        // Trigger custom functionality that may populate the menu options. For
        // example, this hooks is used by Access Policy service
        $option = apply_filters('aam_route_object_option_filter', $option, $this);

        // Making sure that all menu keys are lowercase
        $normalized = array();
        foreach($option as $key => $val) {
            $normalized[strtolower($key)] = $val;
        }

        $this->setOption(is_array($normalized) ? $normalized : array());
    }

    /**
     * Check if route is restricted
     *
     * @param string $type   REST or XMLRPC
     * @param string $route
     * @param string $method
     *
     * @return boolean
     *
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/304
     * @since 6.4.0  Added `aam_route_match_filter` to support enhancement
     *               https://github.com/aamplugin/advanced-access-manager/issues/56
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.13
     */
    public function isRestricted($type, $route, $method = 'POST')
    {
        $options = $this->getOption();
        $id      = strtolower("{$type}|{$route}|{$method}");
        $matched = isset($options[$id]) ? $options[$id] : null;

        if ($matched === null) {
            $matched = apply_filters(
                'aam_route_match_filter', false, $type, $route, $method, $this
            );
        }

        return $matched;
    }

}