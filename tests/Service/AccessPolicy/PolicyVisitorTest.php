<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM,
    AAM_Core_Object_Uri,
    AAM_Core_AccessSettings,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;


/**
 * Test that access policy applies to visitors
 *
 * @version 6.2.0
 */
class PolicyVisitorTest extends TestCase
{

    use ResetTrait;

    /**
     * Policy ID placeholder
     *
     * @var int
     *
     * @access protected
     * @version 6.7.0
     */
    protected static $policy_id;

    /**
     * Targeting page ID
     *
     * @var int
     *
     * @access protected
     * @version 6.7.0
     */
    protected static $page_id;

    /**
     * @inheritDoc
     */
    private static function _setUpBeforeClass()
    {
        // Setup a default policy placeholder
        self::$policy_id = wp_insert_post(array(
            'post_title'  => 'Unittest Policy Placeholder',
            'post_status' => 'publish',
            'post_type'   => 'aam_policy'
        ));

        // Setup a default page
        self::$page_id = wp_insert_post(array(
            'post_title'  => 'Policy Service Integration',
            'post_name'   => 'policy-service-integration-page',
            'post_type'   => 'page',
            'post_status' => 'publish'
        ));
    }

    /**
     * Test that policy actually applied to visitor
     *
     * @return void
     *
     * @access public
     * @version 6.2.0
     */
    public function testPolicyApplied()
    {
        $this->preparePlayground('uri');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);

        $this->assertEquals(array(
            'type'   => 'default',
            'action' => null
        ), $object->findMatch('/hello-world-1'));

        $this->assertEquals(array(
            'type'   => 'message',
            'action' => 'Access Is Denied'
        ), $object->findMatch('/hello-world-2/'));

        $this->assertEquals(array(
            'type'   => 'page',
            'action' => 2
        ), $object->findMatch('/hello-world-3/'));

        $this->assertEquals(array(
            'type'   => 'page',
            'action' => get_page_by_path('policy-service-integration-page', OBJECT, 'page')->ID
        ), $object->findMatch('/hello-world-4'));

        $this->assertEquals(array(
            'type'   => 'url',
            'action' => '/another-location',
            'code'   => 303
        ), $object->findMatch('/hello-world-5'));

        $this->assertEquals(array(
            'type'   => 'callback',
            'action' => 'AAM\\Callback\\Main::helloWorld'
        ), $object->findMatch('/hello-world-6'));

        $this->assertEquals(array(
            'type'   => 'login',
            'action' => null
        ), $object->findMatch('/hello-world-7/'));
    }

    /**
     * Prepare the environment
     *
     * Update Unit Test access policy with proper policy
     *
     * @param string $policy_file
     *
     * @return void
     *
     * @access protected
     * @version 6.2.0
     */
    protected function preparePlayground($policy_file)
    {
        global $wpdb;

        // Update existing Access Policy with new policy
        $wpdb->update($wpdb->posts, array('post_content' => file_get_contents(
            __DIR__ . '/policies/' . $policy_file . '.json'
        )), array('ID' => self::$policy_id));

        $settings = AAM_Core_AccessSettings::getInstance();
        $settings->set(sprintf(
            'visitor.policy.%d', self::$policy_id
        ), true);

        // Resetting all settings as $wpdb->update already initializes it with
        // settings
        \AAM_Core_Policy_Factory::reset();
        $this->_resetSubjects();
    }

}