<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend Settings area abstract manager
 *
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/311
 * @since 6.9.6  https://github.com/aamplugin/advanced-access-manager/issues/249
 * @since 6.7.2  https://github.com/aamplugin/advanced-access-manager/issues/164
 * @since 6.7.0  https://github.com/aamplugin/advanced-access-manager/issues/150
 * @since 6.6.0  https://github.com/aamplugin/advanced-access-manager/issues/130
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/109
 *               https://github.com/aamplugin/advanced-access-manager/issues/106
 * @since 6.2.0  Added Import/Export functionality
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.34
 */
class AAM_Backend_Feature_Settings_Manager extends AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the settings tab
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * Export AAM settings as JSON
     *
     * @param boolean $raw
     *
     * @return string
     *
     * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
     * @since 6.7.0  Added `$raw` argument
     * @since 6.6.0  https://github.com/aamplugin/advanced-access-manager/issues/130
     * @since 6.3.0  Optimized AAM_Core_API::getOption call
     * @since 6.2.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.34
     */
    public function exportSettings($raw = false)
    {
        $data = array(
            'version'   => AAM_VERSION,
            'plugin'    => AAM_KEY,
            'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->format('U'),
            'dataset'   => array()
        );

        $groups = AAM::api()->configs()->get_config('core.export.groups');

        if (is_string($groups)) {
            $groups = array_map('trim', explode(',', $groups));
        }

        $dataset = &$data['dataset'];

        foreach($groups as $group) {
            switch($group) {
                case 'settings':
                    $this->_prepareSettings(
                        AAM_Framework_Manager::settings()->get_settings(),
                        $dataset
                    );
                    break;

                case 'config':
                    $configs                = AAM_Framework_Manager::configs();
                    $dataset['config']      = $configs->get_configs();
                    $dataset['configpress'] = $configs->get_configpress();
                    break;

                case 'roles':
                    $dataset['roles'] = AAM_Core_API::getOption(
                        wp_roles()->role_key
                    );
                    break;

                default:
                    break;
            }
        }

        return ($raw ? $data : wp_json_encode(array(
            'result' => base64_encode(wp_json_encode($data))
        )));
    }

    /**
     * Prepare exported access settings
     *
     * Change the way access policies are exported
     *
     * @param array $settings
     * @param array &$dataset
     *
     * @return void
     *
     * @access private
     * @version 6.0.0
     */
    private function _prepareSettings($settings, &$dataset)
    {
        $policies = array();

        // Extract all defined policies from roles
        if (isset($settings['role'])) {
            foreach($settings['role'] as $role => &$data) {
                if (isset($data['policy'])) {
                    $policies = $this->_preparePolicyList(
                        'role', $role, $data['policy'], $policies
                    );
                    unset($data['policy']);
                }
            }
        }

        // Extract all defined policies from users
        if (isset($settings['user'])) {
            foreach($settings['user'] as $user => &$data) {
                if (isset($data['policy'])) {
                    $policies = $this->_preparePolicyList(
                        'user', $user, $data['policy'], $policies
                    );
                    unset($data['policy']);
                }
            }
        }

        // Extract all defined policies from visitors
        if (isset($settings['visitor']['policy'])) {
            $policies = $this->_preparePolicyList(
                'visitor', null, $settings['visitor']['policy'], $policies
            );
            unset($settings['visitor']['policy']);
        }

        // Extract all defined policies from default
        if (isset($settings['default']['policy'])) {
            $policies = $this->_preparePolicyList(
                'default', null, $settings['default']['policy'], $policies
            );
            unset($settings['default']['policy']);
        }

        $dataset['settings'] = $settings;
        $dataset['policies'] = $policies;
    }

    /**
     * Prepare collection of policies
     *
     * @param string $type
     * @param mixed  $id
     * @param array  $settings
     * @param array  $policies
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _preparePolicyList($type, $id, $settings, $policies)
    {
        foreach($settings as $policyId => $effect) {
            if (!isset($policies[$policyId])) {
                $p = get_post($policyId);

                if (is_a($p, 'WP_Post')) { // Only existing policies
                    $policies[$policyId] = array(
                        'policy'      => wp_json_encode(json_decode($p->post_content)),
                        'title'       => $p->post_title,
                        'description' => $p->post_excerpt,
                        'assignee'    => array()
                    );
                }
            }

            $assignee = (!empty($id) ? "{$type}:{$id}" : $type);
            $policies[$policyId]['assignee'][$assignee] = $effect;
        }

        return $policies;
    }

    /**
     * Import AAM settings
     *
     * @return string
     *
     * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
     * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
     * @since 6.7.0  Added `$payload` argument
     * @since 6.6.0  https://github.com/aamplugin/advanced-access-manager/issues/130
     * @since 6.2.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.34
     */
    public function importSettings($payload = null)
    {
        $error = __('Invalid data', AAM_KEY);

        if (is_null($payload)) {
            $payload = json_decode($this->getFromPost('payload'), true);
        }

        if ($payload) {
            if (isset($payload['dataset']) && is_array($payload['dataset'])) {
                foreach($payload['dataset'] as $group => $settings) {
                    switch($group) {
                        case 'settings':
                            AAM_Framework_Manager::settings()->set_settings(
                                $settings
                            );
                            break;

                        case 'config':
                            AAM_Framework_Manager::configs()->set_configs($settings);
                            break;

                        case 'configpress':
                            AAM_Framework_Manager::configs()->set_configpress(
                                $settings
                            );
                            break;

                        case 'roles':
                            AAM_Core_API::updateOption(
                                wp_roles()->role_key,
                                $this->_sanitizeRoles($settings)
                            );
                            break;

                        case 'policies':
                            $this->_importPolicies($settings);
                        break;

                        default:
                            break;
                    }
                }
                $error = null;
            }
        }

        if ($error !== null) {
            $response = array('status' => 'failure', 'reason' => $error);
        } else {
            $response = array('status' => 'success');
        }

        return wp_json_encode($response);
    }

    /**
     * Import policies
     *
     * @param array $policies
     *
     * @return void
     *
     * @access private
     * @version 6.6.0
     */
    private function _importPolicies($policies)
    {
        foreach($policies as $p) {
            $pid = $this->_isExistingPolicy($p);

            if ($pid === false) {
                $pid = wp_insert_post(array(
                    'post_title'   => $p['title'],
                    'post_content' => $p['policy'],
                    'post_type'    => AAM_Service_AccessPolicy::POLICY_CPT,
                    'post_status'  => 'publish',
                    'post_excerpt' => $p['description']
                ));
            }

            if (!is_wp_error($pid)) {
                foreach($p['assignee'] as $s => $effect) {
                    $this->_applyPolicyToSubject($s, $pid, $effect);
                }
            }
        }
    }

    /**
     * Sanitize list of roles
     *
     * @param array $roleset
     *
     * @return array
     *
     * @access private
     * @version 6.9.21
     */
    private function _sanitizeRoles($roleset)
    {
        $response = array();

        foreach($roleset as $id => $role) {
            $key = sanitize_key($id);

            $response[$key] = array(
                'name'         => esc_js($role['name']),
                'capabilities' => $this->_sanitizeCapabilities($role['capabilities'])
            );
        }

        return $response;
    }

    /**
     * Sanitize list of capabilities
     *
     * @param array $caps
     *
     * @return array
     *
     * @access private
     * @version 6.9.21
     */
    private function _sanitizeCapabilities($caps)
    {
        $response = array();

        foreach($caps as $cap => $effect) {
            $key = sanitize_key($cap);
            $val = false;

            if (is_bool($effect)) {
                $val = $effect;
            } elseif (is_numeric($effect)) {
                $val = intval($effect) !== 0;
            }

            $response[$key] = $val;
        }

        return $response;
    }

    /**
     * Check if the same policy already exists
     *
     * @param array $policy
     *
     * @return boolean|int
     *
     * @access private
     * @version 6.6.0
     */
    private function _isExistingPolicy($policy)
    {
        $existing = false;

        $found = get_page_by_title(
            $policy['title'], OBJECT, AAM_Service_AccessPolicy::POLICY_CPT
        );

        if (!is_null($found)) {
            foreach((is_array($found) ? $found : array($found)) as $p) {
                $title = $p->post_title;
                $json  = wp_json_encode(json_decode($p->post_content));

                if ($title === $policy['title'] && $json === $policy['policy']) {
                    $existing = $p->ID;
                }
            }
        }

        return $existing;
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
     */
    private function _applyPolicyToSubject($s, $policyId, $effect = true)
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
     * Register settings UI manager
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'core',
            'view'       => __CLASS__
        ));
    }

}