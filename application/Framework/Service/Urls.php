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
     * Rule type aliases
     *
     * To be a bit more verbose, we are renaming the legacy rule types to something
     * that is more intuitive
     *
     * @version 6.9.9
     */
    const RULE_TYPE_ALIAS = array(
        'allow'    => 'allow',
        'default'  => 'deny',
        'message'  => 'custom_message',
        'page'     => 'page_redirect',
        'url'      => 'url_redirect',
        'callback' => 'trigger_callback',
        'login'    => 'login_redirect'
    );

    /**
     * Array of allowed HTTP status codes
     *
     * @version 6.9.26
     */
    const HTTP_STATUS_CODES = array(
        'allow'            => null,
        'default'          => array('4xx', '5xx'),
        'custom_message'   => array('4xx', '5xx'),
        'page_redirect'    => array('3xx'),
        'url_redirect'     => array('3xx'),
        'login_redirect'   => null,
        'trigger_callback' => array('3xx', '4xx', '5xx')
    );

    /**
     * Array of default HTTP status codes
     *
     * @version 6.9.26
     */
    const HTTP_DEFAULT_STATUS_CODES = array(
        'allow'            => null,
        'default'          => 401,
        'custom_message'   => 401,
        'page_redirect'    => 307,
        'url_redirect'     => 307,
        'login_redirect'   => null,
        'trigger_callback' => 401
    );

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
        $response = array();
        $subject  = $this->_get_subject($inline_context);
        $object   = $subject->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);

        $options  = $object->getOption();
        $explicit = $object->getExplicitOption();

        if (is_array($options) && count($options)) {
            foreach($options as $url => $settings) {
                array_push(
                    $response,
                    $this->_prepare_rule(
                        $url,
                        $settings,
                        $subject,
                        !array_key_exists($url, $explicit)
                    )
                );
            }
        }

        return $response;
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
     * @throws UnderflowException If rule does not exist
     */
    public function get_rule_by_id($id, $inline_context = null)
    {
        // Validating that incoming data is correct and normalize is for storage
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);

        // Find the rule that we are updating
        $rule = false;

        foreach($object->getOption() as $url => $settings) {
            if (abs(crc32($url)) === $id) {
                $rule = array(
                    'url'  => $url,
                    'rule' => $settings
                );
            }
        }

        if ($rule === false) {
            throw new UnderflowException('Rule does not exist');
        }

        return $this->_prepare_rule($rule['url'], $rule['rule'], $subject);
    }

    /**
     * Create new URL rule
     *
     * @param array $rule           Rule settings
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/320
     * @since 6.9.9  Initial implementation of the method
     *
     * @access public
     * @version 6.9.9
     * @throws Exception If fails to persist the rule
     */
    public function create_rule(array $rule, $inline_context = null)
    {
        // Validating that incoming data is correct and normalize is for storage
        $result  = $this->_validate_rule($rule);
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);
        $success = $object->store($result['url'], $result['rule']);

        if (!$success) {
            throw new Exception('Failed to persist the rule');
        } else {
            do_action('aam_url_access_rule_created_action', $object, $rule);
        }

        return $this->_prepare_rule($result['url'], $result['rule'], $subject);
    }

    /**
     * Update existing rule
     *
     * @param int   $id             Sudo-id for the rule
     * @param array $rule           Rule data
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/320
     * @since 6.9.9  Initial implementation of the method
     *
     * @access public
     * @version 6.9.9
     * @throws UnderflowException If rule does not exist
     * @throws Exception If fails to persist a rule
     */
    public function update_rule($id, array $rule, $inline_context = null)
    {
        // Validating that incoming data is correct and normalize is for storage
        $result  = $this->_validate_rule($rule);
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);

        // Find the rule that we are updating
        $found = false;

        // Note! Getting here all rules (even inherited) to ensure that user can
        // override the inherited rule
        $original_options = $object->getOption();
        $new_options      = array();

        foreach($original_options as $url => $settings) {
            if (abs(crc32($url)) === $id) {
                $found                       = true;
                $new_options[$result['url']] = $result['rule'];
            } else {
                $new_options[$url] = $settings;
            }
        }

        if ($found) {
            $object->setExplicitOption($new_options);
            $success = $object->save();
        } else {
            throw new UnderflowException('Rule does not exist');
        }

        if (!$success) {
            throw new Exception('Failed to update the rule');
        } else {
            do_action('aam_url_access_rule_updated_action', $id, $object, $rule);
        }

        return $this->_prepare_rule($result['url'], $result['rule'], $subject);
    }

    /**
     * Delete rule
     *
     * @param int   $id             Sudo-id for the rule
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @since 6.9.20 https://github.com/aamplugin/advanced-access-manager/issues/337
     * @since 6.9.9  Initial implementation of the method
     *
     * @access public
     * @version 6.9.20
     * @throws UnderflowException If rule does not exist
     * @throws Exception If fails to persist a rule
     */
    public function delete_rule($id, $inline_context = null)
    {
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);

        // Find the rule that we are updating
        $found = null;

        // Note! User can delete only explicitly set rule (overwritten rule)
        $original_options = $object->getExplicitOption();
        $new_options      = array();

        foreach($original_options as $url => $settings) {
            if (abs(crc32($url)) === $id) {
                $found = array(
                    'url'  => $url,
                    'rule' => $settings
                );
            } else {
                $new_options[$url] = $settings;
            }
        }

        if ($found) {
            $object->setExplicitOption($new_options);
            $success = $object->save();
        } else {
            throw new UnderflowException('Rule does not exist');
        }

        if (!$success) {
            throw new Exception('Failed to persist the rule');
        }

        $subject->flushCache();

        return $this->_prepare_rule($found['url'], $found['rule'], $subject);
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
    public function reset_rules($inline_context = null)
    {
        $response = array();

        // Reset the object
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);

        // Communicate about number of rules that were deleted
        $response['deleted_rules_count'] = count($object->getExplicitOption());

        // Reset
        $response['success'] = $object->reset();

        return $response;
    }

    /**
     * Normalize and prepare the rule model
     *
     * @param string           $url
     * @param array            $settings
     * @param AAM_Core_Subject $subject
     * @param bool             $is_inherited
     *
     * @return array
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/320
     * @since 6.9.9  Initial implementation of the method
     *
     * @access private
     * @version 6.9.26
     */
    private function _prepare_rule($url, $settings, $subject, $is_inherited = false)
    {
        // Determine current rule type. If none set, deny by default
        $legacy_type = isset($settings['type']) ? $settings['type'] : 'default';
        $http_code   = isset($settings['code']) ? intval($settings['code']): 307;
        $response    = array(
            'id'           => abs(crc32($url)),
            'is_inherited' => $is_inherited,
            'url'          => $url,
            'type'         => self::RULE_TYPE_ALIAS[$legacy_type]
        );

        if ($response['type'] === 'custom_message') {
            $response['message'] = $settings['action'];
        } elseif ($response['type'] === 'page_redirect') {
            $response['redirect_page_id'] = intval($settings['action']);
            $response['http_status_code'] = $http_code;
        } elseif ($response['type'] === 'url_redirect') {
            $response['redirect_url']     = $settings['action'];
            $response['http_status_code'] = $http_code;
        } elseif ($response['type'] === 'trigger_callback') {
            $response['callback'] = $settings['action'];
        }

        if (!empty($settings['metadata'])) {
            $response['metadata'] = $settings['metadata'];
        }

        return apply_filters('aam_url_access_rule_filter', $response, $subject);
    }

    /**
     * Validate and normalize a rule's incoming data
     *
     * @param array $rule Incoming rule's data
     *
     * @return array
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/322
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/296
     * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/283
     * @since 6.9.9  Initial implementation of the method
     *
     * @access private
     * @version 6.9.26
     */
    private function _validate_rule(array $rule)
    {
        $normalized = array();

        $type = array_search($rule['type'], self::RULE_TYPE_ALIAS);

        // Parse and validate the incoming URL
        if ($rule['url'] === '*') {
            $url = '*';
        } else {
            $parsed = wp_parse_url($rule['url']);
            $url    = wp_validate_redirect(
                empty($parsed['path']) ? '/' : $parsed['path']
            );
        }

        // Adding query params if provided
        if (isset($parsed['query'])) {
            $url .= '?' . $parsed['query'];
        }

        if (empty($type)) {
            throw new InvalidArgumentException('The `type` is required');
        } elseif (empty($url)) {
            throw new InvalidArgumentException('The `url` is required');
        } elseif ($type === 'message') {
            $message = wp_kses_post($rule['message']);

            if (empty($message)) {
                throw new InvalidArgumentException('The `message` is required');
            } else {
                $normalized['action'] = $message;
            }
        } elseif ($type === 'page') {
            $page_id = intval($rule['redirect_page_id']);

            if ($page_id === 0) {
                throw new InvalidArgumentException(
                    'The `redirect_page_id` is required'
                );
            } else {
                $normalized['action'] = $page_id;
            }
        } elseif ($type === 'url') {
            $redirect_url = wp_validate_redirect($rule['redirect_url']);

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid `redirect_url` is required'
                );
            } else {
                $normalized['action'] = $redirect_url;
            }
        } elseif ($type === 'callback') {
            if (is_callable($rule['callback'], true)) {
                $normalized['action'] = $rule['callback'];
            } else {
                throw new InvalidArgumentException(
                    'The valid `callback` is required'
                );
            }
        }

        if (!empty($rule['http_status_code'])) {
            $code = intval($rule['http_status_code']);

            if ($code >= 300) {
                $normalized['code'] = $code;
            }
        }

        if (!empty($rule['metadata'])) {
            $normalized['metadata'] = $rule['metadata'];
        }

        return array(
            'url'  => $url,
            'rule' => array_merge($normalized, array('type' => $type))
        );
    }

}