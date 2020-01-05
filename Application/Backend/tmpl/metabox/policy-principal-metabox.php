<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <iframe src="<?php echo admin_url('admin.php?page=aam&aamframe=principal&id=' . $params->post->ID); ?>" width="100%" height="450" style="border: 0; margin-top:0;" id="policy-principal"></iframe>
<?php }