<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <iframe src="<?php echo admin_url('admin.php?page=aam&aamframe=user&id=' . $params->user->ID); ?>" width="100%" height="550" style="border-bottom: 1px solid #e5e5e5; margin-top:10px;"></iframe>
<?php }