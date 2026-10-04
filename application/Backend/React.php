<?php

class AAM_Backend_React
{

    const ENDPOINTS = [
        'admin_menu' => '/backend-menu',
        'toolbar' => '/admin-toolbar',
        'metabox' => '/metaboxes',
        'widget' => '/widgets',
        'capability' => '/capabilities?fields=description,permissions,is_granted',
        'post' => '/post_types',
        'route' => '/api-routes',
        'url' => '/urls',
        'ability' => '/abilities',
        'policy' => '/policies?fields=permissions,excerpt',
        'identity' => '/identity/roles',
        'jwt' => '/jwts?fields=claims',
        'login_redirect' => '/redirect/login',
        'logout_redirect' => '/redirect/logout',
        '404redirect' => '/redirect/not-found',
        'redirect' => '/redirect/access-denied?area=frontend'
    ];

    public static function boot()
    {
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function enabled()
    {
        return version_compare(get_bloginfo('version'), '6.2', '>=');
    }

    public static function enqueue()
    {
        if (
            !self::enabled() || !isset($_GET['page']) || $_GET['page'] !== 'aam'
            || !current_user_can('aam_manager')
        ) {
            return;
        }
        
        $base = plugins_url('media/', AAM_BASEDIR . '/aam.php');
        // ConfigPress uses the WordPress code editor when syntax highlighting is enabled.
        $code_editor = wp_enqueue_code_editor([
            'type' => 'text/plain',
            'codemirror' => ['mode' => 'aam-ini', 'lint' => false]
        ]);
        $dependencies = ['wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n'];
        if (false !== $code_editor) {
            $dependencies[] = 'code-editor';
        }
        $style_version = filemtime(AAM_BASEDIR . '/media/css/react-admin.css') ?: AAM_VERSION;
        $script_version = filemtime(AAM_BASEDIR . '/media/js/react-admin.js') ?: AAM_VERSION;
        self::enqueue_confirmation_style();
        wp_enqueue_style('wp-components');
        wp_enqueue_style('aam-react', $base . 'css/react-admin.css', ['aam-confirm-dialog'], $style_version);
        wp_enqueue_script(
            'aam-react',
            $base . 'js/react-admin.js',
            $dependencies,
            $script_version,
            true
        );
        wp_add_inline_script('aam-react', 'window.aamReactIniEditorEnabled = '
            . (false !== $code_editor ? 'true' : 'false') . ';', 'before');
        wp_set_script_translations('aam-react', 'advanced-access-manager', AAM_BASEDIR . '/lang');
    }

    /** Load the post editor access controls. */
    public static function enqueue_post_metabox()
    {
        $base = plugins_url('media/', AAM_BASEDIR . '/aam.php');
        self::enqueue_confirmation_style();
        $roles = [];

        if (current_user_can('aam_manage_roles')) {
            foreach (AAM::api()->roles->get_editable_roles(true) as $slug => $role) {
                if (AAM::api()->roles->is_editable_role($slug)) {
                    $roles[] = [
                        'value' => $slug,
                        'label' => translate_user_role($role['name'])
                    ];
                }
            }
        }
        $bootstrap = [
            'roles' => $roles,
            'blogId' => get_current_blog_id(),
            'viewerId' => get_current_user_id(),
            'levels' => [
                'role'    => current_user_can('aam_manage_roles') && !empty($roles),
                'user'    => current_user_can('aam_manage_users'),
                'visitor' => current_user_can('aam_manage_visitors'),
                'default' => current_user_can('aam_manage_default')
            ]
        ];
        $bootstrap = apply_filters('aam_react_post_metabox_bootstrap_filter', $bootstrap);
        $style = AAM_BASEDIR . '/media/css/post-access-metabox.css';
        $script = AAM_BASEDIR . '/media/js/post-access-metabox.js';

        wp_enqueue_style('wp-components');
        wp_enqueue_style(
            'aam-post-access',
            $base . 'css/post-access-metabox.css',
            ['wp-components'],
            filemtime($style) ?: AAM_VERSION
        );
        wp_enqueue_script(
            'aam-post-access',
            $base . 'js/post-access-metabox.js',
            ['wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n'],
            filemtime($script) ?: AAM_VERSION,
            true
        );
        wp_add_inline_script('aam-post-access', 'window.aamPostAccessBootstrap = '
            . wp_json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . ';', 'before');
        wp_set_script_translations('aam-post-access', 'advanced-access-manager', AAM_BASEDIR . '/lang');
    }

    /** Load the compact term editor entry point and its access dialog. */
    public static function enqueue_term_access()
    {
        $base = plugins_url('media/', AAM_BASEDIR . '/aam.php');
        $roles = [];
        if (current_user_can('aam_manage_roles')) {
            foreach (AAM::api()->roles->get_editable_roles(true) as $slug => $role) {
                if (AAM::api()->roles->is_editable_role($slug)) {
                    $roles[] = [
                        'value' => $slug,
                        'label' => translate_user_role($role['name'])
                    ];
                }
            }
        }
        $bootstrap = apply_filters('aam_react_term_access_bootstrap_filter', [
            'roles' => $roles,
            'blogId' => get_current_blog_id(),
            'viewerId' => get_current_user_id(),
            'contentPremium' => self::content_premium_status(),
            'levels' => [
                'role' => current_user_can('aam_manage_roles') && !empty($roles),
                'user' => current_user_can('aam_manage_users'),
                'visitor' => current_user_can('aam_manage_visitors'),
                'default' => current_user_can('aam_manage_default')
            ]
        ]);
        self::enqueue_confirmation_style();
        wp_enqueue_style('wp-components');
        wp_enqueue_style(
            'aam-term-access-base',
            $base . 'css/react-admin.css',
            ['wp-components'],
            filemtime(AAM_BASEDIR . '/media/css/react-admin.css') ?: AAM_VERSION
        );
        wp_enqueue_style(
            'aam-term-access',
            $base . 'css/term-access.css',
            ['aam-term-access-base', 'aam-confirm-dialog'],
            filemtime(AAM_BASEDIR . '/media/css/term-access.css') ?: AAM_VERSION
        );
        wp_enqueue_script(
            'aam-term-access',
            $base . 'js/term-access.js',
            ['wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n'],
            filemtime(AAM_BASEDIR . '/media/js/term-access.js') ?: AAM_VERSION,
            true
        );
        wp_add_inline_script('aam-term-access', 'window.aamTermAccessBootstrap = '
            . wp_json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . ';', 'before');
        wp_set_script_translations('aam-term-access', 'advanced-access-manager', AAM_BASEDIR . '/lang');
    }

    private static function content_premium_status()
    {
        $active = defined('AAM_COMPLETE_PACKAGE');
        $installed = $active;
        if (!$installed && !function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (!$installed) {
            foreach (array_keys(get_plugins()) as $plugin) {
                if (preg_match('~^aam-complete-package[^/]*/bootstrap\.php$~', $plugin)) {
                    $installed = true;
                    break;
                }
            }
        }
        return [
            'active' => $active,
            'installed' => $installed,
            'enabled' => $active && (bool) AAM::api()->config->get(
                'service.content.enabled',
                true
            ),
            'purchaseUrl' => 'https://aamportal.com/premium?ref=content-access',
            'pluginsUrl' => current_user_can('activate_plugins')
                ? admin_url('plugins.php') : null,
            'settingsUrl' => current_user_can('aam_manage_settings')
                ? admin_url('admin.php?page=aam&aam_service=settings') : null
        ];
    }

    private static function enqueue_confirmation_style()
    {
        $path = AAM_BASEDIR . '/media/css/confirmation-dialog.css';
        wp_enqueue_style(
            'aam-confirm-dialog',
            plugins_url('media/css/confirmation-dialog.css', AAM_BASEDIR . '/aam.php'),
            ['wp-components'],
            filemtime($path) ?: AAM_VERSION
        );
    }

    /** Load the policy assignee selector on published access policies. */
    public static function enqueue_policy_assignee_metabox($policy_id)
    {
        $base = plugins_url('media/', AAM_BASEDIR . '/aam.php');
        $style = AAM_BASEDIR . '/media/css/policy-assignee-metabox.css';
        $script = AAM_BASEDIR . '/media/js/policy-assignee-metabox.js';
        $bootstrap = [
            'levels' => [
                'role' => current_user_can('aam_manage_roles')
                    && current_user_can('aam_list_roles'),
                'user' => current_user_can('aam_manage_users'),
                'visitor' => current_user_can('aam_manage_visitors'),
                'default' => current_user_can('aam_manage_default')
            ],
            'attached' => [
                'visitor' => current_user_can('aam_manage_visitors')
                    ? AAM::api()->policies('visitor')->is_attached($policy_id) : false,
                'default' => current_user_can('aam_manage_default')
                    ? AAM::api()->policies('default')->is_attached($policy_id) : false
            ]
        ];
        wp_enqueue_style('wp-components');
        wp_enqueue_style(
            'aam-policy-assignee',
            $base . 'css/policy-assignee-metabox.css',
            ['wp-components'],
            filemtime($style) ?: AAM_VERSION
        );
        wp_enqueue_script(
            'aam-policy-assignee',
            $base . 'js/policy-assignee-metabox.js',
            ['wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n'],
            filemtime($script) ?: AAM_VERSION,
            true
        );
        wp_add_inline_script('aam-policy-assignee', 'window.aamPolicyAssigneeBootstrap = '
            . wp_json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . ';', 'before');
        wp_set_script_translations('aam-policy-assignee', 'advanced-access-manager', AAM_BASEDIR . '/lang');
    }

    /** Authorize context independently of navigation visibility. */
    public static function validate_context($type, $id)
    {
        $caps = [
            'role' => 'aam_manage_roles',
            'user' => 'aam_manage_users',
            'visitor' => 'aam_manage_visitors',
            'default' => 'aam_manage_default'
        ];
        if (!current_user_can('aam_manager') || !isset($caps[$type]) || !current_user_can($caps[$type])) {
            throw new RuntimeException(__('You cannot manage this access level.', 'advanced-access-manager'));
        }
        if ($type === 'role' && (!$id || !AAM::api()->roles->is_editable_role($id))) {
            throw new RuntimeException(__('This role is not editable.', 'advanced-access-manager'));
        }
        if ($type === 'user') {
            $user = apply_filters('aam_get_user', get_user_by('id', absint($id)));
            if (!$user || is_wp_error($user) || !current_user_can('edit_user', $user->ID)) {
                throw new RuntimeException(__('This user is not editable.', 'advanced-access-manager'));
            }
        }
    }

    public static function render()
    {
        if (!current_user_can('aam_manager')) {
            wp_die(esc_html__('You cannot manage access settings.', 'advanced-access-manager'));
        }
        if (!self::enabled()) {
            echo '<div class="notice notice-error"><p>'
                . esc_html__('WordPress 6.2 or newer is required for the access workspace.', 'advanced-access-manager')
                . '</p></div>';
            return;
        }
        try {
            // The AccessLevel class validates URL context before choosing it.
            $payload = self::payload(sanitize_key($_GET['aam_service'] ?? 'admin_menu'));
            wp_add_inline_script('aam-react', 'window.aamReactBootstrap = ' . wp_json_encode(
                $payload,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) . ';', 'before');
            echo '<div id="aam-react-root"><div class="aam-react-loading" role="status">'
                . esc_html__('Loading access workspace…', 'advanced-access-manager') . '</div></div>';
            echo '<noscript>' . esc_html__('JavaScript is required for the access workspace.', 'advanced-access-manager') . '</noscript>';
        } catch (Throwable $e) {
            echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public static function payload($screen, $access_level = null)
    {
        $context = AAM_Backend_AccessLevel::bootstrap($access_level);
        $al = $context->get_access_level();
        self::validate_context($al->type, $al->get_id());
        AAM_Backend_View::get_instance();
        $features = [];
        foreach (AAM_Backend_Feature::retrieveList('main') as $f) {
            $features[] = [
                'id' => $f->uid,
                'title' => html_entity_decode(wp_strip_all_tags($f->title), ENT_QUOTES, 'UTF-8'),
                'endpoint' => self::ENDPOINTS[$f->uid] ?? null
            ];
        }
        $caps = [];
        foreach (
            [
                'manage_roles',
                'manage_users',
                'manage_visitors',
                'manage_default',
                'list_roles',
                'create_roles',
                'edit_roles',
                'delete_roles',
                'manage_settings',
                'manage_configs',
                'manage_services',
                'trigger_audit',
                'manage_addons',
                'manage_policies',
                'manage_jwt',
                'toggle_users'
            ] as $c
        ) {
            $caps[$c] = current_user_can('aam_' . $c);
        }
        $caps['create_users'] = current_user_can('create_users');
        $available = array_column($features, 'id');
        $global = ($screen === 'settings' && $caps['manage_settings'])
            || ($screen === 'audit' && $caps['trigger_audit'])
            || ($screen === 'roles' && $caps['list_roles'])
            || ($screen === 'users' && $caps['manage_users'])
            || ($screen === 'extensions' && $caps['manage_addons']);
        if (!$global && !in_array($screen, $available, true)) {
            $screen = $available[0] ?? 'welcome';
        }
        $subject = ['type' => $al->type, 'id' => $al->get_id(), 'name' => $al->get_display_name()];
        if ($al->type === 'user') {
            $subject['email'] = $al->user_email;
        } elseif ($al->type === 'role') {
            $subject['userCount'] = $al->user_count;
        }
        $premium_status = self::content_premium_status();
        $payload = [
            'version' => AAM_VERSION,
            'subject' => $subject,
            'screen' => $screen,
            'features' => $features,
            'abilityApiAvailable' => function_exists('wp_get_abilities'),
            'mcpAdapterAvailable' => class_exists('\\WP\\MCP\\Core\\McpAdapter'),
            'caps' => $caps,
            'blogId' => get_current_blog_id(),
            'viewerId' => get_current_user_id(),
            'contentPremium' => $premium_status,
            'premiumAvailability' => [
                'active' => $premium_status['active'],
                'installed' => $premium_status['installed'],
                'pluginsUrl' => $premium_status['pluginsUrl'],
                'purchaseUrl' => 'https://aamportal.com/premium?ref=default-access'
            ],
            'siteName' => get_bloginfo('name'),
            'brandLogoUrl' => plugins_url('media/img/logo.svg', AAM_BASEDIR . '/aam.php'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'adminUrl' => admin_url(),
            'settings' => [],
            'audit' => null,
            'preload' => null,
            'roleParentSupported' => (bool) apply_filters(
                'aam_react_role_parent_supported_filter',
                false
            )
        ];
        if ($screen === 'metabox') {
            $payload['indexUrls'] = [];
            foreach (get_post_types(['show_ui' => true]) as $type) {
                $payload['indexUrls'][] = add_query_arg(
                    'init',
                    'metabox',
                    admin_url('post-new.php?post_type=' . $type)
                );
            }
        } elseif ($screen === 'widget') {
            $payload['indexUrls'] = [
                add_query_arg('init', 'widget', admin_url('index.php'))
            ];
        }
        if ($caps['manage_users'] || $caps['manage_roles']) {
            $payload['roleOptions'] = [];
            foreach (AAM::api()->roles->get_editable_roles(true) as $slug => $role) {
                $payload['roleOptions'][] = [
                    'value' => $slug,
                    'label' => translate_user_role($role['name'])
                ];
            }
        }
        if ($screen === 'extensions' && $caps['manage_addons']) {
            $license = AAM_Addon_Repository::get_instance()->get_premium_license_key();
            $payload['premium'] = [
                'installed' => defined('AAM_COMPLETE_PACKAGE'),
                'version' => defined('AAM_COMPLETE_PACKAGE') ? AAM_COMPLETE_PACKAGE : null,
                'license' => $license,
                'manageUrl' => !empty($license)
                    ? 'https://aamportal.com/license/' . rawurlencode($license) . '?ref=plugin'
                    : null,
                'purchaseUrl' => 'https://aamportal.com/premium?ref=plugin'
            ];
        }
        if ($screen === 'settings') {
            foreach (AAM_Backend_Feature::retrieveList('settings') as $f) {
                $class = is_object($f->view) ? get_class($f->view) : $f->view;
                if (is_callable([$class, 'getList'])) {
                    $items = call_user_func([$class, 'getList']);
                    if (empty($items)) {
                        continue;
                    }
                    foreach ($items as &$item) {
                        $item['title'] = html_entity_decode(wp_strip_all_tags($item['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $item['description'] = html_entity_decode(wp_strip_all_tags($item['description'] ?? ''), ENT_QUOTES, 'UTF-8');
                    }
                    unset($item);
                    $payload['settings'][] = ['id' => $f->uid, 'title' => $f->title, 'items' => $items];
                }
            }
        }
        if ($screen === 'audit' && $caps['trigger_audit']) {
            $audit = AAM_Service_SecurityAudit::get_instance();
            $report = $audit->read();
            foreach ($report as $step => $result) {
                $report[$step] = $audit->prepare_ui_result($step, $result);
            }
            $payload['audit'] = [
                'steps'   => $audit->get_steps(),
                'report'  => $report,
                'score'   => $audit->get_score(),
                'summary' => $audit->get_summary()
            ];
        }
        $endpoint = self::ENDPOINTS[$screen] ?? null;
        if ($endpoint) {
            $path = self::context_path($endpoint, $subject);
            $response = self::read_rest($path);
            if ($response->get_status() < 400) {
                $payload['preload'] = ['path' => $path, 'data' => $response->get_data()];
            }
        }
        
        return apply_filters('aam_react_bootstrap_filter', $payload, $al);
    }

    private static function context_path($endpoint, $subject)
    {
        $args = ['access_level' => $subject['type']];
        if ($subject['type'] === 'role') $args['role_id'] = $subject['id'];
        if ($subject['type'] === 'user') $args['user_id'] = $subject['id'];
        return '/aam/v2' . $endpoint . (strpos($endpoint, '?') === false ? '?' : '&') . http_build_query($args, '', '&', PHP_QUERY_RFC3986);
    }

    /** Internal dispatch retains route permission callbacks and validation. */
    private static function read_rest($path)
    {
        $parts = explode('?', $path, 2);
        $request = new WP_REST_Request('GET', $parts[0]);
        if (isset($parts[1])) {
            parse_str($parts[1], $args);
            $request->set_query_params($args);
        }
        return rest_do_request($request);
    }

}