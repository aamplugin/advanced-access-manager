<?php
    /**
     * @since 6.9.2 https://github.com/aamplugin/advanced-access-manager/issues/229
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.9.2
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="capability-content">
        <?php $subject = AAM_Backend_Subject::getInstance(); ?>

        <?php if (current_user_can('aam_page_help_tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-notification">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('[Be careful!] On this tab, you can manage capabilities for [%s]. Any changes to the list of capabilities is [permanent]. Consider to backup at least your database tables [_options] and [_usermeta] regularly.', 'b', 'b', 'b', 'i', 'i'), AAM_Backend_Subject::getInstance()->getName()); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="aam-feature-top-actions text-right">
            <div class="btn-group">
                <a href="#" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="capability-filter">
                    <i class="icon-filter"></i> <?php echo __('Filter', AAM_KEY); ?> <span class="caret"></span>
                </a>
                <ul class="dropdown-menu" id="capability-groups" aria-labelledby="capability-filter">
                    <?php foreach ($this->getGroupList() as $group) { ?>
                        <li><a href="#"><?php echo esc_js($group); ?></a></li>
                    <?php } ?>
                    <li role="separator" class="divider"></li>
                    <li><a href="#" data-assigned="true"><?php echo __('All Assigned', AAM_KEY); ?></a></li>
                    <li><a href="#" data-unassigned="true"><?php echo __('All Unassigned', AAM_KEY); ?></a></li>
                    <li><a href="#" data-clear="true"><?php echo __('All Capabilities', AAM_KEY); ?></a></li>
                </ul>
            </div>
            <a href="#" class="btn btn-xs btn-primary" id="add-capability"><i class="icon-plus"></i> <?php echo __('Create', AAM_KEY); ?></a>
        </div>

        <table id="capability-list" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th width="30%"><?php echo __('Category', AAM_KEY); ?></th>
                    <th width="50%"><?php echo __('Capability', AAM_KEY); ?></th>
                    <th><?php echo __('Actions', AAM_KEY); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <div class="modal fade" id="add-capability-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Create Capability', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="new-capability-name"><?php echo __('Capability', AAM_KEY); ?><span class="aam-asterix">*</span></label>
                            <input type="text" class="form-control" id="new-capability-name" placeholder="<?php echo __('Enter Capability', AAM_KEY); ?>" />
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="assign-new-capability" value="1" /> <?php echo __('Also assign this capability to me', AAM_KEY); ?>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="add-capability-btn"><?php echo __('Create', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="edit-capability-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Update Capability', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="capability-id"><?php echo __('Capability', AAM_KEY); ?><span class="aam-asterix">*</span></label>
                            <input type="text" class="form-control" id="capability-id" placeholder="<?php echo __('Enter Capability', AAM_KEY); ?>" />
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="update-capability" value="1" /> <?php echo __('Update this capability for me too', AAM_KEY); ?>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning" id="update-capability-btn"><?php echo __('Update', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="delete-capability-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete Capability', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="text-center aam-confirm-message alert alert-danger" data-message="<?php echo __('You are about to delete the %s capability. Any functionality that depends on this capability will no longer be accessible by %n.', AAM_KEY); ?>"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="delete-subject-cap-btn" data-message="<?php echo __('Delete For %n Only', AAM_KEY); ?>"></button>
                        <button type="button" class="btn btn-danger" id="delete-all-roles-cap-btn"><?php echo __('Delete For All Roles', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }