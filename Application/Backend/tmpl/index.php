<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-iframe-container" style="overflow: hidden; padding-top: 56.25%; position: relative; margin: 0;">
        <iframe src="<?php echo admin_url('admin.php?page=aam&aamframe=main'); ?>" width="100%" height="100%" style="border: 0; height: 100%; left: 0; position: absolute; top: 0; width: 100%;"></iframe>
    </div>
    <style>.wp-admin { background-color: #FFFFFF; }</style>
<?php }