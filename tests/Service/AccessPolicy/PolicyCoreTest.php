<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM,
    AAM_Framework_Manager,
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
     * Targeting post ID
     *
     * @var int
     *
     * @access protected
     * @version 6.7.0
     */
    protected static $post_id;

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
     * Test setup
     *
     * @return void
     *
     * @access private
     * @static
     * @version 6.7.0
     */
    private static function _setUpBeforeClass()
    {
        // Setup a default post
        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Policy Core Test',
            'post_name'   => 'policy-core-test',
            'post_status' => 'publish'
        ));

        // Setup a default policy placeholder
        self::$policy_id = wp_insert_post(array(
            'post_title'  => 'Unittest Policy Placeholder',
            'post_status' => 'publish',
            'post_type'   => 'aam_policy'
        ));
    }

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

        $post = AAM::getUser()->getObject('post', self::$post_id);

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
        $post = AAM::getUser()->getObject('post', self::$post_id);

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
        $post = AAM::getUser()->getObject('post', self::$post_id);

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
        $post = AAM::getUser()->getObject('post', self::$post_id);

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
        )), array('ID' => self::$policy_id));

        // Resetting all settings as $wpdb->update already initializes it with
        // settings
        \AAM_Core_Policy_Factory::reset();
        $this->_resetSubjects();

        $settings = AAM_Framework_Manager::settings([
            'access_level' => 'visitor'
        ]);

        $settings->set_setting('policy', [
            self::$policy_id => true
        ]);
    }

}