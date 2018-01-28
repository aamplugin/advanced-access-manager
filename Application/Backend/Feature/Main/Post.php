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
                apply_filters('aam-type-row-actions-filter', 'drilldown,manage', $type)
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
        $all    = AAM_Core_Config::get('manage-hidden-post-types', false);
        
        foreach (get_post_types(array(), 'objects') as $type) {
            if (($all || $type->public) 
                    && (empty($s) || stripos($type->labels->name, $s) !== false)) {
                $filtered[] = $type;
            }
        }
        
        return (object) array(
            'total'    => count($list),
            'filtered' => count($filtered),
            'records'  => array_slice($filtered, $start, $length)
        );
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
        $response = array(
            'data'            => array(), 
            'recordsTotal'    => $list->total, 
            'recordsFiltered' => $list->filtered
        );
        
        foreach($list->records as $record) {
            if (isset($record->ID)) { //this is post
                $link = get_edit_post_link($record->ID, 'link');
                $response['data'][] = array(
                    $record->ID,
                    $link,
                    'post',
                    get_the_title($record),
                    apply_filters(
                        'aam-post-row-actions-filter', 
                        'manage' . ($link ? ',edit' : ''), 
                        $record
                    ),
                    //get_post_permalink($record)
                );
            } else { //term
                $response['data'][] = array(
                    $record->term_id . '|' . $record->taxonomy,
                    get_edit_term_link($record->term_id, $record->taxonomy),
                    'term',
                    $record->name,
                    apply_filters('aam-term-row-actions-filter', 'manage,edit', $record)
                );
            }
        } 


        return $response;
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
        
        $query  = "SELECT COUNT( * ) AS total FROM {$wpdb->posts} ";
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
            'number'     => $limit
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
            'post_type'   => $type, 
            'category'    => 0, 
            's'           => $search,
            'offset'      => $offset,
            'numberposts' => $limit, 
            'post_status' => 'any', 
            'fields'      => 'all'
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

        return json_encode($response);
    }
    
    /**
     * 
     * @return type
     */
    public function autocomplete() {
        $res  = array();
        $list = get_posts(array(
            'post_type'   => AAM_Core_Request::post('type'), 
            'category'    => 0, 
            's'           => AAM_Core_Request::post('s'),
            'numberposts' => 10, 
            'post_status' => 'any', 
            'fields'      => 'all'
        ));

        if (count($list)) {
            foreach($list as $post) {
                $res[] = "{$post->ID}|{$post->post_title}";
            }
        }

        return json_encode($res);
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
        if (is_a($object, 'AAM_Core_Object')) {
            foreach($object->getOption() as $key => $value) {
                if (is_bool($value) || in_array($value, array('0', '1'))) {
                    $access[$key] = ($value ? 1 : 0); //TODO - to support legacy
                } else {
                    $access[$key] = $value;
                }
            }
            $metadata = array('overwritten' => $object->isOverwritten());
        }

        return json_encode(array(
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
                $str     = strip_tags($val);
                $preview = (strlen($str) > 25 ? substr($str, 0, 22) . '...' : $str);
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
        $value = AAM_Core_Request::post('value');

        if (strpos($param, 'frontend.expire_datetime') !== false) {
            $value = date('Y-m-d H:i:s', strtotime($value));
        }

        //clear cache
        AAM_Core_Cache::clear();

        $result = $subject->save($param, $value, $object, $id);

        return json_encode(array(
            'status' => ($result ? 'success' : 'failure'),
            'value'  => $value,
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
            AAM_Core_Cache::clear();
        } else {
            $result = false;
        }
        
        return json_encode(array('status' => ($result ? 'success' : 'failure')));
    }

    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/post.phtml';
    }
    
    /**
     * 
     * @staticvar type $list
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
            $add = empty($data['exclude']) || !in_array($subject, $data['exclude']);
            
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
            'title'      => __('Posts & Pages', AAM_KEY),
            'capability' => 'aam_manage_posts',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'option'     => 'backend-access-control,frontend-access-control',
            'view'       => __CLASS__
        ));
    }

}