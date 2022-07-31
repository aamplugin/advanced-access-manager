<?php
/**
 * @since 6.8.4 https://github.com/aamplugin/advanced-access-manager/issues/213
 * @since 6.5.1 https://github.com/aamplugin/advanced-access-manager/issues/113
 * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/104
 * @since 6.0.0 Initial implementation of the template
 *
 * @version 6.8.4
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <style>.wp-admin { background-color: #FFFFFF; }</style>
    <?php
        AAM_Backend_View_Helper::loadIframe(
            admin_url('admin.php?page=aam&aamframe=main'),
            'border: 0; width: 100%; min-height: 100vh;'
        );
    ?>
<?php }