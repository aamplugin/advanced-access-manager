<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM,
    AAM_Core_AccessSettings,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;


/**
 * Test the core policy functionality
 *
 * @version 6.5.3
 */
class PolicyCoreTest extends TestCase
{

    use ResetTrait;

    /**
     * Test that "denied" policy is selected because second statement is not applicable
     *
     * @return void
     *
     * @access public
     * @version 6.5.3
     */
    public function testMultiResourceTargetDenied()
    {
        $this->preparePlayground('multi-resource-target-conditional');

        $post = AAM::getUser()->getObject('post', AAM_UNITTEST_POST_ID);

        $this->assertTrue($post->is('restricted'));
    }

    /**
     * Test that "allowed" policy is selected because second statement is not
     * applicable
     *
     * @return void
     *
     * @access public
     * @version 6.5.3
     */
    public function testMultiResourceTargetAllowed()
    {
        $_GET['q'] = 'testing';

        $this->preparePlayground('multi-resource-target-conditional');
        $post = AAM::getUser()->getObject('post', AAM_UNITTEST_POST_ID);

        $this->assertFalse($post->is('restricted'));

        unset($_GET['q']);
    }

    /**
     * Test that access is allowed because statement is enforced
     *
     * @return void
     *
     * @access public
     * @version 6.5.3
     */
    public function testMultiResourceEnforcedAllowed()
    {
        $this->preparePlayground('multi-resource-enforced-statement');
        $post = AAM::getUser()->getObject('post', AAM_UNITTEST_POST_ID);

        $this->assertFalse($post->is('restricted'));
    }

    /**
     * Test that access is allowed because statement is enforced
     *
     * @return void
     *
     * @access public
     * @version 6.5.3
     */
    public function testMultiResourceEnforcedReversedAllowed()
    {
        $this->preparePlayground('multi-resource-enforced-statement-reversed');
        $post = AAM::getUser()->getObject('post', AAM_UNITTEST_POST_ID);

        $this->assertFalse($post->is('restricted'));
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
     * @version 6.5.3
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

}