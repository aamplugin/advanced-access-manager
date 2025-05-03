<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <!DOCTYPE html>
    <html xmlns="https://www.w3.org/1999/xhtml" lang="en-US">

    <head>
        <title>Advanced Access Manager</title>

        <meta charset="UTF-8" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <?php global $wp_styles; ?>

        <?php $wp_styles->do_item('common'); ?>
        <?php $wp_styles->do_item('dashicons'); ?>
        <?php $wp_styles->do_item('aam-vendor'); ?>
        <?php $wp_styles->do_item('aam'); ?>

        <?php do_action('aam_iframe_header_action'); ?>
    </head>

    <body id="aam-container" class="aam-iframe">
<?php }