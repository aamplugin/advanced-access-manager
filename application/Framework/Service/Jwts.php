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
        $response = array();
        $user     = $this->_get_subject($inline_context);
        $tokens   = AAM_Service_Jwt::getInstance()->getTokenRegistry($user->ID);


        foreach($tokens as $token) {
            array_push($response, $this->_prepare_token($token));
        }

        return $response;
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
     * @throws UnderflowException If token does not exist
     */
    public function get_token_by_id($id, $inline_context = null)
    {
        $found = false;

        foreach($this->get_token_list($inline_context) as $token) {
            if ($token['id'] === $id) {
                $found = $token;
            }
        }

        if ($found === false) {
            throw new UnderflowException('Token does not exist');
        }

        return $found;
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
     * @throws Exception If fails to persist the rule
     * @throws InvalidArgumentException If route is not provided
     */
    public function create_token(array $claims, $inline_context = null)
    {
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
            throw new Exception('Failed to register token');
        }

        return $this->_prepare_token($token->token);
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
     * @throws UnderflowException If token does not exist
     * @throws Exception If fails to persist a rule
     */
    public function delete_token($id, $inline_context = null)
    {
        // Find the token that we are deleting
        $found = $this->get_token_by_id($id, $inline_context);
        $user  = $this->_get_subject($inline_context);

        // Revoking the token
        $result = AAM_Service_Jwt::getInstance()->revokeUserToken(
            $user->ID, $found['token']
        );

        if (!$result) {
            throw new Exception('Failed to revoke the token');
        }

        return $this->_prepare_token($found['token']);
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
    public function reset_tokens($inline_context = null)
    {
        $response = array();

        $user   = $this->_get_subject($inline_context);
        $tokens = $this->get_token_list($inline_context);

        // Communicate about number of tokens that were deleted
        $response['deleted_token_count'] = count($tokens);

        // Reset
        $result = AAM_Service_Jwt::getInstance()->resetTokenRegistry($user->ID);

        if (!$result) {
            throw new Exception('Failed to reset tokens');
        }

        return $response;
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
            $response['signed_url'] = add_query_arg('aam-jwt', $token, site_url());
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