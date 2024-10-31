<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Utility;

use AAM,
    InvalidArgumentException,
    PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Framework manager test
 */
class TestCase extends PHPUnitTestCase
{

    /**
     * Collection of shared fixtures
     *
     * @var array
     *
     * @access protected
     */
    protected $fixtures = [];

    /**
     * Create a test user
     *
     * @param array $user_data
     *
     * @return integer
     *
     * @access public
     */
    public function createUser(array $user_data) : int
    {
        $result = self::_createUser($user_data);

        // Storing this in shared fixtures
        if (!array_key_exists('users', $this->fixtures)) {
            $this->fixtures['users'] = [];
        }

        $this->fixtures['users'][$result['id']] = $result;

        return $result['id'];
    }

    /**
     * Get create user data
     *
     * @param int $user_id
     *
     * @return array
     *
     * @access public
     */
    public function getUserFixture($user_id)
    {
        if (!array_key_exists($user_id, $this->fixtures['users'])) {
            throw new InvalidArgumentException(
                "User with ID {$user_id} does not exist"
            );
        }

        return $this->fixtures['users'][$user_id];
    }

    /**
     * Clear all resources
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function tearDownAfterClass(): void
    {
        global $wpdb;

        // Resetting all users
        $wpdb->query("TRUNCATE TABLE {$wpdb->users}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->usermeta}");

        // Resetting content
        $wpdb->query("TRUNCATE TABLE {$wpdb->posts}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->postmeta}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->terms}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->termmeta}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->term_taxonomy}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->term_relationships}");

        // Re-building default user & content
        $user_result = self::_createUser([
            'user_login' => 'admin',
            'user_email' => 'admin@aamportal.local',
            'first_name' => 'John',
            'last_name'  => 'Smith',
            'role'       => 'administrator',
            'user_pass'  => constant('AAM_UNITTEST_DEFAULT_ADMIN_PASS')
        ]);

        file_put_contents(__DIR__ . '/../../.default.setup.json', json_encode([
            'admin_user' => $user_result
        ]));
    }

    /**
     * Reset AAM settings to default
     *
     * @return void
     */
    public function tearDown() : void
    {
        global $wpdb;

        // Resetting all AAM settings
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'aam_%'");

        // Clear entire WP cache
        wp_cache_flush();

        // Reset user
        wp_set_current_user(0);
    }

    /**
     * Create a test user
     *
     * @param array $user_data
     *
     * @return array
     *
     * @access private
     */
    private static function _createUser(array $user_data) : array
    {
        $user_login        = uniqid();
        $default_user_data = [
            'user_login' => $user_login,
            'user_email' => $user_login . '@aamportal.unit',
            'first_name' => ucfirst(uniqid()),
            'last_name'  => ucfirst(uniqid()),
            'role'       => 'subscriber',
            'user_pass'  => wp_generate_password()
        ];

        $final_user_data = array_merge($default_user_data, $user_data);
        $user_id         = wp_insert_user($final_user_data);

        return array_merge([ 'id' => $user_id ], $final_user_data);
    }

}