<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM,
    AAM_Core_Object_Policy,
    AAM_Core_Policy_Manager,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test policy manager
 *
 * Make sure that access policies are parsed properly
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class PolicyManagerTest extends TestCase
{
    use ResetTrait;

    /**
     * @inheritDoc
     */
    private static function _setUpBeforeClass()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);
    }

    /**
     * @inheritDoc
     */
    private static function _tearDownAfterClass()
    {
        // Unset the forced user
        wp_set_current_user(0);
    }

    /**
     * Test simple policy load
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testSimplePolicy()
    {
        $stub = $this->prepareManagerStub('simple-policy');

        $this->assertEquals($stub->getTree(), array(
            'Statement' => array(
                'backendmenu:edit.php' => array(
                    array(
                        'Effect' => 'deny'
                    )
                )
            ),
            'Param' => array()
        ));
    }

    /**
     * Test simple policy load
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testSimplePolicyWithAction()
    {
        $stub = $this->prepareManagerStub('simple-policy-with-action');

        $this->assertEquals($stub->getTree(), array(
            'Statement' => array(
                'capability:switch_themes:aam:toggle' => array(
                    array(
                        'Effect' => 'deny'
                    )
                )
            ),
            'Param' => array()
        ));
    }

    /**
     * Test that site options are overwritten by policy
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testOptionOverridePolicy()
    {
        $stub = $this->prepareManagerStub('option-override-policy');

        $this->assertEquals($stub->getTree(), array(
            'Statement' => array(),
            'Param'     => array(
                'option:unittest' => array(
                    array(
                        'Key'   => 'option:unittest',
                        'Value' => 'unititest.me'
                    )
                )
            )
        ));

        $this->assertEquals('unititest.me', get_option('unittest'));
        $this->assertEquals('unititest.me', get_site_option('unittest'));
    }

    /**
     * Test that dynamic markers are replaced with actual value
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDynamicResourcePolicy()
    {
        $GLOBALS['UT'] = 'test';

        $stub = $this->prepareManagerStub('dynamic-resource');

        $this->assertArrayHasKey('post:post:test:read', $stub->getTree()['Statement']);

        unset($GLOBALS['UT']);
    }

    /**
     * Test that dynamic markers are replaced with actual value
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDynamicParamPolicy()
    {
        $GLOBALS['UT'] = 'test';

        $stub = $this->prepareManagerStub('dynamic-param');

        $this->assertArrayHasKey('hello-world-test', $stub->getTree()['Param']);

        unset($GLOBALS['UT']);
    }
    /**
     * Test if param mapping is working as expected
     *
     * @return void
     *
     * @access public
     * @version 6.4.1
     */
    public function testParamMapping()
    {
        $GLOBALS['unit_test'] = array('a', 'b');

        $stub = $this->prepareManagerStub('param-mapping-user-meta');

        $this->assertEquals($stub->getTree(), array(
            'Statement' => array(),
            'Param' => array(
                'param:a' => array(
                    array(
                        'Key'   => 'param:%s => ${PHP_GLOBAL.unit_test}',
                        'Value' => true
                    )
                ),
                'param:b' => array(
                    array(
                        'Key'   => 'param:%s => ${PHP_GLOBAL.unit_test}',
                        'Value' => true
                    )
                )
            ),
        ));
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testGetParam()
    {
        $manager = $this->prepareManagerStub('{
            "Param": {
                "Key": "test",
                "Value": "hello"
            }
        }', false);

        $this->assertEquals("hello", $manager->getParam('test'));
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testGetEnforcedParam()
    {
        $manager = $this->prepareManagerStub('{
            "Param": [
                {
                    "Key": "test",
                    "Value": "hello"
                },
                {
                    "Key": "test",
                    "Enforce": true,
                    "Value": "yes"
                },
                {
                    "Key": "test",
                    "Value": "no"
                }
            ]
        }', false);

        $this->assertEquals("yes", $manager->getParam('test'));
    }

    public function testIsAllowedFunction()
    {
        $manager = $this->prepareManagerStub('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Widget:hello-test"
            }
        }', false);

        $this->assertFalse($manager->isAllowed('Widget:hello-test'));
        $this->assertFalse($manager->isAllowed('widget:Hello-test'));
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testIsAllowedToFunction()
    {
        $manager = $this->prepareManagerStub('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:hello-world",
                "Action": [
                    "Edit"
                ]
            }
        }', false);

        $this->assertFalse($manager->isAllowedTo('post:post:hello-world', 'edit'));
        $this->assertNull($manager->isAllowed('post:post:hello-world-b'));
    }

    /**
     * Prepare proper policy manager stub
     *
     * @param string  $policy
     * @param boolean $is_file
     *
     * @return object
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareManagerStub($policy, $is_file = true)
    {
        // Fake the assigned policy to the user
        $object = AAM::getUser()->getObject(AAM_Core_Object_Policy::OBJECT_TYPE);
        $object->updateOptionItem(1, true)->save();

        // Create a stub for the SomeClass class.
        $stub = $this->getMockBuilder(AAM_Core_Policy_Manager::class)
            ->setConstructorArgs(array(AAM::getUser(), false))
            ->onlyMethods(array('fetchPolicies'))
            ->getMock();

        if ($is_file === true) {
            $policy = file_get_contents(__DIR__ . '/policies/' . $policy . '.json');
        }

        // Configure the stub
        $stub->method('fetchPolicies')->willReturn(array(
            (object) array(
                'ID'           => 1,
                'post_content' => $policy
            )
        ));

        // Initialize the policy tree
        $stub->initialize();

        return $stub;
    }

}