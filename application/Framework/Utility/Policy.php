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
        } elseif ($resource_type === 'role') {
            $result = $this->_role_statement_to_permission($stm);
        } elseif ($resource_type === 'user') {
            $result = $this->_user_statement_to_permission($stm);
        }

        return apply_filters(
            'aam_policy_statement_to_permission_filter',
            $result,
            $stm,
            $resource_type
        );
    }

    /**
     * Convert `Redirect` property to internal representation of redirect
     *
     * @param array $redirect
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function convert_statement_redirect($redirect)
    {
        $result = [ 'type' => 'default' ];

        // Getting redirect type
        if (array_key_exists('Type', $redirect)) {
            $result['type'] = $redirect['Type'];
        }

        // Getting HTTP status code
        if (array_key_exists('StatusCode', $redirect)) {
            $result['http_status_code'] = intval(
                $redirect['StatusCode']
            );
        }

        // Getting additional redirect attributes
        if (array_key_exists('Slug', $redirect)) {
            $result['redirect_page_slug'] = $redirect['Slug'];
        }

        if (array_key_exists('Id', $redirect)) {
            $result['redirect_page_id'] = $redirect['Id'];
        }

        if (array_key_exists('Url', $redirect)) {
            $result['redirect_url'] = $redirect['Url'];
        }

        if (array_key_exists('Callback', $redirect)) {
            $result['callback'] = $redirect['Callback'];
        }

        if (array_key_exists('Message', $redirect)) {
            $result['message'] = $redirect['Message'];
        }

        return $result;
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
                    'effect' => $effect !== 'allow' ? 'deny' : 'allow'
                ]
            ];

            if ($action === 'read') {
                if (array_key_exists('Password', $stm)) { // Is Password Protected?
                    $result['read']['restriction_type'] = 'password_protected';
                    $result['read']['password']         = $stm['Password'];
                } elseif (array_key_exists('Teaser', $stm)) { // Has Teaser Message?
                    $result['read']['restriction_type'] = 'teaser_message';
                    $result['read']['message']          = $stm['Teaser'];
                } elseif (array_key_exists('Redirect', $stm)) { // Has Redirect?
                    $result['read']['restriction_type'] = 'redirect';

                    // Convert redirect
                    if (is_array($stm['Redirect'])) {
                        $result['read']['redirect'] = $this->convert_statement_redirect(
                            $stm['Redirect']
                        );
                    }
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

    /**
     * Convert policy statement with role resource to internal permission
     *
     * @param array $stm
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _role_statement_to_permission($stm)
    {
        $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';
        $action = isset($stm['Action']) ? strtolower($stm['Action']) : 'assume';

        // There are two possible representations of Role resource:
        //   1. Role:<slug>
        //   2. Role:<slug>:users
        // Depending on type, different list of actions are supported
        $bits = explode(':', $stm['Resource']);

        if (count($bits) === 3) {
            if ($action === 'list') {
                $action = 'list_user';
            } elseif ($action === 'edit') {
                $action = 'edit_user';
            } elseif ($action === 'changepassword') {
                $action = 'change_user_password';
            } elseif (in_array($action, [ 'changerole', 'promote' ], true)) {
                $action = 'promote_user';
            } elseif ($action === 'delete') {
                $action = 'delete_user';
            }
        } else {
            $action = "{$action}_role";
        }

        return [
            $action => [
                'effect' => $effect !== 'allow' ? 'deny' : 'allow'
            ]
        ];
    }

    /**
     * Convert policy statement with user resource to internal permission
     *
     * @param array $stm
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _user_statement_to_permission($stm)
    {
        $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';
        $action = isset($stm['Action']) ? strtolower($stm['Action']) : 'list_user';

        if ($action === 'list') {
            $action = 'list_user';
        } elseif ($action === 'edit') {
            $action = 'edit_user';
        } elseif ($action === 'changepassword') {
            $action = 'change_user_password';
        } elseif (in_array($action, [ 'changerole', 'promote' ], true)) {
            $action = 'promote_user';
        } elseif ($action === 'delete') {
            $action = 'delete_user';
        }

        return [
            $action => [
                'effect' => $effect !== 'allow' ? 'deny' : 'allow'
            ]
        ];
    }

}