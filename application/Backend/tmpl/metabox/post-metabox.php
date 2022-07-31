<?php
/**
 * @since 6.8.4 https://github.com/aamplugin/advanced-access-manager/issues/213
 * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/104
 * @since 6.0.0 Initial implementation of the template
 *
 * @version 6.8.4
 **/

if (defined('AAM_KEY')) {
    AAM_Backend_View_Helper::loadIframe(
        admin_url('admin.php?page=aam&aamframe=post&id=' . $params->post->ID . '&type=post'),
        'margin-top:10px;',
        'aam-post-iframe'
    );
}