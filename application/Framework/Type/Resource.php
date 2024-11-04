<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Resource types
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Type_Resource
{

    /**
     * Resource type that represents a single WordPress post
     *
     * @version 7.0.0
     */
    const POST = 'post';

    /**
     * Resource type that represents a single WordPress post type
     *
     * @version 7.0.0
     */
    const POST_TYPE = 'post_type';

    /**
     * Resource type that represents a single WordPress taxonomy
     *
     * @version 7.0.0
     */
    const TAXONOMY = 'taxonomy';

    /**
     * Resource type that represents a single WordPress term
     *
     * @version 7.0.0
     */
    const TERM = 'term';

    /**
     * Resource type that represents access denied redirect preferences
     *
     * @version 7.0.0
     */
    const ACCESS_DENIED_REDIRECT = 'denied_redirect';

    /**
     * Resource type that represents WordPress admin toolbar
     *
     * @version 7.0.0
     */
    const TOOLBAR = 'admin_toolbar';

    /**
     * Resource type that represents all RESTful API endpoints
     *
     * @version 7.0.0
     */
    const API_ROUTE = 'api_route';

    /**
     * Resource type that represents WordPress backend menu
     *
     * @version 7.0.0
     */
    const BACKEND_MENU = 'backend_menu';

    /**
     * Resource type that represents all traditional WordPress metaboxes
     *
     * WordPress metaboxes are functional UI blocks that are rendered only post, page
     * or custom post type edit page.
     *
     * @version 7.0.0
     */
    const METABOX = 'metabox';

    /**
     * Resource type that represents all dashboard and frontend widgets
     *
     * @version 7.0.0
     */
    const WIDGET = 'widget';

    /**
     * Resource type that represents WordPress identities like roles, users or levels
     *
     * @version 7.0.0
     */
    const IDENTITY = 'identity';

    /**
     * Resource type that represents logout redirect preferences
     *
     * @version 7.0.0
     */
    const LOGOUT_REDIRECT = 'logout_redirect';

    /**
     * Resource type that represents login redirect preferences
     *
     * @version 7.0.0
     */
    const LOGIN_REDIRECT = 'login_redirect';

    /**
     * Resource type that represents 404 (Not Found) redirect preferences
     *
     * @version 7.0.0
     */
    const NOT_FOUND_REDIRECT = 'not_found_redirect';

    /**
     * Resource type that represents all URLs on a WordPress website
     *
     * @version 7.0.0
     */
    const URL = 'url';

    /**
     * Resource type that represents all JSON access policies
     *
     * @version 7.0.0
     */
    const ACCESS_POLICY = 'policy';

}