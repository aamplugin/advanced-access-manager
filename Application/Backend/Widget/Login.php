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
 * Secure login widget
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Widget_Login extends WP_Widget
{

    /**
     * Widget arguments
     *
     * @var array
     *
     * @access public
     * @version 6.0.0
     */
    public $args = array();

    /**
     * Constructor
     *
     * @access public
     *
     * @return void
     * @version 6.0.0
     */
    public function __construct()
    {
        $options = array(
            'description' => __('AAM Secure Login Widget', AAM_KEY)
        );

        parent::__construct(false, __('AAM Secure Login', AAM_KEY), $options);
    }

    /**
     * Get frontend widget template
     *
     * @param array $args
     * @param array $instance
     *
     * @access public
     *
     * @return string
     * @version 6.0.0
     */
    public function widget($args, $instance)
    {
        $this->args = array_merge($args, $this->normalize($instance));

        require AAM_Core_Config::get(
            'service.secureLogin.settings.widget.template',
            realpath(dirname(__DIR__) . '/tmpl/widget/login-frontend.php')
        );
    }

    /**
     * Generate backend form for the widget
     *
     * @param array $instance
     *
     * @access public
     *
     * @return void
     * @version 6.0.0
     */
    public function form($instance)
    {
        $instance = $this->normalize($instance);

        require dirname(__DIR__) . '/tmpl/widget/login-backend.php';
    }

    /**
     * Normalize widget's settings
     *
     * @param array $instance
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function normalize($instance)
    {
        if (empty($instance['login-title'])) {
            $instance['login-title'] = __('Login', AAM_KEY);
        }

        if (empty($instance['user-title'])) {
            $instance['user-title'] = __('Howdy, %username%', AAM_KEY);
        }

        $instance['redirect'] = filter_input(INPUT_GET, 'redirect_to');

        return $instance;
    }

}