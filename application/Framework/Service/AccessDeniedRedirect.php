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
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/322
 * @since 6.9.14 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.17
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
     * @version 6.9.14
     */
    const REDIRECT_TYPE_ALIAS = array(
        'default'  => 'default',
        'message'  => 'custom_message',
        'page'     => 'page_redirect',
        'url'      => 'url_redirect',
        'callback' => 'trigger_callback'
    );

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
        $object    = $this->get_object($inline_context);
        $redirects = $this->_prepare_redirects(
            $object->getOption(),
            !$object->isOverwritten()
        );

        $response = array();

        if (!empty($area)) {
            $response = isset($redirects[$area]) ? $redirects[$area] : array();
        } else {
            $response = array_values($redirects);
        }

        return $response;
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
     * @throws Exception If fails to persist the data
     */
    public function set_redirect(array $redirect, $inline_context = null)
    {
        // Validating that incoming data is correct and normalize is for storage
        $data   = $this->_validate_redirect($redirect);
        $object = $this->get_object($inline_context);

        // Merging explicit options
        $new_option = array_merge($object->getExplicitOption(), $data);

        if (!$object->setExplicitOption($new_option)->save()) {
            throw new Exception('Failed to persist the access denied redirect');
        }

        $area      = $redirect['area'];
        $redirects = $this->_prepare_redirects(
            $object->getExplicitOption(), false
        );

        return $redirects[$area];
    }

    /**
     * Reset the redirect rule
     *
     * @param string $area
     * @param array  $inline_context Runtime context
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.14
     */
    public function reset_redirect($area = null, $inline_context = null)
    {
        $response = false;
        $object   = $this->get_object($inline_context);

        if (empty($area)) {
            $response = $object->reset();
        } else {
            $settings     = $object->getExplicitOption();
            $new_settings = array();

            foreach($settings as $k => $v) {
                if (strpos($k, $area) !== 0) {
                    $new_settings[$k] = $v;
                }
            }

            $response = $object->setExplicitOption($new_settings)->save();
        }

        return $response;
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
     * @access private
     * @version 6.9.14
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
            $response['message'] = $settings['redirect.message'];
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
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/322
     * @since 6.9.14 Initial implementation of the method
     *
     * @access private
     * @version 6.9.17
     */
    private function _validate_redirect(array $rule)
    {
        $normalized = array();

        // Determine the area
        $area = isset($rule['area']) ? $rule['area'] : null;

        if (!in_array($area, array('frontend', 'backend'), true)) {
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
                $normalized[$area . '.redirect.message'] = wp_kses_post($rule['message']);
            } else {
                throw new InvalidArgumentException(
                    'The access denied `message` is required'
                );
            }
        }

        return $normalized;
    }

    /**
     * Get object
     *
     * @param array $inline_context
     *
     * @return AAM_Core_Object
     * @version 6.9.14
     */
    protected function get_object($inline_context)
    {
        return $this->_get_subject($inline_context)->getObject(
            AAM_Core_Object_Redirect::OBJECT_TYPE
        );
    }

}