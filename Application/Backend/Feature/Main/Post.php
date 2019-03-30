<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend posts & pages manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Main_Post extends AAM_Backend_Feature_Abstract {
    
    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        $allowed = AAM_Backend_Subject::getInstance()->isAllowedToManage();
        if (!$allowed || !current_user_can('aam_manage_posts')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_posts'));
        }
    }
    
    /**
     * Get list for the table
     * 
     * @return string
     * 
     * @access public
     */
    public function getTable() {
        $type = trim(AAM_Core_Request::request('type'));

        if (empty($type)) {
            $response = $this->retrieveTypeList();
        } else {
            $response = $this->retrieveTypeContent($type);
        }

        return $this->wrapTable($response);
    }
    
    /**
     * Retrieve list of registered post types
     * 
     * @return array
     * 
     * @access protected
     */
    protected function retrieveTypeList() {
        $list     = $this->prepareTypeList();
        $response = array(
            'data'            => array(), 
            'recordsTotal'    => $list->total, 
            'recordsFiltered' => $list->filtered
        );
        
        foreach ($list->records as $type) {
            $response['data'][] = array(
                $type->name, 
                null, 
                'type', 
                $type->labels->name, 
                'drilldown,manage',
                null,
                apply_filters(
                    'aam-type-override-status', 
                    false, 
                    $type->name, 
                    AAM_Backend_Subject::getInstance()
                )
            );
        }
        
        return $response;
    }
    
    /**
     * 
     * @return type
     */
    protected function prepareTypeList() {
        $list     = get_post_types(array(), 'objects');
        $filtered = array();
        
        //filters
        $s      = AAM_Core_Request::post('search.value');
        $length = AAM_Core_Request::post('length');
        $start  = AAM_Core_Request::post('start');
        $all    = AAM_Core_Config::get('core.settings.manageHiddenPostTypes', false);
        
        foreach (get_post_types(array(), 'objects') as $type) {
            if (($all || $type->show_ui) 
                    && (empty($s) || stripos($type->labels->name, $s) !== false)) {
                $filtered[$type->label] = $type;
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
     * 
     * @return type
     */
    protected function getOrderDirection() {
        $dir   = 'asc';
        $order = AAM_Core_Request::post('order.0');
        
        if (!empty($order['column']) && ($order['column'] === '3')) {
            $dir = !empty($order['dir']) ? $order['dir'] : 'asc';
        }
        
        return strtoupper($dir);
    }

    /**
     * Get post type children
     * 
     * Retrieve list of all posts and terms that belong to specified post type
     * 
     * @param string $type
     * 
     * @return array
     * 
     * @access protected
     */
    protected function retrieveTypeContent($type) {
        $list     = $this->prepareContentList($type);
        $subject  = AAM_Backend_Subject::getInstance();
        $response = array(
            'data'            => array(), 
            'recordsTotal'    => $list->total, 
            'recordsFiltered' => $list->filtered
        );
        
        foreach($list->records as $record) {
            if (isset($record->ID)) { //this is post
                $link = get_edit_post_link($record->ID, 'link');
                
                $parent = '';
                
                if (!empty($record->post_parent)) {
                    $p = get_post($record->post_parent);
                    $parent = (is_a($p, 'WP_Post') ? $p->post_title : '');
                }
                
                if (empty($parent)) {
                    $taxonomies = array_filter(
                        get_object_taxonomies($record), 'is_taxonomy_hierarchical'
                    );
                    if (!empty($taxonomies)) {
                        $terms  = wp_get_object_terms(
                                $record->ID, $taxonomies, array('fields' => 'names')
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
                    $subject->getObject('post', $record->ID)->isOverwritten()
                );
            } else { //term
                $response['data'][] = array(
                    $record->term_id . '|' . $record->taxonomy . '|' . $type,
                    get_edit_term_link($record->term_id, $record->taxonomy),
                    'term',
                    $record->name,
                    implode(',', apply_filters('aam-term-row-actions', array('manage', 'edit'), $subject, $record, $type)),
                    rtrim($this->getParentTermList($record), '/'),
                    apply_filters(
                        'aam-term-override-status', 
                        false, 
                        $record->term_id . '|' . $record->taxonomy, 
                        $subject
                    )
                );
            }
        }

        return $response;
    }
    
    /**
     * 
     * @global type $wp_version
     * @param type $term
     * @return type
     * @todo Remove when min WP version will be 4.8
     */
    protected function getParentTermList($term) {
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
     * 
     * @return type
     */
    protected function prepareContentList($type) {
        $list   = array();
        //filters
        $s      = AAM_Core_Request::post('search.value');
        $length = AAM_Core_Request::post('length');
        $start  = AAM_Core_Request::post('start');
        
        //calculate how many term and/or posts we need to fetch
        $paging = $this->getFetchPagination($type, $s, $start, $length);
        
        //first retrieve all hierarchical terms that belong to Post Type
        if ($paging['terms']) {
            $list = $this->retrieveTermList(
                $this->getTypeTaxonomies($type), 
                $s, 
                $paging['term_offset'], 
                $paging['terms']
            );
        }
        
        //retrieve all posts
        if ($paging['posts']) {
            $list = array_merge(
                $list, 
                $this->retrievePostList(
                    $type, $s, $paging['post_offset'], $paging['posts']
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
     * 
     * @param type $type
     * @return type
     */
    protected function getTypeTaxonomies($type) {
        $list = array();
        
        foreach (get_object_taxonomies($type) as $name) {
            if (is_taxonomy_hierarchical($name)) {
                //get all terms that have no parent category
                $list[] = $name;
            }
        }
        
        return $list;
    }
    
    /**
     * 
     * @param type $type
     * @param type $search
     * @param type $offset
     * @param type $limit
     * @return type
     */
    protected function getFetchPagination($type, $search, $offset, $limit) {
        $result = array('terms' => 0, 'posts' => 0, 'term_offset' => $offset);
        
        //get terms count
        $taxonomy = $this->getTypeTaxonomies($type);
        
        if (!empty($taxonomy)) {
            $terms = get_terms(array(
                'fields'     => 'count', 
                'search'     => $search, 
                'hide_empty' => false, 
                'taxonomy'   => $taxonomy
            ));
        } else {
            $terms = 0;
        }
        
        //get posts count
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
        
        $result['total']       = $terms + $posts;
        $result['post_offset'] = ($offset ? $offset - $terms : 0);
        
        return $result;
    }
    
    /**
     * 
     * @global type $wpdb
     * @param type $type
     * @param type $search
     * @return type
     */
    protected function getPostCount($type, $search) {
        global $wpdb;
        
        $query  = "SELECT COUNT(*) AS total FROM {$wpdb->posts} ";
        $query .= "WHERE (post_type = %s) AND (post_title LIKE %s)";
        
        $args   = array($type, "{$search}%");
        
        foreach (get_post_stati(array( 'exclude_from_search' => true)) as $status ) {
            $query .= " AND ({$wpdb->posts}.post_status <> %s)";
            $args[] = $status;
        }
        
        return $wpdb->get_var($wpdb->prepare($query, $args));
    }
    
    /**
     * Retrieve term list
     * 
     * @param array $taxonomies
     * 
     * @return array
     * 
     * @access protected
     */
    protected function retrieveTermList($taxonomies, $search, $offset, $limit) {
        $args = array(
            'fields'     => 'all', 
            'hide_empty' => false, 
            'search'     => $search, 
            'taxonomy'   => $taxonomies,
            'offset'     => $offset,
            'number'     => $limit,
            'order'      => $this->getOrderDirection()
        );

        return get_terms($args);
    }
    
    /**
     * 
     * @param type $type
     * @param type $search
     * @param type $offset
     * @param type $limit
     * @return type
     */
    protected function retrievePostList($type, $search, $offset, $limit) {
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
     * Prepare response
     * 
     * @param array $response
     * 
     * @return string
     * 
     * @access protected
     */
    protected function wrapTable($response) {
        $response['draw'] = AAM_Core_Request::request('draw');

        return wp_json_encode($response);
    }
    
    /**
     * Get Post or Term access
     *
     * @return string
     *
     * @access public
     */
    public function getAccess() {
        $type   = trim(AAM_Core_Request::post('type'));
        $id     = AAM_Core_Request::post('id');
        $access = $metadata = array();
        $object = AAM_Backend_Subject::getInstance()->getObject($type, $id);
        
        //prepare the response object
        $bValues = array(1, '1', 0, '0', false, "false", true, "true");
        if (is_a($object, 'AAM_Core_Object')) {
            foreach($object->getOption() as $key => $value) {
                if (in_array($value, $bValues, true)) {
                    $access[$key] = !empty($value);
                } else {
                    $access[$key] = $value;
                }
            }
            $metadata = array('overwritten' => $object->isOverwritten());
            $access   = apply_filters('aam-get-post-access-filter', $access, $object);
        }
        
        return wp_json_encode(array(
            'access'  => $access, 
            'meta'    => $metadata,
            'preview' => $this->preparePreviewValues($access)
        ));
    }
    
    /**
     * 
     * @param type $options
     * @return type
     */
    protected function preparePreviewValues($options) {
        $previews = array();
        
        foreach($options as $option => $value) {
            $previews[$option] = $this->getPreviewValue($option, $value);
        }
        
        return $previews;
    }
    
    /**
     * 
     * @param type $option
     * @param type $val
     * @return type
     */
    protected function getPreviewValue($option, $val) {
        switch($option) {
            case 'frontend.teaser':
                $str = wp_strip_all_tags($val);
                if (function_exists('mb_strlen')) {
                    $preview = (mb_strlen($str) > 25 ? mb_substr($str, 0, 22) . '...' : $str);
                } else {
                    $preview = (strlen($str) > 25 ? substr($str, 0, 22) . '...' : $str);
                }
                break;
                
            case 'frontend.location':
                if (!empty($val)) {
                    $chunks = explode('|', $val);
                    if ($chunks[0] === 'page') {
                        $preview = __('Existing Page', AAM_KEY);
                    } elseif ($chunks[0] === 'url') {
                        $preview = __('Valid URL', AAM_KEY);
                    } elseif ($chunks[0] === 'callback') {
                        $preview = __('Custom Callback', AAM_KEY);
                    } elseif ($chunks[0] === 'login') {
                        $preview = __('Redirect To Login Page', AAM_KEY);
                    }
                }
                break;
            
            default:
                $preview = apply_filters(
                    'aam-post-option-preview-filter', $val, $option
                );
                break;
        }
        
        return $preview;
    }
    
    /**
     * Save post properties
     * 
     * @return string
     * 
     * @access public
     */
    public function save() {
        $subject = AAM_Backend_Subject::getInstance();

        $object = trim(AAM_Core_Request::post('object'));
        $id     = AAM_Core_Request::post('objectId', null);

        $param = AAM_Core_Request::post('param');
        $value = filter_input(INPUT_POST, 'value');

        //clear cache
        AAM_Core_API::clearCache();

        $result = $subject->save($param, $value, $object, $id);

        return wp_json_encode(array(
            'status'  => ($result ? 'success' : 'failure'),
            'value'   => $value,
            'preview' => $this->getPreviewValue($param, $value)
        ));
    }
    
    /**
     * Reset the object settings
     * 
     * @return string
     * 
     * @access public
     */
    public function reset() {
        $type = trim(AAM_Core_Request::post('type'));
        $id   = AAM_Core_Request::post('id', 0);

        $object = AAM_Backend_Subject::getInstance()->getObject($type, $id);
        if ($object instanceof AAM_Core_Object) {
            $result = $object->reset();
            //clear cache
            AAM_Core_API::clearCache();
        } else {
            $result = false;
        }
        
        return wp_json_encode(array('status' => ($result ? 'success' : 'failure')));
    }

    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/post.phtml';
    }
    
    /**
     * 
     * @param type $area
     * @return type
     */
    public static function getAccessOptionList($area) {
        static $cache = null;
        
        if (is_null($cache)) {
            $cache = AAM_Backend_View_PostOptionList::get();
        }
        
        $subject = AAM_Backend_Subject::getInstance()->getUID();
        $list    = apply_filters(
                'aam-post-access-options-filter', $cache[$area], $area
        );
        
        $filtered = array();
        foreach($list as $option => $data) {
            $add = empty($data['exclude']) || !in_array($subject, $data['exclude'], true);
            
            if ($add) {
               $add = empty($data['config']) || AAM_Core_Config::get($data['config'], true); 
            }
            
            if ($add) {
                $filtered[$option] = $data;
            }
        }
        
        return $filtered;
    }
    
    /**
     * 
     * @param type $renderBackButton
     * @param type $extraClass
     */
    public static function renderAccessForm() {
        ob_start();
        require_once AAM_BASEDIR . '/Application/Backend/phtml/partial/post-access-form.phtml';
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
    
    /**
     * 
     * @return type
     */
    public static function getCurrentObject() {
        $object = (object) array(
            'id'   => urldecode(AAM_Core_Request::request('oid')),
            'type' => AAM_Core_Request::request('otype')
        );
        
        if ($object->id) {
            if (strpos($object->id, '|') !== false) { //term
                $part = explode('|', $object->id);
                $object->term = get_term($part[0], $part[1]);
            } else {
                $object->post = get_post($object->id);
            }
        }
        
        return $object;
    }

    /**
     * Register Posts & Pages feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'post',
            'position'   => 20,
            'title'      => __('Posts & Terms', AAM_KEY),
            'capability' => 'aam_manage_posts',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'option'     => 'core.settings.backendAccessControl,core.settings.frontendAccessControl,core.settings.apiAccessControl',
            'view'       => __CLASS__
        ));
    }

}