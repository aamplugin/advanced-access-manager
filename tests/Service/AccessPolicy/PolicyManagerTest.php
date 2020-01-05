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
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait;

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
    use ResetTrait,
        AuthUserTrait;

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
                    'Effect' => 'deny'
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
                    'Effect' => 'deny'
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
                    'Key'   => 'option:unittest',
                    'Value' => 'unititest.me'
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
        $stub = $this->prepareManagerStub('dynamic-resource');

        $this->assertArrayHasKey('post:post:1:read', $stub->getTree()['Statement']);
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
        $stub = $this->prepareManagerStub('dynamic-param');

        $this->assertArrayHasKey('hello-world-admin', $stub->getTree()['Param']);
    }

    /**
     * Prepare proper policy manager stub
     *
     * @param string $policy_file
     *
     * @return object
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareManagerStub($policy_file)
    {
        // Fake the assigned policy to the user
        $object = AAM::getUser()->getObject(AAM_Core_Object_Policy::OBJECT_TYPE);
        $object->updateOptionItem(1, true)->save();

        // Create a stub for the SomeClass class.
        $stub = $this->getMockBuilder(AAM_Core_Policy_Manager::class)
            ->setConstructorArgs(array(AAM::getUser(), false))
            ->setMethods(array('fetchPolicies'))
            ->getMock();

        // Configure the stub
        $stub->method('fetchPolicies')->willReturn(array(
            (object) array(
                'ID'           => 1,
                'post_content' => file_get_contents(
                    __DIR__ . '/policies/' . $policy_file . '.json'
                )
            )
        ));

        // Initialize the policy tree
        $stub->initialize();

        return $stub;
    }

}