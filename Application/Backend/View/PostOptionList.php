<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post option list
 */
class AAM_Backend_View_PostOptionList {

    /**
     * Get post option list
     * 
     * @return array
     * 
     * @access public
     */
    public static function get() {
        return array(
            'frontend' => array(
                'list' => array(
                    'title'  => __('List', AAM_KEY),
                    'descr'  => __('Hide %s however still allow access with direct URL.', AAM_KEY) . sprintf(__(' %sSee in action.%s', AAM_KEY), "<a href='https://youtu.be/2jiu_CL6JJg' target='_blank'>", '</a>'),
                ),
                'read' => array(
                    'title' => __('Read', AAM_KEY),
                    'descr' => __('Restrict access to view, read or download %s. Any attempts to open %s will be denied and redirected based on the Access Denied Redirect rule.', AAM_KEY) . sprintf(__(' %sSee in action.%s', AAM_KEY), "<a href='https://youtu.be/1742nVeGvgs' target='_blank'>", '</a>')
                ),
                'limit' => array(
                    'title'   => __('Limit', AAM_KEY),
                    'sub'     => __('Teaser message', AAM_KEY),
                    'option'  => 'frontend.teaser',
                    'preview' => 'frontend-teaser-preview',
                    'modal'   => 'modal-teaser',
                    'descr'   => __('Replace %s content with defined teaser message.', AAM_KEY)
                ),
                'access_counter' => array(
                    'title'   => __('Read Counter', AAM_KEY),
                    'sub'     => __('Threshold', AAM_KEY),
                    'option'  => 'frontend.access_counter_limit',
                    'preview' => 'frontend-access_counter_limit-preview',
                    'modal'   => 'modal-access-counter',
                    'exclude' => array(AAM_Core_Subject_Visitor::UID),
                    'descr'   => __('Define how many times %s can be read, viewed or download. After number of times exceeds the specified threshold, access will be denied and redirected based on the Access Denied Redirect rule.', AAM_KEY)
                ),
                'comment' => array(
                    'title' => __('Comment', AAM_KEY),
                    'descr' => __('Restrict access to comment on %s if commenting is allowed.', AAM_KEY)
                ),
                'redirect'    => array(
                    'title'   => __('Redirect', AAM_KEY),
                    'sub'     => __('Redirect Rule', AAM_KEY),
                    'option'  => 'frontend.location',
                    'preview' => 'frontend-location-preview',
                    'modal'   => 'modal-redirect',
                    'descr'   => __('Redirect user based on the defined redirect rule when user tries to read the %s. The REDIRECT option will be ignored if READ option is checked.', AAM_KEY),
                ),
                'protected'   => array(
                    'title'   => __('Password Protected', AAM_KEY),
                    'sub'     => __('Password', AAM_KEY),
                    'option'  => 'frontend.password',
                    'preview' => 'frontend-option-preview',
                    'modal'   => 'modal-password',
                    'descr'   => __('Protect access to %s with password. Available with WordPress 4.7.0 or higher.', AAM_KEY)
                ),
                'expire' => array(
                    'title'   => __('Access Expiration', AAM_KEY),
                    'sub'     => __('Expires', AAM_KEY),
                    'option'  => 'frontend.expire_datetime',
                    'preview' => 'frontend-expire_datetime-preview',
                    'modal'   => 'modal-access-expires',
                    'descr'   => __('Define when access will expire for %s.', AAM_KEY) . sprintf(__('After expiration, the access to %s will be denied and redirected based on the Access Denied Redirect rule. For more information %scheck this article%s or ', AAM_KEY), '%s', "<a href='https://aamplugin.com/article/how-to-set-expiration-date-for-any-wordpress-content' target='_blank'>", '</a>') . sprintf(__(' %ssee in action.%s', AAM_KEY), "<a href='https://youtu.be/IgtgVoWs35w' target='_blank'>", '</a>')
                ),
                'monetize' => array(
                    'title'   => __('Monetized Access', AAM_KEY),
                    'sub'     => __('E-Product', AAM_KEY),
                    'option'  => 'frontend.eproduct',
                    'preview' => 'frontend-eproduct-preview',
                    'modal'   => 'modal-eproduct',
                    'exclude' => array(AAM_Core_Subject_Visitor::UID),
                    'descr'   => sprintf(AAM_Backend_View_Helper::preparePhrase('[Premium feature!] Start selling access to %s. Access will be granted to open %s only if selected E-Product had been purchased. For more information %scheck this article%s.', 'b'), '%s', '%s', "<a href='https://aamplugin.com/article/how-to-monetize-access-to-the-wordpress-content' target='_blank'>", '</a>')
                )
            ),
            'backend' => array(
                'list' => array(
                    'title'   => __('List', AAM_KEY),
                    'exclude' => array(AAM_Core_Subject_Visitor::UID),
                    'descr'   => __('Hide %s however still allow access with direct URL.', AAM_KEY),
                ),
                'edit' => array(
                    'title'   => __('Edit', AAM_KEY),
                    'exclude' => array(AAM_Core_Subject_Visitor::UID),
                    'descr'   => __('Restrict access to edit %s. Any attempts to edit %s will result in redirecting user based on the Access Denied Redirect rule.', AAM_KEY)
                ),
                'delete' => array(
                    'title'   => __('Delete', AAM_KEY),
                    'exclude' => array(AAM_Core_Subject_Visitor::UID),
                    'descr'   => __('Restrict access to trash or permanently delete %s.', AAM_KEY)
                ),
                'publish' => array(
                    'title'   => __('Publish', AAM_KEY),
                    'exclude' => array(AAM_Core_Subject_Visitor::UID),
                    'descr'   => __('Restrict access to publish %s. User will be allowed only to submit %s for review.', AAM_KEY)
                )
            ),
            'api' => array(
                'list' => array(
                    'title'  => __('List', AAM_KEY),
                    'descr'  => __('Hide %s however still allow access to retrieve %s.', AAM_KEY),
                ),
                'read' => array(
                    'title' => __('Read', AAM_KEY),
                    'descr' => __('Restrict access to retrieve %s. Any attempts to retrieve %s will be denied.', AAM_KEY)
                ),
                'limit' => array(
                    'title'   => __('Limit', AAM_KEY),
                    'sub'     => __('Teaser message', AAM_KEY),
                    'option'  => 'api.teaser',
                    'preview' => 'api-teaser-preview',
                    'modal'   => 'modal-teaser',
                    'descr'   => __('Replace %s content with defined teaser message.', AAM_KEY)
                ),
                'access_counter' => array(
                    'title'   => __('Read Counter', AAM_KEY),
                    'sub'     => __('Threshold', AAM_KEY),
                    'option'  => 'api.access_counter_limit',
                    'preview' => 'api-access_counter_limit-preview',
                    'modal'   => 'modal-access-counter',
                    'exclude' => array(AAM_Core_Subject_Visitor::UID),
                    'descr'   => __('Define how many times %s can be retrieved. After number of time exceeds the defined threshold, the access will be denied to %s.', AAM_KEY)
                ),
                'comment' => array(
                    'title' => __('Comment', AAM_KEY),
                    'descr' => __('Restrict access to comment on %s if commenting feature is enabled.', AAM_KEY)
                ),
                'protected' => array(
                    'title'   => __('Password Protected', AAM_KEY),
                    'sub'     => __('Password', AAM_KEY),
                    'option'  => 'api.password',
                    'preview' => 'api-option-preview',
                    'modal'   => 'modal-password',
                    'descr'   => __('Protected %s with password. Available with WordPress 4.7.0 or higher.', AAM_KEY)
                ),
                'expire' => array(
                    'title'   => __('Access Expiration', AAM_KEY),
                    'sub'     => __('Expires', AAM_KEY),
                    'option'  => 'api.expire_datetime',
                    'preview' => 'api-expire_datetime-preview',
                    'modal'   => 'modal-access-expires',
                    'descr'   => __('Define when access expires to %s.', AAM_KEY) . sprintf(__('After expiration, the access to %s will be denied. For more information %scheck this article%s or ', AAM_KEY), '%s', "<a href='https://aamplugin.com/article/how-to-set-expiration-date-for-any-wordpress-content' target='_blank'>", '</a>')
                ),
                'edit' => array(
                    'title'   => __('Update', AAM_KEY),
                    'exclude' => array(AAM_Core_Subject_Visitor::UID),
                    'descr'   => __('Restrict access to update %s. Any attempts to update %s will be denied.', AAM_KEY)
                ),
                'delete' => array(
                    'title'   => __('Delete', AAM_KEY),
                    'exclude' => array(AAM_Core_Subject_Visitor::UID),
                    'descr'   => __('Restrict access to trash or permanently delete %s.', AAM_KEY)
                )
            )
        );
    }
}