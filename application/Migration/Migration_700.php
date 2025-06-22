<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Migration to AAM 7.0.0
 *
 * @package AAM
 * @version 7.0.0
 */
final class AAM_Migration_700
{

    /**
     * Redirect type aliases
     *
     * @version 7.0.0
     */
    const REDIRECT_TYPE_ALIAS = [
        'default'  => 'default',
        'login'    => 'login_redirect',
        'message'  => 'custom_message',
        'page'     => 'page_redirect',
        'url'      => 'url_redirect',
        'callback' => 'trigger_callback'
    ];

    /**
     * Run the migration
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function run()
    {
        // Convert configs to new names
        $this->_transform_legacy_config_names();

        // Convert Backend Menu settings to new format
        $this->_transform_legacy_settings(
            'menu',
            AAM_Framework_Type_Resource::BACKEND_MENU,
            function($data) {
                return $this->_convert_legacy_backend_menu($data);
            }
        );

        // Convert Admin Toolbar settings to new format
        $this->_transform_legacy_settings(
            'toolbar',
            AAM_Framework_Type_Resource::TOOLBAR,
            function($data) {
                return $this->_convert_legacy_admin_toolbar($data);
            }
        );

        // Convert URL Access rules to new format
        $this->_transform_legacy_settings(
            'uri',
            AAM_Framework_Type_Resource::URL,
            function($data) {
                return $this->_convert_legacy_url_rules($data);
            }
        );

        // Convert Login Redirect settings to new format
        $this->_transform_legacy_settings(
            'loginRedirect',
            AAM_Framework_Type_Preference::LOGIN_REDIRECT,
            function($data) {
                return $this->_convert_legacy_login_redirect($data);
            }
        );

        // Convert Logout Redirect settings to new format
        $this->_transform_legacy_settings(
            'logoutRedirect',
            AAM_Framework_Type_Preference::LOGOUT_REDIRECT,
            function($data) {
                return $this->_convert_legacy_logout_redirect($data);
            }
        );

        // Convert Access Denied Redirect settings to new format
        $this->_transform_legacy_settings(
            'redirect',
            AAM_Framework_Type_Preference::ACCESS_DENIED_REDIRECT,
            function($data) {
                return $this->_convert_legacy_access_denied_redirect($data);
            }
        );

        // Convert 404 Redirect settings to new format
        $this->_transform_legacy_settings(
            'notFoundRedirect',
            AAM_Framework_Type_Preference::NOT_FOUND_REDIRECT,
            function($data) {
                return $this->_convert_legacy_not_found_redirect($data);
            }
        );

        // Convert Post settings to new format
        $this->_transform_legacy_settings(
            'post',
            AAM_Framework_Type_Resource::POST,
            function($data) {
                return $this->_convert_legacy_post_settings($data);
            }
        );

        // Convert Post Type settings to new format
        $this->_transform_legacy_settings(
            'type',
            AAM_Framework_Type_Resource::POST_TYPE,
            function($data) {
                return $this->_convert_legacy_post_type_settings($data);
            }
        );

        // Convert Taxonomy settings to new format
        $this->_transform_legacy_settings(
            'taxonomy',
            AAM_Framework_Type_Resource::TAXONOMY,
            function($data) {
                return $this->_convert_legacy_taxonomy_settings($data);
            }
        );

        // Convert Term settings to new format
        $this->_transform_legacy_settings(
            'term',
            AAM_Framework_Type_Resource::TERM,
            function($data) {
                return $this->_convert_legacy_term_settings($data);
            }
        );
    }

    /**
     * Rename legacy config keys with new
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _transform_legacy_config_names()
    {
        $service     = AAM::api()->config;
        $configs     = $service->get();
        $configpress = AAM::api()->db->read(AAM_Service_Core::DB_OPTION, '');

        // The list of changes
        $changes = [
            'core.settings.multiSubject'                => 'core.settings.multi_access_levels',
            'core.service.login-redirect.enabled'       => 'service.log_redirect.enabled',
            'core.service.logout-redirect.enabled'      => 'service.logout_redirect.enabled',
            'core.service.denied-redirect.enabled'      => 'service.access_denied_redirect.enabled',
            'core.service.404-redirect.enabled'         => 'service.not_found_redirect.enabled',
            'core.service.content.enabled'              => 'service.content.enabled',
            'core.service.content.manageAllPostTypes'   => 'service.post_types.manage_all_post_types',
            'core.service.content.manageAllTaxonomies'  => 'service.taxonomies.manage_all',
            'core.settings.tips'                        => 'core.settings.ui.tips',
            'ui.settings.renderAccessMetabox'           => 'core.settings.ui.render_access_metabox',
            'core.settings.inheritParentPost'           => 'service.content.inherit_from_parent_post',
            'core.service.content.exclude.taxonomies'   => 'service.content.exclude.taxonomies',
            'feature.post.password.expires'             => 'service.content.password_ttl',
            'geoapi.adapter'                            => 'service.geo_lookup.geoapi.adapter',
            'ipstack.license'                           => 'service.geo_lookup.geoapi.api_key',
            'geoapi.api_key'                            => 'service.geo_lookup.geoapi.api_key',
            'geoapi.test_ip'                            => 'service.geo_lookup.geoapi.test_ip',
            'ipstack.schema'                            => 'service.geo_lookup.ipstack.schema',
            'ipstack.fields'                            => 'service.geo_lookup.ipstack.fields',
            'service.uri.enabled'                       => 'service.url.enabled',
            'core.service.admin-menu.enabled'           => 'service.backend_menu.enabled',
            'core.service.metabox.enabled'              => 'service.metabox.enabled',
            'core.service.toolbar.enabled'              => 'service.admin_toolbar.enabled',
            'core.service.route.enabled'                => 'service.api_route.enabled',
            'core.service.identity-governance.enabled'  => 'service.identity.enabled',
            'core.service.jwt.enabled'                  => 'service.jwt.enabled',
            'core.service.capability.enabled'           => 'service.capability.enabled',
            'core.settings.editCapabilities'            => 'service.capability.edit_caps',
            'core.service.secure-login.enabled'         => 'service.secure_login.enabled',
            'service.secureLogin.feature.singleSession' => 'service.secure_login.single_session',
            'service.secureLogin.feature.bruteForceLockout' => 'service.secure_login.brute_force_lockout',
            'core.settings.xmlrpc'                      => 'core.settings.xmlrpc_enabled',
            'core.settings.restful'                     => 'core.settings.restful_enabled',
            'core.service.welcome.enabled'              => 'service.welcome.enabled',
            'addon.protected-media-files.settings.deniedRedirect' => 'addon.protected_media_files.settings.denied_redirect',
            'addon.protected-media-files.settings.absolutePath'   => 'addon.protected_media_files.settings.absolute_path',
            'core.settings.menu.merge.preference'                 => 'core.settings.backend_menu.merge.preference',
            'core.settings.toolbar.merge.preference'              => 'core.settings.admin_toolbar.merge.preference',
            'core.settings.route.merge.preference'                => 'core.settings.api_route.merge.preference',
            'core.settings.type.merge.preference'                 => 'core.settings.post_type.merge.preference',
            'core.settings.uri.merge.preference'                  => 'core.settings.url.merge.preference',
        ];

        foreach($changes as $legacy => $new) {
            if (isset($configs[$legacy])) {
                $configs[$new] = $configs[$legacy];

                unset($configs[$legacy]);
            }

            $configpress = str_replace($legacy, $new, $configpress);
        }

        $service->set($configs);
        AAM::api()->db->write(AAM_Service_Core::DB_OPTION, $configpress);
    }

    /**
     * Transform legacy resource settings to new format
     *
     * @param string   $legacy_type
     * @param string   $new_type
     * @param callback $cb
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _transform_legacy_settings($legacy_type, $new_type, $cb)
    {
        // Let's get all the settings first
        $legacy   = AAM::api()->db->read('aam_access_settings', []);
        $settings = AAM::api()->db->read(AAM_Framework_Service_Settings::DB_OPTION);

        // Iterating of the list of all settings and modify the URL Access rule
        foreach($legacy as $access_level => &$level) {
            if (in_array($access_level, ['role', 'user'])) {
                foreach($level as $id => $data) {
                    if ($this->_access_level_exists($access_level, $id)
                        && array_key_exists($legacy_type, $data)
                    ) {
                        $settings[$access_level][$id][$new_type] = $cb(
                            $data[$legacy_type]
                        );
                    }
                }
            } else {
                if (array_key_exists($legacy_type, $level)) {
                    $settings[$access_level][$new_type] = $cb($level[$legacy_type]);
                }
            }
        }

        // Save changes
        if (!empty($settings)) {
            AAM::api()->db->write(
                AAM_Framework_Service_Settings::DB_OPTION,
                $settings
            );
        }
    }

    /**
     * Determine if access level exists
     *
     * @param string     $type
     * @param string|int $id
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _access_level_exists($type, $id)
    {
        if ($type === 'role') {
            $exists = wp_roles()->is_role($id);
        } else {
            $exists = is_a(get_user_by('id', $id), WP_User::class);
        }

        return $exists;
    }

    /**
     * Convert legacy backend menu settings
     *
     * @param array $data
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _convert_legacy_backend_menu($data)
    {
        $result = [];

        if (is_array($data)) {
            foreach($data as $id => $effect) {
                $parsed_slug = $this->_normalize_backend_menu_slug($id);

                $result[urldecode(str_replace('menu-', 'menu/', $parsed_slug))] = [
                    'access' => [
                        'effect' => $this->_convert_to_effect($effect)
                    ]
                ];
            }
        }

        return $result;
    }

    /**
     * Normalize the backend menu slug
     *
     * @param string $menu_slug
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _normalize_backend_menu_slug($menu_slug)
    {
        if (strpos($menu_slug, '.php') !== false) {
            $parsed_url  = wp_parse_url($menu_slug);
            $parsed_slug = $parsed_url['path'];

            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);

                // Removing some redundant query params
                $redundant_params = apply_filters(
                    'aam_ignored_backend_menu_item_query_params_filter',
                    ['return', 'path']
                );

                foreach($redundant_params as $to_remove) {
                    if (array_key_exists($to_remove, $query_params)) {
                        unset($query_params[$to_remove]);
                    }
                }

                // Finally, sort the list of query params in alphabetical order to
                // ensure consistent order
                ksort($query_params);

                if (count($query_params)) {
                    $parsed_slug .= '?' . http_build_query($query_params);
                }
            }
        } else {
            $parsed_slug = trim($menu_slug);
        }

        return urldecode($parsed_slug);
    }

    /**
     * Convert legacy admin toolbar settings
     *
     * @param array $data
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _convert_legacy_admin_toolbar($data)
    {
        $result = [];

        if (is_array($data)) {
            foreach($data as $id => $effect) {
                $result[str_replace('toolbar-', '', $id)] = [
                    'list' => [
                        'effect' => $this->_convert_to_effect($effect)
                    ]
                ];
            }
        }

        return $result;
    }

    /**
     * Convert legacy login redirect
     *
     * @param array $data
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _convert_legacy_login_redirect($data)
    {
        $type = $data['login.redirect.type'];

        if ($type === 'page') {
            $result = [
                'type'             => 'page_redirect',
                'redirect_page_id' => intval($data['login.redirect.page'])
            ];
        } elseif ($type === 'url') {
            $result = [
                'type'         => 'url_redirect',
                'redirect_url' => $data['login.redirect.url']
            ];
        } elseif ($type === 'callback') {
            $result = [
                'type'     => 'trigger_callback',
                'callback' => $data['login.redirect.callback']
            ];
        } else {
            $result = [
                'type' => 'default'
            ];
        }

        return $result;
    }

    /**
     * Convert legacy logout redirect
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_legacy_logout_redirect($data)
    {
        $type = $data['logout.redirect.type'];

        if ($type === 'page') {
            $result = [
                'type'             => 'page_redirect',
                'redirect_page_id' => intval($data['logout.redirect.page'])
            ];
        } elseif ($type === 'url') {
            $result = [
                'type'         => 'url_redirect',
                'redirect_url' => $data['logout.redirect.url']
            ];
        } elseif ($type === 'callback') {
            $result = [
                'type'     => 'trigger_callback',
                'callback' => $data['logout.redirect.callback']
            ];
        } else {
            $result = [
                'type' => 'default'
            ];
        }

        return $result;
    }

    /**
     * Convert legacy access denied redirect
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_legacy_access_denied_redirect($data)
    {
        $result = [];

        // Group frontend and backend settings
        $settings = [
            'frontend' => [],
            'backend'  => []
        ];

        foreach(['frontend', 'backend'] as $area) {
            foreach($data as $key => $value) {
                if (strpos($key, $area) === 0) {
                    $settings[$area][str_replace("{$area}.", '' , $key)] = $value;
                }
            }
        }

        // Do the actual conversion
        foreach($settings as $area => $setting) {
            if (!empty($setting)) {
                $redirect = [];

                $type = $setting['redirect.type'];

                if ($type === 'message') {
                    $redirect['type']    = 'custom_message';
                    $redirect['message'] = $setting['redirect.message'];

                    if (isset($setting['redirect.message.code'])) {
                        $redirect['http_status_code'] = intval(
                            $setting['redirect.message.code']
                        );
                    }
                } elseif ($type === 'page') {
                    $redirect = [
                        'type'             => 'page_redirect',
                        'redirect_page_id' => intval($setting['redirect.page'])
                    ];
                } elseif ($type === 'url') {
                    $redirect = [
                        'type'         => 'url_redirect',
                        'redirect_url' => $setting['redirect.url']
                    ];
                } elseif ($type === 'callback') {
                    $redirect = [
                        'type'     => 'trigger_callback',
                        'callback' => $setting['redirect.callback']
                    ];
                } elseif ($type === 'login') {
                    $redirect = [ 'type' => 'login_redirect' ];
                } else {
                    $redirect['type'] = 'default';

                    if (isset($setting['redirect.default.code'])) {
                        $redirect['http_status_code'] = intval(
                            $setting['redirect.default.code']
                        );
                    }
                }

                $result[$area] = $redirect;
            }
        }

        return $result;
    }

    /**
     * Convert legacy 404 (Not Found) Redirect
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_legacy_not_found_redirect($data)
    {
        $type = $data['404.redirect.type'];

        if ($type === 'page') {
            $result = [
                'type'             => 'page_redirect',
                'redirect_page_id' => intval($data['404.redirect.page'])
            ];
        } elseif ($type === 'url') {
            $result = [
                'type'         => 'url_redirect',
                'redirect_url' => $data['404.redirect.url']
            ];
        } elseif ($type === 'callback') {
            $result = [
                'type'     => 'trigger_callback',
                'callback' => $data['404.redirect.callback']
            ];
        } elseif ($type !== 'login_redirect') {
            $result = [
                'type' => 'default'
            ];
        }

        return $result;
    }

    /**
     * Convert legacy URL Access rules to new format
     *
     * @param array $rules
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_legacy_url_rules($rules)
    {
        $result = [];

        foreach($rules as $url => $rule) {
            $new_url = AAM::api()->misc->sanitize_url($url);

            if ($rule['type'] === 'allow') {
                $result[$new_url] = [
                    'access' => [
                        'effect' => 'allow'
                    ]
                ];
            } elseif ($rule['type'] === 'default') {
                $result[$new_url] = [
                    'access' => [
                        'effect' => 'deny'
                    ]
                ];
            } else {
                $redirect = [
                    'type' => $this->_convert_legacy_redirect_type(
                        $rule['type']
                    )
                ];

                if ($redirect['type'] === 'trigger_callback') {
                    $redirect['callback'] = $rule['action'];
                } elseif ($redirect['type'] === 'url_redirect') {
                    $redirect['redirect_url'] = $rule['action'];
                } elseif ($redirect['type'] === 'page_redirect') {
                    $redirect['redirect_page_id'] = $rule['action'];
                } elseif ($redirect['type'] === 'custom_message') {
                    $redirect['message'] = $rule['action'];
                }

                // Add HTTP Redirect Code if provided
                if (in_array($redirect['type'], ['url_redirect', 'page_redirect'], true)
                    && isset($rule['code'])
                ) {
                    $redirect['http_status_code'] = intval($rule['code']);
                }

                $new_rule = [
                    'effect'   => 'deny',
                    'redirect' => $redirect
                ];

                // Adding also conditions if specified
                $condition = $this->_prepare_url_access_rule_condition($rule);

                if (!empty($condition)) {
                    $new_rule['condition'] = $condition;
                }

                $result[$new_url] = [ 'access' => $new_rule ];
            }
        }

        return $result;
    }

    /**
     * Convert legacy URL Access Rule type to new
     *
     * @param string $type
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_legacy_redirect_type($type)
    {
        if (isset(self::REDIRECT_TYPE_ALIAS[$type])) {
            $type = self::REDIRECT_TYPE_ALIAS[$type];
        }

        return $type;
    }

    /**
     * Prepare URL Access Rule condition
     *
     * @param array $rule
     *
     * @return array|null
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_url_access_rule_condition($rule)
    {
        $condition   = [];
        $data_points = 0; // Count number of data points

        if (isset($rule['metadata'])) {
            if (!empty($rule['metadata']['reference_condition_type'])) {
                $condition['type'] = $rule['metadata']['reference_condition_type'];
                $data_points++;
            }

            if (!empty($rule['metadata']['reference_criteria_type'])) {
                $condition['criteria'] = $rule['metadata']['reference_criteria_type'];
                $data_points++;
            }

            if (!empty($rule['metadata']['reference_query_param_name'])) {
                $condition['query_param'] = $rule['metadata']['reference_query_param_name'];
            }

            if (!empty($rule['metadata']['reference_condition_value'])) {
                $condition['value'] = $rule['metadata']['reference_condition_value'];
                $data_points++;
            }
        }

        // Condition has to be valid in order to be translated
        return $data_points === 3 ? $condition : null;
    }

    /**
     * Convert legacy post access controls to new format
     *
     * @param array $settings
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _convert_legacy_post_settings($settings)
    {
        $result = [];

        foreach($settings as $id => $data) {
            $post = get_post($id);

            if (is_a($post, WP_Post::class)) {
                $new_id = strpos($id, '|') ? $id : $id . '|' . $post->post_type;
                $result[$new_id] = $this->_convert_legacy_post_object($data);
            }
        }

        return $result;
    }

    /**
     * Convert legacy post access controls to new format
     *
     * @param array $settings
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _convert_legacy_post_type_settings($settings)
    {
        $result = [];

        foreach($settings as $id => $data) {
            // Group by scopes
            $scopes = [
                'post' => [],
                'term' => [] // We are going to ignore these settings
            ];

            foreach($data as $action => $d) {
                list($scope, $a)    = explode('/', $action);
                $scopes[$scope][$a] = $d;
            }

            $result[$id] = [];

            if (!empty($scopes['post'])) {
                $result[$id] = $this->_convert_legacy_post_object(
                    $scopes['post']
                );
            }
        }

        return $result;
    }

    /**
     * Convert legacy taxonomy access controls to new format
     *
     * @param array $settings
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_legacy_taxonomy_settings($settings)
    {
        $result = [];

        foreach($settings as $id => $data) {
            // Group by scopes
            $scopes = [
                'term' => []
            ];

            foreach($data as $action => $d) {
                list($scope, $a)    = explode('/', $action);

                if ($scope === 'term') {
                    $scopes[$scope][$a] = $d;
                }
            }

            $result[$id] = [];

            if (!empty($scopes['term'])) {
                $result[$id] = $this->_convert_legacy_term_object(
                    $scopes['term']
                );
            }
        }

        return $result;
    }

    /**
     * Convert legacy term access controls to new format
     *
     * @param array $settings
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_legacy_term_settings($settings)
    {
        $result = [];

        foreach($settings as $id => $data) {
            // Group by scopes
            $scopes = [
                'post' => [],
                'term' => []
            ];

            foreach($data as $action => $d) {
                list($scope, $a)    = explode('/', $action);
                $scopes[$scope][$a] = $d;
            }

            $result[$id] = [];

            if (!empty($scopes['post'])) {
                $result[$id] = $this->_convert_legacy_post_object(
                    $scopes['post']
                );
            } elseif (!empty($scopes['term'])) {
                $result[$id] = $this->_convert_legacy_term_object(
                    $scopes['term']
                );
            }
        }

        return $result;
    }

    /**
     * Convert legacy post actions to permissions
     *
     * @param array $data
     *
     * @return array
     * @access private
     *
     * @version 7.0.6
     */
    private function _convert_legacy_post_object($data)
    {
        $result = [];

        // The following actions compete for the same permission. If there are more
        // then one competing action defined - ignore others.
        $read_permissions = [
            'restricted',  // Simply restricted without any conditions
            'protected',   // Password-protected
            'teaser',      // Show custom wp_die message
            'redirected',  // Redirected to a different location
            'ceased'       // Deny access after certain day/time
        ];

        // Prepare the filtered list of actions
        $filtered_list      = [];
        $ignore_other_reads = false;
        $first_read         = null;

        foreach($data as $action => $settings) {
            if (in_array($action, $read_permissions, true)) {
                // Covering scenario when all "read" controls are unchecked and
                // we need to pick the first one
                if ($first_read === null) {
                    $first_read = [ $action => $settings ];
                }

                $enabled = $this->_convert_to_boolean($settings);

                if (!$ignore_other_reads && $enabled) {
                    $filtered_list[$action] = $settings;
                    $ignore_other_reads     = true;
                    $first_read             = false; // We chose the read option
                }
            } elseif ($action !== 'limited') {
                $filtered_list[$action] = $settings;
            }
        }

        // If all "read" controls are unchecked, pick the first one
        if (!empty($first_read)) {
            $filtered_list = array_merge($filtered_list, $first_read);
        }

        // Convert the filtered list of actions to new format
        foreach($filtered_list as $action => $settings) {
            if (in_array($action, ['hidden', 'hidden_others'], true)) {
                // Determine the areas the post is hidden on
                $areas = $this->_prepare_visibility_areas($settings);
                $item  = [ 'effect' => $this->_convert_to_effect($settings) ];

                if (!empty($areas)) {
                    $item['on'] = $areas;
                }

                if ($action !== 'hidden') {
                    $item['exclude_authors'] = true;
                }

                $result['list'] = $item;
            } elseif (in_array($action, ['restricted', 'restricted_others'], true)) {
                $item = [
                    'effect'           => $this->_convert_to_effect($settings),
                    'restriction_type' => 'default'
                ];

                if ($action !== 'restricted') {
                    $item['exclude_authors'] = true;
                }

                $result['read'] = $item;
            } elseif (in_array($action, ['edit', 'edit_others'], true)) {
                $item = [
                    'effect' => $this->_convert_to_effect($settings)
                ];

                if ($action !== 'edit') {
                    $item['exclude_authors'] = true;
                }

                $result['edit'] = $item;
            } elseif (in_array($action, ['delete', 'delete_others'], true)) {
                $item = [
                    'effect' => $this->_convert_to_effect($settings)
                ];

                if ($action !== 'delete') {
                    $item['exclude_authors'] = true;
                }

                $result['delete'] = $item;
            } elseif (in_array($action, ['publish', 'publish_others'], true)) {
                $item = [
                    'effect' => $this->_convert_to_effect($settings)
                ];

                if ($action !== 'publish') {
                    $item['exclude_authors'] = true;
                }

                $result['publish'] = $item;
            } elseif ($action === 'comment') {
                $result['comment'] = [
                    'effect' => $this->_convert_to_effect($settings)
                ];
            } elseif ($action === 'teaser') {
                $result['read'] = [
                    'effect'           => $this->_convert_to_effect($settings),
                    'restriction_type' => 'teaser_message',
                    'message'          => $settings['message']
                ];
            } elseif ($action === 'redirected') {
                $redirect = [
                    'type' => $this->_convert_legacy_redirect_type($settings['type'])
                ];

                if (isset($settings['httpCode'])) {
                    $redirect['http_status_code'] = intval($settings['httpCode']);
                }

                if ($redirect['type'] === 'page_redirect') {
                    $redirect['redirect_page_id'] = intval($settings['destination']);
                } elseif($redirect['type'] === 'url_redirect') {
                    $redirect['redirect_url'] = trim($settings['destination']);
                } elseif($redirect['type'] === 'trigger_callback') {
                    $redirect['callback'] = trim($settings['destination']);
                }

                $result['read'] = [
                    'effect'           => $this->_convert_to_effect($settings),
                    'restriction_type' => 'redirect',
                    'redirect'         => $redirect
                ];
            } elseif ($action === 'protected') {
                $result['read'] = [
                    'effect'           => $this->_convert_to_effect($settings),
                    'restriction_type' => 'password_protected',
                    'password'         => $settings['password']
                ];
            } elseif ($action === 'ceased') {
                $result['read'] = [
                    'effect'           => $this->_convert_to_effect($settings),
                    'restriction_type' => 'expire',
                    'expires_after'    => intval($settings['after'])
                ];
            }
        }

        return $result;
    }

