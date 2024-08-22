<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service URL manager
 *
 * @since 6.9.37 https://github.com/aamplugin/advanced-access-manager/issues/411
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.20 https://github.com/aamplugin/advanced-access-manager/issues/337
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/322
 *               https://github.com/aamplugin/advanced-access-manager/issues/320
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/296
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/283
 * @since 6.9.9  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.37
 */
class AAM_Framework_Service_Urls
implements
    AAM_Framework_Service_Interface
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * List of allowed rule types
     *
     * @version 7.0.0
     */
    const ALLOWED_RULE_TYPES = [
        'allow',
        'deny',
        'custom_message',
        'page_redirect',
        'url_redirect',
        'trigger_callback',
        'login_redirect'
    ];

    /**
     * Return list of rules for give subject
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/320
     * @since 6.9.9  Initial implementation of the method
     *
     * @access public
     * @version 6.9.9
     */
    public function get_rule_list($inline_context = null)
    {
        try {
            $result   = [];
            $resource = $this->get_resource(null, true, $inline_context);
            $settings = $resource->get_settings();

            if (is_array($settings) && count($settings)) {
                foreach($settings as $rule) {
                    array_push(
                        $result,
                        $this->_prepare_rule(
                            $rule,
                            $this->_get_access_level($inline_context),
                            $this->_is_inherited($rule, $resource)
                        )
                    );
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get existing rule by ID
     *
     * @param int   $id             Sudo-id for the rule
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.9
     * @throws OutOfRangeException If rule does not exist
     */
    public function get_rule_by_id($id, $inline_context = null)
    {
        try {
            $resource = $this->get_resource(null, true, $inline_context);
            $match    = false;

            foreach($resource->get_settings() as $url => $rule) {
                if (abs(crc32($url)) === $id) {
                    $match = $rule;
                    break;
                }
            }

            if ($match === false) {
                throw new OutOfRangeException('Rule does not exist');
            }

            $result = $this->_prepare_rule(
                $match,
                $this->_get_access_level($inline_context),
                $this->_is_inherited($match, $resource)
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Create new URL rule
     *
     * @param array $incoming_data  Rule settings
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/320
     * @since 6.9.9  Initial implementation of the method
     *
     * @access public
     * @version 6.9.9
     * @throws RuntimeException If fails to persist the rule
     */
    public function create_rule(array $incoming_data, $inline_context = null)
    {
        try {
            $resource  = $this->get_resource(null, false, $inline_context);
            $rule_data = $this->_convert_to_rule($incoming_data);
            $success   = $resource->set_explicit_setting(
                $rule_data['url'], $rule_data
            );

            if (!$success) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->_prepare_rule(
                $rule_data, $this->_get_access_level($inline_context)
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Update existing rule
     *
     * @param int   $id             Sudo-id for the rule
     * @param array $incoming_data  Rule data
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/320
     * @since 6.9.9  Initial implementation of the method
     *
     * @access public
     * @version 6.9.9
     * @throws OutOfRangeException If rule does not exist
     * @throws RuntimeException If fails to persist a rule
     */
    public function update_rule($id, array $incoming_data, $inline_context = null)
    {
        try {
            $resource     = $this->get_resource(null, false, $inline_context);
            $rule_data    = $this->_convert_to_rule($incoming_data);
            $found        = false;
            $new_settings = [];

            // Note! Getting here all rules (even inherited) to ensure that user can
            // override the inherited rule
            foreach($resource->get_settings() as $url => $settings) {
                if (abs(crc32($url)) === $id) {
                    $found                     = true;
                    $new_settings[$rule_data['url']] = $rule_data;
                } else {
                    $new_settings[$url] = $settings;
                }
            }

            if ($found) {
                $success = $resource->set_explicit_settings($new_settings);
            } else {
                throw new OutOfRangeException('Rule does not exist');
            }

            if (!$success) {
                throw new RuntimeException('Failed to update the rule');
            }

            $result = $this->_prepare_rule(
                $rule_data, $this->_get_access_level($inline_context)
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete rule
     *
     * @param int   $id             Sudo-id for the rule
     * @param array $inline_context Runtime context
     *
     * @return boolean
     *
     * @since 6.9.20 https://github.com/aamplugin/advanced-access-manager/issues/337
     * @since 6.9.9  Initial implementation of the method
     *
     * @access public
     * @version 6.9.20
     */
    public function delete_rule($id, $inline_context = null)
    {
        try {
            $resource     = $this->get_resource(null, false, $inline_context);
            $found        = null;
            $new_settings = [];

            // Note! User can delete only explicitly set rule (overwritten rule)
            foreach($resource->get_explicit_settings() as $url => $rule) {
                if (abs(crc32($url)) === $id) {
                    $found = $rule;
                } else {
                    $new_settings[$url] = $rule;
                }
            }

            if ($found !== null) {
                $result = $resource->set_explicit_settings($new_settings);
            } else {
                throw new OutOfRangeException('Rule does not exist');
            }

            if (!$result) {
                throw new RuntimeException('Failed to persist the rule');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset all rules
     *
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
     * @since 6.9.9 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function reset($inline_context = null)
    {
        try {
            $resource = $this->get_resource(null, false, $inline_context);

            // Resetting the settings to default
            $resource->reset();

            $result = $this->get_rule_list($inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Determine if given URL is restricted
     *
     * @param string $url
     * @param array  $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($url, $inline_context = null)
    {
        try {
            $resource = $this->get_resource($url, false, $inline_context);
            $result   = $resource->is_restricted();
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get redirect for given URL
     *
     * @param string $url
     * @param array  $inline_context
     *
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_redirect($url, $inline_context = null)
    {
        try {
            $resource = $this->get_resource($url, false, $inline_context);
            $result   = $resource->get_redirect($url);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get URL resource
     *
     * @param string  $resource_id
     * @param boolean $reload
     * @param array   $inline_context
     *
     * @return AAM_Framework_Resource_Url
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource(
        $resource_id = null, $reload = false, $inline_context = null
    ) {
        return $this->_get_access_level($inline_context)->get_resource(
            AAM_Framework_Resource_Url::TYPE, $resource_id, $reload
        );
    }

    /**
     * Determine if current rule is inherited or not
     *
     * @param array                      $rule
     * @param AAM_Framework_Resource_Url $resource
     *
     * @return boolean
     *
     * @access private
     * @version 7.0.0
     */
    private function _is_inherited($rule, $resource)
    {
        return array_key_exists($rule['url'], $resource->get_explicit_settings());
    }

    /**
     * Normalize and prepare the rule model
     *
     * @param array                               $rule
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param bool                                $is_inherited
     *
     * @return array
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/320
     * @since 6.9.9  Initial implementation of the method
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_rule(
        $rule, $access_level, $is_inherited = false
    ) {
        $result = [
            'id'           => abs(crc32($rule['url'])),
            'url'          => $rule['url'],
            'is_inherited' => $is_inherited
        ];

        if ($rule['effect'] === 'allow') {
            $result['type'] = 'allow';
        } elseif (!isset($rule['redirect'])) {
            $result['type'] = 'deny'; //Default Access Denied Redirect behavior
        } else {
            $result = array_merge($result, $rule['redirect']);
        }

        return apply_filters(
            'aam_url_access_rule_filter',
            $result,
            $rule,
            $access_level
        );
    }

    /**
     * Convert raw URL rule data to rule
     *
     * The result of this method execution is an array of settings that is stored in
     * DB.
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _convert_to_rule($data)
    {
        // First, let's validate tha the rule type is correct
        if (!in_array($data['type'], self::ALLOWED_RULE_TYPES, true)) {
            throw new InvalidArgumentException('The valid `type` is required');
        }

        // Now, validating that the URL is acceptable
        // Parse and validate the incoming URL
        if ($data['url'] === '*') {
            $url = '*';
        } else {
            $parsed = wp_parse_url($data['url']);
            $url    = wp_validate_redirect(
                empty($parsed['path']) ? '/' : $parsed['path']
            );
        }

        // Adding query params if provided
        if (isset($parsed['query'])) {
            $url .= '?' . $parsed['query'];
        }

        if (empty($url)) {
            throw new InvalidArgumentException('The valid URL is required');
        }

        $result = [
            'effect' => $data['type'] === 'allow' ? 'allow' : 'deny',
            'url'    => $url
        ];

        // Redirect data will be stored in the "redirect" property
        if (in_array($data['type'], ['deny', 'default'], true)) {
            $result['redirect'] = [
                'type' => 'default'
            ];
        } else {
            $result['redirect'] = [
                'type' => $data['type']
            ];
        }

        if ($data['type'] === 'custom_message') {
            $message = wp_kses_post($data['message']);

            if (empty($message)) {
                throw new InvalidArgumentException('Provide non-empty message');
            } else {
                $result['redirect']['message'] = $message;
            }
        } elseif ($data['type'] === 'page_redirect') {
            $page_id = intval($data['redirect_page_id']);

            if ($page_id === 0) {
                throw new InvalidArgumentException(
                    'The valid redirect page ID is required'
                );
            } else {
                $result['redirect']['redirect_page_id'] = $page_id;
            }
        } elseif ($data['type'] === 'url_redirect') {
            $redirect_url = wp_validate_redirect($data['redirect_url']);

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid redirect URL is required'
                );
            } else {
                $result['redirect']['redirect_url'] = $redirect_url;
            }
        } elseif ($data['type'] === 'trigger_callback') {
            if (!is_callable($data['callback'], true)) {
                throw new InvalidArgumentException(
                    'The valid PHP callback function is required'
                );
            } else {
                $result['redirect']['callback'] = $data['callback'];
            }
        }

        if (!empty($data['http_status_code'])) {
            $code = intval($data['http_status_code']);

            if ($code >= 300) {
                $result['redirect']['http_status_code'] = $code;
            }
        }

        // TODO: Implement this hook in the premium add-on
        return apply_filters('aam_convert_url_access_data_filter', $result, $data);
    }

}