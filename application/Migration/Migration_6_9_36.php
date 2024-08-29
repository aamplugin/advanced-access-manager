<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Migration class for 6.9.36 release
 *
 * Fixed corrupted access control settings due to the bug introduced in 6.9.35
 *
 * @package AAM
 * @version 6.9.36
 */
final class AAM_Migration_6_9_36 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritDoc
     */
    public function run()
    {
        $service  = AAM_Framework_Manager::settings();
        $settings = $service->get_settings();

        if (is_array($settings)) {
            foreach($settings as $access_level => $data) {
                if (in_array($access_level, [ 'role', 'user' ], true)) {
                    foreach($data as $level_id => $controls) {
                        $settings[$access_level][$level_id] = $this->_fix_corruption(
                            $controls
                        );
                    }
                } else {
                    $settings[$access_level] = $this->_fix_corruption($data);
                }
            }

            $service->set_settings($settings);
        }
    }

    /**
     * Fix corruption
     *
     * @param array $settings
     *
     * @return void
     *
     * @access private
     * @version 6.9.36
     */
    private function _fix_corruption($settings)
    {
        if (array_key_exists('term', $settings)) {
            $new_list = [];

            foreach($settings['term'] as $id => $controls) {
                if (preg_match('/|undefined$/', $id)) {
                    list($term_id, $term_taxonomy) = explode('|', $id);

                    $taxonomy = get_taxonomy($term_taxonomy);

                    if (is_a($taxonomy, 'WP_Taxonomy')) {
                        if (count($taxonomy->object_type) === 1) {
                            $post_type = $taxonomy->object_type[0];

                            $new_list[
                                "{$term_id}|{$term_taxonomy}|{$post_type}"
                            ] = $controls;
                        } else {
                            $new_list["{$term_id}|{$term_taxonomy}"] = $controls;
                        }
                    } else {
                        $new_list["{$term_id}|{$term_taxonomy}"] = $controls;
                    }
                } else {
                    $new_list[$id] = $controls;
                }
            }

            $settings['term'] = $new_list;
        }

        return $settings;
    }

}

if (defined('ABSPATH')) {
    (new AAM_Migration_6_9_36())->run();
}