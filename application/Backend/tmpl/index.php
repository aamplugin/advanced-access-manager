<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <style>.wp-admin { background-color: #FFFFFF; }</style>
    <?php
        $sub_page = isset($_GET['aam_page']) ? $_GET['aam_page'] : 'main';
        AAM_Backend_View_Helper::loadIframe(
            admin_url('admin.php?page=aam&aamframe=main&aam_page=' . $sub_page),
            'border: 0; width: 100%; min-height: 100vh;'
        );
    ?>
<?php }