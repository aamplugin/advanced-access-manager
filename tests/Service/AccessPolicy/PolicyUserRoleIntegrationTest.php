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
    AAM\UnitTest\Libs\ResetTrait,
    AAM_Service_IdentityGovernance;


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
        AAM_Framework_Manager::configs()->set_config(
            'core.service.identity-governance.enabled', true
        );

        AAM_Service_IdentityGovernance::bootstrap(true);

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
        $this->preparePlayground('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": [
                        "Capability:switch_themes"
                    ]
                },
                {
                    "Effect": "allow",
                    "Resource": [
                        "Capability:hello_world"
                    ]
                }
            ]
        }');

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
        $this->preparePlayground('{
            "Statement": [
                {
                    "Effect": "allow",
                    "Resource": [
                        "Role:contributor"
                    ]
                }
            ]
        }');

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
        $this->preparePlayground('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": [
                        "Role:author"
                    ]
                }
            ]
        }', AAM_UNITTEST_MULTIROLE_USER_ID);

        // Reset current user to trigger policy changes
        wp_set_current_user(AAM_UNITTEST_MULTIROLE_USER_ID);

        $this->assertFalse(in_array('author', AAM::getUser()->roles, true));
        $this->assertContains('subscriber', AAM::getUser()->roles);

        wp_set_current_user(0);
    }

    /**
     * Test if hidden roles are visible
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function testRoleList()
    {
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $roles = get_editable_roles();

        $this->assertContains('author', array_keys($roles));
        $this->assertContains('subscriber', array_keys($roles));

        $this->preparePlayground('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": [
                        "Role:author",
                        "Role:subscriber"
                    ],
                    "Action": "List"
                }
            ]
        }', AAM_UNITTEST_ADMIN_USER_ID);

        $roles = get_editable_roles();

        $this->assertFalse(in_array('author', array_keys($roles), true));
        $this->assertFalse(in_array('subscriber', array_keys($roles), true));

        wp_set_current_user(0);
    }

    /**
     * Test that users that belong to specific role can be filtered out
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function testUserListByRole()
    {
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $users = array_map('intval', get_users([
            'number' => 10,
            'fields' => 'ID'
        ]));

        $this->assertContains(AAM_UNITTEST_MULTIROLE_USER_ID, $users);

        $this->preparePlayground('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": [
                        "Role:subscriber:users"
                    ],
                    "Action": "List"
                }
            ]
        }', AAM_UNITTEST_ADMIN_USER_ID);

        $users = array_map('intval', get_users([
            'number' => 10,
            'fields' => 'ID'
        ]));

        $this->assertFalse(in_array(AAM_UNITTEST_MULTIROLE_USER_ID, $users, true));

        wp_set_current_user(0);
    }

    /**
     * Test that users that belong to specific roles can be managed
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function testUserManagementByRole()
    {
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertTrue(current_user_can('edit_user', AAM_UNITTEST_MULTIROLE_USER_ID));
        $this->assertTrue(current_user_can('delete_user', AAM_UNITTEST_MULTIROLE_USER_ID));
        $this->assertTrue(current_user_can('promote_user', AAM_UNITTEST_MULTIROLE_USER_ID));

        $this->preparePlayground('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": [
                        "Role:subscriber:users"
                    ],
                    "Action": [
                        "Edit",
                        "Delete",
                        "Promote"
                    ]
                }
            ]
        }', AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertFalse(current_user_can('edit_user', AAM_UNITTEST_MULTIROLE_USER_ID));
        $this->assertFalse(current_user_can('delete_user', AAM_UNITTEST_MULTIROLE_USER_ID));
        $this->assertFalse(current_user_can('promote_user', AAM_UNITTEST_MULTIROLE_USER_ID));

        wp_set_current_user(0);
    }

    /**
     * Test that roles are filtered out by access level
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function testRoleLevelList()
    {
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $roles = get_editable_roles();

        $this->assertContains('administrator', array_keys($roles));

        $this->preparePlayground('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": [
                        "RoleLevel:10"
                    ],
                    "Action": "List"
                }
            ]
        }', AAM_UNITTEST_ADMIN_USER_ID);

        $roles = get_editable_roles();

        $this->assertFalse(in_array('administrator', array_keys($roles), true));

        wp_set_current_user(0);
    }

    /**
     * Test that specific users can be filtered out
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function testUserList()
    {
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $users = array_map('intval', get_users([
            'number' => 10,
            'fields' => 'ID'
        ]));

        $this->assertContains(AAM_UNITTEST_MULTIROLE_USER_ID, $users);
        $this->assertContains(AAM_UNITTEST_USER_EDITOR_USER_ID, $users);

        $this->preparePlayground(sprintf('{
            "Statement": {
                "Effect": "deny",
                "Resource": [
                    "User:utmultirole@testing.local",
                    "User:%d"
                ],
                "Action": [
                    "List"
                ]
            }
        }', AAM_UNITTEST_USER_EDITOR_USER_ID), AAM_UNITTEST_ADMIN_USER_ID);

        $users = array_map('intval', get_users([
            'number' => 10,
            'fields' => 'ID'
        ]));

        $this->assertFalse(in_array(AAM_UNITTEST_MULTIROLE_USER_ID, $users, true));
        $this->assertFalse(in_array(AAM_UNITTEST_USER_EDITOR_USER_ID, $users, true));

        wp_set_current_user(0);
    }

    /**
     * Test User resource
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function testUserManageList()
    {
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertTrue(current_user_can('edit_user', AAM_UNITTEST_USER_EDITOR_USER_ID));
        $this->assertTrue(current_user_can('delete_user', AAM_UNITTEST_USER_EDITOR_USER_ID));

        $this->preparePlayground(sprintf('{
            "Statement": {
                "Effect": "deny",
                "Resource": [
                    "User:%d"
                ],
                "Action": [
                    "Edit",
                    "Delete"
                ]
            }
        }', AAM_UNITTEST_USER_EDITOR_USER_ID), AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertFalse(current_user_can('edit_user', AAM_UNITTEST_USER_EDITOR_USER_ID));
        $this->assertFalse(current_user_can('delete_user', AAM_UNITTEST_USER_EDITOR_USER_ID));

        wp_set_current_user(0);
    }

    /**
     * Test that user management is correct by user access level
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function testUserLevelManage()
    {
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertTrue(current_user_can('edit_user', AAM_UNITTEST_USER_EDITOR_USER_ID));
        $this->assertTrue(current_user_can('delete_user', AAM_UNITTEST_USER_EDITOR_USER_ID));

        $this->preparePlayground('{
            "Statement": {
                "Effect": "deny",
                "Resource": [
                    "UserLevel:7"
                ],
                "Action": [
                    "Edit",
                    "Delete"
                ]
            }
        }', AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertFalse(current_user_can('edit_user', AAM_UNITTEST_USER_EDITOR_USER_ID));
        $this->assertFalse(current_user_can('delete_user', AAM_UNITTEST_USER_EDITOR_USER_ID));

        wp_set_current_user(0);
    }

    /**
     * Test that users are filtered by user access level
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function testUserLevelList()
    {
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $users = array_map('intval', get_users([
            'number' => 10,
            'fields' => 'ID'
        ]));

        $this->assertContains(AAM_UNITTEST_ADMIN_USER_ID, $users);

        $this->preparePlayground('{
            "Statement": {
                "Effect": "deny",
                "Resource": [
                    "UserLevel:10"
                ],
                "Action": [
                    "List"
                ]
            }
        }', AAM_UNITTEST_ADMIN_USER_ID);

        $users = array_map('intval', get_users([
            'number' => 10,
            'fields' => 'ID'
        ]));

        $this->assertFalse(in_array(AAM_UNITTEST_ADMIN_USER_ID, $users, true));

        wp_set_current_user(0);
    }

    /**
     * Prepare the environment
     *
     * Update Unit Test access policy with proper policy
     *
     * @param string $policy
     * @param int    $user
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function preparePlayground($policy, $user = AAM_UNITTEST_ADMIN_USER_ID)
    {
        global $wpdb;

        // Update existing Access Policy with new policy
        $wpdb->update(
            $wpdb->posts,
            array('post_content' => $policy),
            array('ID' => self::$policy_id)
        );

        $settings = AAM_Framework_Manager::settings([
            'access_level_type' => 'user',
            'access_level_id'   => $user
        ]);
        $settings->set_setting('policy', [
            self::$policy_id => true
        ]);

        // Resetting all settings as $wpdb->update already initializes it with
        // settings
        \AAM_Core_Policy_Factory::reset();
        $this->_resetSubjects();
    }

}