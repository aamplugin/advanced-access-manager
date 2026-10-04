<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) {
    $post_id = $params->post->post_status === 'auto-draft'
        ? 0 : $params->post->ID;
    echo '<div id="aam-post-access-root" data-post-id="'
        . esc_attr($post_id) . '"></div>';
}
