<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract for redirect service
 *
 * @package AAM
 * @version 6.9.12
 */
abstract class AAM_Framework_Service_RedirectAbstract
{

    /**
     * Redirect type aliases
     *
     * To be a bit more verbose, we are renaming the legacy rule types to something
     * that is more intuitive
     *
     * @version 6.9.12
     */
    const REDIRECT_TYPE_ALIAS = array(
        'default'  => 'default',
        'page'     => 'page_redirect',
        'url'      => 'url_redirect',
        'callback' => 'trigger_callback'
    );

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
        $object = $this->get_object($inline_context);

        return $this->_prepare_redirect(
            $object->getOption(),
            !$object->isOverwritten()
        );
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
     * @throws Exception If fails to persist the data
     */
    public function set_redirect(array $redirect, $inline_context = null)
    {
        // Validating that incoming data is correct and normalize is for storage
        $data   = $this->_validate_redirect($redirect);
        $object = $this->get_object($inline_context);

        if (!$object->setExplicitOption($data)->save()) {
            throw new Exception('Failed to persist the login redirect');
        }

        return $this->_prepare_redirect($object->getExplicitOption(), false);
    }

    /**
     * Reset the redirect rule
     *
     * @param array $inline_context Runtime context
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.12
     */
    public function reset_redirect($inline_context = null)
    {
        return $this->get_object($inline_context)->reset();
    }

    /**
     * Get object
     *
     * @param array $inline_context
     *
     * @return AAM_Core_Object
     */
    abstract protected function get_object($inline_context);

    /**
     * Normalize and prepare the redirect details
     *
     * @param array $settings
     * @param bool  $is_inherited
     *
     * @return array
     *
     * @access private
     * @version 6.9.12
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

        $response = array('type' => self::REDIRECT_TYPE_ALIAS[$legacy_type]);

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
     * @access private
     * @version 6.9.12
     */
    private function _validate_redirect(array $rule)
    {
        $normalized = array();

        $type       = array_search($rule['type'], self::REDIRECT_TYPE_ALIAS);
        $normalized[static::REDIRECT_TYPE . '.redirect.type'] = $type;


        if (empty($type)) {
            throw new InvalidArgumentException('The `type` is required');
        } elseif ($type === 'page') {
            $page_id = intval($rule['redirect_page_id']);

            if ($page_id === 0) {
                throw new InvalidArgumentException(
                    'The `redirect_page_id` is required'
                );
            } else {
                $normalized[static::REDIRECT_TYPE . '.redirect.page'] = $page_id;
            }
        } elseif ($type === 'url') {
            $redirect_url = wp_validate_redirect($rule['redirect_url']);

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid `redirect_url` is required'
                );
            } else {
                $normalized[static::REDIRECT_TYPE . '.redirect.url'] = $redirect_url;
            }
        } elseif ($type === 'callback') {
            if (is_callable($rule['callback'], true)) {
                $normalized[static::REDIRECT_TYPE . '.redirect.callback'] = $rule['callback'];
            } else {
                throw new InvalidArgumentException(
                    'The valid `callback` is required'
                );
            }
        }

        return $normalized;
    }

}