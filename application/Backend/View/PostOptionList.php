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
                'title'         => __('Hidden', AAM_KEY),
                'modal'         => 'modal-hidden',
                'scope'         => [
                    AAM_Framework_Type_Resource::POST,
                    AAM_Framework_Type_Resource::TERM,
                    AAM_Framework_Type_Resource::POST_TYPE,
                    AAM_Framework_Type_Resource::TAXONOMY
                ],
                'documentation' => 'https://aamportal.com'
            ),
            'restricted' => array(
                'title'         => __('Restricted', AAM_KEY),
                'exclude'       => [ 'nav_menu_item' ],
                'modal'         => 'modal-restricted',
                'scope'         => [
                    AAM_Framework_Type_Resource::POST,
                    AAM_Framework_Type_Resource::TERM,
                    AAM_Framework_Type_Resource::POST_TYPE,
                    AAM_Framework_Type_Resource::TAXONOMY
                ],
                'documentation' => 'https://aamportal.com'
            ),
            'comment' => array(
                'title'         => __('Leave Comments', AAM_KEY),
                'exclude'       =>[ 'nav_menu_item' ],
                'scope'         => [
                    AAM_Framework_Type_Resource::POST,
                    AAM_Framework_Type_Resource::POST_TYPE
                ],
                'documentation' => 'https://aamportal.com'
            ),
            'edit' => array(
                'title'         => __('Edit', AAM_KEY),
                'exclude'       => [ AAM_Framework_Type_AccessLevel::VISITOR, 'nav_menu_item' ],
                'scope'         => [
                    AAM_Framework_Type_Resource::POST,
                    AAM_Framework_Type_Resource::TERM,
                    AAM_Framework_Type_Resource::POST_TYPE,
                    AAM_Framework_Type_Resource::TAXONOMY
                ],
                'documentation' => 'https://aamportal.com'
            ),
            'delete' => array(
                'title'         => __('Delete', AAM_KEY),
                'exclude'       => [ AAM_Framework_Type_AccessLevel::VISITOR, 'nav_menu_item' ],
                'scope'         => [
                    AAM_Framework_Type_Resource::POST,
                    AAM_Framework_Type_Resource::TERM,
                    AAM_Framework_Type_Resource::POST_TYPE,
                    AAM_Framework_Type_Resource::TAXONOMY
                ],
                'documentation' => 'https://aamportal.com'
            ),
            'publish' => array(
                'title'         => __('Publish', AAM_KEY),
                'exclude'       => [ AAM_Framework_Type_AccessLevel::VISITOR, 'nav_menu_item' ],
                'scope'         => [
                    AAM_Framework_Type_Resource::POST,
                    AAM_Framework_Type_Resource::POST_TYPE
                ],
                'documentation' => 'https://aamportal.com'
            )
        );
    }

}