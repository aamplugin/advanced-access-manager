<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Term Resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Term implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::TERM;

    /**
     * Determine correct resource identifier based on provided data
     *
     * @param WP_Term $resource_identifier
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource_id($identifier)
    {
        $result = [
            $identifier->term_id,
            $identifier->taxonomy
        ];

        if (property_exists($identifier, 'post_type')) {
            $result[] = $identifier->post_type;
        }

        return implode('|', $result);
    }

}