<?php /** @version 7.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="visitor-message">
        <span class="aam-bordered"><?php echo __('Manage default access to your website resources for all users, roles and visitor. This includes Administrator role and your user', 'advanced-access-manager'); ?>.</span>
        <button class="btn btn-danger btn-block" id="manage-default"><i class="icon-cog"></i> <?php echo __('Manage Default Access', 'advanced-access-manager'); ?></button>
    </div>
<?php }