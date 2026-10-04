<?php /** @version 7.1.4 **/

if (defined('AAM_KEY')) {
    echo '<div id="aam-term-access-root" data-term-id="'
        . esc_attr($params->term->term_id) . '" data-taxonomy="'
        . esc_attr($params->term->taxonomy) . '" data-term-name="'
        . esc_attr($params->term->name) . '"></div>';
}
