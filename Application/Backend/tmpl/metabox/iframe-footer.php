<?php
    /**
     * @since 6.0.5 Changed the way core libraries are loaded to avoid issue with
     *              concatenated scripts with PHP
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.0.5
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
        <?php global $wp_scripts; ?>

        <?php $wp_scripts->do_items(array('jquery-core', 'jquery-migrate')); ?>
        <?php do_action('aam_iframe_footer_action'); ?>
    </body>
</html>
<?php }