<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="modal fade" id="add-role-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Create Role', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo __('Role Name', AAM_KEY); ?><span class="aam-asterix">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="<?php echo __('Enter Role Name', AAM_KEY); ?>" />
                    </div>
                    <?php echo apply_filters('aam_add_role_ui_filter', AAM_Backend_View::getInstance()->loadPartial('role-inheritance')); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="add-role-btn"><?php echo __('Create', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-role-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Update Role', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="new-role-name"><?php echo __('Role Name', AAM_KEY); ?></label>
                        <input type="text" class="form-control" id="edit-role-name" placeholder="<?php echo __('Enter Role Name', AAM_KEY); ?>" name="name" />
                    </div>
                    <?php do_action('aam_edit_role_ui_action'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="edit-role-btn"><?php echo __('Update', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delete-role-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Delete Role', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <p class="text-center aam-confirm-message alert alert-danger" data-message="<?php echo __('Are you sure that you want to delete the %s role?', AAM_KEY); ?>"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="delete-role-btn"><?php echo __('Delete', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-user-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Manage User', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <p class="aam-info"><?php echo __('Define for how long user can access the website and what action needs to be taken after access expires.', AAM_KEY); ?>

                    <div class="form-group aam-bordered">
                        <div id="user-expiration-datapicker"></div>
                        <input type="hidden" id="user-expires" />
                    </div>

                    <div class="aam-bordered">
                        <div class="form-group">
                            <label><?php echo __('Action After Expiration', AAM_KEY); ?> </label>
                            <?php
                                $expirationActions = array(
                                    ''            => __('Select Action', AAM_KEY),
                                    'logout'      => __('Logout User', AAM_KEY),
                                    'delete'      => __('Delete Account', AAM_KEY),
                                    'change-role' => __('Change User Role', AAM_KEY)
                                );
                            ?>
                            <select class="form-control" id="action-after-expiration">
                                <?php foreach(apply_filters('aam_user_expiration_actions_filter', $expirationActions) as $key => $label) { ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group hidden" id="expiration-change-role-holder">
                            <label><?php echo __('Change To Role', AAM_KEY); ?></label>
                            <select class="form-control" id="expiration-change-role">
                                <option value=""><?php echo __('Select Role', AAM_KEY); ?></option>
                            </select>
                        </div>
                    </div>

                    <?php do_action('aam_post_edit_user_modal_action'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning hidden" id="reset-user-expiration-btn"><?php echo __('Reset', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-success" id="edit-user-expiration-btn"><?php echo __('Save', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php }