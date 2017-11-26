<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

/**
 * AAM Media Access
 *
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Media {

    /**
     * Instance of itself
     * 
     * @var AAM_PlusPackage 
     * 
     * @access private
     */
    private static $_instance = null;
    
    /**
     *
     * @var type 
     */
    protected $request = '';
    
    /**
     *
     * @var type 
     */
    protected $request_uri = '';
    
    /**
     * Initialize the extension
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        if (AAM_Core_Request::get('aam-media')) {
            $this->initialize();
            
            if (AAM_Core_Config::get('media-access-control', false)) {
                $area = (is_admin() ? 'backend' : 'frontend');
                if (AAM_Core_Config::get("{$area}-access-control", true)) {
                    $this->checkMediaAccess();
                } else {
                    $this->printMedia();
                }
            } else {
                $this->printMedia();
            }
        }
    }
    
    /**
     * 
     */
    protected function initialize() {
        $media   = filter_input(INPUT_GET, 'aam-media');
        $request = ($media != '1' ? $media : urldecode(AAM_Core_Request::server('REQUEST_URI')));
        $root    = AAM_Core_Request::server('DOCUMENT_ROOT');
        
        $this->request     = str_replace('\\', '/', $root . $request);
        $this->request_uri = preg_replace('/\?.*$/', '', $request);
    }
    
    /**
     * Check media access
     * 
     * @return void
     * 
     * @access protected
     */
    protected function checkMediaAccess() {
        if (apply_filters('aam-media-request', true, $this->request)) {
            $media = $this->findMedia();
            $area  = (is_admin() ? 'backend' : 'frontend');
            
            if (empty($media)) {
                $this->printMedia();
            } else {
                $read   = $media->has('frontend.read');
                $others = $media->has('frontend.read_others');
                $author = ($media->post_author == get_current_user_id());
                
                if ($read || ($others && !$author)) {
                    $args = array(
                        'hook'   => 'media_read', 
                        'action' => "{$area}.read", 
                        'post'   => $media->getPost()
                    );

                    if ($default = AAM_Core_Config::get('media.restricted.default')) {
                        do_action('aam-rejected-action', $area, $args);
                        $this->printMedia(get_post($default));
                    } else {
                        AAM_Core_API::reject($area, $args);
                    }
                } else {
                    $this->printMedia($media);
                }
            }
        } else {
            $this->printMedia($media);
        }
    }
    
    /**
     * 
     * @param type $media
     */
    protected function printMedia($media = null) {
        $type = 'application/octet-stream';
        
        if (is_null($media)) {
            $media   = $this->findMedia();
        }
        
        if (!empty($media)) {
            $mime = $media->post_mime_type;
            $path = get_attached_file($media->ID); // This can be buggy!
        }
        
        if (empty($path) || !file_exists($path)) {
            $path = ABSPATH . $this->request_uri;
        }
        
        //normalize path and strip all unexpected trails. Thanks to Antonius Hegyes
        $path  = preg_replace('/\?.*$/', '', $path);
        $rpath = preg_replace('/\?.*$/', '', $this->request_uri);
        
        //finally replace the filename with requested filename
        $request = str_replace(basename($path), basename($rpath), $path);
        
        if (empty($mime)) {
            if (function_exists('mime_content_type')) {
                $mime = mime_content_type($request);
            }
        }
        
        @header('Content-Type: ' . (empty($mime) ? $type : $mime));
        echo file_get_contents($request);
        exit;
    }
    
    /**
     * Find media by URI
     * 
     * @global Wpdb $wpdb
     * 
     * @return AAM_Core_Object_Post|null
     * 
     * @access protected
     */
    protected function findMedia() {
        global $wpdb;
        
        $s   = preg_replace('/(-[\d]+x[\d]+)(\.[\w]+)$/', '$2', $this->request_uri);
        $id  = apply_filters(
                'aam-find-media',  
                $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} WHERE guid LIKE %s", 
                        array('%' . $s)
                    )
                ), 
                $this->request_uri
        );
                        
        return ($id ? AAM::getUser()->getObject('post', $id) : null);
    }
    
    /**
     * Bootstrap the extension
     * 
     * @return AAM_Skeleton
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}