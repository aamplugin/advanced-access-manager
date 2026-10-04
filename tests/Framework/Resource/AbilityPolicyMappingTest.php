<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Resource;

use AAM;
use AAM\UnitTest\Utility\TestCase;

/**
 * Core Ability policy mapping tests
 *
 * @package AAM
 * @version 8.0.0
 */
final class AbilityPolicyMappingTest extends TestCase
{
    /**
     * Verify core maps exact names and leaves wildcards to the premium add-on
     *
     * @return void
     * @access public
     *
     * @version 8.0.0
     */
    public function testCorePolicyMappingIsExactOnly(): void
    {
        $user_id = $this->createUser();
        $level = AAM::api()->user($user_id);
        $policy = wp_json_encode([
            'Statement' => [
                [ 'Resource' => 'Ability:core/get-site-info', 'Effect' => 'deny' ],
                [ 'Resource' => 'Ability:core/get-more-info' ],
            ]
        ]);
        $this->assertIsInt(AAM::api()->policies($level)->create($policy));

        $core_permissions = null;
        $capture = function($permissions, $resource) use (&$core_permissions) {
            if ($resource->type === 'ability') {
                $core_permissions = $permissions;
            }
            return $permissions;
        };
        add_filter('aam_apply_policy_filter', $capture, 1, 2);
        try {
            $level->get_resource('ability');
        } finally {
            remove_filter('aam_apply_policy_filter', $capture, 1);
        }

        $this->assertSame('deny', $core_permissions['get-site-info|core']['access']['effect']);
        $this->assertSame('deny', $core_permissions['get-more-info|core']['access']['effect']);
    }
}
