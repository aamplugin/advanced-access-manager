<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Policy UI manager
 *
 * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/109
 * @since 6.3.0 Enhanced service to be able to generate policy into new policy
 *              post type record
 * @since 6.2.2 Integration with multisite network where user is allowed to manage
 *              policies only on the main site if Multiste Sync Settings is enabled
 * @since 6.2.0 Added ability to generate Access Policy
 * @since 6.1.0 Fixed bug with "Attach to Default" button
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.5.0
 */
class AAM_Backend_Feature_Main_Policy
extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the feature
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_policy';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Policy::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/policy.php';

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/109
     * @since 6.3.0 Enhanced per https://github.com/aamplugin/advanced-access-manager/issues/27
     * @since 6.2.0 Registering a new action to allow the Access Policy generation
     * @since 6.1.0 Fixed the bug where "Attach to Default" button was not showing at
     *              all
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.5.0
     */
    public function __construct()
    {
        add_filter('aam_iframe_content_filter', array($this, 'renderPrincipalIframe'), 1, 3);
        add_filter('aam_role_row_actions_filter', array($this, 'renderRoleActions'), 1, 2);
        add_filter('aam_user_row_actions_filter', array($this, 'renderUserActions'), 1, 2);

        add_filter('aam_visitor_subject_tab_filter', function ($content, $params) {
            if ($this->getFromQuery('aamframe') === 'principal') {
                $content = AAM_Backend_View::getInstance()->loadPartial(
                    'visitor-principal-subject-tab',
                    $params
                );
            }

            return $content;
        }, 10, 2);

        add_filter('aam_default_subject_tab_filter', function ($content, $params) {
            if ($this->getFromQuery('aamframe') === 'principal') {
                $content = AAM_Backend_View::getInstance()->loadPartial(
                    'default-principal-subject-tab',
                    $params
                );
            }

            return $content;
        }, 10, 2);

        if (current_user_can(AAM_Backend_Feature_Main_Policy::ACCESS_CAPABILITY)) {
            add_action('aam_top_subject_panel_action', function() {
                echo AAM_Backend_View::loadPartial('access-policy-action');
            });
        }
    }

    /**
     * Render access policy principal metabox
     *
     * @param null|string      $content
     * @param string           $type
     * @param AAM_Backend_View $view
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function renderPrincipalIframe($content, $type, $view)
    {
        if ($type === 'principal') {
            $content = $view->loadTemplate(
                dirname(__DIR__) . '/../tmpl/metabox/principal-iframe.php',
                (object) array(
                    'policyId' => $this->getFromQuery('id', FILTER_VALIDATE_INT)
                )
            );
        }

        return $content;
    }

    /**
     * Render role actions
     *
     * @param array  $actions
     * @param string $id
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function renderRoleActions($actions, $id)
    {
        if ($this->getFromPost('ui') === 'principal') {
            $object = AAM::api()->getRole($id)->getObject(
                AAM_Core_Object_Policy::OBJECT_TYPE
            );
            $policyId = $this->getFromPost('policyId', FILTER_VALIDATE_INT);
            $actions = array($object->has($policyId) ? 'detach' : 'attach');
        }

        return $actions;
    }

    /**
     * Render user actions
     *
     * @param array                 $actions
     * @param AAM_Core_Subject_User $user
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function renderUserActions($actions, $user)
    {
        if ($this->getFromPost('ui') === 'principal') {
            $object =  $user->getObject(AAM_Core_Object_Policy::OBJECT_TYPE);
            $policyId = $this->getFromPost('policyId', FILTER_VALIDATE_INT);
            $actions = array($object->has($policyId) ? 'detach' : 'attach');
        }

        return $actions;
    }

    /**
     * Get list of access policies
     *
     * @return string
     *
     * @since 6.2.0 Changed the way, all the policies are fetched
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.0
     */
    public function getTable()
    {
        $list = AAM::api()->getAccessPolicyManager()->fetchPolicies();

        $response = array(
            'recordsTotal'    => count($list),
            'recordsFiltered' => count($list),
            'draw'            => $this->getFromRequest('draw'),
            'data'            => array(),
        );

        foreach ($list as $record) {
            $policy = json_decode($record->post_content);

            if ($policy) {
                $response['data'][] = array(
                    $record->ID,
                    $this->preparePolicyTitle($record),
                    $this->preparePolicyActionList($record),
                    get_edit_post_link($record->ID, 'link'),
                    (!empty($record->post_title) ? $record->post_title : $record->ID)
                );
            }
        }

        return wp_json_encode($response);
    }

    /**
     * Prepare policy title
     *
     * @param WP_Post $record
     *
     * @return string
     *
     * @since 6.2.0 Added new "icon-attention" if policy has error/warning
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.2.0
     */
    protected function preparePolicyTitle($record)
    {
        $errors = (new AAM_Core_Policy_Validator($record->post_content))->validate();

        if (!empty($errors)) {
            $title = '<i class="icon-attention text-danger"></i>&nbsp;';
        } else {
            $title = '';
        }

        if (!empty($record->post_title)) {
            $title .= $record->post_title;
        } else {
            $title .= __('(no title)', AAM_KEY);
        }

        $title .= '<br/>';

        if (isset($record->post_excerpt)) {
            $title .= '<small>' . esc_js($record->post_excerpt) . '</small>';
        }

        return $title;
    }

    /**
     * Prepare the list of policy actions
     *
     * @param WP_Post $record
     *
     * @return string
     *
     * @since 6.3.0 Optimized for Multisite Network setup
     * @since 6.2.2 Changed the way list of actions is determined for a policy
     * @since 6.2.0 Added "delete" action
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.3.0
     */
    protected function preparePolicyActionList($record)
    {
        $subject = AAM_Backend_Subject::getInstance();

        $policy  = $subject->getObject(AAM_Core_Object_Policy::OBJECT_TYPE);
        $post    = $subject->getObject(AAM_Core_Object_Post::OBJECT_TYPE, $record->ID);
        $actions = array(
            $policy->has($record->ID) ? "detach" : "attach",
            is_main_site() && $post->isAllowedTo('edit') ? 'edit' : 'no-edit',
            is_main_site() && $post->isAllowedTo('delete') ? 'delete' : 'no-delete'
        );

        return implode(',', $actions);
    }

    /**
     * Save access policy effect
     *
     * @return string
     *
     * @since 6.2.0 Simplified implementation
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.0
     */
    public function save()
    {
        $subject = AAM_Backend_Subject::getInstance();

        $id      = $this->getFromPost('id');
        $effect  = $this->getFromPost('effect', FILTER_VALIDATE_BOOLEAN);

        $object = $subject->getObject(self::OBJECT_TYPE, null, true);
        $result = $object->updateOptionItem($id, $effect)->save();

        return wp_json_encode(array(
            'status'  => ($result ? 'success' : 'failure')
        ));
    }

    /**
     * Delete policy
     *
     * @return string
     *
     * @access public
     * @version 6.2.0
     */
    public function delete()
    {
        $id     = $this->getFromPost('id');
        $result = wp_delete_post($id);

        return wp_json_encode(array(
            'status' => (is_a($result, 'WP_Post') ? 'success' : 'failure')
        ));
    }

    /**
     * Generate Access Policy from AAM settings
     *
     * @return string
     *
     * @since 6.3.0 Enhanced per https://github.com/aamplugin/advanced-access-manager/issues/27
     * @since 6.2.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.0
     */
    public function generate()
    {
        $subject   = AAM_Backend_Subject::getInstance()->getSubject();
        $generator = new AAM_Core_Policy_Generator($subject);

        // Prepare the policy name
        if ($subject::UID === AAM_Core_Subject_User::UID) {
            $title = sprintf('Policy for %s user', $subject->display_name);
        } elseif ($subject::UID === AAM_Core_Subject_Role::UID) {
            $title = sprintf('Policy for %s role', $subject->getId());
        } elseif ($subject::UID === AAM_Core_Subject_Visitor::UID) {
            $title = 'Policy for all visitors';
        } else {
            $title = 'Policy for everybody';
        }

        $create = $this->getFromPost('createNewPolicy', FILTER_VALIDATE_BOOLEAN);
        $policy = $generator->generate();

        if ($create) {
            $id = wp_insert_post(array(
                'post_type'    => AAM_Service_AccessPolicy::POLICY_CPT,
                'post_content' => $policy,
                'post_title'   => $title,
                'post_status'  => 'publish'
            ));

            $response = array('redirect' => get_edit_post_link($id, 'link'));
        } else {
            $response = array(
                'title'  => $title,
                'policy' => base64_encode($policy)
            );
        }

        return wp_json_encode($response);
    }

    /**
     * Install access policy from the official hub
     *
     * @return string
     *
     * @since 6.2.1 Added support for the policy_meta property
     * @since 6.2.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.1
     */
    public function install()
    {
        $metadata = json_decode($this->getFromPost('metadata'));

        // Do some basic validation & normalization
        $title    = esc_js($metadata->title);
        $excerpt  = esc_js($metadata->description);
        $assignee = $metadata->assignee;
        $override = $metadata->override;
        $consts   = !empty($metadata->policy_meta) ? $metadata->policy_meta: array();
        $policy   = $this->getFromPost('aam-policy');

        $id = wp_insert_post(array(
            'post_type'    => AAM_Service_AccessPolicy::POLICY_CPT,
            'post_content' => $policy,
            'post_title'   => $title,
            'post_excerpt' => $excerpt,
            'post_status'  => 'publish'
        ));

        $errors = array();

        if (!is_wp_error($id)) { // Assign & override
            foreach($assignee as $s) {
                $error = $this->applyToSubject($s, $id, true);
                if ($error) {
                    $errors[] = $error;
                }
            }

            foreach($override as $s) {
                $error = $this->applyToSubject($s, $id, false);
                if ($error) {
                    $errors[] = $error;
                }
            }

            // Insert policy meta values if any
            foreach($consts as $key => $value) {
                add_post_meta($id, $key, $value);
            }
        } else {
            $errors[] = $id->get_error_message();
        }

        if (!empty($errors)) {
            $response = array(
                'status' => 'failure', 'errors' => implode('; ', $errors)
            );
        } else {
            $response = array(
                'status' => 'success', 'redirect' => get_edit_post_link($id, 'link')
            );
        }

        return wp_json_encode($response);
    }

    /**
     * Apply policy to provided subject
     *
     * @param string  $s
     * @param int     $policyId
     * @param boolean $effect
     *
     * @return string|null
     *
     * @access protected
     * @version 6.2.0
     */
    protected function applyToSubject($s, $policyId, $effect = true)
    {
        $error = null;

        if ($s === 'visitor') {
            $subject = AAM::api()->getVisitor();
        } elseif ($s === 'default') {
            $subject = AAM::api()->getDefault();
        } elseif (strpos($s, 'role:') === 0) {
            $subject = AAM::api()->getRole(substr($s, 5));
        } elseif (strpos($s, 'user:') === 0) {
            $uid     = substr($s, 5);
            $subject = AAM::api()->getUser(($uid === 'current') ? null : $uid);
        } else {
            $error   = sprintf(__('Failed applying to %s', AAM_KEY), $s);
            $subject = null;
        }

        if ($subject !== null) {
            $subject->getObject(
                AAM_Core_Object_Policy::OBJECT_TYPE, null, true
            )->updateOptionItem($policyId, $effect)->save();
        }

        return $error;
    }

    /**
     * Get default Access Policy
     *
     * @global string $wp_version
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public static function getDefaultPolicy()
    {
        return include dirname(__DIR__) . '/../tmpl/policy/default-policy.php';
    }

    /**
     * Register Access Policy UI feature
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'policy',
            'position'   => 2,
            'title'      => __('Access Policies', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}