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

        // Convert URL Access rules to new format
        $this->_transform_legacy_settings(
            AAM_Core_Object_Uri::OBJECT_TYPE,
            AAM_Framework_Type_Resource::URL,
            function($data) {
                return $this->_convert_legacy_url_rules($data);
            }
        );

        // Convert Login Redirect settings to new format
        $this->_transform_legacy_settings(
            AAM_Core_Object_LoginRedirect::OBJECT_TYPE,
            AAM_Framework_Type_Resource::LOGIN_REDIRECT,
            function($data) {
                return $this->_convert_legacy_login_redirect($data);
            }
        );

        // Convert Logout Redirect settings to new format
        $this->_transform_legacy_settings(
            AAM_Core_Object_LogoutRedirect::OBJECT_TYPE,
            AAM_Framework_Type_Resource::LOGOUT_REDIRECT,
            function($data) {
                return $this->_convert_legacy_logout_redirect($data);
            }
        );

        // Convert Access Denied Redirect settings to new format
        $this->_transform_legacy_settings(
            AAM_Core_Object_Redirect::OBJECT_TYPE,
            AAM_Framework_Type_Resource::ACCESS_DENIED_REDIRECT,
            function($data) {
                return $this->_convert_legacy_access_denied_redirect($data);
            }
        );

        // Convert 404 Redirect settings to new format
        $this->_transform_legacy_settings(
            AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE,
            AAM_Framework_Type_Resource::NOT_FOUND_REDIRECT,
            function($data) {
                return $this->_convert_legacy_not_found_redirect($data);
            }
        );

        // Convert Post settings to new format
        $this->_transform_legacy_settings(
            AAM_Core_Object_Post::OBJECT_TYPE,
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
        $service     = AAM::api()->configs();
        $configs     = $service->get_configs();
        $configpress = $service->get_configpress();

        // The list of changes
        $changes = [
            'core.settings.multiSubject'               => 'core.settings.multi_access_levels',
            'core.service.login-redirect.enabled'      => 'service.log_redirect.enabled',
            'core.service.logout-redirect.enabled'     => 'service.logout_redirect.enabled',
            'core.service.denied-redirect.enabled'     => 'service.access_denied_redirect.enabled',
            'core.service.404-redirect.enabled'        => 'service.not_found_redirect.enabled',
            'core.service.content.enabled'             => 'service.content.enabled',
            'core.service.content.manageAllPostTypes'  => 'service.content.manage_all_post_types',
            'core.service.content.manageAllTaxonomies' => 'service.content.manage_all_taxonomies',
            'core.settings.tips'                       => 'core.settings.ui.tips',
            'ui.settings.renderAccessMetabox'          => 'core.settings.ui.render_access_metabox',
            'core.settings.inheritParentPost'          => 'service.content.inherit_from_parent_post',
            'core.service.content.exclude.taxonomies'  => 'service.content.exclude.taxonomies',
            'feature.post.password.expires'            => 'service.content.password_ttl',
            'geoapi.adapter'                           => 'service.geo_lookup.geoapi.adapter',
            'ipstack.license'                          => 'service.geo_lookup.geoapi.api_key',
            'geoapi.api_key'                           => 'service.geo_lookup.geoapi.api_key',
            'geoapi.test_ip'                           => 'service.geo_lookup.geoapi.test_ip',
            'ipstack.schema'                           => 'service.geo_lookup.ipstack.schema',
            'ipstack.fields'                           => 'service.geo_lookup.ipstack.fields',
            'service.uri.enabled'                      => 'service.url.enabled',
            'core.service.admin-menu.enabled'          => 'service.backend_menu.enabled',
            'core.service.metabox.enabled'             => 'service.metabox.enabled',
            'core.service.toolbar.enabled'             => 'service.admin_toolbar.enabled',
            'core.service.route.enabled'               => 'service.api_route.enabled',
            'core.service.identity-governance.enabled' => 'service.identity.enabled',
            'core.service.jwt.enabled'                 => 'service.jwt.enabled',
            'core.service.capability.enabled'          => 'service.capability.enabled',
            'core.settings.editCapabilities'           => 'service.capability.edit_caps'
        ];

        foreach($changes as $legacy => $new) {
            if (isset($configs[$legacy])) {
                $configs[$new] = $configs[$legacy];

                unset($configs[$legacy]);
            }

            $configpress = str_replace($legacy, $new, $configpress);
        }

        $service->set_configs($configs);
        $service->set_configpress($configpress);
    }

    /**
     * Transform legacy resource settings to new format
     *
     * @param string   $legacy_type
     * @param string   $new_type
     * @param callback $cb
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _transform_legacy_settings($legacy_type, $new_type, $cb)
    {
        // Let's get all the settings first
        $settings = AAM::api()->settings()->get_settings();

        // Iterating of the list of all settings and modify the URL Access rule
        foreach($settings as $access_level => &$level) {
            if (in_array($access_level, ['role', 'user'])) {
                foreach($level as $id => $data) {
                    if (array_key_exists($legacy_type, $data)) {
                        $level[$id][$new_type] = $cb($data[$legacy_type]);

                        // Delete legacy data
                        unset($level[$id][$legacy_type]);
                    }
                }
            } else {
                if (array_key_exists($legacy_type, $level)) {
                    $level[$new_type] = $cb($level[$legacy_type]);

                    // Delete legacy data
                    unset( $level[$legacy_type]);
                }
            }
        }

        // Save changes
        AAM::api()->settings()->set_settings($settings);
    }

    /**
     * Convert legacy login redirect
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
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
            if ($rule['type'] === 'allow') {
                $result[$url] = [
                    'effect' => 'allow',
                    'url'    => $url
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
                    'url'      =>  $url,
                    'redirect' => $redirect
                ];

                // Adding also conditions if specified
                $condition = $this->_prepare_url_access_rule_condition($rule);

                if (!empty($condition)) {
                    $new_rule['condition'] = $condition;
                }

                $result[$url] = $new_rule;
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
     *
     * @access private
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
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_legacy_post_settings($settings)
    {
        $result = [];

        foreach($settings as $id => $data) {
            $result[$id] = $this->_convert_legacy_post_object($data);
        }
    }

    /**
     * Convert legacy post access controls to new format
     *
     * @param array $settings
     *
     * @return array
     *
     * @access private
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
     *
     * @access private
     * @version 7.0.0
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

        foreach($data as $action => $settings) {
            if (in_array($action, $read_permissions, true)) {
                if (!$ignore_other_reads) {
                    $filtered_list[$action] = $settings;
                    $ignore_other_reads     = true;
                }
            } elseif ($action !== 'limited') {
                $filtered_list[$action] = $settings;
            }
        }

        // Convert the filtered list of actions to new format
        foreach($filtered_list as $action => $settings) {
            if (in_array($action, ['hidden', 'hidden_others'], true)) {
                // Determine the areas the post is hidden on
                $areas = $this->_prepare_visibility_areas($settings);

                $item = [
                    'permission' => 'list',
                    'enabled'    => $this->_convert_to_boolean($settings),
                    'on'         => $areas
                ];

                if ($action !== 'hidden') {
                    $item['exclude_authors'] = true;
                }

                if (!empty($areas)) { // Ignore the control if no areas defined
                    $result['list'] = $item;
                }
            } elseif (in_array($action, ['restricted', 'restricted_others'], true)) {
                $item = [
                    'permission'       => 'read',
                    'enabled'          => $this->_convert_to_boolean($settings),
                    'restriction_type' => 'default'
                ];

                if ($action !== 'restricted') {
                    $item['exclude_authors'] = true;
                }

                $result['read'] = $item;
            } elseif (in_array($action, ['edit', 'edit_others'], true)) {
                $item = [
                    'permission' => 'edit',
                    'enabled'    => $this->_convert_to_boolean($settings)
                ];

                if ($action !== 'edit') {
                    $item['exclude_authors'] = true;
                }

                $result['edit'] = $item;
            } elseif (in_array($action, ['delete', 'delete_others'], true)) {
                $item = [
                    'permission' => 'delete',
                    'enabled'    => $this->_convert_to_boolean($settings)
                ];

                if ($action !== 'delete') {
                    $item['exclude_authors'] = true;
                }

                $result['delete'] = $item;
            } elseif (in_array($action, ['publish', 'publish_others'], true)) {
                $item = [
                    'permission' => 'publish',
                    'enabled'    => $this->_convert_to_boolean($settings)
                ];

                if ($action !== 'publish') {
                    $item['exclude_authors'] = true;
                }

                $result['publish'] = $item;
            } elseif ($action === 'comment') {
                $result['comment'] = [
                    'permission' => 'comment',
                    'enabled'    => $this->_convert_to_boolean($settings)
                ];
            } elseif ($action === 'teaser') {
                $result['read'] = [
                    'permission'       => 'read',
                    'enabled'          => $this->_convert_to_boolean($settings),
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
                    'permission'       => 'read',
                    'enabled'          => $this->_convert_to_boolean($settings),
                    'restriction_type' => 'redirect',
                    'redirect'         => $redirect
                ];
            } elseif ($action === 'protected') {
                $result['read'] = [
                    'permission'       => 'read',
                    'enabled'          => $this->_convert_to_boolean($settings),
                    'restriction_type' => 'password_protected',
                    'password'         => $settings['password']
                ];
            } elseif ($action === 'ceased') {
                $result['read'] = [
                    'permission'       => 'read',
                    'enabled'          => $this->_convert_to_boolean($settings),
                    'restriction_type' => 'expire',
                    'after_timestamp'  => intval($settings['after'])
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
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_legacy_term_object($data)
    {
        $result = [];

        foreach($data as $action => $settings) {
            if ($action === 'restricted') {
                $result['browse'] = [
                    'permission' => 'browse',
                    'enabled'    => $this->_convert_to_boolean($settings)
                ];
            } elseif ($action === 'hidden') {
                $result['list'] = [
                    'permission' => 'list',
                    'enabled'    => $this->_convert_to_boolean($settings),
                    'on'         =>  $this->_prepare_visibility_areas($settings)
                ];
            } elseif (in_array($action, ['create', 'edit', 'delete', 'assign'], true)) {
                $result[$action] = [
                    'permission' => $action,
                    'enabled'    => $this->_convert_to_boolean($settings)
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
     *
     * @access private
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
     * Convert REFERENCE access control rules to new format
     *
     * @param array $settings
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_selective_rules($settings)
    {
        $result = [];

        foreach($settings['rules'] as $rule => $effect) {
            list($criteria, $value) = explode('|', $rule);

            array_push($result, [
                'effect'   => $this->_convert_to_boolean($effect) ? 'deny' : 'allow',
                'criteria' => $criteria,
                'compare'  => $value
            ]);
        }

        return $result;
    }

    /**
     * Convert AAM access control flag to permission effect
     *
     * @param mixed $setting
     *
     * @return boolean
     *
     * @access private
     * @version 6.9.31
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

}

if (defined('ABSPATH')) {
    $migration = new AAM_Migration_700();
    $migration->run();
}