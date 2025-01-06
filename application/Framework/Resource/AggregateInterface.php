<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Resource that is also used as container for multiple entities
 *
 * There are resources that are also used as aggregate for all permissions of any
 * give post type. For example, Post or Term resources can be used to aggregate all
 * permissions. This is used by functionalities that need a complete list of
 * explicitly defined permissions.
 *
 * @package AAM
 * @version 7.0.0
 */
interface AAM_Framework_Resource_AggregateInterface
{

    /**
     * Determine if current resource acts as aggregate
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_aggregate();

}