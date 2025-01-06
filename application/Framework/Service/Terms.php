<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Terms framework service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Terms
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Return term resource data as array
     *
     * @param mixed $identifier
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function to_array($identifier)
    {
        $resource = $this->_get_resource($identifier);

        return apply_filters('aam_term_to_array_filter', [
            'resource_type'   => $resource::TYPE,
            'identifier'      => $resource->get_internal_id(false),
            'term'            => $resource->get_core_instance(),
            'permissions'     => $resource->get_permissions(),
            'is_customized'   => $resource->is_customized(),
            'is_hierarchical' => get_taxonomy($resource->taxonomy)->hierarchical
        ], $resource);
    }

    /**
     * Get term resource
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_Resource_Term
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource($identifier)
    {
        if (!empty($this->_settings['scope'])) {
            if (is_array($identifier)) {
                $identifier = array_replace(
                    [ 'post_type' => $this->_settings['scope'] ],
                    $identifier
                );
            } elseif (is_a($identifier, WP_Term::class)) {
                $identifier = [
                    'term'      => $identifier,
                    'post_type' => $this->_settings['scope']
                ];
            } elseif (is_numeric($identifier)) {
                $identifier = [
                    'id'        => $identifier,
                    'post_type' => $this->_settings['scope']
                ];
            }
        }

        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::TERM, $identifier
        );
    }

}