<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Service;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "Logout Redirect" framework service
 */
final class LogoutRedirectTest extends TestCase
{

    /**
     * Test that we can get and set redirect properly
     *
     * @return void
     */
    public function testSetGetRedirect()
    {
        $redirect_a = [ 'type' => 'default' ];
        $redirect_b = [
            'type'         => 'url_redirect',
            'redirect_url' => site_url() . '/some-page'
        ];

        // Setting redirect
        $this->assertEquals(
            $redirect_a,
            AAM::api()->logout_redirect()->set_redirect($redirect_a)
        );

        // Verifying that we are getting the same redirect back
        $this->assertEquals(
            $redirect_a,
            AAM::api()->logout_redirect()->get_redirect()
        );

        // Setting another redirect
        $this->assertEquals(
            [ 'type' => 'url_redirect', 'redirect_url' => '/some-page' ],
            AAM::api()->logout_redirect()->set_redirect($redirect_b)
        );

        // Verifying that we are getting the same redirect back
        $this->assertEquals(
            [ 'type' => 'url_redirect', 'redirect_url' => '/some-page' ],
            AAM::api()->logout_redirect()->get_redirect()
        );
    }


}