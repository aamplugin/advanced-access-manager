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
        'default',
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
            $result      = [];
            $resource    = $this->get_resource(null, true, $inline_context);
            $permissions = $resource->get_permissions();

            foreach($permissions as $rule) {
                array_push(
                    $result,
                    $this->_prepare_rule(
                        $rule,
                        $this->_get_access_level($inline_context),
                        $this->_is_inherited($rule, $resource)
                    )
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get existing rule by URL
     *
     * @param string $url
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     * @throws OutOfRangeException If rule does not exist
     */
    public function get_rule($url, $inline_context = null)
    {
        try {
            $resource    = $this->get_resource(null, true, $inline_context);
            $permissions = $resource->get_permissions();

            if (!array_key_exists($url, $permissions)) {
                throw new OutOfRangeException('Rule does not exist');
            }

            $result = $this->_prepare_rule(
                $permissions[$url],
                $this->_get_access_level($inline_context),
                $this->_is_inherited($permissions[$url], $resource)
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
     * @access public
     * @version 7.0.0
     * @throws RuntimeException If fails to persist the rule
     */
    public function create_rule(array $incoming_data, $inline_context = null)
    {
        try {
            $resource  = $this->get_resource(null, false, $inline_context);
            $rule_data = $this->_validate_incoming_data($incoming_data);
            $success   = $resource->set_permission($rule_data['url'], $rule_data);

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
     * @param string $url            URL
     * @param array  $incoming_data  Rule data
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     * @throws OutOfRangeException If rule does not exist
     * @throws RuntimeException If fails to persist a rule
     */
    public function update_rule($url, array $incoming_data, $inline_context = null)
    {
        try {
            $resource    = $this->get_resource(null, false, $inline_context);
            $rule_data   = $this->_validate_incoming_data($incoming_data);
            $permissions = $resource->get_permissions();

            // If URL exists in explicitly defined permissions, the update the rule.
            // Otherwise, it means that user is trying to update inherited rule.
            if (array_key_exists($url, $permissions)) {
                $updates = [];
                $updated = false;

                foreach($resource->get_permissions(true) as $rule_url => $rule) {
                    if ($rule_url === $url) { // Preserving the order
                        $updates[$rule_data['url']] = $rule_data;
                        $updated                    = true;
                    } else {
                        $updates[$rule_url] = $rule;
                    }
                }

                if (!$updated) {
                    $updates[$rule_data['url']] = $rule_data;
                }

                if (!$resource->set_permissions($permissions)) {
                    throw new RuntimeException('Failed to update the rule');
                }
            } else {
                throw new OutOfRangeException('Rule does not exist');
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
     * @param string $url            URL
     * @param array  $inline_context Runtime context
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function delete_rule($url, $inline_context = null)
    {
        try {
            $resource    = $this->get_resource(null, false, $inline_context);
            $permissions = $resource->get_permissions(true);

            // Note! User can delete only explicitly set rule (overwritten rule)
            if (array_key_exists($url, $permissions)) {
                unset($permissions[$url]);
            } else {
                throw new OutOfRangeException('Rule does not exist');
            }

            if (!$resource->set_permissions($permissions)) {
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
            $result   = $resource->get_redirect();
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
        return array_key_exists($rule['url'], $resource->get_permissions(true));
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
     * @access private
     * @version 7.0.0
     */
    private function _prepare_rule(
        $rule, $access_level, $is_inherited = false
    ) {
        return apply_filters(
            'aam_url_access_rule_filter',
            array_merge($rule, [ 'is_inherited' => $is_inherited ]),
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
    private function _validate_incoming_data($data)
    {
        // Making sure that effect is set and is valid
        if (empty($data['effect'])
            || !in_array($data['effect'], [ 'allow', 'deny' ], true)
        ) {
            throw new InvalidArgumentException('Invalid effect value');
        }

        // Validating the incoming URL by parsing it first and them verifying that it
        // is a safe redirect URL.
        if (!empty($data['url'])) {
            $normalized_url = $this->_validate_url($data['url']);
        }

        if (empty($normalized_url)) {
            throw new InvalidArgumentException('The valid URL is required');
        }

        $result = [
            'effect' => $data['effect'],
            'url'    => $normalized_url
        ];

        // If redirect data is defined, validate data points
        if (!empty($data['redirect'])) {
            $redirect_type = $data['redirect']['type'];

            if ($redirect_type === 'custom_message') {
                $message = wp_kses_post($data['redirect']['message']);

                if (empty($message)) {
                    throw new InvalidArgumentException('Invalid custom message');
                } else {
                    $result['redirect']['message'] = $message;
                }
            } elseif ($redirect_type === 'page_redirect') {
                $page_id = intval($data['redirect']['redirect_page_id']);

                if ($page_id === 0) {
                    throw new InvalidArgumentException(
                        'The valid redirect page ID is required'
                    );
                } else {
                    $result['redirect']['redirect_page_id'] = $page_id;
                }
            } elseif ($redirect_type === 'url_redirect') {
                $redirect_url = $this->__validate_url(
                    $data['redirect']['redirect_url']
                );

                if (empty($redirect_url)) {
                    throw new InvalidArgumentException(
                        'The valid redirect URL is required'
                    );
                } else {
                    $result['redirect']['redirect_url'] = $redirect_url;
                }
            } elseif ($redirect_type === 'trigger_callback') {
                if (!is_callable($data['redirect']['callback'], true)) {
                    throw new InvalidArgumentException(
                        'The valid PHP callback function is required'
                    );
                } else {
                    $result['redirect']['callback'] = $data['redirect']['callback'];
                }
            }

            if (!empty($data['redirect']['http_status_code'])) {
                $code = intval($data['redirect']['http_status_code']);

                if ($code >= 300) {
                    $result['redirect']['http_status_code'] = $code;
                }
            }
        }

        // TODO: Implement this hook in the premium add-on
        return apply_filters('aam_validate_url_rule_data_filter', $result, $data);
    }

    /**
     * Validate incoming URL
     *
     * @param string $url
     *
     * @return boolean|string
     *
     * @access private
     * @version 7.0.0
     */
    private function __validate_url($url)
    {
        $result     = false;
        $parsed_url = wp_parse_url($url);

        if ($parsed_url !== false) {
            $result = empty($parsed_url['path']) ? '/' : $parsed_url['path'];

            // Adding query params if provided
            if (isset($parsed_url['query'])) {
                $result .= '?' . $parsed_url['query'];
            }

            // Finally sanitize the safe URL
            $result = wp_validate_redirect($result);
        }

        return apply_filters('aam_validate_url_filter', $result, $url);
    }

}