<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="visitor-message">
        <span class="aam-bordered"><?php echo __('Manage default access to your website resources for all users, roles and visitor. This includes Administrator role and your user', AAM_KEY); ?>.</span>
        <button class="btn btn-danger btn-block" id="manage-default"><i class="icon-cog"></i> <?php echo __('Manage Default Access', AAM_KEY); ?></button>
    </div>
<?php }