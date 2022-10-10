<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service;

use AAM,
    WP_User_Query,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test User Level Filter service
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class UserLevelFilterTest extends TestCase
{
    use ResetTrait;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_USER_MANAGER_B_USER_ID);

        \AAM_Core_Config::set('core.service.user-level-filter.enabled', true);
        $instance = \AAM_Service_UserLevelFilter::getInstance(true);

        add_filter('editable_roles', array($instance, 'filterRoles'));
        add_action('pre_get_users', array($instance, 'filterUserQuery'), 999);
        add_filter('views_users', array($instance, 'filterViews'));
        add_filter('rest_user_query', array($instance, 'prepareUserQueryArgs'));
    }

    /**
     * @inheritdoc
     */
    private static function _tearDownAfterClass()
    {
        // Unset the forced user
        wp_set_current_user(0);
    }

    /**
     * Test that only allowed roles are returned
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testEditableRoles()
    {
        require_once ABSPATH . '/wp-admin/includes/user.php';

        $roles = get_editable_roles();

        $this->assertFalse(array_key_exists('administrator', $roles));
    }

    /**
     * Test that restricted roles are added to the "excluded" list of roles during
     * search
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPrepareUserQuery()
    {
        $query = new WP_User_Query(array(
            'search' => 'a'
        ));

        $this->assertTrue(
            in_array('administrator', $query->query_vars['role__not_in'], true)
        );
    }

    /**
     * Test that top User List table view does not have restricted roles listed
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testListTableViews()
    {
        if (!isset($GLOBALS['hook_suffix'])) {
            $GLOBALS['hook_suffix'] = 'users';
        }

        require_once ABSPATH . 'wp-admin/includes/admin.php';

        $table = _get_list_table( 'WP_Users_List_Table' , array('screen' => 'users'));

        ob_start();
        $table->views();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertFalse(strpos($content, "class='administrator'"));
    }

    /**
     * Test that subadmin is allowed to manage users with lower user level
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAllowedUserEdit()
    {
        $this->assertTrue(
            current_user_can('edit_user', AAM_UNITTEST_MULTIROLE_USER_ID)
        );
    }

    /**
     * Test that subadmin is not allowed to manage users with higher user level
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testNotAllowedUserEdit()
    {
        $this->assertFalse(current_user_can('edit_user', AAM_UNITTEST_ADMIN_USER_ID));
    }

    /**
     * Test that subadmin is allowed to manage users with the same user level
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAllowedSameLevelUserEdit()
    {
        $this->assertTrue(
            current_user_can('edit_user', AAM_UNITTEST_USER_MANAGER_B_USER_ID)
        );
    }

    /**
     * Test that subadmin is not allowed to manage users with the same user level
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testNotAllowedSameLevelUserEdit()
    {
        // Fake the un assigned `manage_same_user_level`
        $user = AAM::getUser()->getPrincipal();
        $user->caps['aam_manage_same_user_level'] = false;

        $this->assertFalse(
            current_user_can('edit_user', AAM_UNITTEST_USER_MANAGER_B_USER_ID)
        );
    }

}