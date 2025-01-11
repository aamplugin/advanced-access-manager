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
 * @method bool is_hidden_on(mixed $term_identifier, string $website_area) **[Premium Feature]**
 * @method bool is_hidden(mixed $term_identifier) **[Premium Feature]**
 * @method bool is_denied_to(mixed $term_identifier, string $permission) **[Premium Feature]**
 * @method bool is_allowed_to(mixed $term_identifier, string $permission) **[Premium Feature]**
 * @method bool deny(mixed $term_identifier, string|array $permission) **[Premium Feature]**
 * @method bool allow(mixed $term_identifier, string|array $permission) **[Premium Feature]**
 * @method bool hide(mixed $term_identifier, string|array $website_area = null) **[Premium Feature]**
 * @method bool show(mixed $term_identifier, string|array $website_area = null) **[Premium Feature]**
 * @method bool is_password_protected(mixed $post_identifier) **[Premium Feature]**
 * @method bool is_restricted(mixed $post_identifier) **[Premium Feature]**
 * @method bool is_redirected(mixed $post_identifier) **[Premium Feature]**
 * @method bool is_teaser_message_set(mixed $post_identifier) **[Premium Feature]**
 * @method bool is_access_expired(mixed $post_identifier) **[Premium Feature]**
 * @method bool set_password(mixed $post_identifier, string $password) **[Premium Feature]**
 * @method bool set_teaser_message(mixed $post_identifier, string $message) **[Premium Feature]**
 * @method bool set_redirect(mixed $post_identifier, array $redirect) **[Premium Feature]**
 * @method bool set_expiration(mixed $post_identifier, int $timestamp) **[Premium Feature]**
 * @method string|null get_password(mixed $post_identifier) **[Premium Feature]**
 * @method string|null get_teaser_message(mixed $post_identifier) **[Premium Feature]**
 * @method string|null get_redirect(mixed $post_identifier) **[Premium Feature]**
 * @method string|null get_expiration(mixed $post_identifier) **[Premium Feature]**
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
     * @param mixed $identifier [Optional]
     *
     * @return AAM_Framework_Resource_Term
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource($identifier = null)
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