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
    AAM_Framework_Type_Resource,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "Hooks" framework service
 */
final class HooksTest extends TestCase
{

    /**
     * Testing Hooks restrictions can be set property
     *
     * @return void
     */
    public function testHookPermissionsSets() : void
    {
        $service = AAM::api()->hooks();

        // Setting all possible types of restrictions
        $this->assertTrue($service->deny('aam_hook_a'));
        $this->assertTrue($service->deny('aam_hook_b', 1));
        $this->assertTrue($service->allow('aam_hook_c'));
        $this->assertTrue($service->allow('aam_hook_d', 2));
        $this->assertTrue($service->alter('aam_hook_e', 'nope'));
        $this->assertTrue($service->alter('aam_hook_f', [ 'test' => true ], 3));
        $this->assertTrue($service->merge('aam_hook_g', [ 'test' ]));
        $this->assertTrue($service->merge('aam_hook_h', [ 'test' => true ], 4));
        $this->assertTrue($service->replace('aam_hook_i', false));
        $this->assertTrue($service->replace('aam_hook_j', -1, 5));

        // Getting raw data to see that everything is stored as it should
        $permissions = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::HOOK
        )->get_permissions();

        $this->assertEquals([
            'aam_hook_a|10' => [
                'access' => [
                    'effect'         => 'deny',
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_hook_b|1' => [
                'access' => [
                    'effect'         => 'deny',
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_hook_c|10' => [
                'access' => [
                    'effect'         => 'allow',
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_hook_d|2' => [
                'access' => [
                    'effect'         => 'allow',
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_hook_e|10' => [
                'access' => [
                    'effect'         => 'alter',
                    'return'         => 'nope',
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_hook_f|3' => [
                'access' => [
                    'effect'         => 'alter',
                    'return'         => [ 'test' => true ],
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_hook_g|10' => [
                'access' => [
                    'effect'         => 'merge',
                    'return'         => [ 'test' ],
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_hook_h|4' => [
                'access' => [
                    'effect'         => 'merge',
                    'return'         => [ 'test' => true ],
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_hook_i|10' => [
                'access' => [
                    'effect'         => 'replace',
                    'return'         => false,
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_hook_j|5' => [
                'access' => [
                    'effect'         => 'replace',
                    'return'         => -1,
                    '__access_level' => 'visitor'
                ]
            ]
        ], $permissions);
    }

    /**
     * Making sure that access controls are properly merged when multi access levels
     * support is enabled and merging preference is "deny"
     *
     * @return void
     */
    public function testMultiRolePermissionPreferenceDenyMerging()
    {
        // Setting multi-role support
        AAM::api()->config->set('core.settings.multi_access_levels', true);

        // Creating user with multiple roles and activating it
        $user_a = $this->createUser([ 'role' => [ 'author' , 'contributor' ] ]);

        wp_set_current_user($user_a);

        // Setting various permissions
        $author      = AAM::api()->hooks('role:author');
        $contributor = AAM::api()->hooks('role:contributor');

        // #1 case: permissions should be denied
        $author->deny('hook_a');
        $contributor->allow('hook_a');

        // #2 case: permissions should be denied
        $author->replace('hook_b', false);
        $contributor->deny('hook_b');

        // #3 case: first permissions should be applied
        $author->alter('hook_c', 'yes');
        $contributor->merge('hook_c', [ 'no' ]);

        // #4 case: permissions should be denied
        $author->deny('hook_d');

        // #5 case: permissions should be allowed
        $author->allow('hook_e');
        $contributor->alter('hook_e', 34);

        // Confirm permissions
        $permissions = AAM::api()->user($user_a)->get_resource(
            AAM_Framework_Type_Resource::HOOK
        )->get_permissions();

        $this->assertEquals([
            'hook_a|10' => [
                'access' => [
                    'effect'            => 'deny',
                    '__access_level'    => 'role',
                    '__access_level_id' => 'author',
                    '__inherited'       => true
                ]
            ],
            'hook_b|10' => [
                'access' => [
                    'effect'            => 'deny',
                    '__access_level'    => 'role',
                    '__access_level_id' => 'contributor',
                    '__inherited'       => true
                ]
            ],
            'hook_c|10' => [
                'access' => [
                    'effect'            => 'alter',
                    'return'            => 'yes',
                    '__access_level'    => 'role',
                    '__access_level_id' => 'author',
                    '__inherited'       => true
                ]
            ],
            'hook_d|10' => [
                'access' => [
                    'effect'            => 'deny',
                    '__access_level'    => 'role',
                    '__access_level_id' => 'author',
                    '__inherited'       => true
                ]
            ],
            'hook_e|10' => [
                'access' => [
                    'effect'            => 'allow',
                    '__access_level'    => 'role',
                    '__access_level_id' => 'author',
                    '__inherited'       => true
                ]
            ]
        ], $permissions);
    }

    /**
     * Making sure that access controls are properly merged when multi access levels
     * support is enabled and merging preference is "allow"
     *
     * @return void
     */
    public function testMultiRolePermissionPreferenceAllowMerging()
    {
        // Setting multi-role support
        AAM::api()->config->set('core.settings.multi_access_levels', true);
        AAM::api()->config->set('core.settings.merge.preference', 'allow');

        // Creating user with multiple roles and activating it
        $user_a = $this->createUser([ 'role' => [ 'author' , 'contributor' ] ]);

        wp_set_current_user($user_a);

        // Setting various permissions
        $author      = AAM::api()->hooks('role:author');
        $contributor = AAM::api()->hooks('role:contributor');

        // #1 case: permissions should be allowed
        $author->deny('hook_a');
        $contributor->allow('hook_a');

        // #2 case: permissions should be replace
        $author->replace('hook_b', false);
        $contributor->deny('hook_b');

        // #3 case: first permissions should be alter
        $author->alter('hook_c', 'yes');
        $contributor->merge('hook_c', [ 'no' ]);

        // #4 case: permissions should be allowed
        $author->deny('hook_d');

        // #5 case: permissions should be denied
        $author->deny('hook_e');
        $contributor->deny('hook_e');

        // Confirm permissions
        $permissions = AAM::api()->user($user_a)->get_resource(
            AAM_Framework_Type_Resource::HOOK
        )->get_permissions();

        $this->assertEquals([
            'hook_a|10' => [
                'access' => [
                    'effect'            => 'allow',
                    '__access_level'    => 'role',
                    '__access_level_id' => 'contributor',
                    '__inherited'       => true
                ]
            ],
            'hook_b|10' => [
                'access' => [
                    'effect' => 'replace',
                    'return' => false,
                    '__access_level' => 'role',
                    '__access_level_id' => 'author',
                    '__inherited' => true
                ]
            ],
            'hook_c|10' => [
                'access' => [
                    'effect'            => 'alter',
                    'return'            => 'yes',
                    '__access_level'    => 'role',
                    '__access_level_id' => 'author',
                    '__inherited'       => true
                ]
            ],
            'hook_d|10' => [
                'access' => [
                    'effect' => 'allow'
                ]
            ],
            'hook_e|10' => [
                'access' => [
                    'effect'            => 'deny',
                    '__access_level'    => 'role',
                    '__access_level_id' => 'author',
                    '__inherited'       => true
                ]
            ]
        ], $permissions);
    }

}