<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) {
    AAM_Backend_View_Helper::loadIframe(
        admin_url('admin.php?page=aam&aamframe=post&id=' . $params->post->ID . '&type=post'),
        'margin-top:10px;',
        'aam-post-iframe'
    );
}