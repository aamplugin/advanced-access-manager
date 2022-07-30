<?php
    /**
     * @since 6.0.5 Changed the way core libraries are loaded to avoid issue with
     *              concatenated styles with PHP
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.0.5
     * */
?>

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
        <link rel="stylesheet" href="<?php echo AAM_MEDIA; ?>/css/vendor.min.css" type="text/css" media="all" />
        <link rel="stylesheet" href="<?php echo AAM_MEDIA; ?>/css/aam.css" type="text/css" media="all" />

        <?php do_action('aam_iframe_header_action'); ?>
    </head>

    <body id="aam-container" class="aam-iframe">
<?php }