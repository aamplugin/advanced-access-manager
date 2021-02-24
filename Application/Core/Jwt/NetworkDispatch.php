<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM JWT NetworkDispatch
 *
 * @since 6.7.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.7.2
 */
class AAM_Core_Jwt_NetworkDispatch
    extends AAM_Core_Jwt_Issuer
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * JWT token claim(s) dispatch to WP Network sites
     *
     * @param string $token
     *
     * @return object
     *
     * @since 6.1.0 Enriched error response with more details
     * @since 6.0.4 Making sure that JWT expiration is checked with UTC timezone
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function tokenClaimsNetworkDispatch($token)
    {
        try {
            $response = $this->validateToken($token);

            // Step #1. Check if token is actually valid
            if($response->isValid) {
                $claims = $this->extractTokenClaims($token);
                $headers = $this->extractTokenHeaders($token);

                // Step #2.

                $response->hasDispatched = true;
            } else {
                $response->hasDispatched = false;
            }

        } catch (Exception $ex) {
            $status = $ex->getCode();
            $response = array(
                'hasDispatched' => false,
                'reason' => $ex->getMessage(),
                'status' => (!empty($status) ? $status : 400)
            );
        }

        return (object)$response;
    }

    /**
     * JWT token sites claim extract
     *
     * @param string $token
     *
     * @return object
     *
     * @since 6.7.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function extractSitesTokenClaims($token)
    {
        try {

            $headers = $this->extractTokenHeaders($token);

        } catch (Exception $ex) {
            $status = $ex->getCode();
            $response = array(
                'hasDispatched' => false,
                'reason' => $ex->getMessage(),
                'status' => (!empty($status) ? $status : 400)
            );
        }

        return (object)$response;
    }
}
