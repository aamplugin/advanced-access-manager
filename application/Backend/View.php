<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend view manager
 *
 * This class is used to manage all AAM UI templates and interaction of the UI with
 * AAM backend core
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_View
{

    /**
     * Single instance of itself
     *
     * @var object
     * @access private
     *
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        // Allow other plugins to register new AAM UI tabs/features
        do_action(
            'aam_initialize_ui_action',
            function($feature) { AAM_Backend_Feature::registerFeature($feature); },
            AAM_Backend_AccessLevel::get_instance()
        );
    }

    /**
     * Replace sprintf string with URLs to aamportal.com
     *
     * @param string $str
     * @param string ...$url
     *
     * @return string
     *
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function replace_aam_urls($str)
    {
        $args = array_slice(func_get_args(), 1);

        if (!empty($args)) {
            // Preparing the array of arguments for the sprintf
            $replace_args = [$str];

            foreach($args as $relative_url) {
                array_push($replace_args,
                    '<a href="https://aamportal.com' . $relative_url . '?ref=plugin" target="_blank">',
                    '</a>'
                );
            }

            $str = call_user_func_array('sprintf', $replace_args);
        }

        return $str;
    }

    /**
     * Load partial template
     *
     * The specified template has to be located inside the ./tmpl/partial folder
     *
     * @param string $tmpl
     * @param array  $params
     *
     * @return string|null
     * @access public
     *
     * @version 6.0.0
     */
    public static function loadPartial($tmpl, $params = array())
    {
        if (preg_match('/^[a-z-]+$/i', $tmpl)) {
            $html = self::loadTemplate(
                __DIR__ . "/tmpl/partial/{$tmpl}.php",
                (is_object($params) ? $params : (object) $params)
            );
        } else {
            $html = null;
        }

        return $html;
    }

    /**
     * Load dynamic template
     *
     * @param string $file_path
     * @param object $params
     *
     * @return string
     * @access public
     *
     * @version 6.6.0
     */
    public static function loadTemplate($file_path, $params =  null)
    {
        ob_start();

        require $file_path;
        $content = ob_get_contents();

        ob_end_clean();

        return $content;
    }

    /**
     * Process the ajax call
     *
     * @return string
     * @access public
     *
     * @version 6.7.9
     */
    public function processAjax()
    {
        $response = null;

        $action       = AAM::api()->misc->get($_POST, 'sub_action');
        $parts        = explode('.', $action);
        $access_level = AAM_Backend_AccessLevel::get_instance();

        if (count($parts) === 2) {
            $id = 'AAM_Backend_Feature_' . $parts[0];

            if (AAM_Backend_Feature::isFeatureRegistered($id)) {
                $response = call_user_func(
                    array(AAM_Backend_Feature::getFeatureView($id), $parts[1])
                );
            }

            $response = apply_filters(
                'aam_ajax_filter',
                $response,
                $access_level->get_access_level(),
                $action
            );
        } elseif ($action === 'renderContent') {
            $partial  = AAM::api()->misc->get($_POST, 'partial');
            $response = $this->renderContent((!empty($partial) ? $partial : 'main'));

            $accept = AAM::api()->misc->get($_SERVER, 'HTTP_ACCEPT_ENCODING');
            header('Content-Type: text/html; charset=UTF-8');

            $compressed = count(array_intersect(
                array('zlib output compression', 'ob_gzhandler'),
                ob_list_handlers()
            )) > 0;

            if (!empty($accept)) {
                header('Vary: Accept-Encoding'); // Handle proxies

                if (false !== stripos($accept, 'gzip') && function_exists('gzencode')) {
                    header('Content-Encoding: gzip');
                    $response = ($compressed ? $response : gzencode($response, 3));
                }
            }
        }

        return $response;
    }

    /**
     * Render the main AAM page
     *
     * This is the landing page for the /wp-admin/admin.php?page=aam
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function renderPage()
    {
        return $this->loadTemplate(dirname(__FILE__) . '/tmpl/index.php');
    }

    /**
     * Run AAM iFrame
     *
     * @return string
     * @access public
     *
     * @version 6.0.0
     */
    public function renderIFrame($type)
    {
        $basedir = dirname(__FILE__) . '/tmpl/metabox/';

        if (current_user_can('aam_manager')) {
            if (($type === 'post') && current_user_can('aam_manage_content')) {
                echo $this->loadTemplate(
                    $basedir . 'post-iframe.php',
                    (object) array(
                        'objectId'    => filter_input(INPUT_GET, 'id'),
                        'objectType'  => filter_input(INPUT_GET, 'type'),
                        'postManager' => new AAM_Backend_Feature_Main_Content()
                    )
                );
            } elseif ($type === 'user' && current_user_can('aam_manage_users')) {
                echo $this->loadTemplate(
                    $basedir . 'user-iframe.php',
                    (object) array(
                        'user' => new WP_User(filter_input(INPUT_GET, 'id')),
                        'type' => 'main'
                    )
                );
            } elseif ($type === 'main') {
                echo $this->loadTemplate($basedir . 'main-iframe.php');
            } else {
                echo apply_filters('aam_iframe_content_filter', null, $type, $this);
            }
        }

        exit;
    }

    /**
     * Render Access Manager metabox iFrame element for posts
     *
     * @param WP_Post $post
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public static function renderPostMetabox($post)
    {
        return static::loadTemplate(
            dirname(__FILE__) . '/tmpl/metabox/post-metabox.php',
            (object) array('post' => $post)
        );
    }

    /**
     * Render Access Manager metabox iFrame element for user
     *
     * @param WP_User $term
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public static function renderUserMetabox($user)
    {
        return static::loadTemplate(
            dirname(__FILE__) . '/tmpl/metabox/user-metabox.php',
            (object) array(
                'user' => $user
            )
        );
    }

    /**
     * Render Access Policy editor
     *
     * @return string
     *
     * @access public
     * @global WP_Post $post
     *
     * @version 7.0.0
     */
    public function render_policy_metabox()
    {
        global $post;

        if (is_a($post, 'WP_Post')) {
            $content = $this->loadTemplate(
                dirname(__FILE__) . '/tmpl/metabox/policy-metabox.php',
                (object) array('post' => $post)
            );
        } else {
            $content = null;
        }

        return  $content;
    }

    /**
     * Render policy parent metabox
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function render_policy_parent_metabox()
    {
        global $post;

        if (is_a($post, 'WP_Post')) {
            $content = $this->loadTemplate(
                dirname(__FILE__) . '/tmpl/metabox/policy-parent-metabox.php',
                (object) [ 'post' => $post ]
            );
        } else {
            $content = null;
        }

        return  $content;
    }

    /**
     * Render policy principal metabox
     *
     * @return string
     *
     * @access public
     * @global WP_Post $post
     * @version 6.0.0
     */
    public static function renderPolicyPrincipalMetabox()
    {
        global $post;

        if (is_a($post, 'WP_Post')) {
            $content = static::loadTemplate(
                dirname(__FILE__) . '/tmpl/metabox/policy-principal-metabox.php',
                (object) array('post' => $post)
            );
        } else {
            $content = null;
        }

        return $content;
    }

    /**
     * Render the AAM HTML content
     *
     * Depending on the $type of the content, verify correct permissions and load
     * proper HTML template.
     *
     * @param string $type
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function renderContent($type = 'main')
    {
        $basedir = __DIR__ . '/tmpl/page/';

        switch ($type) {
            case 'main':
                // No need to do the authorization as this is already done in the
                // AAM_Backend_Manager class
                $content = $this->loadTemplate(
                    $basedir . 'main-panel.php',
                    (object) array('type' => 'main')
                );
                break;

            case 'settings':
                if (current_user_can('aam_manage_settings')) {
                    $content = $this->loadTemplate(
                        $basedir . 'main-panel.php',
                        (object) array('type' => 'settings')
                    );
                }
                break;

            case 'extensions':
                if (current_user_can('aam_manage_addons')) {
                    $content = $this->loadTemplate($basedir . 'addon-panel.php');
                }
                break;

            case 'content-access-form':
                $manager = new AAM_Backend_Feature_Main_Content();
                $content = $manager->render_content_access_form(
                    AAM::api()->misc->get($_POST, 'resource_id'),
                    AAM::api()->misc->get($_POST, 'resource_type')
                );
                break;

            case 'audit':
                if (current_user_can('aam_trigger_audit')) {
                    $content = $this->loadTemplate($basedir . 'security-audit.php');
                }
                break;

            default:
                // Allow other plugins to hook into the AAM template rendering with
                // with custom HTML
                $content = apply_filters('aam_ui_content_filter', null, $type);
                break;
        }

        return $content;
    }

    /**
     * Bootstrap the object
     *
     * @return AAM_Backend_View
     *
     * @access public
     * @version 7.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Get single instance of itself
     *
     * @return AAM_Backend_View
     *
     * @access public
     * @version 7.0.0
     */
    public static function get_instance()
    {
        return self::bootstrap();
    }

}