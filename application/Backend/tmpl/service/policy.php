<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="policy-content">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access and security policies for [%s]. For more information check %sAccess &amp; Security Policy%s page.', 'b'), AAM_Backend_AccessLevel::get_instance()->get_display_name(), '<a href="https://aamportal.com/reference/json-access-policy/?ref=plugin" target="_blank">', '</a>'); ?>
                </p>
            </div>
        </div>

        <?php $service = AAM_Backend_AccessLevel::get_instance()->policies(); ?>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-policy-overwrite" style="display: <?php echo ($service->is_customized() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Policies are customized', 'advanced-access-manager'); ?></span>
                    <span><a href="#" id="policy_reset" class="btn btn-xs btn-primary"><?php echo __('Reset To Default', 'advanced-access-manager'); ?></a></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <table id="policy_list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="80%"><?php echo __('Policy', 'advanced-access-manager'); ?></th>
                            <th><?php echo __('Actions', 'advanced-access-manager'); ?></th>
                            <th>Policy</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="delete-policy-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete Policy', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="text-center aam-confirm-message alert alert-danger" data-message="<?php echo __('You are about to delete the %s policy. Please confirm.', 'advanced-access-manager'); ?>"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="delete-policy-btn"><?php echo __('Delete Policy', 'advanced-access-manager'); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }