<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post Types framework service
 *
 * @method bool is_hidden_on(string|WP_Post_Type $post_type, string $website_area) **[Premium Feature]**
 * @method bool is_hidden(string|WP_Post_Type $post_type) **[Premium Feature]**
 * @method bool is_password_protected(string|WP_Post_Type $post_type) **[Premium Feature]**
 * @method bool is_restricted(string|WP_Post_Type $post_type) **[Premium Feature]**
 * @method bool is_redirected(string|WP_Post_Type $post_type) **[Premium Feature]**
 * @method bool is_teaser_message_set(string|WP_Post_Type $post_type) **[Premium Feature]**
 * @method bool is_denied_to(string|WP_Post_Type $post_type, string $permission) **[Premium Feature]**
 * @method bool is_allowed_to(string|WP_Post_Type $post_type, string $permission) **[Premium Feature]**
 * @method bool is_access_expired(string|WP_Post_Type $post_type, string $permission) **[Premium Feature]**
 * @method bool set_password(string|WP_Post_Type $post_type, string $password, bool $exclude_authors = false) **[Premium Feature]**
 * @method bool set_teaser_message(string|WP_Post_Type $post_type, string $message, bool $exclude_authors = false) **[Premium Feature]**
 * @method bool set_redirect(string|WP_Post_Type $post_type, array $redirect, bool $exclude_authors = false) **[Premium Feature]**
 * @method bool set_expiration(string|WP_Post_Type $post_type, int $timestamp, bool $exclude_authors = false) **[Premium Feature]**
 * @method bool deny(string|WP_Post_Type $post_type, string|array $permission, bool $exclude_authors = false) **[Premium Feature]**
 * @method bool allow(string|WP_Post_Type $post_type, string|array $permission, bool $exclude_authors = false) **[Premium Feature]**
 * @method bool hide(string|WP_Post_Type $post_type, string|array $website_area = null, bool $exclude_authors = false) **[Premium Feature]**
 * @method bool show(string|WP_Post_Type $post_type, string|array $website_area = null) **[Premium Feature]**
 * @method string|null get_password(string|WP_Post_Type $post_type) **[Premium Feature]**
 * @method string|null get_teaser_message(string|WP_Post_Type $post_type) **[Premium Feature]**
 * @method array|null get_redirect(string|WP_Post_Type $post_type) **[Premium Feature]**
 * @method int|null get_expiration(string|WP_Post_Type $post_type) **[Premium Feature]**
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_PostTypes
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get post type resource
     *
     * @return AAM_Framework_Resource_PostType
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::POST_TYPE
        );
    }

    /**
     * @inheritDoc
     *
     * @return WP_Post_Type
     */
    private function _normalize_resource_identifier($resource_identifier)
    {
        if (is_string($resource_identifier)) {
            $result = get_post_type_object($resource_identifier);
        } elseif (is_a($resource_identifier, WP_Post_Type::class)) {
            $result = $resource_identifier;
        }

        if (!is_a($result, WP_Post_Type::class)) {
            throw new OutOfRangeException('Invalid post type resource identifier');
        }

        return $result;
    }

}