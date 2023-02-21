<?php
    /**
     * @since 6.3.0 Removed limitation to attach policy to Default
     * @since 6.2.0 Enhanced the table with new functionality
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.3.0
     * */
?>
<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="policy-content">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access and security policies for [%s]. For more information check %sAccess &amp; Security Policy%s page.', 'b'), AAM_Backend_Subject::getInstance()->getName(), '<a href="https://aamportal.com/advanced/access-policy/" target="_blank">', '</a>'); ?>
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-policy-overwrite" style="display: <?php echo ($this->isOverwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Policies are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="policy-reset" class="btn btn-xs btn-primary"><?php echo __('Reset To Default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <table id="policy-list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="80%"><?php echo __('Policy', AAM_KEY); ?></th>
                            <th><?php echo __('Actions', AAM_KEY); ?></th>
                            <th>Edit Link</th>
                            <th>Just Title</th>
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete Policy', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="text-center aam-confirm-message alert alert-danger" data-message="<?php echo __('You are about to delete the %s policy. Please confirm.', AAM_KEY); ?>"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="delete-policy-btn"><?php echo __('Delete Policy', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal-install-policy" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title text-left"><?php echo __('Install Access Policy', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="alert alert-info"><?php echo sprintf(__('Install an access policy from the official %sAAM Access Policy Hub%s. Insert the policy ID and it will be automatically downloaded and applied to proper assignees.', AAM_KEY), '<a href="https://aamplugin.com/access-policy-hub" target="_blank">', '</a>'); ?></p>

                        <div class="input-group aam-outer-top-xs">
                            <input type="text" class="form-control" placeholder="<?php echo __('Insert Policy ID', AAM_KEY); ?>" id="policy-id" aria-describedby="basic-addon2" />
                            <span class="input-group-addon" id="basic-addon2"><?php echo __('Fetch', AAM_KEY); ?></span>
                        </div>

                        <table class="table table-striped table-bordered aam-ghost aam-outer-top-xxs" id="policy-details">
                            <tbody>
                                <tr>
                                    <th width="30" class="text-muted"><?php echo __('Title', AAM_KEY); ?></th>
                                    <td id="policy-title"></td>
                                </tr>
                                <tr>
                                    <th width="30" class="text-muted"><?php echo __('Description', AAM_KEY); ?></th>
                                    <td id="policy-description"></td>
                                </tr>
                                <tr>
                                    <th width="30" class="text-muted"><?php echo __('Assignee(s)', AAM_KEY); ?></th>
                                    <td id="policy-subjects"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" disabled id="install-policy"><?php echo __('Install', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }