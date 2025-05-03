<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend view helper
 *
 * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
 * @since 6.8.4  https://github.com/aamplugin/advanced-access-manager/issues/213
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.21
 */
class AAM_Backend_View_Helper
{

    /**
     * Was resizer library already loaded?
     *
     * @var boolean
     *
     * @access protected
     * @static
     *
     * @version 6.8.4
     */
    protected static $isResizerLoaded = false;

    /**
     * Prepare phrase or label
     *
     * @param string $phrase
     * @param mixed  $...
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    public static function preparePhrase($phrase)
    {
        // Prepare search patterns
        $num    = func_num_args();
        $search = ($num > 1 ? array_fill(0, ($num - 1) * 2, null) : array());

        array_walk($search, 'AAM_Backend_View_Helper::prepareWalk');

        $replace = array();
        foreach (array_slice(func_get_args(), 1) as $key) {
            array_push($replace, "<{$key}>", "</{$key}>");
        }

        // Localize the phase first
        return preg_replace($search, $replace, __($phrase, 'advanced-access-manager'), 1);
    }

    /**
     * Prepare the wrapper replacement
     *
     * @param string $value
     * @param int    $index
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function prepareWalk(&$value, $index)
    {
        $value = '/\\' . ($index % 2 ? ']' : '[') . '/';
    }

    /**
     * Prepare and print iframe HTML markup
     *
     * @param string $url
     * @param string $style
     * @param string $id
     *
     * @return void
     *
     * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
     * @since 6.8.4  Initial implementation of the method
     *
     * @access public
     * @static
     *
     * @version 6.9.21
     */
    public static function loadIframe($url, $style = null, $id = 'aam-iframe')
    {
        echo '<iframe src="' . esc_url($url) . '" width="100%" id="' . esc_attr($id) . '" style="' . esc_attr($style) . '"></iframe>';

        if (!self::$isResizerLoaded) {
            echo '<script>' . file_get_contents(AAM_BASEDIR . '/media/js/iframe-resizer.js') . '</script>';
            self::$isResizerLoaded = true;
        }

        echo '<script>iFrameResize({ log: false  }, "#' . $id . '");</script>';
    }

}