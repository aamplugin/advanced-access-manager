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
    use AAM_Core_Contract_ServiceTrait,
        AAM_Core_Contract_RequestTrait;

    /**
     * AAM configuration setting that is associated with the feature
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.policies.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        $enabled = AAM::api()->config->get(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
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
            $this->initialize_hooks();
        }
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
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        // Register RESTful API
        AAM_Restful_PolicyService::bootstrap();

        // Override role list permissions
        add_filter('aam_rest_role_output_filter', function($result, $role) {
            if (isset($_GET['context']) && $_GET['context'] === 'policy_assignee') {
                $is_attached = AAM::api()->policies('role:' . $role->slug)->is_attached(
                    intval($_GET['policy_id'])
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
            if (isset($_GET['context']) && $_GET['context'] === 'policy_assignee') {
                $is_attached = AAM::api()->policies('user:' . $user->ID)->is_attached(
                    intval($_GET['policy_id'])
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
                __('Access Policy Document', AAM_KEY),
                function() {
                    echo AAM_Backend_View::get_instance()->renderPolicyMetabox();
                },
                AAM_Framework_Service_Policies::CPT,
                'normal',
                'high'
            );

            // Only display the assignee when policy is published
            if ($post->post_status === 'publish') {
                add_meta_box(
                    'aam-policy-assignee',
                    __('Access Policy Assignee', AAM_KEY),
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
            $content = $this->getFromPost('aam-policy');

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