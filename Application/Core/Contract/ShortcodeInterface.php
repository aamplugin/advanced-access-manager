<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * AAM shortcode strategy interface
 *
 * @package AAM
 * @version 6.0.0
 */
interface AAM_Core_Contract_ShortcodeInterface
{

    /**
     * Initialize shortcode strategy
     *
     * @param array  $args
     * @param string $content
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function __construct($args, $content);

    /**
     * Process shortcode strategy
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function run();

}