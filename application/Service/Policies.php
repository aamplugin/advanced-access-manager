<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * JSON Access Policy service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Policies
{
    use AAM_Service_BaseTrait;

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function __construct()
    {
        // Register RESTful API
        AAM_Restful_Policies::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Get a boilerplate policy
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function get_boilerplate_policy()
    {
        return json_encode(json_decode('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": [],
                    "Action": []
                }
            ],
            "Param": [
                {
                    "Key": "SomeParam",
                    "Value": "some-value"
                }
            ]
        }'), JSON_PRETTY_PRINT);
    }

    /**
     * Initialize hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_Policy::register();
            });

            // Register custom access control metabox
            add_action('add_meta_boxes', function() {
                $this->_add_meta_boxes();
            });

            // Access policy save
            add_filter('wp_insert_post_data', function($data) {
                return $this->_wp_insert_post_data($data);
            });
        }

        // Override role list permissions
        add_filter('aam_rest_role_output_filter', function($result, $role) {
            $context = AAM::api()->misc->get($_GET, 'context');

            if ($context === 'policy_assignee') {
                $policy_id   = AAM::api()->misc->get($_GET, 'policy_id');
                $is_attached = AAM::api()->policies('role:' . $role->slug)->is_attached(
                    intval($policy_id)
                );

                $result['is_attached'] = $is_attached;

                if (array_key_exists('permissions', $result)) {
                    $result['permissions'] = [ 'toggle_role_policy' ];
                }
            }

            return $result;
        }, 10, 2);

        // Override user list RESTful output
        add_filter('aam_rest_user_output_filter', function($result, $user) {
            $context = AAM::api()->misc->get($_GET, 'context');

            if ($context === 'policy_assignee') {
                $policy_id   = AAM::api()->misc->get($_GET, 'policy_id');
                $is_attached = AAM::api()->policies('user:' . $user->ID)->is_attached(
                    intval($policy_id)
                );

                $result['is_attached'] = $is_attached;

                if (array_key_exists('permissions', $result)) {
                    $result['permissions'] = [ 'toggle_user_policy' ];
                }
            }

            return $result;
        }, 10, 2);
    }

    /**
     * Register UI metaboxes for the Access Policy edit screen
     *
     * @global WP_Post $post
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _add_meta_boxes()
    {
        global $post;

        if (is_a($post, 'WP_Post')
            && ($post->post_type === AAM_Framework_Service_Policies::CPT)
        ) {
            add_meta_box(
                AAM_Framework_Service_Policies::CPT,
                __('Access Policy Document', 'advanced-access-manager'),
                function() {
                    echo AAM_Backend_View::get_instance()->render_policy_metabox();
                },
                AAM_Framework_Service_Policies::CPT,
                'normal',
                'high'
            );

            // Parent policy metabox
            add_meta_box(
                AAM_Framework_Service_Policies::CPT . '_parent',
                __('Parent Policy', 'advanced-access-manager'),
                function() {
                    echo AAM_Backend_View::get_instance()->render_policy_parent_metabox();
                },
                AAM_Framework_Service_Policies::CPT,
                'side',
                'low'
            );

            // Only display the assignee when policy is published
            if ($post->post_status === 'publish') {
                add_meta_box(
                    'aam-policy-assignee',
                    __('Access Policy Assignee', 'advanced-access-manager'),
                    function() {
                        echo AAM_Backend_View::get_instance()->renderPolicyPrincipalMetabox();
                    },
                    AAM_Framework_Service_Policies::CPT,
                    'side'
                );
            }
        }
    }

    /**
     * Hook into policy submission and filter its content
     *
     * @param array $data
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _wp_insert_post_data($data)
    {
        if (isset($data['post_type'])
            && ($data['post_type'] === AAM_Framework_Service_Policies::CPT)
        ) {
            $content = AAM::api()->misc->get($_POST, 'aam-policy');

            if (empty($content)) {
                if (empty($data['post_content'])) {
                    $content = $this->get_boilerplate_policy();
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

}