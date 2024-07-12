<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Trait for redirect service
 *
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.12 Initial implementation of the method
 *
 * @package AAM
 * @version 6.9.35
 */
trait AAM_Framework_Service_RedirectTrait
{

    /**
     * Get the login redirect
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 6.9.12
     */
    public function get_redirect($inline_context = null)
    {
        try {
            $object = $this->_get_object($inline_context);

            $result = $this->_prepare_redirect(
                $object->getOption(),
                !$object->isOverwritten()
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Set the login redirect
     *
     * @param array $redirect       Redirect settings
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.12
     * @throws RuntimeException If fails to persist the data
     */
    public function set_redirect(array $redirect, $inline_context = null)
    {
        try {
            // Validating that incoming data is correct and normalize is for storage
            $data   = $this->_validate_redirect($redirect);
            $object = $this->_get_object($inline_context);

            if (!$object->setExplicitOption($data)->save()) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->_prepare_redirect($object->getExplicitOption(), false);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset the redirect rule
     *
     * @param array $inline_context Runtime context
     *
     * @return boolean
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
     * @since 6.9.12 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function reset($inline_context = null)
    {
        try {
            // Reset settings to default
            $this->_get_object($inline_context)->reset();

            $result = $this->get_redirect($inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Normalize and prepare the redirect details
     *
     * @param array $settings
     * @param bool  $is_inherited
     *
     * @return array
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.9.12 Initial implementation of the method
     *
     * @access private
     * @version 6.9.26
     */
    private function _prepare_redirect($settings, $is_inherited = false)
    {
        // Filter out only settings that relate to current redirect type
        $s = array();

        foreach($settings as $k => $v) {
            if (strpos("{$k}.", static::REDIRECT_TYPE) === 0) {
                $s[str_replace(static::REDIRECT_TYPE . '.', '', $k)] = $v;
            }
        }

        // Determine current rule type. If none set, deny by default
        if (isset($s['redirect.type'])) {
            $legacy_type = $s['redirect.type'];
        } else {
            $legacy_type = 'default';
        }

        $response = array('type' => static::REDIRECT_TYPE_ALIAS[$legacy_type]);

        if ($response['type'] === 'page_redirect') {
            $response['redirect_page_id'] = intval($s['redirect.page']);
        } elseif ($response['type'] === 'url_redirect') {
            $response['redirect_url'] = $s['redirect.url'];
        } elseif ($response['type'] === 'trigger_callback') {
            $response['callback'] = $s['redirect.callback'];
        }

        $response['is_inherited'] = $is_inherited;

        return $response;
    }

    /**
     * Validate and normalize the incoming redirect data
     *
     * @param array $rule Incoming rule's data
     *
     * @return array
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.9.12 Initial implementation of the method
     *
     * @access private
     * @version 6.9.26
     */
    private function _validate_redirect(array $rule)
    {
        $normalized = array();

        $type       = array_search($rule['type'], static::REDIRECT_TYPE_ALIAS);
        $normalized[static::REDIRECT_TYPE . '.redirect.type'] = $type;


        if (empty($type)) {
            throw new InvalidArgumentException('The `type` is required');
        } elseif ($type === 'page') {
            if (isset($rule['redirect_page_id'])) {
                $page_id = intval($rule['redirect_page_id']);
            } else {
                $page_id = 0;
            }

            if ($page_id === 0) {
                throw new InvalidArgumentException(
                    'The `redirect_page_id` is required'
                );
            } else {
                $normalized[static::REDIRECT_TYPE . '.redirect.page'] = $page_id;
            }
        } elseif ($type === 'url') {
            if (isset($rule['redirect_url'])) {
                $redirect_url = wp_validate_redirect($rule['redirect_url']);
            } else {
                $redirect_url = null;
            }

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid `redirect_url` is required'
                );
            } else {
                $normalized[static::REDIRECT_TYPE . '.redirect.url'] = $redirect_url;
            }
        } elseif ($type === 'callback') {
            if (isset($rule['callback']) && is_callable($rule['callback'], true)) {
                $normalized[
                    static::REDIRECT_TYPE . '.redirect.callback'
                ] = $rule['callback'];
            } else {
                throw new InvalidArgumentException(
                    'The valid `callback` is required'
                );
            }
        }

        // If HTTP status code is defined, save it as well
        if (!empty($rule['http_status_code'])) {
            $normalized[
                static::REDIRECT_TYPE . '.redirect.' . $type . '.code'
            ] = $this->_validate_status_code(
                $rule['http_status_code'], $rule['type']
            );
        }

        return $normalized;
    }

    /**
     * Validate status code
     *
     * @param int    $code
     * @param string $redirect_type
     *
     * @return int
     * @throws InvalidArgumentException
     * @access private
     *
     * @version 6.9.26
     */
    private function _validate_status_code($code, $redirect_type)
    {
        $allowed_codes = static::HTTP_STATUS_CODES[$redirect_type];
        $code          = intval($code);

        if (is_null($allowed_codes) && !empty($code)) {
            throw new InvalidArgumentException(
                "Redirect type {$redirect_type} does not accept status codes"
            );
        } elseif (is_array($allowed_codes)) {
            $list = array();

            foreach($allowed_codes as $range) {
                $list = array_merge(
                    $list,
                    range(
                        str_replace('xx', '00', $range),
                        str_replace('xx', '99', $range)
                    )
                );
            }

            if (!in_array($code, $list, true)) {
                $allowed = implode(', ', $allowed_codes);

                throw new InvalidArgumentException(
                    "For redirect type {$redirect_type} allowed codes are {$allowed}"
                );
            }
        }

        return $code;
    }

    /**
     * Get object
     *
     * @param array $inline_context
     *
     * @return AAM_Core_Object
     * @version 6.9.33
     */
    private function _get_object($inline_context)
    {
        return $this->_get_subject($inline_context)->reloadObject(
            static::OBJECT_TYPE
        );
    }

}