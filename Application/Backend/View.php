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
 * Backend view manager
 *
 * This class is used to manage all AAM UI templates and interaction of the UI with
 * AAM backend core
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_View
{

    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_SingletonTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        $subject = AAM_Backend_Subject::getInstance();

        // Allow other plugins to register new AAM UI tabs/features
        do_action(
            'aam_init_ui_action', 'AAM_Backend_Feature::registerFeature', $subject
        );
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
     *
     * @access public
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
     *
     * @access public
     * @version 6.0.0
     */
    public static function loadTemplate($file_path, $params =  null)
    {
        ob_start();

        require_once $file_path;
        $content = ob_get_contents();

        ob_end_clean();

        return $content;
    }

    /**
     * Prepare AAM iFrame WordPress assets URL
     *
     * Based on the provided $type, return either JS or CSS URL
     *
     * @param string $type
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function prepareIframeWPAssetsURL($type)
    {
        global $wp_scripts, $compress_scripts, $compress_css;

        if ($type === 'js') {
            $zip    = $compress_scripts ? 1 : 0;
            $script = 'load-scripts.php';
            $concat = 'jquery-core,jquery-migrate';
        } else {
            $zip    = $compress_css ? 1 : 0;
            $script = 'load-styles.php';
            $concat = 'wp-edit-post,common';
        }

        if ($zip && defined('ENFORCE_GZIP') && ENFORCE_GZIP) {
            $zip = 'gzip';
        }

        $src  = $wp_scripts->base_url . "/wp-admin/{$script}?c={$zip}&";
        $src .= "load%5B%5D={$concat}&ver=" . $wp_scripts->default_version;

        return esc_attr($src);
    }

    /**
     * Process the ajax call
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function processAjax()
    {
        $response = null;

        $action  = $this->getFromPost('sub_action');
        $parts   = explode('.', $action);
        $subject = AAM_Backend_Subject::getInstance();

        if (count($parts) === 2) {
            $id = 'AAM_Backend_Feature_' . $parts[0];

            if (AAM_Backend_Feature::isFeatureRegistered($id)) {
                $response = call_user_func(
                    array(AAM_Backend_Feature::getFeatureView($id), $parts[1])
                );
            }
        }

        return apply_filters(
            'aam_ajax_filter', $response, $subject->getSubject(), $action
        );
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
     *
     * @access public
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
                        'objectId'    => $this->getFromQuery('id'),
                        'objectType'  => $this->getFromQuery('type'),
                        'postManager' => new AAM_Backend_Feature_Main_Post()
                    )
                );
            } elseif ($type === 'user' && current_user_can('aam_manage_users')) {
                echo $this->loadTemplate(
                    $basedir . 'user-iframe.php',
                    (object) array(
                        'user' => new WP_User($this->getFromQuery('id')),
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
     * Render Access Manager metabox iFrame element for terms
     *
     * @param WP_Term $term
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public static function renderTermMetabox($term)
    {
        return static::loadTemplate(
            dirname(__FILE__) . '/tmpl/metabox/term-metabox.php',
            (object) array(
                'term'     => $term,
                'postType' => filter_input(INPUT_GET, 'post_type')
            )
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
     * @version 6.0.0
     */
    public static function renderPolicyMetabox()
    {
        global $post;

        if (is_a($post, 'WP_Post')) {
            $content = static::loadTemplate(
                dirname(__FILE__) . '/tmpl/metabox/policy-metabox.php',
                (object) array('post' => $post)
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

            case 'post-access-form':
                $type    = $this->getFromPost('type'); // Type of object to load
                $id      = $this->getFromPost('id'); // Object Id

                $manager = new AAM_Backend_Feature_Main_Post();
                $content = $manager->getAccessForm($id, $type);
                break;

            default:
                // Allow other plugins to hook into the AAM template rendering with
                // with custom HTML
                $content = apply_filters('aam_ui_content_filter', null, $type);
                break;
        }

        return $content;
    }

}