<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) {
    AAM_Backend_View_Helper::loadIframe(
        admin_url('admin.php?page=aam&aamframe=principal&id=' . $params->post->ID),
        'border: 0; margin-top:0;',
        'aam-principal-iframe'
    );
}