<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM custom shortcodes service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Shortcodes
{

    use AAM_Service_BaseTrait;

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        if (!is_admin()) {
            add_shortcode('aam', function ($args, $content) {
                $shortcode = new AAM_Service_Shortcode_Factory($args, $content);

                return $shortcode->process();
            });

            add_shortcode('aam-login', function($args, $content) {
                $shortcode = new AAM_Service_Shortcode_Handler_LoginForm(
                    $args, $content
                );

                return $shortcode->run();
            });

            add_shortcode('aam-post-list', function($args, $content) {
                $shortcode = new AAM_Service_Shortcode_Handler_PostList(
                    $args, $content
                );

                return $shortcode->run();
            });
        }
    }

}