<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Hooks service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Hooks
{

    use AAM_Service_BaseTrait;

    /**
     * Constructor
     *
     * @access protected
     * @return void
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_action('init', function() {
            AAM::api()->hooks()->listen();
        }, PHP_INT_MAX);
    }

}