<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Secure login widget
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Widget_Login extends WP_Widget
{

    /**
     * Widget arguments
     *
     * @var array
     * @access public
     *
     * @version 7.0.0
     */
    public $args = [];

    /**
     * Constructor
     *
     * @access public
     * @return void
     *
     * @version 6.0.0
     */
    public function __construct()
    {
        $options = array(
            'description' => __('AAM Secure Login Widget', 'advanced-access-manager')
        );

        parent::__construct(false, __('AAM Secure Login', 'advanced-access-manager'), $options);
    }

    /**
     * Get frontend widget template
     *
     * @param array $args
     * @param array $instance
     *
     * @access public
     * @return string
     *
     * @version 7.0.0
     */
    public function widget($args, $instance)
    {
        $this->args = array_merge($args, $this->normalize($instance));

        require AAM::api()->config->get(
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
     * @return void
     *
     * @version 7.0.0
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
     * @access protected
     *
     * @version 7.0.0
     */
    protected function normalize($instance)
    {
        if (empty($instance['login-title'])) {
            $instance['login-title'] = __('Login', 'advanced-access-manager');
        }

        if (empty($instance['user-title'])) {
            $instance['user-title'] = __(
                'Howdy, %username%',
                'advanced-access-manager'
            );
        }

        $instance['redirect'] = filter_input(INPUT_GET, 'redirect_to');

        return $instance;
    }

}