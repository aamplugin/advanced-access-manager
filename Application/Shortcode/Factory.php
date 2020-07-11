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
 * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/90
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.6.0
 */
class AAM_Shortcode_Factory
{

    /**
     * Shortcode handler based on the provided attributes
     *
     * @var AAM_Core_Contract_ShortcodeInterface
     *
     * @access protected
     * @version 6.0.0
     */
    protected $handler = null;

    /**
     * Initialize shortcode factory
     *
     * @param array  $args
     * @param string $content
     *
     * @return void
     *
     * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/90
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.0
     */
    public function __construct($args, $content)
    {
        $cnt = strtolower(!empty($args['context']) ? $args['context'] : 'content');

        if ($cnt === 'content') {
            $this->handler = new AAM_Shortcode_Handler_Content($args, $content);
        } elseif ($cnt === 'loginredirect') {
            $this->handler = new AAM_Shortcode_Handler_LoginRedirect($args, $content);
        } elseif ($cnt === 'loginform') {
            $this->handler = new AAM_Shortcode_Handler_LoginForm($args);
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
     *
     * @access public
     * @version 6.0.0
     */
    public function process()
    {
        $content = null;

        if (is_a($this->handler, 'AAM_Core_Contract_ShortcodeInterface')) {
            $content = $this->handler->run();
        } else {
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                'No valid strategy found for the given context',
                AAM_VERSION
            );
        }

        return $content;
    }

}