<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core policy generator
 *
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/285
 * @since 6.4.0  Enhanced with redirects params generation
 *               https://github.com/aamplugin/advanced-access-manager/issues/76
 * @since 6.3.0  Refactored post statement generation to cover the bug
 *               https://github.com/aamplugin/advanced-access-manager/issues/22
 * @since 6.2.2  Fixed bug with incompatibility with PHP lower than 7.0.0
 * @since 6.2.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.12
 */
class AAM_Core_Policy_Generator
{
    /**
     * Current subject
     *
     * Who should we use to get access settings from?
     *
     * @var AAM_Core_Subject
     *
     * @access protected
     * @version 6.2.0
     */
    protected $subject;

    /**
     * Access settings
     *
     * @var array
     *
     * @access protected
     * @version 6.2.0
     */
    protected $settings;

    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     *
     * @since 6.4.0 Removed `aam_post_policy_generator_filter` and moved it to the
     *              content service
     * @since 6.2.0 Initial implementation of the method
     *
     * @access public
     * @version 6.4.0
     */
    public function __construct(AAM_Core_Subject $subject)
    {
        $this->subject = $subject;

        // Read all direct access settings for provided subject
        $this->settings = AAM_Framework_Manager::settings([
            'subject' => $subject
        ])->get_settings();
    }

    /**
     * Generate Access Policy and return it as JSON string
     *
     * @return string
     *
     * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/287
     * @since 6.4.0  Enhanced with redirect rules generators
     *               https://github.com/aamplugin/advanced-access-manager/issues/76
     * @since 6.2.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.12
     */
    public function generate()
    {
        $policy = array(
            'Statement' => array(),
            'Param'     => array()
        );

        foreach($this->settings as $type => $data) {
            $policy = apply_filters(
                'aam_generated_policy_filter', $policy, $type, $data, $this
            );
        }

        // If subject is User or Role, then also include explicitly defined
        // capabilities
        $subject = $this->subject;

        if (in_array($subject::UID, array('user', 'role'))) {
            $allowed = $denied = array();

            foreach($subject->getCapabilities() as $cap => $effect) {
                if (!empty($effect)) {
                    $allowed[] = 'Capability:' . $cap;
                } else {
                    $denied[] = 'Capability:' . $cap;
                }
            }

            if (!empty($allowed)) {
                $policy['Statement'][] = array(
                    'Effect'   => 'allow',
                    'Resource' => $allowed
                );
            }

            if (!empty($denied)) {
                $policy['Statement'][] = array(
                    'Effect'   => 'deny',
                    'Enforce'  => true,
                    'Resource' => $denied
                );
            }
        }

        $base = json_decode(
            AAM_Backend_Feature_Main_Policy::getDefaultPolicy(), true
        );

        return wp_json_encode(array_merge($base, $policy));
    }

    /**
     * Generate redirect param of a given type
     *
     * @param array  $options
     * @param string $redirect_type
     *
     * @return array
     *
     * @access public
     * @version 6.9.12
     */
    public function generateRedirectParam($options, $redirect_type)
    {
        $param = array(
            'Key'   => "redirect:on:{$redirect_type}",
            'Value' => array()
        );

        // Get the redirect type
        if (isset($options["{$redirect_type}.redirect.type"])) {
            $type = $options["{$redirect_type}.redirect.type"];
        } else {
            $type = 'default';
        }

        if (!empty(AAM_Framework_Service_RedirectAbstract::REDIRECT_TYPE_ALIAS[$type])) {
            $param['Value']['Type'] = AAM_Framework_Service_RedirectAbstract::REDIRECT_TYPE_ALIAS[$type];
        } else {
            $param['Value']['Type'] = 'default';
        }

        // Add additional attributes
        if ($param['Value']['Type'] === 'page_redirect') {
            $page = get_post($options["{$redirect_type}.redirect.page"]);

            if (is_a($page, 'WP_Post')) {
                $param['Value']['PageSlug'] = $page->post_name;
            } else {
                $param['Value']['PageId'] = intval(
                    $options["{$redirect_type}.redirect.page"]
                );
            }
        } elseif ($param['Value']['Type'] === 'url_redirect') {
            $param['Value']['Url'] = wp_validate_redirect(
                $options["{$redirect_type}.redirect.url"], '/'
            );
        } elseif ($param['Value']['Type'] === 'trigger_callback') {
            if (is_callable($options["{$redirect_type}.redirect.callback"], true)) {
                $param['Value']['Callback'] = $options["{$redirect_type}.redirect.callback"];
            } else {
                $param['Value']['Callback'] = '';
            }
        }

        return array($param);
    }

    /**
     * Generate basic access policy statement
     *
     * @param array  $options
     * @param string $resource
     *
     * @return array
     *
     * @since 6.4.0 Made the method public
     * @since 6.2.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.0
     */
    public function generateBasicStatements($options, $resource)
    {
        $denied = $allowed =  $statements = array();

        foreach($options as $id => $effect) {
            if ($effect === true) {
                $denied[] = "{$resource}:{$id}";
            } else {
                $allowed[] = "{$resource}:{$id}";
            }
        }

        if (!empty($denied)) {
            $statements[] = array(
                'Effect'   => 'deny',
                'Resource' => $denied
            );
        }

        if (!empty($allowed)) {
            $statements[] = array(
                'Effect'   => 'allow',
                'Resource' => $allowed
            );
        }

        return $statements;
    }

}