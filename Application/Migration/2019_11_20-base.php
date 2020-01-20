<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\Migration;

use AAM_Core_Migration,
    AAM_Core_AccessSettings,
    AAM_Core_Contract_MigrationInterface;

/**
 * This migration class clears legacy AAM cache options
 *
 * The Migration600 does not take in consideration legacy AAM cache options and this
 * migration script clears them up as well as migration log
 *
 * @package AAM
 * @version 6.0.1
 */
class Migration601 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @version 6.0.1
     */
    public function run()
    {
        // Reset failure log
        AAM_Core_Migration::resetFailureLog();

        // Clear any cache that AAM set in the past
        $this->clearInternalCache();

        // Clear corrupted data from the previous migration script
        $this->clearCorruptedData();

        // Finally store this script as completed
        AAM_Core_Migration::storeCompletedScript(basename(__FILE__));

        return array('errors' => array());
    }

    /**
     * Clear internal AAM cache
     *
     * @return void
     *
     * @access protected
     * @version 6.0.1
     */
    protected function clearInternalCache()
    {
        global $wpdb;

        // Delete AAM internal cache from the _options table
        $opt_query  = "DELETE FROM {$wpdb->options} WHERE (`option_name` LIKE %s) ";
        $opt_query .= "OR (`option_name` LIKE %s)";
        $wpdb->query($wpdb->prepare($opt_query, array('aam_cache_%', 'aam_%_cache')));

        // Fetch access settings from the wp_usermeta table
        $query  = "DELETE FROM {$wpdb->usermeta} WHERE (`meta_key` = %s)";
        $wpdb->query($wpdb->prepare($query, array("{$wpdb->prefix}aam_cache")));
    }

    /**
     * Clear corrupted data from the previously incorrectly executed script
     *
     * @link https://forum.aamplugin.com/d/369-notice-undefined-offset-1-service-content-php-on-line-509
     *
     * @return void
     *
     * @access protected
     * @version 6.0.1
     */
    protected function clearCorruptedData()
    {
        $settings = AAM_Core_AccessSettings::getInstance();

        // Possibly fix role settings
        foreach($settings->get('role') as $role => $data) {
            if (isset($data['post'])) {
                $this->_fixPostSettingsCorruption("role.{$role}", $data['post']);
            }
        }

        // Possibly fix user settings
        foreach($settings->get('user') as $id => $data) {
            if (isset($data['post'])) {
                $this->_fixPostSettingsCorruption("user.{$id}", $data['post']);
            }
        }

        // Possibly fix visitor settings
        $visitor = $settings->get('visitor');
        if (isset($visitor['post'])) {
            $this->_fixPostSettingsCorruption('visitor', $visitor['post']);
        }

        // Possibly fix default settings
        $default = $settings->get('default');
        if (isset($default['post'])) {
            $this->_fixPostSettingsCorruption('default', $default['post']);
        }

        // Save access settings
        $settings->save();
    }

    /**
     * Fix the post settings corruption
     *
     * @param string $prefix
     * @param array  $posts
     *
     * @return void
     *
     * @since 6.0.2 Making sure that get_post returns actually a post object
     * @since 6.0.1 Initial implementation of the method
     *
     * @access private
     * @version 6.0.1
     */
    private function _fixPostSettingsCorruption($prefix, $posts)
    {
        $settings = AAM_Core_AccessSettings::getInstance();

        foreach( $posts as $id => $options) {
            if (strpos($id, '|') === false && is_numeric($id)) {
                $settings->delete("{$prefix}.post.{$id}");
                $post = get_post($id);

                // Making sure that we have actually a post object
                if (is_a($post, 'WP_Post')) {
                    $settings->set(
                        "{$prefix}.post.{$post->ID}|{$post->post_type}", $options
                    );
                }
            }
        }
    }

}

if (defined('AAM_KEY')) {
    return (new Migration601())->run();
}