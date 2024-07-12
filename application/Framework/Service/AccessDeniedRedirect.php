<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service Access Denied Redirect manager
 *
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/359
 * @since 6.9.22 https://github.com/aamplugin/advanced-access-manager/issues/346
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/322
 * @since 6.9.14 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
 */
class AAM_Framework_Service_AccessDeniedRedirect
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Redirect type aliases
     *
     * To be a bit more verbose, we are renaming the legacy rule types to something
     * that is more intuitive
     *
     * @since 6.9.22 https://github.com/aamplugin/advanced-access-manager/issues/346
     * @since 6.9.14 Initial implementation of the constant
     *
     * @version 6.9.22
     */
    const REDIRECT_TYPE_ALIAS = [
        'default'  => 'default',
        'login'    => 'login_redirect',
        'message'  => 'custom_message',
        'page'     => 'page_redirect',
        'url'      => 'url_redirect',
        'callback' => 'trigger_callback'
    ];

    /**
     * Array of allowed HTTP status codes
     *
     * @version 6.9.26
     */
    const HTTP_STATUS_CODES = [
        'default'          => [ '4xx', '5xx' ],
        'custom_message'   => [ '4xx', '5xx' ],
        'page_redirect'    => [ '3xx' ],
        'url_redirect'     => [ '3xx' ],
        'login_redirect'   => null,
        'trigger_callback' => [ '3xx', '4xx', '5xx' ]
    ];

    /**
     * Array of default HTTP status codes
     *
     * @version 6.9.26
     */
    const HTTP_DEFAULT_STATUS_CODES = [
        'default'          => 401,
        'custom_message'   => 401,
        'page_redirect'    => 307,
        'url_redirect'     => 307,
        'login_redirect'   => null,
        'trigger_callback' => 401
    ];

    /**
     * Allowed redirect areas
     *
     * @version 6.9.33
     */
    const ALLOWED_REDIRECT_AREAS = [
        'frontend',
        'backend'
    ];

    /**
     * Get the access denied redirect
     *
     * @param string $area
     * @param array  $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 6.9.14
     */
    public function get_redirect($area = null, $inline_context = null)
    {
        try {
            $object = $this->_get_subject($inline_context)->reloadObject(
                AAM_Core_Object_Redirect::OBJECT_TYPE
            );

            $redirects = $this->_prepare_redirects(
                $object->getOption(),
                !$object->isOverwritten()
            );

            if (!empty($area)) {
                $result = isset($redirects[$area]) ? $redirects[$area] : array();
            } else {
                $result = array_values($redirects);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Set the access denied redirect
     *
     * @param array $redirect       Redirect settings
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.14
     * @throws RuntimeException If fails to persist the data
     */
    public function set_redirect(array $redirect, $inline_context = null)
    {
        try {
            // Validating that incoming data is correct and normalize is for storage
            $data   = $this->_validate_redirect($redirect);
            $object = $this->_get_subject($inline_context)->getObject(
                AAM_Core_Object_Redirect::OBJECT_TYPE
            );

            // Merging explicit options
            $new_option = array_merge($object->getExplicitOption(), $data);
            $result     = $object->setExplicitOption($new_option)->save();

            if (!$result) {
                throw new RuntimeException('Failed to persist settings');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset the redirect rule
     *
     * @param string $area
     * @param array  $inline_context Runtime context
     *
     * @return boolean
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
     * @since 6.9.14 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function reset($area = null, $inline_context = null)
    {
        try {
            $object = $this->_get_subject($inline_context)->getObject(
                AAM_Core_Object_Redirect::OBJECT_TYPE
            );

            $success = true;

            if (empty($area)) {
                $object->reset();
            } else {
                $settings     = $object->getExplicitOption();
                $new_settings = array();

                foreach($settings as $k => $v) {
                    if (strpos($k, $area) !== 0) {
                        $new_settings[$k] = $v;
                    }
                }

                $success = $object->setExplicitOption($new_settings)->save();
            }

            if ($success) {
                $result = $this->get_redirect($area, $inline_context);
            } else {
                throw new RuntimeException('Failed to reset settings');
            }
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
     * @access private
     * @version 6.9.14
     */
    private function _prepare_redirects($settings, $is_inherited = false)
    {
        // Split the redirect settings by area
        $areas = array();

        foreach($settings as $k => $v) {
            $split = explode('.', $k);

            if (!isset($areas[$split[0]])) {
                $areas[$split[0]] = array();
            }

            $areas[$split[0]][str_replace($split[0] . '.', '', $k)] = $v;
        }

        // Normalize each redirect
        foreach($areas as $area => $data) {
            $areas[$area] = $this->_prepare_redirect($area, $data, $is_inherited);
        }

        return $areas;
    }

    /**
     * Prepare an individual redirect
     *
     * @param string $area
     * @param array  $settings
     * @param bool   $is_inherited
     *
     * @return array
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/359
     * @since 6.9.14 Initial implementation of the method
     *
     * @access private
     * @version 6.9.26
     */
    private function _prepare_redirect($area, $settings, $is_inherited)
    {
        // Determine current rule type. If none set, deny by default
        if (isset($settings['redirect.type'])) {
            $legacy_type = $settings['redirect.type'];
        } else {
            $legacy_type = 'default';
        }

        $response = array(
            'area' => $area,
            'type' => self::REDIRECT_TYPE_ALIAS[$legacy_type]
        );

        if ($response['type'] === 'page_redirect') {
            $response['redirect_page_id'] = intval($settings['redirect.page']);
        } elseif ($response['type'] === 'url_redirect') {
            $response['redirect_url'] = $settings['redirect.url'];
        } elseif ($response['type'] === 'trigger_callback') {
            $response['callback'] = $settings['redirect.callback'];
        } elseif ($response['type'] === 'custom_message') {
            $response['message']     = $settings['redirect.message'];

            if (isset($settings['redirect.message.code'])) {
                $response['http_status_code'] = $settings['redirect.message.code'];
            }
        } elseif ($response['type'] === 'default') {
            if (isset($settings['redirect.default.code'])) {
                $response['http_status_code'] = $settings['redirect.default.code'];
            }
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
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/359
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/322
     * @since 6.9.14 Initial implementation of the method
     *
     * @access private
     * @version 6.9.26
     */
    private function _validate_redirect(array $rule)
    {
        $normalized = array();

        // Determine the area
        $area = isset($rule['area']) ? $rule['area'] : null;

        if (!in_array($area, self::ALLOWED_REDIRECT_AREAS, true)) {
            throw new InvalidArgumentException(
                'The `area` is not valid. It should be either frontend or backend'
            );
        }

        $type       = array_search($rule['type'], self::REDIRECT_TYPE_ALIAS);
        $normalized[$area . '.redirect.type'] = $type;

        if (empty($type)) {
            throw new InvalidArgumentException('The `type` is required');
        } elseif ($type === 'page') {
            $page_id = intval($rule['redirect_page_id']);

            if ($page_id === 0) {
                throw new InvalidArgumentException(
                    'The `redirect_page_id` is required'
                );
            } else {
                $normalized[$area . '.redirect.page'] = $page_id;
            }
        } elseif ($type === 'url') {
            $redirect_url = wp_validate_redirect($rule['redirect_url']);

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid `redirect_url` is required'
                );
            } else {
                $normalized[$area . '.redirect.url'] = $redirect_url;
            }
        } elseif ($type === 'callback') {
            if (is_callable($rule['callback'], true)) {
                $normalized[$area . '.redirect.callback'] = $rule['callback'];
            } else {
                throw new InvalidArgumentException(
                    'The valid `callback` is required'
                );
            }
        } elseif ($type === 'message') {
            if (is_callable($rule['message'], true)) {
                $normalized[$area . '.redirect.message'] = wp_kses_post(
                    $rule['message']
                );
            } else {
                throw new InvalidArgumentException(
                    'The access denied `message` is required'
                );
            }
        }

        // If HTTP status code is defined, save it as well
        if (!empty($rule['http_status_code'])) {
            $normalized["{$area}.redirect.{$type}.code"] = $this->_validate_status_code(
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
        $allowed_codes = self::HTTP_STATUS_CODES[$redirect_type];
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
                    "For redirect type {$redirect_type} allowed status codes are {$allowed}"
                );
            }
        }

        return $code;
    }

}