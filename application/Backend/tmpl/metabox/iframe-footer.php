<?php
/**
 * @since 6.8.4 https://github.com/aamplugin/advanced-access-manager/issues/212
 * @since 6.8.4 https://github.com/aamplugin/advanced-access-manager/issues/213
 * @since 6.0.5 Changed the way core libraries are loaded to avoid issue with
 *              concatenated scripts with PHP
 * @since 6.0.0 Initial implementation of the template
 *
 * @version 6.8.4
 **/
if (defined('AAM_KEY')) { ?>
        <?php global $wp_scripts; ?>

        <?php $wp_scripts->do_items(array('jquery-core', 'jquery-migrate', 'code-editor')); ?>
        <?php do_action('aam_iframe_footer_action'); ?>

        <script><?php echo file_get_contents(AAM_BASEDIR . '/media/js/iframe-content.js'); ?></script>
    </body>
</html>
<?php }