<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract class for each backend UI feature
 *
 * @package AAM
 * @version 7.0.0
 */
abstract class AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the service
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manager';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = null;

    /**
     * Get content
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function __invoke()
    {
        return $this->get_content();
    }

    /**
     * Get HTML content
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function get_content()
    {
        ob_start();
        require_once(dirname(__DIR__) . '/tmpl/' . static::TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Get currently managed access level
     *
     * @return AAM_Backend_AccessLevel
     * @access public
     *
     * @version 7.0.0
     */
    public function get_access_level()
    {
        return AAM_Backend_AccessLevel::get_instance();
    }

    /**
     * Register feature
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register() {}

}