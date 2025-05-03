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
 * @method urls(mixed $access_level = null, array $settings = []) **[Premium Feature]**
 * @method bool is_hidden_on(mixed $resource_identifier, string $website_area) **[Premium Feature]**
 * @method bool is_hidden(mixed $resource_identifier) **[Premium Feature]**
 * @method bool is_denied_to(mixed $resource_identifier, string $permission) **[Premium Feature]**
 * @method bool is_allowed_to(mixed $resource_identifier, string $permission) **[Premium Feature]**
 * @method bool deny(mixed $resource_identifier, string|array $permission) **[Premium Feature]**
 * @method bool allow(mixed $resource_identifier, string|array $permission) **[Premium Feature]**
 * @method bool hide(mixed $resource_identifier, string|array $website_area = null) **[Premium Feature]**
 * @method bool show(mixed $resource_identifier, string|array $website_area = null) **[Premium Feature]**
 * @method bool is_password_protected(mixed $resource_identifier) **[Premium Feature]**
 * @method bool is_restricted(mixed $resource_identifier) **[Premium Feature]**
 * @method bool is_redirected(mixed $resource_identifier) **[Premium Feature]**
 * @method bool is_teaser_message_set(mixed $resource_identifier) **[Premium Feature]**
 * @method bool is_access_expired(mixed $resource_identifier) **[Premium Feature]**
 * @method bool set_password(mixed $resource_identifier, string $password) **[Premium Feature]**
 * @method bool set_teaser_message(mixed $resource_identifier, string $message) **[Premium Feature]**
 * @method bool set_redirect(mixed $resource_identifier, array $redirect) **[Premium Feature]**
 * @method bool set_expiration(mixed $resource_identifier, int $timestamp) **[Premium Feature]**
 * @method string|null get_password(mixed $resource_identifier) **[Premium Feature]**
 * @method string|null get_teaser_message(mixed $resource_identifier) **[Premium Feature]**
 * @method string|null get_redirect(mixed $resource_identifier) **[Premium Feature]**
 * @method string|null get_expiration(mixed $resource_identifier) **[Premium Feature]**
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Terms
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get term resource
     *
     * @return AAM_Framework_Resource_Term
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::TERM
        );
    }

    /**
     * @inheritDoc
     *
     * @return WP_Term
     */
    private function _normalize_resource_identifier($resource_identifier)
    {
        $result = null;

        if (is_numeric($resource_identifier)) {
            $result = get_term($resource_identifier);
        } elseif (is_array($resource_identifier) && isset($identifier['term'])) {
            $result = $identifier['term'];
        } elseif (is_array($resource_identifier)) {
            // Based on the WP DB structure, the wp_terms table contains the unique
            // list of all terms, however, the same term can be associated with
            // multiple taxonomies. The table wp_term_taxonomy has the UNIQUE KEY
            // `term_id_taxonomy` (`term_id`,`taxonomy`). Looking deeper, you notice
            // that the table wp_term_relationships is the one that actually associate
            // terms with other content types (e.g. posts) and this table uses
            // term_taxonomy_id for associations.
            if (!empty($resource_identifier['taxonomy'])) {
                $taxonomy = $resource_identifier['taxonomy'];
            } else {
                $taxonomy = '';
            }

            if (isset($resource_identifier['slug']) && !empty($taxonomy)) {
                $result = AAM_Framework_Manager::_()->misc->get_term_by_slug(
                    $resource_identifier['slug'], $taxonomy
                );
            } elseif (!empty($resource_identifier['id'])) {
                $result = get_term($resource_identifier['id'], $taxonomy);
            }
        } elseif(is_a($resource_identifier, WP_Term::class)) {
            $result = $resource_identifier;
        }

        if (!is_a($result, WP_Term::class)) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        // If term resource is scoped for a specific post type, add it to the
        // term object
        if (is_array($resource_identifier)
            && !empty($resource_identifier['post_type'])
        ) {
            $result->post_type = $resource_identifier['post_type'];
        } elseif (!empty($this->_settings['scope'])) {
            $result->post_type = $this->_settings['scope'];
        }

        return $result;
    }

}