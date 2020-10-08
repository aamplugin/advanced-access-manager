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
 * Test access policy integration with core user roles system
 *
 * @version 6.0.0
 */
class PolicyUserRoleIntegrationTest extends TestCase
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
    }

    /**
     * Test that policy allows to assign or deprive specific capabilities
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testCapabilityAdded()
    {
        $this->preparePlayground('capability-changes');

        // Reset current user to trigger policy changes
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertFalse(current_user_can('switch_themes'));
        $this->assertTrue(current_user_can('hello_world'));

        wp_set_current_user(0);
    }

    /**
     * Test that policy allows to add new role to user
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAddedRole()
    {
        $this->preparePlayground('role-add');

        // Reset current user to trigger policy changes
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertContains('administrator', AAM::getUser()->roles);
        $this->assertContains('contributor', AAM::getUser()->roles);

        wp_set_current_user(0);
    }

    /**
     * Test that policy allows to add new role to user
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRemovedRole()
    {
        $this->preparePlayground('role-remove', AAM_UNITTEST_MULTIROLE_USER_ID);

        // Reset current user to trigger policy changes
        wp_set_current_user(AAM_UNITTEST_MULTIROLE_USER_ID);

        $this->assertFalse(in_array('editor', AAM::getUser()->roles, true));
        $this->assertContains('subscriber', AAM::getUser()->roles);

        wp_set_current_user(0);
    }

    /**
     * Prepare the environment
     *
     * Update Unit Test access policy with proper policy
     *
     * @param string $policy_file
     * @param int    $user
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function preparePlayground($policy_file, $user = AAM_UNITTEST_ADMIN_USER_ID)
    {
        global $wpdb;

        // Update existing Access Policy with new policy
        $wpdb->update($wpdb->posts, array('post_content' => file_get_contents(
            __DIR__ . '/policies/' . $policy_file . '.json'
        )), array('ID' => self::$policy_id));

        $settings = AAM_Core_AccessSettings::getInstance();
        $settings->set(sprintf(
            'user.%d.policy.%d', $user, self::$policy_id
        ), true);

        // Resetting all settings as $wpdb->update already initializes it with
        // settings
        \AAM_Core_Policy_Factory::reset();
        $this->_resetSubjects();
    }

}