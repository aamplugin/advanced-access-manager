<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for JWT Tokens
 *
 * @package AAM
 * @version 6.9.10
 */
class AAM_Framework_Service_Jwts
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Return list of tokens
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 6.9.10
     */
    public function get_token_list($inline_context = null)
    {
        try {
            $result = [];
            $user   = $this->_get_subject($inline_context);
            $tokens = AAM_Service_Jwt::getInstance()->getTokenRegistry($user->ID);

            foreach($tokens as $token) {
                array_push($result, $this->_prepare_token($token));
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get existing token by ID
     *
     * @param string $id             Token ID
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.10
     * @throws OutOfRangeException If token does not exist
     */
    public function get_token_by_id($id, $inline_context = null)
    {
        try {
            $result = false;

            foreach($this->get_token_list($inline_context) as $token) {
                if ($token['id'] === $id) {
                    $result = $token;
                }
            }

            if ($result === false) {
                throw new OutOfRangeException('Token does not exist');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Create new token
     *
     * @param array $claims         Token claims
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.10
     * @throws RuntimeException If fails to persist token
     */
    public function create_token(array $claims, $inline_context = null)
    {
        try {
            $subject = $this->_get_subject($inline_context);

            // Adding user ID to the list of claims
            $claims['userId'] = $subject->ID;

            // Generating token
            $token = AAM_Core_Jwt_Manager::getInstance()->encode($claims);

            // Register token
            $result = AAM_Service_Jwt::getInstance()->registerToken(
                $subject->ID, $token->token
            );

            if (!$result) {
                throw new RuntimeException('Failed to register token');
            }

            $result = $this->_prepare_token($token->token);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete token
     *
     * @param string $id             Token ID
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.10
     * @throws RuntimeException If fails to revoke a token
     */
    public function delete_token($id, $inline_context = null)
    {
        try {
            // Find the token that we are deleting
            $found = $this->get_token_by_id($id, $inline_context);
            $user  = $this->_get_subject($inline_context);

            // Revoking the token
            $result = AAM_Service_Jwt::getInstance()->revokeUserToken(
                $user->ID, $found['token']
            );

            if (!$result) {
                throw new RuntimeException('Failed to revoke the token');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset all tokens
     *
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.10
     */
    public function reset($inline_context = null)
    {
        try {
            $user = $this->_get_subject($inline_context);

            // Reset
            if (!AAM_Service_Jwt::getInstance()->resetTokenRegistry($user->ID)) {
                throw new RuntimeException('Failed to reset tokens');
            } else {
                $result = $this->get_token_list($inline_context);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Prepare token model
     *
     * @param string $token
     *
     * @return array
     *
     * @access private
     * @version 6.9.10
     */
    private function _prepare_token($token)
    {
        $response = array();

        $manager = AAM_Core_Jwt_Manager::getInstance();
        $claims  = $manager->validate($token);

        if (!is_wp_error($claims)) {
            $response['id']         = $claims->jti;
            $response['is_valid']   = true;
            $response['claims']     = $claims;
            $response['signed_url'] = add_query_arg(
                'aam-jwt', $token, site_url()
            );
        } else {
            $response['is_valid'] = false;
            $response['error']    = $claims->get_error_message();

            // Otherwise just try to extract claims
            $claims = $manager->extractClaims($token);

            if ($claims !== null) {
                $response['id']     = $claims->jti;
                $response['claims'] = $claims;
            }
        }

        $response['token'] = $token;

        return $response;
    }

}