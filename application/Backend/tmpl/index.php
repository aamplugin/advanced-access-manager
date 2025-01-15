<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <style>.wp-admin { background-color: #FFFFFF; }</style>
    <?php
        AAM_Backend_View_Helper::loadIframe(
            admin_url('admin.php?page=aam&aamframe=main'),
            'border: 0; width: 100%; min-height: 100vh;'
        );
    ?>
<?php }