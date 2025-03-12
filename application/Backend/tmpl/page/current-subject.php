<?php /** @version 7.0.0 **/ ?>

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
                        title="<?php echo __('Reset Settings', 'advanced-access-manager'); ?>"
                    ><i class="icon-ccw"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reset-subject-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Reset Access Settings', 'advanced-access-manager'); ?></h4>
                </div>
                <div class="modal-body">
                    <p class="alert alert-danger text-center" id="reset-subject-msg" data-message="<?php echo __('You are about to reset all access settings for the %s. Please confirm.', 'advanced-access-manager'); ?>"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="reset-subject-btn"><?php echo __('Reset', 'advanced-access-manager'); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php }