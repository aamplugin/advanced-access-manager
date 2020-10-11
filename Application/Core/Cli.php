<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM CLI commands
 *
 * @package AAM
 * @version 6.7.0
 */
class AAM_Core_Cli
{

    /**
     * Export AAM settings
     *
     * ## OPTIONS
     *
     * [--roles]
     * : Export list of roles and capabilities
     *
     * [--settings]
     * : Export AAM access controls
     *
     * [--policies]
     * : Export defined access policies
     *
     * [--configpress]
     * : Export ConfigPress
     *
     * ## EXAMPLES
     *
     *  wp aam export --roles
     *  wp aam export --settings --policies
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return void
     *
     * @access public
     * @subcommand export
     * @version 6.7.0
     */
    public function export($args, $assoc_args)
    {
        $manager  = new AAM_Backend_Feature_Settings_Manager();
        $settings = $manager->exportSettings(true);

        if ($settings) {
            if (is_iterable($assoc_args) && count($assoc_args)) {
                $dataset = array();

                foreach(array_keys($assoc_args) as $group) {
                    if (isset($settings['dataset'][$group])) {
                        $dataset[$group] = $settings['dataset'][$group];
                    }
                }

                $settings['dataset'] = $dataset;
            }

            echo json_encode($settings);
        } else {
            WP_CLI::error('Failed to export settings');
        }
    }

    /**
     * Import AAM settings
     *
     * ## OPTIONS
     *
     * [<payload>]
     * : JSON-formatted payload if omitted, the payload is taken from STDIN
     *
     * [--roles]
     * : Import list of roles and capabilities
     *
     * [--settings]
     * : Import AAM access controls
     *
     * [--policies]
     * : Import defined access policies
     *
     * [--configpress]
     * : Import ConfigPress
     *
     * ## EXAMPLES
     *
     *  wp aam import < /tmp/settings.json
     *  wp aam import < ./settings.json --settings --policies
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return void
     *
     * @access public
     * @subcommand import
     * @version 6.7.0
     */
    public function import($args, $assoc_args)
    {
        $manager = new AAM_Backend_Feature_Settings_Manager();

        if (isset($args[0])) {
            $payload = json_decode($args[0], true);
        } else {
            $payload = json_decode(
                WP_CLI::get_value_from_arg_or_stdin($args, 1), true
            );
        }

        if (is_iterable($assoc_args) && count($assoc_args)) {
            $dataset = array();

            foreach(array_keys($assoc_args) as $group) {
                if (isset($payload['dataset'][$group])) {
                    $dataset[$group] = $payload['dataset'][$group];
                }
            }

            $payload['dataset'] = $dataset;
        }

        $result = json_decode($manager->importSettings($payload));

        if ($result->status === 'success') {
            WP_CLI::success('Settings imported successfully');
        } else {
            WP_CLI::error($result->reason);
        }
    }

}