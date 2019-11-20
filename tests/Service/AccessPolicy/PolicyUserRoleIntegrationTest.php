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
    AAM_Core_Policy_Factory,
    AAM_Core_AccessSettings,
    PHPUnit\Framework\TestCase;


/**
 * Test access policy integration with core user roles system
 *
 * @version 6.0.0
 */
class PolicyUserRoleIntegrationTest extends TestCase
{

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
        wp_set_current_user(AAM_UNITTEST_AUTH_USER_ID);

        $this->assertFalse(current_user_can('switch_themes'));
        $this->assertTrue(current_user_can('hello_world'));
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
        wp_set_current_user(AAM_UNITTEST_AUTH_USER_ID);

        $this->assertContains('administrator', AAM::getUser()->roles);
        $this->assertContains('contributor', AAM::getUser()->roles);
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
        $this->preparePlayground('role-remove', AAM_UNITTEST_AUTH_MULTIROLE_USER_ID);

        // Reset current user to trigger policy changes
        wp_set_current_user(AAM_UNITTEST_AUTH_MULTIROLE_USER_ID);

        $this->assertFalse(in_array('editor', AAM::getUser()->roles, true));
        $this->assertContains('subscriber', AAM::getUser()->roles);
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
    protected function preparePlayground($policy_file, $user = AAM_UNITTEST_AUTH_USER_ID)
    {
        global $wpdb;

        // Update existing Access Policy with new policy
        $wpdb->update($wpdb->posts, array('post_content' => file_get_contents(
            __DIR__ . '/policies/' . $policy_file . '.json'
        )), array('ID' => AAM_UNITTEST_ACCESS_POLICY_ID));

        $settings = AAM_Core_AccessSettings::getInstance();
        $settings->set(sprintf(
            'user.%d.policy.%d', $user, AAM_UNITTEST_ACCESS_POLICY_ID
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

        // Unset the forced user
        wp_set_current_user(0);

        // Clear WP core cache
        wp_cache_flush();

        // Reset internal AAM config cache
        AAM_Core_Config::bootstrap();

        // Reset Access Policy Factory cache
        AAM_Core_Policy_Factory::reset();
    }

}