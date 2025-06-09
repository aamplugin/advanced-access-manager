<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Taxonomies framework service
 *
 * @method bool is_hidden_on(mixed $resource_identifier, string $website_area) **[Premium Feature]**
 * @method bool is_hidden(mixed $resource_identifier) **[Premium Feature]**
 * @method bool is_denied_to(mixed $resource_identifier, string $permission) **[Premium Feature]**
 * @method bool is_allowed_to(mixed $resource_identifier, string $permission) **[Premium Feature]**
 * @method bool deny(mixed $resource_identifier, string|array $permission) **[Premium Feature]**
 * @method bool allow(mixed $resource_identifier, string|array $permission) **[Premium Feature]**
 * @method bool hide(mixed $resource_identifier, string|array $website_area = null) **[Premium Feature]**
 * @method bool show(mixed $resource_identifier, string|array $website_area = null) **[Premium Feature]**
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Taxonomies
{

    use AAM_Framework_Service_BaseTrait;

     /**
     * Get taxonomy resource
     *
     * @return AAM_Framework_Resource_Taxonomy
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::TAXONOMY
        );
    }

    /**
     * @inheritDoc
     *
     * @return WP_Taxonomy
     */
    private function _normalize_resource_identifier($resource_identifier)
    {
        $result = null;

        if (is_a($resource_identifier, WP_Taxonomy::class)) {
            $result = $resource_identifier;
        } elseif (is_string($resource_identifier)) {
            $result = get_taxonomy($resource_identifier);
        }

        if (!is_a($result, WP_Taxonomy::class)) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        return $result;
    }

}