<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM,
    AAM_Core_API,
    AAM_Core_Config,
    AAM_Core_Object_Uri,
    AAM_Core_Policy_Factory,
    AAM_Core_AccessSettings,
    PHPUnit\Framework\TestCase;


/**
 * Test that access policy applies to visitors
 *
 * @version 6.2.0
 */
class PolicyVisitorTest extends TestCase
{

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
            'action' => 'Access Is Denied',
            'code'   => 307
        ), $object->findMatch('/hello-world-2/'));

        $this->assertEquals(array(
            'type'   => 'page',
            'action' => 2,
            'code'   => 307
        ), $object->findMatch('/hello-world-3/'));

        $this->assertEquals(array(
            'type'   => 'page',
            'action' => get_page_by_path('sample-page', OBJECT, 'page')->ID,
            'code'   => 307
        ), $object->findMatch('/hello-world-4'));

        $this->assertEquals(array(
            'type'   => 'url',
            'action' => '/another-location',
            'code'   => 303
        ), $object->findMatch('/hello-world-5'));

        $this->assertEquals(array(
            'type'   => 'callback',
            'action' => 'AAM\\Callback\\Main::helloWorld',
            'code'   => 307
        ), $object->findMatch('/hello-world-6'));

        $this->assertEquals(array(
            'type'   => 'login',
            'action' => null,
            'code'   => 401
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
        )), array('ID' => AAM_UNITTEST_ACCESS_POLICY_ID));

        $settings = AAM_Core_AccessSettings::getInstance();
        $settings->set(sprintf(
            'visitor.policy.%d', AAM_UNITTEST_ACCESS_POLICY_ID
        ), true);
    }

    /**
     * Reset all AAM settings to the default
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function tearDown()
    {
        // Clear all AAM settings
        AAM_Core_API::clearSettings();

        // Reset Access Settings repository
        AAM_Core_AccessSettings::getInstance()->reset();

        // Clear WP core cache
        wp_cache_flush();

        // Reset internal AAM config cache
        AAM_Core_Config::bootstrap();

        // Reset Access Policy Factory cache
        AAM_Core_Policy_Factory::reset();
    }

}