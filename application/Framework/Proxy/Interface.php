<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Proxy interface
 *
 * @package AAM
 * @version 7.0.0
 */
interface AAM_Framework_Proxy_Interface {

    /**
     * Get WordPress core instance
     *
     * @return object
     * @access public
     *
     * @version 7.0.0
     */
    public function get_core_instance();

}