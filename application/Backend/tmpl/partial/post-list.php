<?php

/** @version 6.9.35 */

if (defined('AAM_KEY')) {
    $old_query = $GLOBALS['wp_query'];

    $m = query_posts([
        'post_type'   => $params->post_type,
        'nopaging'    => filter_var($params->nopaging, FILTER_VALIDATE_BOOL),
        'post_status' => $params->post_status
    ]);

    while (have_posts()) {
        the_post();

        // Trying to render the template. In no luck, default to plain list
        ob_start();
        get_template_part(
            $params->template,
            get_theme_mod( 'display_excerpt_or_full_post', 'excerpt' )
        );
        $content = ob_get_contents();
        ob_end_clean();

        if (empty($content)) {
            $post = get_post();

            the_title(sprintf('<a href="%s">', esc_url(get_permalink())), '</a>');
        } else {
            echo $content;
        }
    }

    $GLOBALS['wp_query'] = $old_query;
}