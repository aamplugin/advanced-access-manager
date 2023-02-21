<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post & Term option list for the Post object
 *
 * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/89
 * @since 6.2.0 Enhanced HIDDEN option with more granular access controls
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.5.0
 */
class AAM_Backend_View_PostOptionList
{

    /**
     * Get post option list
     *
     * @return array
     *
     * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/89
     * @since 6.2.0 Enhanced HIDDEN option with more granular access controls
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.5.0
     */
    public static function get()
    {
        return array(
            'hidden' => array(
                'title'       => __('Hidden', AAM_KEY),
                'sub'         => __('Hidden Areas', AAM_KEY),
                'modal'       => 'modal-hidden',
                'description' => __('Completely hide the post however, still allow direct access with the valid URL.', AAM_KEY),
            ),
            'restricted' => array(
                'title'       => __('Restricted', AAM_KEY),
                'exclude'     => array('nav_menu_item'),
                'description' => __('Restrict direct access to the post. Any attempt to access the post will be denied and redirected based on the Access Denied Redirect rule.', AAM_KEY)
            ),
            'teaser' => array(
                'title'       => __('Teaser Message', AAM_KEY),
                'sub'         => __('Message', AAM_KEY),
                'modal'       => 'modal-teaser',
                'exclude'     => array('nav_menu_item'),
                'description' => __('Dynamically replace the post content with defined plain text or HTML teaser message.', AAM_KEY)
            ),
            'limited'  => array(
                'title'       => __('Limited', AAM_KEY),
                'sub'         => __('Access Limit', AAM_KEY),
                'modal'       => 'modal-limited',
                'exclude'     => array(AAM_Core_Subject_Visitor::UID, 'nav_menu_item'),
                'description' => __('Define how many times the post can be accessed. When the number of times exceeds the defined threshold, access will be denied and redirected based on the Access Denied Redirect rule.', AAM_KEY)
            ),
            'comment' => array(
                'title'       => __('Leave Comments', AAM_KEY),
                'exclude'     => array('nav_menu_item'),
                'description' => __('Restrict access to leave comments for the post.', AAM_KEY)
            ),
            'redirected'      => array(
                'title'       => __('Redirect', AAM_KEY),
                'sub'         => __('Destination', AAM_KEY),
                'modal'       => 'modal-redirect',
                'exclude'     => array('nav_menu_item'),
                'description' => __('Redirect user based on the defined redirect rule when user tries to access the post. The REDIRECT option has lower precedence and will be ignored if RESTRICTED option is checked.', AAM_KEY),
            ),
            'protected'       => array(
                'title'       => __('Password Protected', AAM_KEY),
                'sub'         => __('Password', AAM_KEY),
                'modal'       => 'modal-password',
                'exclude'     => array('nav_menu_item'),
                'description' => __('Protect access to the post with a password. Available with WordPress 4.7.0 or higher.', AAM_KEY)
            ),
            'ceased' => array(
                'title'       => __('Access Expires', AAM_KEY),
                'sub'         => __('After', AAM_KEY),
                'modal'       => 'modal-cease',
                'exclude'     => array('nav_menu_item'),
                'description' => __('Define when access will expire to the post. After expiration, the access to the post will be denied and redirected based on the Access Denied Redirect rule.', AAM_KEY)
            ),
            'edit' => array(
                'title'       => __('Edit', AAM_KEY),
                'exclude'     => array(AAM_Core_Subject_Visitor::UID, 'nav_menu_item'),
                'description' => __('Restrict access to edit the post.', AAM_KEY)
            ),
            'delete' => array(
                'title'       => __('Delete', AAM_KEY),
                'exclude'     => array(AAM_Core_Subject_Visitor::UID, 'nav_menu_item'),
                'description' => __('Restrict access to trash or permanently delete the post.', AAM_KEY)
            ),
            'publish' => array(
                'title'       => __('Publish', AAM_KEY),
                'exclude'     => array(AAM_Core_Subject_Visitor::UID, 'nav_menu_item'),
                'description' => __('Restrict the ability to publish the post. User will be allowed only to submit the post for review.', AAM_KEY)
            )
        );
    }

}