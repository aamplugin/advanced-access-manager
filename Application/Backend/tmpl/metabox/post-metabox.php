<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <iframe src="<?php echo admin_url('admin.php?page=aam&aamframe=post&id=' . $params->post->ID . '&type=post'); ?>" width="100%" height="450" style="border-bottom: 1px solid #e5e5e5; margin-top:10px;"></iframe>
<?php }