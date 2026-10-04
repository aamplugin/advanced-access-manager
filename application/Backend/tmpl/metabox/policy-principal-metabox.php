<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) {
    if (current_user_can('aam_manager') && current_user_can('aam_manage_policies')) {
        echo '<div id="aam-policy-assignee-root" data-policy-id="'
            . esc_attr($params->post->ID) . '"></div>';
    } else {
        echo '<p>' . esc_html__(
            'You do not have permission to manage policy assignments.',
            'advanced-access-manager'
        ) . '</p>';
    }
}
