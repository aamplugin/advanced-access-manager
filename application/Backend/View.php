<?php

/**
 * Backend registration and WordPress editor template helpers.
 *
 * The access workspace itself is rendered by AAM_Backend_React.
 *
 * @package AAM
 */
class AAM_Backend_View
{
    private static $_instance = null;

    protected function __construct()
    {
        do_action(
            'aam_initialize_ui_action',
            function($feature) { AAM_Backend_Feature::registerFeature($feature); },
            AAM_Backend_AccessLevel::get_instance()
        );
    }

    public static function loadPartial($template, $params = [])
    {
        if (!preg_match('/^[a-z-]+$/i', $template)) {
            return null;
        }

        return self::loadTemplate(
            __DIR__ . "/tmpl/partial/{$template}.php",
            is_object($params) ? $params : (object) $params
        );
    }

    public static function loadTemplate($file_path, $params = null)
    {
        ob_start();
        require $file_path;
        return ob_get_clean();
    }

    public static function renderPostMetabox($post)
    {
        return self::loadTemplate(
            __DIR__ . '/tmpl/metabox/post-metabox.php',
            (object) [ 'post' => $post ]
        );
    }

    public static function renderTermAccess($term)
    {
        return self::loadTemplate(
            __DIR__ . '/tmpl/metabox/term-access.php',
            (object) [ 'term' => $term ]
        );
    }

    public static function renderUserMetabox($user)
    {
        return self::loadTemplate(
            __DIR__ . '/tmpl/metabox/user-metabox.php',
            (object) [ 'user' => $user ]
        );
    }

    public function render_policy_metabox()
    {
        global $post;

        return is_a($post, 'WP_Post')
            ? self::loadTemplate(
                __DIR__ . '/tmpl/metabox/policy-metabox.php',
                (object) [ 'post' => $post ]
            ) : null;
    }

    public function render_policy_parent_metabox()
    {
        global $post;

        return is_a($post, 'WP_Post')
            ? self::loadTemplate(
                __DIR__ . '/tmpl/metabox/policy-parent-metabox.php',
                (object) [ 'post' => $post ]
            ) : null;
    }

    public static function renderPolicyPrincipalMetabox()
    {
        global $post;

        return is_a($post, 'WP_Post')
            ? self::loadTemplate(
                __DIR__ . '/tmpl/metabox/policy-principal-metabox.php',
                (object) [ 'post' => $post ]
            ) : null;
    }

    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    public static function get_instance()
    {
        return self::bootstrap();
    }
}
