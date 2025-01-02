<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM framework utilities
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_Utility_Policy implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Convert policy statement to internal permissions
     *
     * @param array  $stm
     * @param string $resource_type
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function statement_to_permission($stm, $resource_type)
    {
        if ($resource_type === 'post') {
            $result = $this->_post_statement_to_permission($stm);
        }

        return apply_filters(
            'aam_policy_statement_to_permission_filter',
            $result,
            $stm,
            $resource_type
        );
    }

    /**
     * Convert policy statement for Post resource to internal permissions
     *
     * @param array $stm
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _post_statement_to_permission($stm)
    {
        $action = isset($stm['Action']) ? strtolower($stm['Action']) : null;
        $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

        if (!empty($action)) {
            $result = [
                $action => [
                    'effect' => $effect
                ]
            ];

            if ($action === 'read') {
                if (array_key_exists('Password', $stm)) { // Is Password Protected?
                    $result['read'] = [
                        'restriction_type' => 'password_protected',
                        'password'         => $stm['Password']
                    ];
                } elseif (array_key_exists('Teaser', $stm)) { // Has Teaser Message?
                    $result['read'] = [
                        'restriction_type' => 'teaser_message',
                        'message'          => $stm['Teaser']
                    ];
                } elseif (array_key_exists('Redirect', $stm)) { // Has Redirect?
                    $result['read'] = [
                        'restriction_type' => 'redirect'
                    ];

                    $redirect = [];

                    // Getting redirect type
                    if (array_key_exists('Type', $stm['Redirect'])) {
                        $redirect['type'] = $stm['Redirect']['Type'];
                    }

                    // Getting HTTP status code
                    if (array_key_exists('StatusCode', $stm['Redirect'])) {
                        $redirect['http_status_code'] = intval(
                            $stm['Redirect']['StatusCode']
                        );
                    }

                    // Getting additional redirect attributes
                    if (array_key_exists('Slug', $stm['Redirect'])) {
                        $redirect['redirect_page_slug'] = $stm['Redirect']['Slug'];
                    }

                    if (array_key_exists('Id', $stm['Redirect'])) {
                        $redirect['redirect_page_id'] = $stm['Redirect']['Id'];
                    }

                    if (array_key_exists('Url', $stm['Redirect'])) {
                        $redirect['redirect_url'] = $stm['Redirect']['Url'];
                    }

                    if (array_key_exists('Callback', $stm['Redirect'])) {
                        $redirect['callback'] = $stm['Redirect']['Callback'];
                    }

                    if (array_key_exists('Message', $stm['Redirect'])) {
                        $redirect['message'] = $stm['Redirect']['Message'];
                    }

                    $result['read']['redirect'] = $redirect;
                }
            } elseif ($action === 'list') {
                if (array_key_exists('On', $stm)) {
                    $result['list']['on'] = (array) $stm['On'];
                } else {
                    $result['list']['on'] = [ 'frontend', 'backend', 'api' ];
                }
            }
        } else {
            $result = [];
        }

        return $result;
    }

}