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
 * Access Policy UI manager
 *
 * @package AAM
 * @version 6.0.0
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
     * @access public
     * @version 6.0.0
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
            global $post;

            if (is_a($post, 'WP_Post')
                    && ($post->post_type === AAM_Service_AccessPolicy::POLICY_CPT)) {
                $content = AAM_Backend_View::getInstance()->loadPartial(
                    'default-principal-subject-tab',
                    $params
                );
            }

            return $content;
        }, 10, 2);
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
     * @access public
     * @version 6.0.0
     */
    public function getTable()
    {
        $list = get_posts(array(
            'post_type'   => AAM_Service_AccessPolicy::POLICY_CPT,
            'numberposts' => -1,
            'post_status' => 'publish'
        ));

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
                    get_edit_post_link($record->ID, 'link')
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
     * @access protected
     * @version 6.0.0
     */
    protected function preparePolicyTitle($record)
    {
        if (!empty($record->post_title)) {
            $title = $record->post_title;
        } else {
            $title = __('(no title)', AAM_KEY);
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
     * @access protected
     * @version 6.0.0
     */
    protected function preparePolicyActionList($record)
    {
        $subject = AAM_Backend_Subject::getInstance();

        $policy  = $subject->getObject(AAM_Core_Object_Policy::OBJECT_TYPE);
        $post    = $subject->getObject(AAM_Core_Object_Post::OBJECT_TYPE, $record->ID);

        $actions = array(
            $policy->has($record->ID) ? "detach" : "attach",
            $post->isAllowedTo('edit') ? 'edit' : 'no-edit'
        );

        return implode(',', $actions);
    }

    /**
     * Save access policy effect
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $subject = AAM_Backend_Subject::getInstance();

        $id      = $this->getFromPost('id');
        $effect  = $this->getFromPost('effect', FILTER_VALIDATE_BOOLEAN);

        // Verify that current user can perform following action
        if (current_user_can('read_post', $id)) {
            $object = $subject->getObject(self::OBJECT_TYPE, null, true);
            $result = $object->updateOptionItem($id, $effect)->save();
        } else {
            $result = false;
        }

        return wp_json_encode(array(
            'status'  => ($result ? 'success' : 'failure')
        ));
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