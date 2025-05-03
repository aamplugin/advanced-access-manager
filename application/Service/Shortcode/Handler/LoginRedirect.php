<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM shortcode strategy for login button
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Shortcode_Handler_LoginRedirect
{

    /**
     * Shortcode arguments
     *
     * @var array
     * @access protected
     *
     * @version 7.0.0
     */
    protected $args;

    /**
     * Wrapped by shortcode content
     *
     * @var string
     * @access protected
     *
     * @version 7.0.0
     */
    protected $content;

    /**
     * Initialize shortcode decorator
     *
     * Expecting attributes in $args are:
     *   "class"    => CSS class for login button
     *   "label"    => if stand-alone shortcode then defined text label will be used
     *
     * @param type $args
     * @param type $content
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function __construct($args, $content)
    {
        $this->args    = $args;
        $this->content = $content;
    }

    /**
     * Process the shortcode
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function run()
    {
        $redirect = AAM::api()->misc->get($_SERVER, 'REQUEST_URI');
        $class    = (isset($this->args['class']) ? $this->args['class'] : '');
        $url      = add_query_arg('reason', 'restricted', wp_login_url($redirect));

        if (empty($this->content)) {
            if (!empty($this->args['label'])) {
                $label = $this->args['label'];
            } else {
                $label = __('Login to continue', 'advanced-access-manager');
            }
        } else {
            $label = $this->content;
        }

        $button  = '<a href="' . esc_attr($url) . '" ';
        $button .= 'class="' . esc_attr($class) . '">' . esc_js($label) . '</a>';

        return $button;
    }

}