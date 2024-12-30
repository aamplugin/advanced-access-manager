<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) { ?>
        <?php global $wp_scripts; ?>

        <?php $wp_scripts->do_items(array('jquery-core', 'jquery-migrate', 'code-editor', 'aam-iframe')); ?>
        <?php do_action('aam_iframe_footer_action'); ?>

        <?php $wp_scripts->do_item('aam-iframe'); ?>
    </body>
</html>
<?php }