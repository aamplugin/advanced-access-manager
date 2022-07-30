<?php
    /**
     * @since 6.5.1 https://github.com/aamplugin/advanced-access-manager/issues/113
     * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/104
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.5.1
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <style>.wp-admin { background-color: #FFFFFF; }</style>
    <iframe src="<?php echo admin_url('admin.php?page=aam&aamframe=main'); ?>" width="100%" id="aam-iframe" style="border: 0; height: 100%; width: 100%; min-height: 450px;"></iframe>
    <script><?php echo file_get_contents(AAM_BASEDIR . '/media/js/iframe-resizer.js'); ?></script>
<?php }