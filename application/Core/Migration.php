<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Core Migration class
 *
 * @package AAM
 * @version 7.0.0
 */
final class AAM_Core_Migration
{

    /**
     * DB option that stores list of migration scripts that were completed
     *
     * @version 7.0.0
     */
    const DB_OPTION = 'aam_migrations';

    /**
     * Run the pending scripts
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function run()
    {
        $completed = AAM::api()->db->read(self::DB_OPTION);

        foreach(self::get_pending() as $script) {
            if (file_exists($script)) {
                $results     = include $script;
                $completed[] = basename($script);

                AAM::api()->db->write(self::DB_OPTION, $completed);
            } else {
                $results = [];
            }

            return $results;
        }
    }

    /**
     * Get list of migrations that are still pending to be executed
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public static function get_pending()
    {
        $completed = AAM::api()->db->read(self::DB_OPTION, []);
        $pending   = [];
        $iterator  = null;
        $dirname   = dirname(__DIR__) . '/Migration';

        if (file_exists($dirname)) {
            $iterator = new DirectoryIterator($dirname);
        }

        if (is_a($iterator, DirectoryIterator::class)) {
            foreach ($iterator as $mg) {
                if ($mg->isFile() && !in_array($mg->getFilename(), $completed, true)) {
                    $pending[]  = $mg->getPathname();
                }
            }
        }

        return $pending;
    }

}