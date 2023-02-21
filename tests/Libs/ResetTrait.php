<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Libs;

use AAM,
    AAM_Core_API,
    AAM_Core_Config,
    AAM_Core_AccessSettings,
    AAM_Core_Policy_Factory;

/**
 * Reset access settings after each test
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
trait ResetTrait
{

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass() : void
    {
        global $wpdb;

        if (defined('AAM_UNITTEST_RESET_DB') && AAM_UNITTEST_RESET_DB) {
            // Reset the database
            $wpdb->query("TRUNCATE TABLE {$wpdb->posts}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->postmeta}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->term_relationships}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->term_taxonomy}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->termmeta}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->terms}");

            static::_setUpBeforeClass();
        }
    }

    private static function _setUpBeforeClass()
    {
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass() : void
    {
        global $wpdb;

        // Reset the database
        $wpdb->query("TRUNCATE TABLE {$wpdb->posts}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->postmeta}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->term_relationships}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->term_taxonomy}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->termmeta}");
        $wpdb->query("TRUNCATE TABLE {$wpdb->terms}");

        // Create one default post, page and post category
        $post_id = wp_insert_post(array(
            'post_title'  => 'Sample Post',
            'post_status' => 'publish'
        ));

        wp_insert_post(array(
            'post_title'  => 'Sample Page',
            'post_status' => 'publish',
            'post_type'   => 'page'
        ));

        $term = wp_insert_term('Uncategorized', 'category');

        wp_set_post_terms($post_id, $term['term_id'], 'category');

        // Reset Global Headers
        $GLOBALS['UT_HTTP_HEADERS'] = array();

        static::_tearDownAfterClass();
    }

    private static function _tearDownAfterClass()
    {
    }

    /**
     * Reset all AAM settings to the default
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function tearDown() : void
    {
        // Clear all AAM settings
        AAM_Core_API::clearSettings();

        // Reset Access Settings repository
        AAM_Core_AccessSettings::getInstance()->reset();

        // Also clear all the internal caching
        $this->_resetSubjects();

        if (is_subclass_of(self::class, 'AAM\UnitTest\Libs\MultiRoleOptionInterface')) {
            // Enable Multiple Role Support
            AAM_Core_Config::set('core.settings.multiSubject', true);
        }

        // Clear WP core cache
        wp_cache_flush();

        // Reset internal AAM config cache
        AAM_Core_Config::bootstrap();

        // Reset Access Policy Factory cache
        AAM_Core_Policy_Factory::reset();

        // Reset internal content cache
        if (class_exists('AAM\AddOn\PlusPackage\Hooks\ContentHooks')) {
            AAM\AddOn\PlusPackage\Hooks\ContentHooks::bootstrap()->resetCache();
        }
    }

    /**
     * Reset all subjects
     *
     * AAM Subject has internal cache that stored already initiated objects for
     * performance reasons. Reset the cache to allow inheritance mechanism to go
     * through.
     *
     * @return void
     *
     * @access private
     * @see AAM_Core_Subject::getObject
     * @version 6.0.0
     */
    private function _resetSubjects()
    {
        $subject = AAM::getUser();

        do {
            // Take in consideration that a subject can have multiple parent subjects
            // when "Multiple Roles Support" is enabled
            $subject->flushCache();
            if ($subject->hasSiblings()) {
                $siblings = $subject->getSiblings();
                array_walk($siblings, function($sibling) {
                    $sibling->flushCache();
                });
            }
        } while ($subject = $subject->getParent());
    }

}