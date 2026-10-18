<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Secure Login block for the block and site editors.
 *
 * @package AAM
 * @version 8.0.2
 */
class AAM_Backend_Block_SecureLogin
{

    /**
     * Register the dynamic block and its editor script.
     *
     * @return void
     * @access public
     * @static
     * @version 8.0.2
     */
    public static function register()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'aam-secure-login-block',
            plugins_url('media/js/secure-login-block.js', AAM_BASEDIR . '/aam.php'),
            [ 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ],
            AAM_VERSION,
            true
        );
        wp_set_script_translations(
            'aam-secure-login-block',
            'advanced-access-manager',
            AAM_BASEDIR . '/lang'
        );

        register_block_type(__DIR__ . '/SecureLogin/block.json', [
            'render_callback' => [ __CLASS__, 'render' ]
        ]);
    }

    /**
     * Render the widget inside a block wrapper.
     *
     * @param array $attributes Block attributes
     *
     * @return string
     * @access public
     * @static
     * @version 8.0.2
     */
    public static function render($attributes)
    {
        if (!defined('AAM_KEY')) {
            return '';
        }

        $widget = new AAM_Backend_Widget_Login();
        $widget->_set(wp_unique_id('aam-block-'));

        ob_start();

        $widget->widget([
            'before_widget' => '<div ' . get_block_wrapper_attributes() . '>',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="aam-secure-login-title">',
            'after_title'   => '</h3>'
        ], [
            'login-title' => isset($attributes['loginTitle'])
                ? $attributes['loginTitle'] : '',
            'user-title' => isset($attributes['userTitle'])
                ? $attributes['userTitle'] : '',
            'redirect' => isset($attributes['redirect'])
                ? $attributes['redirect'] : ''
        ]);

        return ob_get_clean();
    }

}
