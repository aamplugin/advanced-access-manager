<?php /** @version 6.7.5 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php $violations = AAM_Addon_Repository::getInstance()->getViolations(); ?>
    <div class="notice notice-warning is-dismissible">
        <p>
            <?php if (count($violations) === 1) { ?>
                <?php echo __('There is an issue with one Advanced Access Manager premium license.', AAM_KEY); ?>
            <?php } else { ?>
                <?php echo __('There are multiple issues with Advanced Access Manager premium licenses.', AAM_KEY); ?>
            <?php } ?>
            <?php echo sprintf(__('For more information, please follow %sthe link%s.', AAM_KEY), '<a href="' . admin_url('admin.php?page=aam') . '">', '</a>'); ?>
        </p>
    </div>
<?php } ?>