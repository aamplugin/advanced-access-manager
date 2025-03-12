<?php /** @version 7.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="visitor-message">
        <span class="aam-bordered"><?php echo __('Manage access to your website for visitors (any user that is not authenticated)', 'advanced-access-manager'); ?>.</span>
        <button class="btn btn-primary btn-block" id="manage-visitor"><i class="icon-cog"></i> <?php echo __('Manage Visitors', 'advanced-access-manager'); ?></button>
    </div>
<?php }