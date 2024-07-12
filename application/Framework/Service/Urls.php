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
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.20 https://github.com/aamplugin/advanced-access-manager/issues/337
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/322
 *               https://github.com/aamplugin/advanced-access-manager/issues/320
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/296
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/283
 * @since 6.9.9  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.26
 */
class AAM_Framework_Service_Urls
{

    use AAM_Framework_Service_BaseTrait;

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
            $result       = [];
            $access_level = $this->_get_access_level($inline_context);
            $resource     = $access_level->url(null, true);
            $settings     = $resource->get_settings();

            if (is_array($settings) && count($settings)) {
                foreach($settings as $rule) {
                    array_push(
                        $result,
                        $this->_prepare_rule(
                            $rule,
                            $access_level,
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
            // Validating that incoming data is correct and normalize is for storage
            $access_level = $this->_get_access_level($inline_context);
            $resource     = $access_level->url(null, true);

            // Find the rule that we are updating
            $match = false;

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
                $access_level,
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
            // Validating that incoming data is correct and normalize is for
            // storage
            $access_level = $this->_get_access_level($inline_context);
            $resource     = $access_level->url();

            // Combine together the rule data
            $rule_data = $resource->convert_to_rule($incoming_data);
            $success   = $resource->set_explicit_setting(
                $rule_data['url'], $rule_data
            );

            if (!$success) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->_prepare_rule($rule_data, $access_level);
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
            // Validating that incoming data is correct and normalize is for storage
            $access_level = $this->_get_access_level($inline_context);
            $resource     = $access_level->url();
            $rule_data    = $resource->convert_to_rule($incoming_data);

            // Find the rule that we are updating
            $found = false;

            // Note! Getting here all rules (even inherited) to ensure that user can
            // override the inherited rule
            $new_settings = [];

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

            $result = $this->_prepare_rule($rule_data, $access_level);
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
     * @throws OutOfRangeException If rule does not exist
     * @throws RuntimeException If fails to persist a rule
     */
    public function delete_rule($id, $inline_context = null)
    {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $resource     = $access_level->url();

            // Find the rule that we are updating
            $found = null;

            // Note! User can delete only explicitly set rule (overwritten rule)
            $new_settings = [];

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
     * @access public
     * @version 6.9.9
     */
    public function reset($inline_context = null)
    {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $resource     = $access_level->get_resource(
                AAM_Framework_Type_Resource::URL
            );

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
            $resource = $this->_get_access_level($inline_context)->url($url);
            $result   = $resource->is_restricted();
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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

}