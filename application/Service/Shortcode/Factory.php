<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Shortcode factory for the [aam] shortcode
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Shortcode_Factory
{

    /**
     * Shortcode handler based on the provided attributes
     *
     * @var object
     * @access protected
     *
     * @version 7.0.0
     */
    protected $handler = null;

    /**
     * Initialize shortcode factory
     *
     * @param array  $args
     * @param string $content
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function __construct($args, $content)
    {
        $cnt = strtolower(!empty($args['context']) ? $args['context'] : 'content');

        if ($cnt === 'content') {
            $this->handler = new AAM_Service_Shortcode_Handler_Content(
                $args, $content
            );
        } elseif ($cnt === 'loginredirect') {
            $this->handler = new AAM_Service_Shortcode_Handler_LoginRedirect(
                $args, $content
            );
        } elseif ($cnt === 'loginform') {
            $this->handler = new AAM_Service_Shortcode_Handler_LoginForm($args);
        } elseif ($cnt === 'postlist') {
            $this->handler = new AAM_Service_Shortcode_Handler_PostList($args);
        } else {
            $this->handler = apply_filters(
                'aam_shortcode_filter', null, $cnt, $args, $content
            );
        }
    }

    /**
     * Process the short-code
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function process()
    {
        $handler = $this->handler;

        return is_object($handler)
            && method_exists($handler, 'run') ? $handler->run() : '';
    }

}