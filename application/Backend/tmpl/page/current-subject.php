<?php
    /**
     * @since 6.7.4 Improved the UI consistency
     * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/109
     * @since 6.3.0 Refactored to support https://github.com/aamplugin/advanced-access-manager/issues/27
     * @since 6.2.0 Added `aam_top_subject_actions_filter` hook
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.7.4
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row" id="aam-subject-banner">
        <div class="col-xs-12">
            <div class="aam-current-subject"></div>
            <div class="subject-top-actions">
                <div class="action-row">
                    <a
                        href="#"
                        id="reset-subject-settings"
                        data-toggle="tooltip"
                        data-placement="left"
                        title="<?php echo __('Reset Settings', AAM_KEY); ?>"
                    ><i class="icon-ccw"></i></a>
                    <?php do_action('aam_top_subject_panel_action'); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reset-subject-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Reset Access Settings', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <p class="alert alert-danger text-center" id="reset-subject-msg" data-message="<?php echo __('You are about to reset all access settings for the %s. Please confirm.', AAM_KEY); ?>"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="reset-subject-btn"><?php echo __('Reset', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php }