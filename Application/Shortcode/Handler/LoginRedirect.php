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
 * AAM shortcode strategy for login button
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Shortcode_Handler_LoginRedirect
    implements AAM_Core_Contract_ShortcodeInterface
{

    /**
     * Shortcode arguments
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected $args;

    /**
     * Wrapped by shortcode content
     *
     * @var string
     *
     * @access protected
     * @version 6.0.0
     */
    protected $content;

    /**
     * Initialize shortcode decorator
     *
     * Expecting attributes in $args are:
     *   "class"    => CSS class for login button
     *   "callback" => callback function that returns the login button
     *   "label"    => if stand-alone shortcode then defined text label will be used
     *
     * @param type $args
     * @param type $content
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
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
     *
     * @access public
     * @version 6.0.0
     */
    public function run()
    {
        $redirect = AAM_Core_Request::server('REQUEST_URI');
        $class    = (isset($this->args['class']) ? $this->args['class'] : '');

        if (isset($this->args['callback'])) {
            $button = call_user_func($this->args['callback'], $this);
        } else {
            $url = add_query_arg('reason', 'restricted', wp_login_url($redirect));

            if (empty($this->content)) {
                if (!empty($this->args['label'])) {
                    $label = $this->args['label'];
                } else {
                    $label = __('Login to continue', AAM_KEY);
                }
            } else {
                $label = $this->content;
            }

            $button  = '<a href="' . $url . '" ';
            $button .= 'class="' . $class . '">' . $label . '</a>';
        }

        return $button;
    }

}