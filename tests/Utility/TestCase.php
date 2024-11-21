<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Utility;

use InvalidArgumentException,
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
     * Create a test post
     *
     * @param array $post_data
     *
     * @return integer
     *
     * @access public
     */
    public function createPost(array $post_data = []) : int
    {
        $result = $this->_createPost($post_data);

        // If the input data contains terms, it means, we are assigning this post
        // to either existing terms or creating new terms
        if (!empty($post_data['terms'])) {
            $terms    = [];
            $taxonomy = null;

            foreach((array) $post_data['terms'] as $term_identifier) {
                if(is_numeric($term_identifier)) {
                    $term = get_term($term_identifier, '', ARRAY_A);
                } else {
                    $term = get_term_by('slug', $term_identifier, '', ARRAY_A);
                }

                // If term does not exist - create one
                if ($term === false) {
                    $term = $this->_createTerm([ 'slug' => $term_identifier ]);
                }

                // Capture term
                array_push($terms, $term['term_id']);
                $taxonomy = $term['taxonomy'];
            }

            if (!empty($terms)) {
                wp_set_post_terms($result['ID'], $terms, $taxonomy);
            }
        }

        // Storing this in shared fixtures
        if (!array_key_exists('posts', $this->fixtures)) {
            $this->fixtures['posts'] = [];
        }

        $this->fixtures['posts'][$result['ID']] = $result;

        return $result['ID'];
    }

    /**
     * Create a test term
     *
     * @param array $term_data
     *
     * @return integer
     *
     * @access public
     */
    public function createTerm(array $term_data = []) : int
    {
        $result = self::_createTerm($term_data);

        // Storing this in shared fixtures
        if (!array_key_exists('terms', $this->fixtures)) {
            $this->fixtures['terms'] = [];
        }

        $this->fixtures['terms'][$result['term_id']] = $result;

        return $result['term_id'];
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
     * Read option from DB
     *
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     */
    public function readWpOption($option, $default = null)
    {
        if (is_multisite()) {
            $result = get_blog_option(get_current_blog_id(), $option, $default);
        } else {
            $result = get_option($option, $default);
        }

        return $result;
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
        // Reset DB tables
        self::resetTables();

        // Re-building default user & content
        $user_result = self::_createUser([
            'user_login' => 'admin',
            'user_email' => 'admin@aamportal.local',
            'first_name' => 'John',
            'last_name'  => 'Smith',
            'role'       => 'administrator',
            'user_pass'  => constant('AAM_UNITTEST_DEFAULT_ADMIN_PASS')
        ]);

        // Create a default term
        self::_createTerm([
            'name' => 'Uncategorized'
        ]);

        // Create sample post & page
        self::_createPost([
            'post_title' => 'Sample Post'
        ]);
        self::_createPost([
            'post_title' => 'Sample Page',
            'post_type'  => 'page'
        ]);

        file_put_contents(__DIR__ . '/../../.default.setup.json', json_encode([
            'admin_user' => $user_result
        ]));
    }

    /**
     * Reset DB tables
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function resetTables()
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
     * Capture output
     *
     * @param callback $cb
     *
     * @return string
     *
     * @access public
     */
    public function captureOutput($cb)
    {
        // Start the buffer
        ob_start();

        // Execute the callback and trigger output
        $cb();

        // Capture output and close the buffer
        $content = ob_get_contents();
        ob_end_clean();

        return trim($content);
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

    /**
     * Create a test term
     *
     * @param array $term_data
     *
     * @return array
     *
     * @access private
     */
    private static function _createTerm(array $term_data) : array
    {
        $default_term_data = [
            'name'     => 'Test Term: ' . uniqid(),
            'taxonomy' => 'category'
        ];

        $final = array_merge($default_term_data, $term_data);

        return wp_insert_term($final['name'], $final['taxonomy'], $final);
    }

    /**
     * Create a test post
     *
     * @param array $post_data
     *
     * @return array
     *
     * @access private
     */
    private static function _createPost(array $post_data) : array
    {
        $default_post_data = [
            'post_type'   => 'post',
            'post_status' => 'publish',
            'post_title'  => 'UnitTest Sample: ' . uniqid()
        ];

        $final_post_data = array_merge($default_post_data, $post_data);
        $post_id         = wp_insert_post($final_post_data);

        return array_merge([ 'ID' => $post_id ], $default_post_data);
    }

}