    /**
     * Convert term actions into new format
     *
     * @param array $data
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _convert_legacy_term_object($data)
    {
        $result = [];

        foreach($data as $action => $settings) {
            if ($action === 'restricted') {
                $result['browse'] = [
                    'effect' => $this->_convert_to_effect($settings)
                ];
            } elseif ($action === 'hidden') {
                $result['list'] = [
                    'effect' => $this->_convert_to_effect($settings),
                    'on'     => $this->_prepare_visibility_areas($settings)
                ];
            } elseif (in_array($action, ['create', 'edit', 'delete', 'assign'], true)) {
                $result[$action] = [
                    'effect' => $this->_convert_to_effect($settings)
                ];
            }
        }

        return $result;
    }

    /**
     * Get "HIDDEN" access control areas
     *
     * @param array $settings
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_visibility_areas($settings)
    {
        $areas = [];

        foreach(['frontend', 'backend', 'api'] as $area) {
            if (isset($settings[$area])
                && ($settings[$area] || $settings[$area] === '1')
            ) {
                array_push($areas, $area);
            }
        }

        return $areas;
    }

    /**
     * Convert AAM access control flag to permission effect
     *
     * @param mixed $setting
     *
     * @return boolean
     * @access private
     *
     * @version 7.0.0
     */
    private function _convert_to_boolean($setting)
    {
        $response = false;

        if(is_bool($setting)) {
            $response = $setting;
        } elseif(is_numeric($setting)) { // Legacy
            $response = intval($setting) === 1;
        } elseif(is_array($setting)) {
            $response = $this->_convert_to_boolean(
                isset($setting['enabled']) ? $setting['enabled'] : false
            );
        }

        return $response;
    }

    /**
     * Convert to effect value
     *
     * @param mixed $setting
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _convert_to_effect($setting)
    {
        return $this->_convert_to_boolean($setting) ? 'deny' : 'allow';
    }

}

if (defined('ABSPATH')) {
    $migration = new AAM_Migration_700();
    $migration->run();
}