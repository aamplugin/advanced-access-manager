<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * Backend posts & terms service UI
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Main_Post
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_ServiceTrait,
        AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_posts';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Post::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/post.php';

    /**
     * Get posts & terms list
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function getTable()
    {
        $id = $this->getFromPost('typeId');

        switch($this->getFromPost('type')) {
            case 'taxonomy':
                $response = $this->retrieveTaxonomyTerms($id);
                break;

            case 'type':
                $response = $this->retrievePostTypeObjects($id);
                break;

            default:
                $response = $this->retrieveRootLevelList();
                break;
        }

        // Extend the response with some required props and return JSON
        // response.
        $response['draw'] = $this->getFromRequest('draw');

        return wp_json_encode($response);
    }

    /**
     * Get access form with pre-populated data
     *
     * @param mixed  $id
     * @param string $type
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function getAccessForm($id, $type)
    {
        $object = $this->getSubject()->getObject($type, $id);
        $view   = AAM_Backend_View::getInstance();
        $args   = array(
            'object'    => $object,
            'type'      => $type,
            'id'        => $id,
            'subject'   => $this->getSubject(),
            'httpCodes' => $this->getRedirectHttpCodes(),
            'previews'  => $this->preparePreviewValues(
                apply_filters(
                    'aam_post_preview_options_filter',
                    ($object ? $object->getOption() : array()),
                    $object
                )
            )
        );

        // Prepare HTML response
        switch ($type) {
            case 'term':
                $chunks = explode('|', $id);
                $args['term']     = get_term($chunks[0], $chunks[1]);
                $args['postType'] = (isset($chunks[2]) ? $chunks[2] : null);

                $response = apply_filters(
                    'aam_term_access_form_filter',
                    $view->loadPartial('term-access-form', $args),
                    (object) $args
                );
                break;

            case 'taxonomy':
                $args['taxonomy'] = get_taxonomy($id);

                $response = apply_filters(
                    'aam_taxonomy_access_form_filter',
                    $view->loadPartial('taxonomy-access-form', $args),
                    (object) $args
                );
                break;

            case 'type':
                $args['postType'] = get_post_type_object($id);

                $response = apply_filters(
                    'aam_type_access_form_filter',
                    $view->loadPartial('type-access-form', $args),
                    (object) $args
                );
                break;

            case 'post':
                $args['postType'] = get_post_type_object($object->post_type);
                $args['options']  = $this->getAccessOptionList();

                $response = $view->loadPartial('post-access-form', $args);
                break;

            default:
                $response = null;
                break;
        }

        return $response;
    }

    /**
     * Decorate additional view elements for access settings
     *
     * This method is necessary to prepare some preview information for access
     * options like LIMIT or REDIRECT.
     *
     * @param array $options
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function preparePreviewValues($options)
    {
        $previews = array();

        foreach ($options as $option => $value) {
            $previews[$option] = $this->getPreviewValue($option, $value);
        }

        return $previews;
    }

    /**
     * Get post object access options
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getAccessOptionList()
    {
        $list = apply_filters(
            'aam_post_access_options_filter', AAM_Backend_View_PostOptionList::get()
        );

        return array_filter($list, function ($opt) {
            $type = $this->getSubject()->getSubjectType();

            return empty($opt['exclude']) || !in_array($type, $opt['exclude'], true);
        });
    }

    /**
     * Get list of HTTP redirect types
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getRedirectHttpCodes()
    {
        return apply_filters('aam_content_redirect_http_codes', array(
            '307' => __('307 - Temporary Redirect (Default)', AAM_KEY),
            '301' => __('301 - Moved Permanently', AAM_KEY),
            '303' => __('303 - See Other', AAM_KEY)
        ));
    }

    /**
     * Prepare readable preview value
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getPreviewValue($option, $value)
    {
        switch ($option) {
            case 'teaser':
                $preview = $this->prepareTeaserPreview($value);
                break;

            case 'limited':
                $preview = $this->prepareLimitedPreview($value);
                break;

            case 'redirected':
                $preview = $this->prepareRedirectPreview($value);
                break;

            case 'protected':
                $preview = $value['password'];
                break;

            case 'ceased':
                $preview = $this->prepareCeasePreview($value);
                break;

            default:
                $preview = apply_filters(
                    'aam_post_option_preview_filter',
                    '',
                    $value,
                    $option
                );
                break;
        }

        return $preview;
    }

    /**
     * Prepare teaser message preview
     *
     * @param array $teaser
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareTeaserPreview($teaser)
    {
        $preview = null;

        if (!empty($teaser['message'])) {
            // Remove all HTML tags first
            $str = wp_strip_all_tags($teaser['message']);

            // Take in consideration UTF-8 encoding
            if (function_exists('mb_strlen')) {
                $preview = (mb_strlen($str) > 25 ? mb_substr($str, 0, 22) . '...' : $str);
            } else {
                $preview = (strlen($str) > 25 ? substr($str, 0, 22) . '...' : $str);
            }
        }

        return $preview;
    }

    /**
     * Prepare limited option preview
     *
     * @param array $limited
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareLimitedPreview($limited)
    {
        $preview = null;

        if (!empty($limited['threshold'])) {
            $preview = sprintf(__('%d times', AAM_KEY), $limited['threshold']);
        }

        return $preview;
    }

    /**
     * Prepare redirect option preview
     *
     * @param array $redirect
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareRedirectPreview($redirect)
    {
        switch (isset($redirect['type']) ? $redirect['type'] : null) {
            case 'page':
                $page    = get_post($redirect['destination']);
                $preview = sprintf(
                    __('"%s" page', AAM_KEY),
                    (is_a($page, 'WP_Post') ? $page->post_title : '')
                );
                break;

            case 'url':
                $preview = sprintf(__('%s URL', AAM_KEY), $redirect['destination']);
                break;

            case 'login':
                $preview = __('Login page', AAM_KEY);
                break;

            case 'callback':
                $preview = $redirect['destination'];
                break;

            default:
                $preview = null;
                break;
        }

        return $preview;
    }

    /**
     * Prepare ceased option preview
     *
     * @param array $cease
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareCeasePreview($cease)
    {
        return (!empty($cease['after']) ? $cease['after'] : null);
    }

    /**
     * Save Posts & Terms access properties
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $type  = $this->getFromPost('object');
        $id    = $this->getFromPost('objectId');
        $param = $this->getFromPost('param');
        $value = $this->sanitizeOption($param, AAM_Core_Request::post('value'));

        $object = $this->getSubject()->getObject($type, $id, true);
        $result = $object->updateOptionItem($param, $value)->save();

        return wp_json_encode(array(
            'status'  => ($result ? 'success' : 'failure')
        ));
    }

    /**
     * Reset view counter
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function resetCounter()
    {
        $type  = $this->getFromPost('object');
        $id    = $this->getFromPost('objectId');

        if ($type === 'post') {
            $result = delete_user_option(
                $this->getSubject()->getId(),
                sprintf(AAM_Service_Content::POST_COUNTER_DB_OPTION, $id)
            );
        } else {
            $result = apply_filters(
                'aam_ajax_filter', false, $this->getSubject(), 'Main_Post.resetCounter'
            );
        }

        return wp_json_encode(array(
            'status'  => ($result ? 'success' : 'failure')
        ));
    }

    /**
     * Reset the object access settings
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function reset()
    {
        $type   = $this->getFromPost('type');
        $id     = $this->getFromPost('id');
        $result = $this->getSubject()->getObject($type, $id)->reset();

        return wp_json_encode(array('status' => ($result ? 'success' : 'failure')));
    }

    /**
     * Sanitize and normalize the access settings
     *
     * Depending on the type of access, normalize and sanitize the incoming data
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return mixed
     *
     * @access protected
     * @version 6.0.0
     */
    protected function sanitizeOption($option, $value)
    {
        if (is_array($value)) {
            $value['enabled'] = filter_var($value['enabled'], FILTER_VALIDATE_BOOLEAN);
        } else { // Any scalar value has to be boolean
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $value;
    }

    /**
     * Check if post can be managed for current subject
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function isAllowedToManageCurrentSubject()
    {
        return apply_filters(
            'aam_posts_terms_manage_subject_filter',
            !$this->getSubject()->isDefault(),
            $this->getSubject()->getSubject()
        );
    }

    /**
     * Retrieve list of registered post types & taxonomies
     *
     * The Root level contains the list of all registered post types that are public
     * as well as all the registered taxonomies
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function retrieveRootLevelList()
    {
        $list     = $this->prepareRootLevelList();
        $response = array(
            'data'            => array(),
            'recordsTotal'    => $list->total,
            'recordsFiltered' => $list->filtered
        );

        foreach ($list->records as $type) {
            if (is_a($type, 'WP_Post_Type')) {
                $response['data'][] = array(
                    $type->name,
                    null,
                    'type',
                    $type->labels->name,
                    'drilldown,manage',
                    null,
                    apply_filters(
                        'aam_type_settings_override_status_filter',
                        false,
                        $type->name,
                        $this->getSubject()
                    )
                );
            } elseif(is_a($type, 'WP_Taxonomy')) {
                $response['data'][] = array(
                    $type->name,
                    null,
                    'taxonomy-' . ($type->hierarchical ? 'category' : 'tag'),
                    $type->labels->name,
                    'drilldown,manage',
                    null,
                    apply_filters(
                        'aam_taxonomy_settings_override_status_filter',
                        false,
                        $type->name,
                        $this->getSubject()
                    )
                );
            }
        }

        return $response;
    }

    /**
     * Prepare the list of root level objects
     *
     * @return object
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareRootLevelList()
    {
        $args = array('public' => true);
        $list = array_merge(
            get_post_types($args, 'objects'), // Get all registered post types
            get_taxonomies($args, 'objects') // Get all registered taxonomies
        );

        $filtered = array();

        // Apply filters
        $s      = AAM_Core_Request::post('search.value');
        $length = AAM_Core_Request::post('length');
        $start  = AAM_Core_Request::post('start');

        foreach ($list as $type) {
            if (empty($s) || stripos($type->labels->name, $s) !== false) {
                $filtered[get_class($type) . '_' . $type->name] = $type;
            }
        }

        $this->getOrderDirection() === 'ASC' ? ksort($filtered) : krsort($filtered);

        return (object) array(
            'total'    => count($list),
            'filtered' => count($filtered),
            'records'  => array_slice($filtered, $start, $length)
        );
    }

    /**
     * Retrieve list of all terms that belong to specific taxonomy
     *
     * @param string $taxonomy
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function retrieveTaxonomyTerms($taxonomy)
    {
        $list = $this->retrieveTermList(
            $taxonomy,
            AAM_Core_Request::post('search.value'),
            $this->getFromPost('start'),
            $this->getFromPost('length')
        );

        $countFiltered = get_terms(array(
            'fields'          => 'count',
            'search'          => AAM_Core_Request::post('search.value'),
            'hide_empty'      => false,
            'suppress_filter' => true,
            'taxonomy'        => $taxonomy
        ));
        $count = get_terms(array(
            'fields'          => 'count',
            'hide_empty'      => false,
            'suppress_filter' => true,
            'taxonomy'        => $taxonomy
        ));

        $response = array(
            'data'            => array(),
            'recordsTotal'    => $count,
            'recordsFiltered' => $countFiltered
        );

        foreach ($list as $term) {
            $response['data'][] = $this->_prepareTermRow($term);
        }

        return $response;
    }

    /**
     * Get correct table order
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getOrderDirection()
    {
        $dir   = 'asc';
        $order = AAM_Core_Request::post('order.0');

        if (!empty($order['column']) && ($order['column'] === '3')) {
            $dir = !empty($order['dir']) ? $order['dir'] : 'asc';
        }

        return strtoupper($dir);
    }

    /**
     * Retrieve list of all posts and terms that belong to specified post type
     *
     * @param string $type
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function retrievePostTypeObjects($type)
    {
        $list     = $this->preparePostTermList($type);
        $subject  = $this->getSubject();
        $response = array(
            'data'            => array(),
            'recordsTotal'    => $list->total,
            'recordsFiltered' => $list->filtered
        );

        foreach ($list->records as $record) {
            if (isset($record->ID)) { // this is a post
                $link = get_edit_post_link($record->ID, 'link');

                $parent = '';

                if (!empty($record->post_parent)) {
                    $p = get_post($record->post_parent);
                    $parent = (is_a($p, 'WP_Post') ? $p->post_title : '');
                }

                if (empty($parent)) {
                    $taxonomies = get_object_taxonomies($record);

                    if (!empty($taxonomies)) {
                        $terms  = wp_get_object_terms(
                            $record->ID,
                            $taxonomies,
                            array('fields' => 'names', 'suppress_filter' => true)
                        );
                        $parent = implode(', ', $terms);
                    }
                }

                $response['data'][] = array(
                    $record->ID,
                    $link,
                    'post',
                    get_the_title($record),
                    'manage' . ($link ? ',edit' : ',no-edit'),
                    $parent,
                    $subject->getObject('post', $record->ID, true)->isOverwritten()
                );
            } else { // this is a term
                $response['data'][] = $this->_prepareTermRow($record, $type);
            }
        }

        return $response;
    }

    /**
     * Prepare the term row for the table view
     *
     * @param WP_Term $term
     * @param string  $type
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _prepareTermRow($term, $type = null)
    {
        // Prepare list of actions
        $actions = apply_filters(
            'aam_term_row_actions',
            array('manage', 'edit'),
            $this->getSubject(),
            $term,
            $type
        );

        // Prepare row id
        $id = $term->term_id . '|' . $term->taxonomy . ($type ? '|' . $type : '');

        $is_cat = is_taxonomy_hierarchical($term->taxonomy);
        $path   = ($is_cat ? rtrim($this->getParentTermList($term), '/') : '');

        return array(
            $id,
            get_edit_term_link($term->term_id, $term->taxonomy),
            ($is_cat ? 'cat' : 'tag'),
            $term->name,
            implode(',', $actions),
            $path,
            apply_filters(
                'aam_term_settings_override_status_filter', false, $id, $this->getSubject()
            )
        );
    }

    /**
     * Get list of parent terms
     *
     * @param WP_Term $term
     *
     * @return string
     *
     * @access protected
     * @global string $wp_version
     * @version 6.0.0
     */
    protected function getParentTermList($term)
    {
        global $wp_version;

        $list = '';
        $args = array(
            'link'      => false,
            'format'    => 'name',
            'separator' => '/',
            'inclusive' => false
        );

        if (version_compare($wp_version, '4.8.0') === -1) {
            $term = get_term($term->term_id, $term->taxonomy);

            foreach (array('link', 'inclusive') as $bool) {
                $args[$bool] = wp_validate_boolean($args[$bool]);
            }

            $parents = get_ancestors($term->term_id, $term->taxonomy, 'taxonomy');

            foreach (array_reverse($parents) as $term_id) {
                $parent = get_term($term_id, $term->taxonomy);

                if ($args['link']) {
                    $url = esc_url(get_term_link($parent->term_id, $term->taxonomy));
                    $list .= sprintf('<a href="%s">%s</a>', $url, $parent->name);
                } else {
                    $list .= $parent->name;
                }
                $list .= $args['separator'];
            }
        } else {
            $list = get_term_parents_list($term->term_id, $term->taxonomy, $args);
        }

        return $list;
    }

    /**
     * Prepare the list of posts and terms that are related to specific post type
     *
     * @param string $type
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function preparePostTermList($type)
    {
        $list   = array();

        // Retrieve filters
        $s      = AAM_Core_Request::post('search.value');
        $length = $this->getFromPost('length', FILTER_VALIDATE_INT);
        $start  = $this->getFromPost('start', FILTER_VALIDATE_INT);

        // Calculate how many term and/or posts we need to fetch
        $paging = $this->getFetchPagination($type, $s, $start, $length);

        // First retrieve all terms that belong to Post Type
        if ($paging['terms']) {
            $list = $this->retrieveTermList(
                get_object_taxonomies($type),
                $s,
                $paging['term_offset'],
                $paging['terms']
            );
        }

        // Retrieve all posts
        if ($paging['posts']) {
            $list = array_merge(
                $list,
                $this->retrievePostList(
                    $type,
                    $s,
                    $paging['post_offset'],
                    $paging['posts']
                )
            );
        }

        return (object) array(
            'total'    => $paging['total'],
            'filtered' => $paging['total'],
            'records'  => $list
        );
    }

    /**
     * Compute information for the pagination
     *
     * @param string $type
     * @param string $search
     * @param int    $offset
     * @param int    $limit
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getFetchPagination($type, $search, $offset, $limit)
    {
        $result = array('terms' => 0, 'posts' => 0, 'term_offset' => $offset);

        // Get terms count
        $taxonomy = get_object_taxonomies($type);

        if (!empty($taxonomy)) {
            $terms = get_terms(array(
                'fields'          => 'count',
                'search'          => $search,
                'hide_empty'      => false,
                'suppress_filter' => true,
                'taxonomy'        => $taxonomy
            ));
        } else {
            $terms = 0;
        }

        // Get posts count
        $posts = $this->getPostCount($type, $search);

        if ($offset < $terms) {
            if ($terms - $limit >= $offset) {
                $result['terms'] = $limit;
            } else {
                $result['terms'] = $terms - $offset;
                $result['posts'] = $limit - $result['terms'];
            }
        } else {
            $result['posts'] = $limit;
        }

        // Calculate post offset
        $post_offset = ($offset ? $offset - $terms : 0);

        $result['total']       = $terms + $posts;
        $result['post_offset'] = ($post_offset < 0 ? 0 : $post_offset);

        return $result;
    }

    /**
     * Get list of posts
     *
     * Perform separate computation for the list of posts based on type and search
     * criteria
     *
     * @param string $type
     * @param string $search
     *
     * @return int
     *
     * @access protected
     * @global type $wpdb
     * @version 6.0.0
     */
    protected function getPostCount($type, $search)
    {
        global $wpdb;

        $query  = "SELECT COUNT(*) AS total FROM {$wpdb->posts} ";
        $query .= "WHERE (post_type = %s) AND (post_title LIKE %s || ";
        $query .= "post_excerpt LIKE %s || post_content LIKE %s)";

        $args   = array($type, "%{$search}%", "%{$search}%", "%{$search}%");

        foreach (get_post_stati(array('show_in_admin_all_list' => false)) as $status) {
            $query .= " AND ({$wpdb->posts}.post_status <> %s)";
            $args[] = $status;
        }

        return $wpdb->get_var($wpdb->prepare($query, $args));
    }

    /**
     * Retrieve term list
     *
     * @param array  $taxonomies
     * @param string $search
     * @param int    $offset
     * @param int    $limit
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function retrieveTermList($taxonomies, $search, $offset, $limit)
    {
        $args = array(
            'fields'          => 'all',
            'hide_empty'      => false,
            'search'          => $search,
            'suppress_filter' => true,
            'taxonomy'        => $taxonomies,
            'offset'          => $offset,
            'number'          => $limit,
            'order'           => $this->getOrderDirection()
        );

        return get_terms($args);
    }

    /**
     * Get list of posts for specific post type
     *
     * @param string $type
     * @param string $search
     * @param int    $offset
     * @param int    $limit
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function retrievePostList($type, $search, $offset, $limit)
    {
        return get_posts(array(
            'post_type'        => $type,
            'category'         => 0,
            's'                => $search,
            'suppress_filters' => true,
            'offset'           => $offset,
            'numberposts'      => $limit,
            'orderby'          => 'title',
            'order'            => $this->getOrderDirection(),
            'post_status'      => 'any',
            'fields'           => 'all'
        ));
    }

    /**
     * Register Posts & Pages service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'post',
            'position'   => 20,
            'title'      => __('Posts & Terms', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}