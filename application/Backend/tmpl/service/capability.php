<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="capability-content">
        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-notification">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('[Be careful!] On this tab, you can manage capabilities for [%s]. Any changes to the list of capabilities is [permanent]. Consider to backup at least your database tables [_options] and [_usermeta] regularly.', 'b', 'b', 'b', 'i', 'i'), AAM_Backend_AccessLevel::get_instance()->get_display_name()); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="aam-feature-top-actions text-right">
            <div class="btn-group">
                <a href="#" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="capability-filter">
                    <i class="icon-filter"></i> <?php echo __('Filter', 'advanced-access-manager'); ?> <span class="caret"></span>
                </a>
                <ul class="dropdown-menu" id="capability-groups" aria-labelledby="capability-filter">
                    <li><a href="#" data-assigned="true"><?php echo __('All Assigned', 'advanced-access-manager'); ?></a></li>
                    <li><a href="#" data-unassigned="true"><?php echo __('All Unassigned', 'advanced-access-manager'); ?></a></li>
                    <li><a href="#" data-clear="true"><?php echo __('All Capabilities', 'advanced-access-manager'); ?></a></li>
                </ul>
            </div>
            <a href="#" class="btn btn-xs btn-primary" id="add-capability"><i class="icon-plus"></i> <?php echo __('Create', 'advanced-access-manager'); ?></a>
        </div>

        <table id="capability-list" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th width="80%"><?php echo __('Capability', 'advanced-access-manager'); ?></th>
                    <th><?php echo __('Actions', 'advanced-access-manager'); ?></th>
                    <th>Is Granted</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <div class="modal fade" id="add-capability-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Create Capability', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p
                            id="add_capability_error"
                            class="text-left alert alert-warning hidden aam-mb-2"
                            data-message="<?php echo esc_attr(__('The "%s" capability does not adhere to the WordPress core standard, which recommends using only lowercase letters, numbers, underscores, and hyphens.', 'advanced-access-manager')); ?>"
                        ></p>

                        <div class="form-group">
                            <label for="new-capability-name"><?php echo __('Capability', 'advanced-access-manager'); ?><span class="aam-asterix">*</span></label>
                            <input type="text" class="form-control" id="new-capability-name" placeholder="<?php echo __('Enter Capability', 'advanced-access-manager'); ?>" />
                        </div>
                        <div class="checkbox hidden" id="ignore_capability_format_container">
                            <label for="ignore_capability_format">
                                <input type="checkbox" id="ignore_capability_format" />
                                <?php echo __('Ignore warning and create new capability anyway', 'advanced-access-manager'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="add-capability-btn"><?php echo __('Create', 'advanced-access-manager'); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="update-capability-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Update Capability', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p
                            id="update_capability_error"
                            class="text-left alert alert-warning hidden aam-mb-2"
                            data-message="<?php echo esc_attr(__('The "%s" capability does not adhere to the WordPress core standard, which recommends using only lowercase letters, numbers, underscores, and hyphens.', 'advanced-access-manager')); ?>"
                        ></p>

                        <div class="form-group">
                            <label for="capability-id"><?php echo __('Capability', 'advanced-access-manager'); ?><span class="aam-asterix">*</span></label>
                            <input type="text" class="form-control" id="update-capability-slug" placeholder="<?php echo __('Enter Capability', 'advanced-access-manager'); ?>" />
                        </div>
                        <div class="checkbox hidden" id="ignore_update_capability_format_container">
                            <label for="ignore_update_capability_format">
                                <input type="checkbox" id="ignore_update_capability_format" />
                                <?php echo __('Ignore warning and update capability anyway', 'advanced-access-manager'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="update-capability-btn"><?php echo __('Update For All Roles', 'advanced-access-manager'); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="delete-capability-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete Capability', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p
                            class="text-center aam-confirm-message alert alert-danger"
                            data-message="<?php echo __('You are about to delete the %s capability. Any functionality relying on this capability will no longer be accessible.', 'advanced-access-manager'); ?>"
                        ></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="delete-capability-btn"><?php echo __('Delete For All Roles', 'advanced-access-manager'); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }