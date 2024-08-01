<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Policy service
 *
 * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/408
 * @since 6.9.32 https://github.com/aamplugin/advanced-access-manager/issues/389
 * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/369
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.25 https://github.com/aamplugin/advanced-access-manager/issues/354
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/323
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/294
 *               https://github.com/aamplugin/advanced-access-manager/issues/299
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/285
 * @since 6.9.4  https://github.com/aamplugin/advanced-access-manager/issues/238
 * @since 6.9.1  https://github.com/aamplugin/advanced-access-manager/issues/225
 * @since 6.8.3  https://github.com/aamplugin/advanced-access-manager/issues/207
 * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/71
 *               Added new hook `aam_post_read_action_conversion_filter`
 * @since 6.3.1  Fixed incompatibility with plugins that use WP_User::get_role_caps
 *               method. This method re-index all user capabilities based on assigned
 *               roles and that flushes capabilities attached with Access Policy
 * @since 6.3.0  Removed dependency on PHP core `list` function
 * @since 6.2.0  Bug fixing and enhancements for the multi-site support
 * @since 6.1.0  Changed the way access policy manager is obtained
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.36
 */
class AAM_Service_AccessPolicy
{
    use AAM_Core_Contract_ServiceTrait,
        AAM_Core_Contract_RequestTrait;

    /**
     * Service alias
     *
     * Is used to get service instance if it is enabled
     *
     * @version 6.4.0
     */
    const SERVICE_ALIAS = 'access-policy';

    /**
     * AAM configuration setting that is associated with the feature
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.access-policy.enabled';

    /**
     * Access policy CPT
     *
     * @version 6.0.0
     */
    const POLICY_CPT = 'aam_policy';

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
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        $enabled = AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Policy::register();
                }, 40);

                //register custom access control metabox
                add_action('add_meta_boxes', array($this, 'registerMetaboxes'));

                //access policy save
                add_filter('wp_insert_post_data', array($this, 'managePolicyContent'));
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Access Policies', AAM_KEY),
                    'description' => __('Control website access using thoroughly documented JSON policies for users, roles, and visitors. Maintain a detailed record of all access changes and policy revisions.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 40);
        }

        if ($enabled) {
            $this->initializeHooks();
        }
    }

    /**
     * Register UI metaboxes for the Access Policy edit screen
     *
     * @global WP_Post $post
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function registerMetaboxes()
    {
        global $post;

        if (is_a($post, 'WP_Post') && ($post->post_type === self::POLICY_CPT)) {
            add_meta_box(
                self::POLICY_CPT,
                __('Access Policy Document', AAM_KEY),
                function() {
                    echo AAM_Backend_View::getInstance()->renderPolicyMetabox();
                },
                null,
                'normal',
                'high'
            );

            add_meta_box(
                'aam-policy-assignee',
                __('Access Policy Assignee', AAM_KEY),
                function() {
                    echo AAM_Backend_View::getInstance()->renderPolicyPrincipalMetabox();
                },
                null,
                'side'
            );
        }
    }

    /**
     * Hook into policy submission and filter its content
     *
     * @param array $data
     *
     * @return array
     *
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/294
     * @since 6.3.0  https://github.com/aamplugin/advanced-access-manager/issues/27
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.13
     */
    public function managePolicyContent($data)
    {
        if (isset($data['post_type']) && ($data['post_type'] === self::POLICY_CPT)) {
            $content = $this->getFromPost('aam-policy');

            if (empty($content)) {
                if (empty($data['post_content'])) {
                    $content = AAM_Backend_Feature_Main_Policy::getDefaultPolicy();
                } else {
                    $content = $data['post_content'];
                }
            }

            // Removing any slashes
            $content = htmlspecialchars_decode(stripslashes($content));

            // Reformat the policy content
            $json = json_decode($content);

            if (!empty($json)) {
                $content = wp_json_encode($json, JSON_PRETTY_PRINT);
            }

            if (!empty($content)) { // Edit form was submitted
                $content = addslashes($content);
            }

            $data['post_content'] = $content;
        }

        return $data;
    }

    /**
     * Initialize Access Policy hooks
     *
     * @return void
     *
     * @since 6.9.32 https://github.com/aamplugin/advanced-access-manager/issues/389
     * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/369
     * @since 6.9.25 https://github.com/aamplugin/advanced-access-manager/issues/354
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/323
     * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/286
     * @since 6.9.4  https://github.com/aamplugin/advanced-access-manager/issues/238
     * @since 6.9.1  https://github.com/aamplugin/advanced-access-manager/issues/225
     * @since 6.8.3  https://github.com/aamplugin/advanced-access-manager/issues/207
     * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/71
     *               https://github.com/aamplugin/advanced-access-manager/issues/62
     *               https://github.com/aamplugin/advanced-access-manager/issues/63
     * @since 6.2.1  Access support for custom-fields
     * @since 6.2.0  Added new hook into Multi-site service through
     *               `aam_allowed_site_filter`
     * @since 6.1.1  Refactored the way access policy is applied to object
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.32
     */
    protected function initializeHooks()
    {
        // Register Access Policy CPT
        add_action('init', function () {
            register_post_type('aam_policy', array(
                'label'        => __('Access Policy', AAM_KEY),
                'labels'       => array(
                    'name'          => __('Access Policies', AAM_KEY),
                    'edit_item'     => __('Edit Policy', AAM_KEY),
                    'singular_name' => __('Policy', AAM_KEY),
                    'add_new_item'  => __('Add New Policy', AAM_KEY),
                    'new_item'      => __('New Policy', AAM_KEY)
                ),
                'description'  => __('Access and security policy', AAM_KEY),
                'public'              => false,
                'show_ui'             => true,
                'show_in_rest'        => true,
                'show_in_menu'        => false,
                'exclude_from_search' => true,
                'publicly_queryable'  => false,
                'hierarchical' => false,
                'supports'     => array(
                    'title', 'excerpt', 'revisions', 'custom-fields'
                ),
                'delete_with_user' => false,
                'capabilities' => array(
                    'edit_post'         => 'aam_edit_policy',
                    'read_post'         => 'aam_read_policy',
                    'delete_post'       => 'aam_delete_policy',
                    'delete_posts'      => 'aam_delete_policies',
                    'edit_posts'        => 'aam_edit_policies',
                    'edit_others_posts' => 'aam_edit_others_policies',
                    'publish_posts'     => 'aam_publish_policies',
                )
            ));
        });

        // Can register this only after user object is initialized
        add_action('init', function() {
            AAM_Service_AccessPolicy_HookController::bootstrap();
        }, -10);

        // Hook into AAM core objects initialization
        add_filter('aam_menu_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);
        add_filter('aam_metabox_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);
        add_filter('aam_toolbar_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);
        add_filter('aam_post_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);
        add_action('aam_visibility_object_init_action', function(AAM_Core_Object_Visibility $object) {
            $subject = $object->getSubject();

            if ($subject::UID === AAM_Core_Subject_User::UID) {
                $this->initializeVisibility($object);
            }
        });
        add_filter('aam_uri_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);
        add_filter('aam_route_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);

        // Hooks to support all available Redirects
        add_filter('aam_redirect_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);
        add_filter('aam_login_redirect_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);
        add_filter('aam_logout_redirect_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);
        add_filter('aam_404_redirect_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);
        add_filter('aam_user_governance_object_option_filter', array($this, 'applyAccessPolicyToObject'), 10, 2);

        // Allow third-party to hook into Post resource conversion
        add_filter('aam_post_resource_filter', array($this, 'convertPostStatement'), 10, 4);

        // Manage access to the Capabilities
        add_filter('aam_cap_can_filter', array($this, 'isCapabilityAllowed'), 10, 3);
        add_action('aam_initialize_user_action', array($this, 'initializeUser'));

        // Manage access to the Plugin list and individual plugins
        add_filter('aam_allowed_plugin_action_filter', array($this, 'isPluginActionAllowed'), 10, 3);
        add_filter('all_plugins', array($this, 'filterPlugins'));

        // Multisite support
        add_filter('aam_site_restricted_filter', function($response) {
            if ($response === false) {
                $manager  = AAM::api()->getAccessPolicyManager();
                $response = $manager->isAllowed(
                    'Site:' . get_current_blog_id()
                ) === false;
            }

            return $response;
        });

        // Enrich the RESTful API
        add_filter('aam_role_rest_field_filter', array($this, 'enrich_role_rest_output'), 1, 3);
        add_filter('aam_user_rest_field_filter', array($this, 'enrich_user_rest_output'), 1, 3);

        add_action('aam_valid_jwt_token_detected_action', function($token, $claims) {
            update_user_meta($claims->userId, 'aam_auth_token', $token);
        }, 10, 2);

        // Service fetch
        $this->registerService();
    }

    /**
     * Apply access policy statements to passed object
     *
     * @param array           $options
     * @param AAM_Core_Object $object
     *
     * @return array
     *
     * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/369
     * @since 6.4.0  Enhanced with redirects support
     * @since 6.2.0  Fixed bug when access policy was not applied to visitors
     * @since 6.1.1  Optimized policy implementation
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.28
     */
    public function applyAccessPolicyToObject($options, AAM_Core_Object $object)
    {
        $subject      = $object->getSubject();
        $lowest_level = array(
            AAM_Core_Subject_User::UID, AAM_Core_Subject_Visitor::UID
        );

        if (in_array($subject::UID, $lowest_level, true)) {
            switch($object::OBJECT_TYPE) {
                case AAM_Core_Object_Menu::OBJECT_TYPE:
                    $options = $this->initializeMenu($options, $object);
                    break;

                case AAM_Core_Object_Toolbar::OBJECT_TYPE:
                    $options = $this->initializeToolbar($options, $object);
                    break;

                case AAM_Core_Object_Metabox::OBJECT_TYPE:
                    $options = $this->initializeMetabox($options, $object);
                    break;

                case AAM_Core_Object_Post::OBJECT_TYPE:
                    $options = $this->initializePost($options, $object);
                    break;

                case AAM_Core_Object_Uri::OBJECT_TYPE:
                    $options = $this->initializeUri($options, $object);
                    break;

                case AAM_Core_Object_Route::OBJECT_TYPE:
                    $options = $this->initializeRoute($options, $object);
                    break;

                case AAM_Core_Object_Redirect::OBJECT_TYPE:
                    $options = $this->initializeAccessDeniedRedirect($options);
                    break;

                case AAM_Core_Object_LoginRedirect::OBJECT_TYPE:
                    $options = $this->initializeRedirect($options, $subject, 'login');
                    break;

                case AAM_Core_Object_LogoutRedirect::OBJECT_TYPE:
                    $options = $this->initializeRedirect($options, $subject, 'logout');
                    break;

                case AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE:
                    $options = $this->initializeRedirect($options, $subject, '404');
                    break;

                case AAM_Core_Object_IdentityGovernance::OBJECT_TYPE:
                    $options = $this->initializeIdentityGovernance($options, $object);
                    break;

                default:
                    break;
            }
        }

        return $options;
    }

    /**
     * Initialize Admin Menu Object options
     *
     * @param array                $option
     * @param AAM_Core_Object_Menu $object
     *
     * @return array
     *
     * @since 6.1.1 Method becomes protected
     * @since 6.1.0 Changed the way access policy manage is obtained
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @see https://aamportal.com/reference/json-access-policy/resource-action/backendmenu
     * @version 6.1.1
     */
    protected function initializeMenu($option)
    {
        $manager = AAM::api()->getAccessPolicyManager();
        $found   = $manager->getResources(AAM_Core_Policy_Resource::MENU);
        $parsed  = array();

        foreach ($found as $key => $stm) {
            $parsed[$key] = ($stm['Effect'] === 'deny' ? true : false);
        }

        return array_replace($option, $parsed); // First-class citizen
    }

    /**
     * Initialize Toolbar Object options
     *
     * @param array $option
     *
     * @return array
     *
     * @since 6.1.1 Method becomes protected
     * @since 6.1.0 Changed the way access policy manage is obtained
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @see https://aamportal.com/reference/json-access-policy/resource-action/toolbar
     * @version 6.1.1
     */
    protected function initializeToolbar($option)
    {
        $manager = AAM::api()->getAccessPolicyManager();
        $found   = $manager->getResources(AAM_Core_Policy_Resource::TOOLBAR);
        $parsed  = array();

        foreach ($found as $key => $stm) {
            $parsed[$key] = ($stm['Effect'] === 'deny' ? true : false);
        }

        return array_replace($option, $parsed); // First-class citizen
    }

    /**
     * Initialize Metabox Object options
     *
     * @param array $option
     *
     * @return array
     *
     * @since 6.1.1 Method becomes protected
     * @since 6.1.0 Changed the way access policy manage is obtained
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @see https://aamportal.com/reference/json-access-policy/resource-action/metabox
     * @version 6.1.1
     */
    protected function initializeMetabox($option)
    {
        $manager = AAM::api()->getAccessPolicyManager();
        $found   = $manager->getResources(array(
            AAM_Core_Policy_Resource::METABOX, AAM_Core_Policy_Resource::WIDGET
        ));

        $parsed  = array();

        foreach ($found as $key => $stm) {
            $parsed[$key] = ($stm['Effect'] === 'deny' ? true : false);
        }

        return array_replace($option, $parsed); // First-class citizen
    }

    /**
     * Initialize Post Object options
     *
     * @param array                $option
     * @param AAM_Core_Object_Post $object
     *
     * @return array
     *
     * @since 6.1.1 Method becomes protected
     * @since 6.1.0 Changed the way access policy manage is obtained
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @see https://aamportal.com/reference/json-access-policy/resource-action/post
     * @version 6.1.1
     */
    protected function initializePost($option, AAM_Core_Object_Post $object)
    {
        $manager = AAM::api()->getAccessPolicyManager();
        $found   = $manager->getResources(sprintf(
            '%s:%s:(%d|%s)',
            AAM_Core_Policy_Resource::POST,
            $object->post_type,
            $object->ID,
            $object->post_name
        ));

        $parsed = array();

        foreach($found as $action => $stmt) {
            $parsed = $this->convertPostStatement($parsed, $action, $stmt);
        }

        return array_replace_recursive($option, $parsed); // First-class citizen
    }

    /**
     * Initialize post visibility options
     *
     * @param AAM_Core_Object_Visibility $visibility
     *
     * @return void
     *
     * @since 6.1.1 Method becomes protected
     * @since 6.1.0 Changed the way access policy manage is obtained
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.1.1
     */
    protected function initializeVisibility(AAM_Core_Object_Visibility $visibility)
    {
        $manager = AAM::api()->getAccessPolicyManager();
        $found   = $manager->getResources(AAM_Core_Policy_Resource::POST);

        foreach($found as $resource => $stm) {
            $chunks = explode(':', $resource);
            $effect = (strtolower($stm['Effect']) === 'allow' ? false : true);

            // Allow other plugins to determine what access options should be
            // considered during visibility check. For example Complete Package uses
            // HIDDEN TO OTHERS options
            $map = apply_filters('aam_policy_post_visibility_map_filter', array(
                'list' => 'hidden'
            ));

            // Take in consideration only visibility properties
            if (array_key_exists($chunks[2], $map)) {
                if (is_numeric($chunks[1])) {
                    $id = intval($chunks[1]);
                } else {
                    $post = get_page_by_path($chunks[1], OBJECT, $chunks[0]);
                    $id   = (is_a($post, 'WP_Post') ? $post->ID : null);
                }

                // Making sure that we have at least numeric post ID
                if (!empty($id)) {
                    $visibility->pushOptions('post', "{$id}|{$chunks[0]}", array(
                        $map[$chunks[2]] => $effect
                    ));
                }
            }
        }
    }

    /**
     * Initialize URI Object options
     *
     * @param array $option
     *
     * @return array
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.1.1  Method becomes protected
     * @since 6.1.0  Changed the way access policy manage is obtained
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @see https://aamportal.com/reference/json-access-policy/resource-action/uri
     *
     * @version 6.9.26
     */
    protected function initializeUri($option)
    {
        $manager = AAM::api()->getAccessPolicyManager();
        $found   = $manager->getResources(AAM_Core_Policy_Resource::URI);
        $parsed  = array();

        foreach($found as $uri => $stm) {
            $uri    = rtrim($uri, '/'); // No need to honor the trailing forward slash
            $effect = (strtolower($stm['Effect']) === 'allow' ? false : true);

            if ($effect === false) {
                $parsed[$uri] = array(
                    'type' => 'allow'
                );
            } elseif(isset($stm['Metadata']['Redirect'])) {
                $props = $this->_processRedirectParams($stm['Metadata']['Redirect']);

                if (!empty($props)) {
                    $type = $props['type'];

                    // TODO: Post redirect stores the redirect values in a different
                    // format. Normalize it to be the same way as any other redirect
                    $option[$uri] = array(
                        'type' => $type,
                        'action' => isset($props[$type]) ? $props[$type] : null
                    );

                    // No need to store the HTTP status code
                    if (!is_null($props['code'])) {
                        $option[$uri]['code'] = $props['code'];
                    }
                }
            } else {
                $option[$uri] = array(
                    'type'   => 'default',
                    'action' => null
                );
            }
        }

        return array_merge($option, $parsed); //First-class citizen
    }

    /**
     * Initialize the user governance rules
     *
     * @param array $option
     *
     * @return array
     *
     * @access protected
     * @version 6.9.28
     */
    protected function initializeIdentityGovernance($option)
    {
        // Covert any role or user_role rules
        $parsed = $this->_convertIdentityGovernanceStatements(
            AAM_Core_Policy_Resource::ROLE
        );

        // Convert role_level rules
        $parsed = array_merge($parsed, $this->_convertIdentityGovernanceStatements(
            AAM_Core_Policy_Resource::ROLE_LEVEL
        ));

        // Convert user rules
        $parsed = array_merge($parsed, $this->_convertIdentityGovernanceStatements(
            AAM_Core_Policy_Resource::USER
        ));

        // Convert user_level rules
        $parsed = array_merge($parsed, $this->_convertIdentityGovernanceStatements(
            AAM_Core_Policy_Resource::USER_LEVEL
        ));

        return array_merge($option, $parsed); //First-class citizen
    }

    /**
     * Convert statement to proper user governance settings
     *
     * @param string $resource_type
     *
     * @return array
     *
     * @access private
     * @version 6.9.28
     */
    private function _convertIdentityGovernanceStatements($resource_type)
    {
        $manager  = AAM::api()->getAccessPolicyManager();
        $response = array();

        foreach($manager->getResources($resource_type) as $resource => $statement) {
            $effect = strtolower($statement['Effect']) === 'allow' ? 'allow' : 'deny';
            $parts  = explode(':', $resource);
            $target = null;

            if ($resource_type === AAM_Core_Policy_Resource::ROLE) {
                if (isset($parts[1]) && $parts[1] === 'users') { // Targeting users
                    $target     = "user_role|{$parts[0]}";
                    $permission = $this->_convertIdentityGovernanceAction(
                        isset($parts[2]) ? $parts[2] : null,
                        'user'
                    );
                } else { // Targeting role or all roles with wildcard
                    $target     = "role|{$parts[0]}";
                    $permission = $this->_convertIdentityGovernanceAction(
                        isset($parts[1]) ? $parts[1] : null,
                        'role'
                    );
                }
            } elseif ($resource_type === AAM_Core_Policy_Resource::ROLE_LEVEL) {
                $target     = "role_level|{$parts[0]}";
                $permission = $this->_convertIdentityGovernanceAction(
                    isset($parts[1]) ? $parts[1] : null,
                    'role'
                );
            } elseif ($resource_type === AAM_Core_Policy_Resource::USER) {
                if (is_numeric($parts[0])) { // Get user by ID
                    $user = get_user_by('id', $parts[0]);
                } elseif (is_string($parts[0]) && $parts[0] !== '*') {
                    if (strpos($parts[0], '@') > 0) { // Email?
                        $user = get_user_by('email', $parts[0]);
                    } else {
                        $user = get_user_by('login', $parts[0]);
                    }
                }

                if (isset($user) && is_a($user, 'WP_User')) {
                    $user_login = $user->user_login;
                } elseif ($parts[0] === '*') {
                    $user_login = '*';
                } else {
                    $user_login = null;
                }

                $target     = "user|{$user_login}";
                $permission = $this->_convertIdentityGovernanceAction(
                    isset($parts[1]) ? $parts[1] : null,
                    'user'
                );
            } elseif ($resource_type === AAM_Core_Policy_Resource::USER_LEVEL) {
                $target     = "user_level|{$parts[0]}";
                $permission = $this->_convertIdentityGovernanceAction(
                    isset($parts[1]) ? $parts[1] : null,
                    'user'
                );
            }

            if ($target !== null) {
                if (!isset($response[$target])) {
                    $response[$target] = [];
                }

                $response[$target][$permission] = $effect;
            }
        }

        return $response;
    }

    /**
     * Convert action into setting
     *
     * @param array  $actions
     * @param string $resource_type
     *
     * @return string
     *
     * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/408
     * @since 6.9.28 Initial implementation of the method
     *
     * @access private
     * @version 6.9.36
     */
    private function _convertIdentityGovernanceAction($action, $resource_type)
    {
        $response = null;

        if (!empty($action)) {
            $lowercase = strtolower($action);

            if ($lowercase === 'list') {
                $response = "list_{$resource_type}";
            } elseif (in_array($lowercase, ['edit', 'delete'], true)) {
                $response = "{$lowercase}_{$resource_type}";
            } elseif ($lowercase === 'changepassword') {
                $response = 'change_user_password';
            } elseif (in_array($lowercase, ['promote','changerole'], true)) {
                $response = 'change_user_role';
            }
        }

        return $response;
    }

    /**
     * Initialize Route Object options
     *
     * @param array $option
     *
     * @return array
     *
     * @since 6.1.1 Method becomes protected
     * @since 6.1.0 Changed the way access policy manage is obtained
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @see https://aamportal.com/reference/json-access-policy/resource-action/route
     * @version 6.1.1
     */
    protected function initializeRoute($option)
    {
        $manager = AAM::api()->getAccessPolicyManager();
        $found   = $manager->getResources(AAM_Core_Policy_Resource::ROUTE);
        $parsed  = array();

        foreach($found as $route => $stm) {
            $effect = (strtolower($stm['Effect']) === 'allow' ? false : true);
            $parsed[strtolower(str_replace(':', '|', $route))] = $effect;
        }

        return array_merge($option, $parsed); //First-class citizen
    }

    /**
     * Initialize Access Denied Redirect rules
     *
     * @param array $option
     *
     * @return array
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.4.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.26
     */
    protected function initializeAccessDeniedRedirect($option)
    {
        $manager = AAM::api()->getAccessPolicyManager();
        $parsed  = array();

        // Fetching both frontend & backend access denied redirect params
        $params = $manager->getParams('redirect:on:access-denied:(.*)');

        foreach($params as $key => $param) {
            $parts = explode(':', $key);
            $area  = array_pop($parts);
            $props = $this->_processRedirectParams(
                $param['Value'],
                AAM_Framework_Service_AccessDeniedRedirect::HTTP_DEFAULT_STATUS_CODES
            );

            // Convert the identified properties to the legacy AAM key/value pair
            $type                            = $props['type'];
            $parsed["{$area}.redirect.type"] = $type;

            if (!is_null($props['code'])) {
                $parsed["{$area}.redirect.{$type}.code"] = $props['code'];
            }

            // The default type does not have any additional configurations, so
            // make sure that we take this into account
            if (isset($props[$type])) {
                $parsed["{$area}.redirect.{$type}"] = $props[$type];
            }
        }

        return array_merge($option, $parsed); //First-class citizen
    }

    /**
     * Initialize the Redirect rules
     *
     * @param array            $option
     * @param AAM_Core_Subject $subject
     * @param string           $redirect_type
     *
     * @return array
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/299
     * @since 6.9.12 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.26
     */
    protected function initializeRedirect($option, $subject, $redirect_type)
    {
        $manager     = AAM::api()->getAccessPolicyManager($subject);
        $properties  = $this->_processRedirectParams(
            $manager->getParam("redirect:on:{$redirect_type}")
        );

        // Convert the identified properties to the legacy AAM key/value pair
        $parsed = array();

        foreach($properties as $key => $value) {
            if (!is_null($value)) {
                $parsed["{$redirect_type}.redirect.{$key}"] = $value;
            }
        }

        return array_merge($option, $parsed); //First-class citizen
    }

    /**
     * Convert policy redirect definition to AAM settings
     *
     * @param array $param
     * @param array $default_status_codes
     *
     * @return array
     *
     * @access private
     * @version 6.9.26
     */
    private function _processRedirectParams($param, $default_status_codes = array())
    {
        $response = array();

        if (!empty($param)) {
            $type        = isset($param['Type']) ? $param['Type'] : 'default';
            $status_code = isset($default_status_codes[$type]) ? $default_status_codes[$type] : null;

            if (in_array($type, array('page', 'page_redirect'))) {
                // Adding the redirect type
                $response['type'] = 'page';

                if (isset($param['PageId'])) {
                    $response['page'] = intval($param['PageId']);
                } elseif (isset($param['Id'])) { // legacy param
                    $response['page'] = intval($param['Id']);
                } elseif (isset($param['Slug'])) {
                    $page = get_page_by_path($param['Slug'], OBJECT);
                    $response['page'] = (is_a($page, 'WP_Post') ? $page->ID : 0);
                } elseif (isset($param['PageSlug'])) {
                    $page = get_page_by_path($param['PageSlug'], OBJECT);
                    $response['page'] = (is_a($page, 'WP_Post') ? $page->ID : 0);
                }
            } elseif (in_array($type, array('url', 'url_redirect'))) {
                // Adding the redirect type
                $response['type'] = 'url';

                if (isset($param['Url'])) {
                    $response['url'] = $param['Url'];
                } elseif (isset($param['URL'])) { // legacy
                    $response['url'] = $param['URL'];
                }
            } elseif (in_array($type, array('callback', 'trigger_callback'))) {
                $response['type']     = 'callback';
                $response['callback'] = $param['Callback'];
            } elseif (in_array($type, array('message', 'custom_message'))) {
                $response['type']    = 'message';
                $response['message'] = $param['Message'];
                $default_status_code  = 401;
            } elseif (in_array($type, array('login', 'login_redirect'), true)) {
                $response['type'] = 'login';
            } else {
                $response['type'] = 'default';
            }

            if (isset($param['Code'])) {
                $response['code'] = intval($param['Code']);
            } else {
                $response['code'] = $status_code;
            }
        }

        return $response;
    }

    /**
     * Check if specified action is allowed upon capability
     *
     * @param boolean $allowed
     * @param string  $cap
     * @param string  $action
     *
     * @return boolean
     *
     * @since 6.1.1 Fixed bug with access policy inheritance
     * @since 6.1.0 Changed the way access policy manage is obtained
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @link https://aamportal.com/reference/json-access-policy/resource-action/capability
     * @version 6.1.1
     */
    public function isCapabilityAllowed($allowed, $cap, $action)
    {
        $manager = AAM::api()->getAccessPolicyManager();
        $result  = $manager->isAllowed("Capability:{$cap}:AAM:{$action}");

        return ($result === null ? $allowed : $result);
    }

    /**
     * Initialize user with policy capabilities and roles
     *
     * @param AAM_Core_Subject_User $subject
     *
     * @return void
     *
     * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/369
     * @since 6.3.1  https://github.com/aamplugin/advanced-access-manager/issues/45
     * @since 6.1.0  Changed the way access policy manage is obtained
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @link https://aamportal.com/reference/json-access-policy/resource-action/capability
     * @link https://aamportal.com/reference/json-access-policy/resource-action/role
     *
     * @version 6.9.28
     */
    public function initializeUser(AAM_Core_Subject_User $subject)
    {
        $manager = AAM::api()->getAccessPolicyManager($subject);
        $wp_user = $subject->getPrincipal();

        // Update user's list of roles if policy states so
        $roles = $manager->getResources(AAM_Core_Policy_Resource::ROLE);

        if (count($roles)) {
            foreach($roles as $id => $statement) {
                // Only take into consideration Role resources that either do not
                // have any actions OR have action "Promote"
                $parts = explode(':', $id);

                if (!isset($parts[1]) || $parts[1] === 'promote') {
                    if (AAM_Core_API::getRoles()->is_role($parts[0])) {
                        $effect = strtolower($statement['Effect']);

                        if ($effect === 'allow') { // Add new
                            $wp_user->caps[$parts[0]] = true;
                        } elseif (($effect === 'deny')) { // Remove
                            // Remove capability that represents a role
                            $wp_user->caps = array_filter(
                                $wp_user->caps,
                                function($v, $k) use ($parts) {
                                    return $parts[0] !== $k;
                                }, ARRAY_FILTER_USE_BOTH
                            );

                            // Remove role itself
                            $wp_user->roles = array_filter(
                                $wp_user->caps,
                                function($v) use ($parts) {
                                    return $parts[0] !== $v;
                                }
                            );
                        }
                    } else {
                        do_action(
                            'aam_promote_user_with_role_resource_action',
                            $parts[0],
                            $statement,
                            $wp_user
                        );
                    }
                }
            }

            // Re-index all user capabilities based on new set of roles
            $wp_user->get_role_caps();

            // Add siblings to the User subject
            $user_roles = array_values($wp_user->roles);

            if (count($user_roles) > 1) {
                $subject->getParent()->setSiblings(array_map(function($id) {
                    return AAM::api()->getRole($id);
                }, array_slice($user_roles, 1)));
            }
        }

        // Get all the capabilities that mentioned in the policies explicitly
        $caps = array_filter(
            $manager->getResources(AAM_Core_Policy_Resource::CAPABILITY),
            function($stm, $res) {
                return (strpos($res, ':') === false); // Exclude any :AAM: resources
            },
            ARRAY_FILTER_USE_BOTH
        );

        foreach($caps as $cap => $statement) {
            $effect = (strtolower($statement['Effect']) === 'allow' ? true : false);

            $wp_user->allcaps[$cap] = $effect;

            // Also update user's specific cap if exists
            $wp_user->caps[$cap] = $effect;
        }

        // Finally update user level
        $wp_user->user_level = array_reduce(
            array_keys($wp_user->allcaps), array($wp_user, 'level_reduction'), 0
        );
    }

    /**
     * Convert Post resource statement
     *
     * @param array  $output
     * @param string $action
     * @param array  $stmt
     * @param string $ns
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function convertPostStatement($output, $action, $stmt, $ns = '')
    {
        switch($action) {
            case 'edit':
            case 'delete':
            case 'publish':
            case 'comment':
                $this->convertedPostSimpleAction($output, $ns . $action, $stmt);
                break;

            case 'list':
                $this->convertedPostSimpleAction($output, $ns . 'hidden', $stmt);
                break;

            case 'read':
                $this->convertedPostReadAction($output, $stmt, $ns);
                break;

            default:
                $output = apply_filters(
                    'aam_convert_post_action_filter', $output, $action, $stmt, $ns
                );
                break;
        }

        return $output;
    }

    /**
     * Covert simple post action to post object property
     *
     * @param array  &$options
     * @param string $action
     * @param array  $statement
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function convertedPostSimpleAction(&$options, $action, $statement)
    {
        $options[$action] = strtolower($statement['Effect']) !== 'allow';
    }

    /**
     * Convert Post Read action based on metadata
     *
     * @param array  &$options
     * @param array  $statement
     * @param string $ns
     *
     * @return void
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.4.0  Added `aam_post_read_action_conversion_filter` to support
     *               https://github.com/aamplugin/advanced-access-manager/issues/68
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.26
     */
    protected function convertedPostReadAction(&$options, $statement, $ns = '')
    {
        $effect = strtolower($statement['Effect']) !== 'allow';

        if (array_key_exists('Metadata', $statement)) {
            $metadata = $statement['Metadata'];

            // Password Protected options
            if(array_key_exists('Password', $metadata)) {
                $options[$ns . 'protected'] = array(
                    'enabled'  => $effect,
                    'password' => $metadata['Password']['Value']
                );
            }

            // Teaser message is defined
            if(array_key_exists('Teaser', $metadata)) {
                $options[$ns . 'teaser'] = array(
                    'enabled' => $effect,
                    'message' => $metadata['Teaser']['Value']
                );
            }

            // Redirect options
            if(array_key_exists('Redirect', $metadata)) {
                $redirect = array();
                $props    = $this->_processRedirectParams($metadata['Redirect'], 307);

                // TODO: Post redirect stores the redirect values in a different
                // format. Normalize it to be the same way as any other redirect
                if (!empty($props)) {
                    $type                    = $props['type'];
                    $redirect['type']        = $type;
                    $redirect['destination'] = isset($props[$type]) ? $props[$type] : null;
                    $redirect['enabled']     = $effect;

                    if (!is_null($props['code'])) {
                        $redirect['httpCode'] = $props['code'];
                    }

                    // Set the converted access controls
                    $options[$ns . 'redirected'] = $redirect;
                }
            }

            // Limited option
            if(array_key_exists('Limited', $metadata)) {
                $options[$ns . 'limited'] = array(
                    'enabled'   => $effect,
                    'threshold' => $metadata['Limited']['Threshold']
                );
            }

            $options = apply_filters(
                'aam_post_read_action_conversion_filter', $options, $statement, $ns
            );
        } else { // Simply restrict access to read a post
            $options[$ns . 'restricted'] = $effect;
        }
    }

    /**
     * Check if specific action is allowed upon all plugins or specified plugin
     *
     * @param boolean|null $allowed
     * @param string       $action
     * @param string       $slug
     *
     * @return boolean
     *
     * @since 6.1.0 Changed the way access policy manage is obtained
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @link https://aamportal.com/reference/json-access-policy/resource-action/plugin
     * @version 6.1.0
     */
    public function isPluginActionAllowed($allowed, $action, $slug = null)
    {
        $manager = AAM::api()->getAccessPolicyManager();

        if ($slug === null) {
            $id = AAM_Core_Policy_Resource::PLUGIN . ":WP:{$action}";
        } else {
            $id = AAM_Core_Policy_Resource::PLUGIN . ":{$slug}:WP:{$action}";
        }

        return $manager->isAllowed($id);
    }

    /**
     * Filter out all the plugins that are not allowed to be listed
     *
     * @param array $plugins
     *
     * @return array
     *
     * @since 6.3.0 Fixed potential bug https://github.com/aamplugin/advanced-access-manager/issues/38
     * @since 6.1.0 Changed the way access policy manage is obtained
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.0
     */
    public function filterPlugins($plugins)
    {
        $manager  = AAM::api()->getAccessPolicyManager();
        $filtered = array();

        foreach($plugins as $id => $plugin) {
            $parts    = explode('/', $id);
            $resource = AAM_Core_Policy_Resource::PLUGIN . ":{$parts[0]}:WP:list";

            if ($manager->isAllowed($resource) !== false) {
                $filtered[$id] = $plugin;
            }
        }

        return $filtered;
    }

    /**
     * Get the list of attached policies to role
     *
     * @param null                     $output
     * @param AAM_Framework_Proxy_Role $id
     * @param string                   $field
     *
     * @return array
     *
     * @access public
     * @version 6.9.6
     */
    public function enrich_role_rest_output($output, $role, $field)
    {
        if ($field === 'applied_policy_ids') {
            $object = AAM::api()->getRole($role->slug)->getObject(
                AAM_Core_Object_Policy::OBJECT_TYPE
            );

            $output = array();

            foreach($object->getOption() as $id => $effect) {
                if (!empty($effect)) {
                    array_push($output, $id);
                }
            }
        }

        return $output;
    }

    /**
     * Get the list of attached policies to user
     *
     * @param array $output
     * @param array $id
     * @param array $fields
     *
     * @return array
     *
     * @access public
     * @version 6.9.32
     */
    public function enrich_user_rest_output($output, $user, $fields)
    {
        if (in_array('policies', $fields, true)) {
            $object = AAM::api()->getUser($user['id'])->getObject(
                AAM_Core_Object_Policy::OBJECT_TYPE
            );

            $output['policies'] = [];

            foreach($object->getOption() as $id => $effect) {
                if (!empty($effect)) {
                    array_push($output['policies'], $id);
                }
            }
        }

        return $output;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_AccessPolicy::bootstrap();
}