<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * 404 (Not Found) redirect object
 *
 * @package AAM
 * @version 6.4.0
 */
class AAM_Core_Object_NotFoundRedirect extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.4.0
     */
    const OBJECT_TYPE = 'notFoundRedirect';

    /**
     * @inheritdoc
     * @version 6.4.0
     */
    protected function initialize()
    {
        // Initialize the settings
        $option = $this->getSubject()->readOption(self::OBJECT_TYPE);

        // If options are defined, set the overwritten flag
        $this->determineOverwritten($option);

        // Trigger custom functionality that may populate the redirect options. For
        // example, this hooks is used by Access Policy service
        $option = apply_filters(
            'aam_404_redirect_object_option_filter', $option, $this
        );

        $this->setOption(is_array($option) ? $option : array());
    }

    /**
     * Merge settings
     *
     * The last subject overrides previous
     *
     * @param array $options
     *
     * @return array
     *
     * @access public
     * @version 6.4.0
     */
    public function mergeOption($options)
    {
        return array_replace_recursive($options, $this->getOption());
    }

}