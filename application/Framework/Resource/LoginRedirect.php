<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Login Redirect resource
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_LoginRedirect
    implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * Resource type
     *
     * @version 7.0.0
     */
    const TYPE = AAM_Framework_Type_Resource::LOGIN_REDIRECT;

    /**
     * List of allowed redirect types
     *
     * @version 7.0.0
     */
    const ALLOWED_REDIRECT_TYPES = [
        'default',
        'page_redirect',
        'url_redirect',
        'trigger_callback'
    ];

    /**
     * @inheritDoc
     */
    public function merge_settings($incoming_settings)
    {
        return array_replace_recursive($incoming_settings, $this->_settings);
    }

    /**
     * Validate and normalize the incoming redirect data
     *
     * @param array $incoming_data
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function convert_to_redirect(array $incoming_data)
    {
        $result = [
            'type' => $incoming_data['type']
        ];

        if ($incoming_data['type'] === 'page_redirect') {
            if (isset($incoming_data['redirect_page_id'])) {
                $page_id = intval($incoming_data['redirect_page_id']);
            } else {
                $page_id = 0;
            }

            if ($page_id === 0) {
                throw new InvalidArgumentException(
                    'The `redirect_page_id` is required'
                );
            } else {
                $result['redirect_page_id'] = $page_id;
            }
        } elseif ($incoming_data['type'] === 'url_redirect') {
            if (isset($incoming_data['redirect_url'])) {
                $redirect_url = wp_validate_redirect($incoming_data['redirect_url']);
            } else {
                $redirect_url = null;
            }

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid `redirect_url` is required'
                );
            } else {
                $result['redirect_url'] = $redirect_url;
            }
        } elseif ($incoming_data['type'] === 'trigger_callback') {
            if (isset($incoming_data['callback'])
                && is_callable($incoming_data['callback'], true)
            ) {
                $result['callback'] = $incoming_data['callback'];
            } else {
                throw new InvalidArgumentException(
                    'The valid `callback` is required'
                );
            }
        }

        return $result;
    }

}