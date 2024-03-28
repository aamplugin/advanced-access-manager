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
 * Test the various effects
 *
 * @version 6.9.24
 */
class PolicyEffectTest extends TestCase
{

    use ResetTrait;

    /**
     * Test the standard "allow" effect
     *
     * @return void
     *
     * @access public
     */
    public function testAllowEffect()
    {
        $manager = $this->prepareManagerStub('{
            "Statement": {
                "Resource": "Post:post:hello-world",
                "Effect": "allow"
            }
        }');

        $this->assertTrue($manager->isAllowed('Post:post:hello-world'));
        $this->assertTrue($manager->isAllow('Post:post:hello-world'));
        $this->assertEquals(null, $manager->isAllowed('Post:post:another-post'));
    }

    /**
     * Test the standard "allow" effect with action
     *
     * @return void
     *
     * @access public
     */
    public function testAllowWithActionEffect()
    {
        $manager = $this->prepareManagerStub('{
            "Statement": {
                "Resource": "Post:post:hello-world",
                "Effect": "allow",
                "Action": [
                    "List",
                    "Read"
                ]
            }
        }');

        $this->assertTrue($manager->isAllowedTo('Post:post:hello-world', 'list'));
        $this->assertTrue($manager->isAllowTo('Post:post:hello-world', 'read'));
        $this->assertEquals(null, $manager->isAllowedTo('Post:post:hello-world', 'redirect'));
        $this->assertEquals(null, $manager->isAllowedTo('Post:post:hello-world', 'limit'));
        $this->assertEquals(null, $manager->isAllowedTo('Post:post:another-post', 'list'));
    }

    /**
     * Test the standard "deny" effect
     *
     * @return void
     *
     * @access public
     */
    public function testDenyEffect()
    {
        $manager = $this->prepareManagerStub('{
            "Statement": {
                "Resource": "Post:post:posts",
                "Effect": "deny"
            }
        }');

        $this->assertTrue($manager->isDenied('Post:post:posts'));
        $this->assertTrue($manager->isDeny('Post:post:posts'));
        $this->assertEquals(null, $manager->isDenied('Post:page:posts'));
    }

    /**
     * Prepare proper policy manager stub
     *
     * @param string $policy
     *
     * @return object
     *
     * @access protected
     * @version 6.9.24
     */
    protected function prepareManagerStub($policy)
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
                'post_content' => $policy
            )
        ));

        // Initialize the policy tree
        $stub->initialize();

        return $stub;
    }


}