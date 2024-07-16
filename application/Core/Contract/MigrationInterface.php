<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Migration interface
 *
 * @package AAM
 * @version 6.0.0
 */
interface AAM_Core_Contract_MigrationInterface
{
    /**
     * Trigger migration script
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function run();

}