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
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/357
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.26
 */
final class AAM_Core_Migration
{

    /**
     * DB option that stores list of migration scripts that were completed
     *
     * @version 6.0.0
     */
    const DB_OPTION = 'aam_migrations';

    /**
     * DB option that stores the entire migration log
     *
     * @version 6.0.0
     */
    const DB_FAILURE_OPTION = 'aam_migration_failures';

    /**
     * Run the pending scripts
     *
     * @return void
     *
     * @access public
     * @version 6.9.10
     */
    public static function run()
    {
        foreach(self::getPending() as $script) {
            self::executeScript($script);
        }
    }

    /**
     * Get list of migrations that are still pending to be executed
     *
     * @return array
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/357
     * @since 6.3.0  Optimized AAM_Core_API::getOption call
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.26
     */
    public static function getPending()
    {
        $completed = AAM_Core_API::getOption(self::DB_OPTION);
        $pending   = array();
        $iterator  = self::getDirectoryIterator();

        if (is_a($iterator, DirectoryIterator::class)) {
            foreach ($iterator as $mg) {
                if ($mg->isFile() && !in_array($mg->getFilename(), $completed, true)) {
                    $pending[]  = $mg->getPathname();
                }
            }
        }

        return $pending;
    }

    /**
     * Store failure log
     *
     * @param array $log
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public static function storeFailureLog($log)
    {
        return AAM_Core_API::updateOption(self::DB_FAILURE_OPTION, $log);
    }

    /**
     * Get migration failure log
     *
     * @return array
     *
     * @since 6.3.0 Optimized AAM_Core_API::getOption call
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.0
     */
    public static function getFailureLog()
    {
        return AAM_Core_API::getOption(self::DB_FAILURE_OPTION);
    }

    /**
     * Clear failure log from the database
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.1
     */
    public static function resetFailureLog()
    {
        return AAM_Core_API::deleteOption(self::DB_FAILURE_OPTION);
    }

    /**
     * Store completed script
     *
     * @param string $file_name
     *
     * @return boolean
     *
     * @since 6.3.0 Optimized AAM_Core_API::getOption call
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.0
     */
    public static function storeCompletedScript($file_name)
    {
        $completed   = AAM_Core_API::getOption(self::DB_OPTION);
        $completed[] = $file_name;

        return AAM_Core_API::updateOption(self::DB_OPTION, $completed);
    }

    /**
     * Execute migration script
     *
     * @param string $file_path
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public static function executeScript($file_path)
    {
        if (file_exists($file_path)) {
            $results = include $file_path;

            self::storeCompletedScript(basename($file_path));
        } else {
            $results = [];
        }

        return $results;
    }

    /**
     * Check if there is at least one pending migration script
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public static function hasPending()
    {
        return (count(self::getPending()) > 0);
    }

    /**
     * Get migration scripts directory iterator
     *
     * @return DirectoryIterator
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/357
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.26
     */
    protected static function getDirectoryIterator()
    {
        $iterator = null;
        $dirname  = dirname(__DIR__) . '/Migration';

        if (file_exists($dirname)) {
            $iterator = new DirectoryIterator($dirname);
        }

        return $iterator;
    }

}