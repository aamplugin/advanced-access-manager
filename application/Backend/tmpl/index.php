<?php
/**
 * @since 6.9.46 https://github.com/aamplugin/advanced-access-manager/issues/431
 * @since 6.8.4  https://github.com/aamplugin/advanced-access-manager/issues/213
 * @since 6.5.1  https://github.com/aamplugin/advanced-access-manager/issues/113
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/104
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.46
 * */
 $aam_page = isset($_GET['aam_page']) ? $_GET['aam_page'] : 'main';
?>

<?php if (defined('AAM_KEY')) { ?>
    <style>.wp-admin { background-color: #FFFFFF; }</style>
    <?php
        AAM_Backend_View_Helper::loadIframe(
            admin_url('admin.php?page=aam&aamframe=main&aam_page=' . $aam_page),
            'border: 0; width: 100%; min-height: 100vh;'
        );
    ?>
<?php }