<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM shortcode strategy for login form
 *
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/318
 * @since 6.6.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.6.17
 */
class AAM_Service_Shortcode_Handler_LoginForm
{

    /**
     * Shortcode arguments
     *
     * @var array
     *
     * @access protected
     * @version 6.6.0
     */
    protected $args;

    /**
     * Initialize shortcode decorator
     *
     * Expecting attributes in $args are:
     *   "class"    => CSS class for login form
     *   "redirect" => Redirect to URL after successful login
     *
     * @param array $args
     *
     * @return void
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/318
     * @since 6.6.0  Initial implementation of the method
     *
     * @access public
     * @version 6.6.17
     */
    public function __construct($args, $content = null)
    {
        $this->args = array_merge(
            array('class' => '', 'redirect' => ''),
            is_array($args) ? $args : array()
        );
    }

    /**
     * Process the shortcode
     *
     * @return string
     *
     * @access public
     * @version 6.6.0
     */
    public function run()
    {
        return AAM_Backend_View::loadPartial('login-form', array_merge(
            $this->args,
            array('id' => uniqid())
        ));
    }

}