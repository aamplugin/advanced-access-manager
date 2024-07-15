<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM shortcode strategy for post list
 *
 * @package AAM
 * @version 6.9.35
 */
class AAM_Service_Shortcode_Handler_PostList
    implements AAM_Core_Contract_ShortcodeInterface
{

    /**
     * Shortcode arguments
     *
     * @var array
     *
     * @access protected
     * @version 6.9.35
     */
    protected $args;

    /**
     * Initialize shortcode decorator
     *
     * Expecting attributes in $args are the list of arguments that WP_Query receives
     *
     * @param array $args
     *
     * @return void
     *
     * @access public
     * @version 6.9.35
     */
    public function __construct($args, $content = null)
    {
        // Determine correct default status & post type
        $post_type = isset($args['post_type']) ? $args['post_type'] : 'post';
        $status    = $post_type === 'attachment' ? 'inherit' : 'publish';

        $this->args = array_merge(
            [
                'post_type'   => 'post',
                'nopaging'    => true,
                'post_status' => $status,
                'template'    => 'template-parts/content'
            ],
            is_array($args) ? $args : []
        );
    }

    /**
     * Process the shortcode
     *
     * @return string
     *
     * @access public
     * @version 6.9.35
     */
    public function run()
    {
        // Making sure that post type is public
        $post_type = get_post_type_object($this->args['post_type']);

        if (is_a($post_type, 'WP_Post_Type') && $post_type->public) {
            $result = AAM_Backend_View::loadPartial('post-list', array_merge(
                $this->args,
                array('id' => uniqid())
            ));
        } else {
            $result = null;
        }

        return $result;
    }

}