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
 * @since 6.9.16 https://github.com/aamplugin/advanced-access-manager/issues/316
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.16
 */
class AAM_Service_Shortcode_Handler_LoginRedirect
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
     * @since 6.9.16 https://github.com/aamplugin/advanced-access-manager/issues/316
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.16
     */
    public function run()
    {
        $redirect = AAM_Core_Request::server('REQUEST_URI');
        $class    = (isset($this->args['class']) ? $this->args['class'] : '');
        $url      = add_query_arg('reason', 'restricted', wp_login_url($redirect));

        if (empty($this->content)) {
            if (!empty($this->args['label'])) {
                $label = $this->args['label'];
            } else {
                $label = __('Login to continue', AAM_KEY);
            }
        } else {
            $label = $this->content;
        }

        $button  = '<a href="' . esc_attr($url) . '" ';
        $button .= 'class="' . esc_attr($class) . '">' . esc_js($label) . '</a>';

        return $button;
    }

}