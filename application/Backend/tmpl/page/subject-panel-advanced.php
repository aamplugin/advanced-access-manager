<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="modal fade" id="add-role-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Create Role', 'advanced-access-manager'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="error-container hidden aam-notification">
                        <strong id="role-error-summary"></strong>
                        <ul id="role-error-list"></ul>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('Role Name', 'advanced-access-manager'); ?><span class="aam-asterix">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="<?php echo __('Enter Role Name', 'advanced-access-manager'); ?>" />
                    </div>
                    <div class="form-group">
                        <label><?php echo __('Role Slug', 'advanced-access-manager'); ?></label>
                        <input type="text" class="form-control" name="slug" placeholder="<?php echo __('Enter Role Slug', 'advanced-access-manager'); ?>" />
                    </div>
                    <div class="form-group">
                        <label><?php echo __('Inherit Capabilities From', 'advanced-access-manager'); ?></label>
                        <select class="form-control aam-role-list" name="clone_role" id="inherit-role">
                            <option value=""><?php echo __('Select Role', 'advanced-access-manager'); ?></option>
                        </select>
                    </div>
                    <div class="checkbox">
                        <label for="clone-role">
                            <input type="checkbox" id="clone-role" name="clone_role_settings" />
                            <?php echo __('Also, only once, inherit all settings from selected role (admin menu, metaboxes, redirects, etc.)', 'advanced-access-manager'); ?>
                        </label>
                    </div>
                    <?php echo do_action('aam_ui_add_role_form_action'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="add-role-btn"><?php echo __('Create', 'advanced-access-manager'); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-role-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Update Role', 'advanced-access-manager'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="error-container hidden aam-notification">
                        <strong id="edit-role-error-summary"></strong>
                        <ul id="edit-role-error-list"></ul>
                    </div>

                    <div class="form-group">
                        <label for="edit-role-name"><?php echo __('Role Name', 'advanced-access-manager'); ?></label>
                        <input type="text" class="form-control" id="edit-role-name" placeholder="<?php echo __('Enter Role Name', 'advanced-access-manager'); ?>" name="name" />
                    </div>
                    <div class="form-group">
                        <label for="edit-role-slug"><?php echo __('Role Slug', 'advanced-access-manager'); ?></label>
                        <input type="text" class="form-control" id="edit-role-slug" name="new_slug" />
                        <small class="text-muted hint"><?php echo __('Can be changed if no users are assigned to role', 'advanced-access-manager'); ?></small>
                    </div>
                    <?php do_action('aam_ui_edit_role_form_action'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="edit-role-btn"><?php echo __('Update', 'advanced-access-manager'); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delete-role-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Delete Role', 'advanced-access-manager'); ?></h4>
                </div>
                <div class="modal-body">
                    <p class="text-center aam-confirm-message alert alert-danger" data-message="<?php echo __('Are you sure that you want to delete the %s role?', 'advanced-access-manager'); ?>"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="delete-role-btn"><?php echo __('Delete', 'advanced-access-manager'); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-user-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Manage User', 'advanced-access-manager'); ?></h4>
                </div>
                <div class="modal-body">
                    <p class="aam-info"><?php echo __('Define for how long user can access the website and what action needs to be taken after access expires.', 'advanced-access-manager'); ?>

                    <div class="form-group aam-bordered">
                        <div id="user-expiration-datapicker"></div>
                        <input type="hidden" id="user-expires" />
                    </div>

                    <div class="aam-bordered">
                        <div class="form-group">
                            <label><?php echo __('Action After Expiration', 'advanced-access-manager'); ?> </label>
                            <?php
                                $expirationActions = array(
                                    ''            => __('Select Action', 'advanced-access-manager'),
                                    'logout'      => __('Logout User', 'advanced-access-manager'),
                                    'change_role' => __('Change User Role', 'advanced-access-manager'),
                                    'lock'        => __('Lock User Account', 'advanced-access-manager')
                                );
                            ?>
                            <select class="form-control" id="action-after-expiration">
                                <?php foreach($expirationActions as $key => $label) { ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_js($label); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group hidden" id="expiration-change-role-holder">
                            <label><?php echo __('Change To Role', 'advanced-access-manager'); ?></label>
                            <select class="form-control" id="expiration-change-role">
                                <option value=""><?php echo __('Select Role', 'advanced-access-manager'); ?></option>
                            </select>
                        </div>
                    </div>

                    <?php do_action('aam_post_edit_user_modal_action'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning hidden" id="reset-user-expiration-btn"><?php echo __('Reset', 'advanced-access-manager'); ?></button>
                    <button type="button" class="btn btn-success" id="edit-user-expiration-btn"><?php echo __('Save', 'advanced-access-manager'); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php }