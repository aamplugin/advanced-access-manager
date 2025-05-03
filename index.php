<?php

namespace AAM;

// Let's determine if core WP load file reachable
$wp_load_fl = realpath(dirname(__DIR__, 3) . '/wp-load.php');

if (is_string($wp_load_fl) && file_exists($wp_load_fl)) {
    require_once $wp_load_fl;

    global $wp_query;

    if (is_a($wp_query, \WP_Query::class)) {
        $wp_query->set_404();
    }

    status_header(404);
    nocache_headers();

    $not_found_tmpl = get_404_template();

    if (!empty($not_found_tmpl) && file_exists($not_found_tmpl)) {
        include $not_found_tmpl;
    }
} else {
    http_response_code(404);
}