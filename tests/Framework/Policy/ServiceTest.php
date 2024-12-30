<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Policy;

use AAM,
    AAM\UnitTest\Utility\TestCase,
    AAM_Framework_Service_Settings,
    AAM_Framework_Service_Policies;

/**
 * Framework policy service test
 */
final class ServiceTest extends TestCase
{

    /**
     * Test policy creation
     *
     * @return void
     */
    public function testCreatePolicy()
    {
        // Preparing a couple of policies
        $policy_a = '{
            "Statement": {
                "Resource": "Post:post:hello-world",
                "Action": "Edit",
                "Effect": "deny"
            }
        }';

        $policy_b = '{
            "Param": {
                "Key": "TestParam",
                "Value": "hello"
            }
        }';

        $policy_c = '{
            "Param": {
                "Key": "TestParamB",
                "Value": "nope"
            }
        }';

        // Create new policy and automatically attach it to the current user
        $policy_a_id = AAM::api()->policies()->create($policy_a);
        $policy_b_id = AAM::api()->policies()->create($policy_b);
        $policy_c_id = AAM::api()->policies()->create($policy_c, 'publish', false);

        $this->assertIsInt($policy_a_id);
        $this->assertIsInt($policy_b_id);
        $this->assertIsInt($policy_c_id);

        // Verify that policy is attached and properly parsed
        $this->assertEquals([
            'post:post:hello-world:edit' => [
                'Resource' => 'Post:post:hello-world',
                'Action'   => 'Edit',
                'Effect'   => 'deny'
            ]
        ], AAM::api()->policies()->statements());

        $this->assertEquals([
            'TestParam' => 'hello'
        ], AAM::api()->policies()->params());

        // Check DB value
        $raw_settings = $this->readWpOption(AAM_Framework_Service_Settings::DB_OPTION);

        $this->assertArrayHasKey('visitor', $raw_settings);
        $this->assertArrayHasKey('policy', $raw_settings['visitor']);
        $this->assertArrayHasKey($policy_a_id, $raw_settings['visitor']['policy']);
        $this->assertArrayHasKey($policy_b_id, $raw_settings['visitor']['policy']);
        $this->assertEquals([
            $policy_a_id => [ 'effect' => 'attach' ],
            $policy_b_id => [ 'effect' => 'attach' ]
        ], $raw_settings['visitor']['policy']);

        // Policy C should not be attached to current access level
        $this->assertArrayNotHasKey($policy_c_id, $raw_settings['visitor']['policy']);
    }

    /**
     * Test we can get statements correctly
     *
     * @return void
     */
    public function testGetStatements()
    {
        $policy = '{
            "Statement": [
                {
                    "Resource": "Term:category:uncategorized",
                    "Action": [
                        "List",
                        "Edit"
                    ],
                    "Effect": "deny"
                },
                {
                    "Resource": "Post:page:20",
                    "Action": "List",
                    "Effect": "deny"
                },
                {
                    "Resource": "Post:post:hello-world",
                    "Action": [
                        "List",
                        "Delete"
                    ],
                    "Effect": "deny"
                },
                {
                    "Resource": "Post:post:another-post",
                    "Action": "List",
                    "Effect": "deny"
                }
            ]
        }';

        // Get a single instance of the service
        $service = AAM::api()->policies();

        // Create new policy and attach it to current access level
        $policy_id = $service->create($policy);

        $this->assertIsInt($policy_id);

        // Assert that we can fetch statements with wildcard
        $statements = $service->get_statements('Post:post:*');

        // There should be 3 matching statements
        $this->assertEquals(3, count($statements));
        $this->assertArrayHasKey('post:post:hello-world:list', $statements);
        $this->assertArrayHasKey('post:post:hello-world:delete', $statements);
        $this->assertArrayHasKey('post:post:another-post:list', $statements);

        // Assert we can grab an exact statement by resource as-is
        $statements = $service->get_statements('Post:page:20');

        $this->assertEquals(1, count($statements));
        $this->assertArrayHasKey('post:page:20:list', $statements);

        // Assert middle wildcard
        $statements = $service->get_statements('Term:*:uncategorized');

        $this->assertEquals(2, count($statements));
        $this->assertArrayHasKey('term:category:uncategorized:list', $statements);
        $this->assertArrayHasKey('term:category:uncategorized:edit', $statements);
    }

    /**
     * Get statements that are overlapping based on resource and action
     *
     * Make sure that "Enforce" flag is working as expected
     *
     * @return void
     */
    public function testGetCompetingStatements()
    {
        $policy = '{
            "Statement": [
                {
                    "Resource": "Term:category:uncategorized",
                    "Action": [
                        "List",
                        "Edit"
                    ],
                    "Effect": "deny"
                },
                {
                    "Resource": "Term:category:uncategorized",
                    "Action": [
                        "Delete"
                    ],
                    "Effect": "deny"
                },
                {
                    "Resource": "Post:post:hello-world",
                    "Action": "List",
                    "Effect": "deny"
                },
                {
                    "Resource": "Post:post:hello-world",
                    "Action": [
                        "List",
                        "Edit"
                    ],
                    "Effect": "allow",
                    "Enforce": true
                },
                {
                    "Resource": "Post:post:hello-world",
                    "Action": "Edit",
                    "Effect": "deny"
                }
            ]
        }';

        // Get a single instance of the service
        $service = AAM::api()->policies();

        // Create new policy and attach it to current access level
        $policy_id = $service->create($policy);

        $this->assertIsInt($policy_id);

        // Check that Term statements are properly handled
        $statements = $service->get_statements('Term:*');

        $this->assertEquals(3, count($statements));
        $this->assertArrayHasKey('term:category:uncategorized:list', $statements);
        $this->assertArrayHasKey('term:category:uncategorized:edit', $statements);
        $this->assertArrayHasKey('term:category:uncategorized:delete', $statements);

        // Check that Post statements are properly handled
        $statements = $service->get_statements('Post:*');

        $this->assertEquals(2, count($statements));
        $this->assertArrayHasKey('post:post:hello-world:list', $statements);
        $this->assertArrayHasKey('post:post:hello-world:edit', $statements);
        $this->assertEquals('allow', $statements['post:post:hello-world:list']['Effect']);
        $this->assertEquals('allow', $statements['post:post:hello-world:edit']['Effect']);
    }

    /**
     * Test that correct statements are returned based on defined conditions
     *
     * @return void
     */
    public function testConditionalStatements()
    {
        $policy = '{
            "Statement": [
                {
                    "Resource": "Term:category:uncategorized",
                    "Action": "List",
                    "Effect": "deny"
                },
                {
                    "Resource": "Term:category:uncategorized",
                    "Action": [
                        "Delete"
                    ],
                    "Effect": "deny",
                    "Condition": {
                        "Equals": {
                            "(*int)${ARGS.test}": 3
                        }
                    }
                },
                {
                    "Resource": "Term:category:uncategorized",
                    "Action": [
                        "Delete"
                    ],
                    "Effect": "allow",
                    "Condition": {
                        "Equals": {
                            "(*int)${ARGS.test}": 5
                        }
                    }
                }
            ]
        }';

        // Get a single instance of the service
        $service = AAM::api()->policies();

        // Create new policy and attach it to current access level
        $policy_id = $service->create($policy);

        $this->assertIsInt($policy_id);

        // There should be only one statement in return
        $statements = $service->get_statements('Term:*', [ 'test' => 4 ]);

        $this->assertEquals(1, count($statements));
        $this->assertArrayHasKey('term:category:uncategorized:list', $statements);
        $this->assertArrayNotHasKey('term:category:uncategorized:delete', $statements);

        // There should be only two statements in return
        $statements = $service->get_statements('Term:*', [ 'test' => 3 ]);

        $this->assertEquals(2, count($statements));
        $this->assertArrayHasKey('term:category:uncategorized:list', $statements);
        $this->assertArrayHasKey('term:category:uncategorized:delete', $statements);

        // There should be only two statements in return
        $statements = $service->get_statements('Term:*', [ 'test' => 5 ]);

        $this->assertEquals(2, count($statements));
        $this->assertArrayHasKey('term:category:uncategorized:list', $statements);
        $this->assertArrayHasKey('term:category:uncategorized:delete', $statements);
        $this->assertEquals('allow', $statements['term:category:uncategorized:delete']['Effect']);
    }

    /**
     * Test get params
     *
     * @return void
     */
    public function testGetParams()
    {
        $policy = '{
            "Param": [
                {
                    "Key": "option:siteurl",
                    "Value": "home.xyz"
                },
                {
                    "Key": "option:homepage",
                    "Value": "home.xyz/page"
                },
                {
                    "Key": "TestParam",
                    "Value": "hello world"
                }
            ]
        }';

        // Get a single instance of the service
        $service = AAM::api()->policies();

        // Create new policy and attach it to current access level
        $policy_id = $service->create($policy);

        $this->assertIsInt($policy_id);

        // Make sure we can get all params by wildcard
        $params = $service->params('option:*');

        $this->assertEquals(2, count($params));
        $this->assertArrayHasKey('option:siteurl', $params);
        $this->assertArrayHasKey('option:homepage', $params);
        $this->assertEquals('home.xyz', $params['option:siteurl']);
        $this->assertEquals('home.xyz/page', $params['option:homepage']);

        // Make sure we can get an exact param
        $params = $service->get_params('TestParam');

        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('TestParam', $params);
        $this->assertEquals('hello world', $params['TestParam']);
    }

    /**
     * Test competing params
     *
     * @return void
     */
    public function testGetCompetingParams()
    {
        $policy = '{
            "Param": [
                {
                    "Key": "TestParamA",
                    "Value": 12
                },
                {
                    "Key": "TestParamA",
                    "Value": 13
                },
                {
                    "Key": "TestParamB",
                    "Value": 21,
                    "Enforce": true
                },
                {
                    "Key": "TestParamB",
                    "Value": 22
                }
            ]
        }';

        // Get a single instance of the service
        $service = AAM::api()->policies();

        // Create new policy and attach it to current access level
        $policy_id = $service->create($policy);

        $this->assertIsInt($policy_id);

        // Test that two exactly the same params are handled properly
        $params = $service->get_params('TestParamA');

        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('TestParamA', $params);
        $this->assertEquals(13, $params['TestParamA']);

        // Test that Enforce is properly handled
        $params = $service->get_params('TestParamB');

        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('TestParamB', $params);
        $this->assertEquals(21, $params['TestParamB']);
    }

    /**
     * Test that conditional params are properly handled
     *
     * @return void
     */
    public function testGetConditionalParams()
    {
        $policy = '{
            "Param": [
                {
                    "Key": "TestParamA",
                    "Value": "A"
                },
                {
                    "Key": "TestParamA",
                    "Value": "B",
                    "Condition": {
                        "Equals": {
                            "(*int)${ARGS.test}": 3
                        }
                    }
                },
                {
                    "Key": "TestParamA",
                    "Value": "C",
                    "Condition": {
                        "Equals": {
                            "(*int)${ARGS.test}": 5
                        }
                    }
                }
            ]
        }';

        // Get a single instance of the service
        $service = AAM::api()->policies();

        // Create new policy and attach it to current access level
        $policy_id = $service->create($policy);

        $this->assertIsInt($policy_id);

        // Test that correct param is returned based
        $params_a = $service->get_params('TestParamA');
        $params_b = $service->get_params('TestParamA', [ 'test' => 3 ]);
        $params_c = $service->get_params('TestParamA', [ 'test' => 5 ]);

        $this->assertEquals('A', $params_a['TestParamA']);
        $this->assertEquals('B', $params_b['TestParamA']);
        $this->assertEquals('C', $params_c['TestParamA']);
    }

    /**
     * Test that we can properly attach a policy
     *
     * @return void
     */
    public function testPolicyAttach()
    {
        $policy_a = $this->createPost([
            'post_content' => '{}',
            'post_type'    => AAM_Framework_Service_Policies::CPT
        ]);

        $this->assertTrue(AAM::api()->policies()->attach($policy_a));

        // Check DB value
        $raw_settings = $this->readWpOption(AAM_Framework_Service_Settings::DB_OPTION);

        $this->assertArrayHasKey('visitor', $raw_settings);
        $this->assertArrayHasKey('policy', $raw_settings['visitor']);
        $this->assertArrayHasKey($policy_a, $raw_settings['visitor']['policy']);
        $this->assertEquals([
            $policy_a => [ 'effect' => 'attach' ]
        ], $raw_settings['visitor']['policy']);
    }

    /**
     * Test that we can properly detach a policy
     *
     * @return void
     */
    public function testPolicyDetach()
    {
        // Let's create a simple policy first and attach it to the current access
        // level
        $policy_id = AAM::api()->policies()->create('{
            "Statement": {
                "Resource": "Post:page:hello-page",
                "Action": "Read",
                "Effect": "deny"
            }
        }');

        $this->assertIsInt($policy_id);

        // Detach policy
        $this->assertTrue(AAM::api()->policies()->detach($policy_id));

        $raw_settings = $this->readWpOption(AAM_Framework_Service_Settings::DB_OPTION);

        $this->assertArrayHasKey('visitor', $raw_settings);
        $this->assertArrayHasKey('policy', $raw_settings['visitor']);
        $this->assertArrayHasKey($policy_id, $raw_settings['visitor']['policy']);
        $this->assertEquals([
            $policy_id => [ 'effect' => 'detach' ]
        ], $raw_settings['visitor']['policy']);
    }

    /**
     * Test that we can reset all policy settings
     *
     * @return void
     */
    public function testPolicyReset()
    {
        // Let's create a simple policy first and attach it to the current access
        // level
        $policy_id = AAM::api()->policies()->create('{
            "Statement": {
                "Resource": "Post:page:hello-page",
                "Action": "Read",
                "Effect": "deny"
            }
        }');

        $this->assertIsInt($policy_id);

        // Reset settings
        $this->assertTrue(AAM::api()->policies()->reset());

        $raw_settings = $this->readWpOption(AAM_Framework_Service_Settings::DB_OPTION);

        $this->assertArrayHasKey('visitor', $raw_settings);
        $this->assertArrayNotHasKey('policy', $raw_settings['visitor']);
    }

}