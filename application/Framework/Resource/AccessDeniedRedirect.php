<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Denied Redirect Redirect resource
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_AccessDeniedRedirect
    implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * Resource type
     *
     * @version 7.0.0
     */
    const TYPE = AAM_Framework_Type_Resource::ACCESS_DENIED_REDIRECT;

    /**
     * @inheritDoc
     */
    public function merge_settings($incoming_settings)
    {
        // TODO: Verify that we are correctly merging everything and there are no
        // leftovers
        return array_replace_recursive($incoming_settings, $this->_settings);
    }

}