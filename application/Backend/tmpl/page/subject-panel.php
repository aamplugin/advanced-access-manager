<?php
    /**
     * @since 6.2.0 Changed the size of the "Role Name" column to 60%
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.2.0
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="metabox-holder main-metabox">
        <div class="postbox">
            <h3 class="hndle">
                <span><?php echo __('Users/Roles Manager', AAM_KEY); ?></span>
            </h3>
            <div class="inside" id="user-role-manager-inside">
                <div class="aam-postbox-inside">
                    <ul class="nav nav-tabs" role="tablist">
                        <?php $active = 0; ?>
                        <?php if (current_user_can('aam_manage_roles')) { ?>
                            <li role="presentation" class="<?php echo (!$active++ ? 'active ' : ''); ?>text-center"><a href="#roles" aria-controls="roles" role="tab" data-toggle="tab"><i class="icon-users"></i><span class="aam-subject-title"><?php echo __('Roles', AAM_KEY); ?></span></a></li>
                        <?php } ?>
                        <?php if (current_user_can('aam_manage_users')) { ?>
                            <li role="presentation" class="<?php echo (!$active++ ? 'active ' : ''); ?>text-center"><a href="#users" aria-controls="users" role="tab" data-toggle="tab"><i class="icon-user"></i><span class="aam-subject-title"><?php echo __('Users', AAM_KEY); ?></span></a></li>
                        <?php } ?>
                        <?php if (current_user_can('aam_manage_visitors')) { ?>
                            <li role="presentation" class="<?php echo (!$active++ ? 'active ' : ''); ?>text-center"><a href="#visitor" aria-controls="visitor" role="tab" data-toggle="tab"><i class="icon-user-secret"></i><span class="aam-subject-title"><?php echo __('Visitor', AAM_KEY); ?></span></a></li>
                        <?php } ?>
                        <?php if (current_user_can('aam_manage_default')) { ?>
                            <li role="presentation" class="<?php echo (!$active++ ? 'active ' : ''); ?>text-center"><a href="#default" aria-controls="default" role="tab" data-toggle="tab" class="text-danger"><i class="icon-asterisk"></i><span class="aam-subject-title"><?php echo __('Default', AAM_KEY); ?></span></a></li>
                        <?php } ?>
                        <?php if ($active === 0) { ?>
                            <li role="presentation" class="active text-center"><a href="#none" aria-controls="none" role="tab" data-toggle="tab" class="text-muted"><i class="icon-asterisk"></i><span class="aam-subject-title"><?php echo __('None', AAM_KEY); ?></span></a></li>
                        <?php } ?>
                    </ul>
                    <div class="tab-content">
                        <?php $active = 0; ?>
                        <?php if (current_user_can('aam_manage_roles')) { ?>
                            <div role="tabpanel" class="tab-pane<?php echo (!$active++ ? ' active' : ''); ?>" id="roles">
                                <table id="role-list" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Users</th>
                                            <th width="60%"><?php echo __('Role', AAM_KEY); ?></th>
                                            <th><?php echo __('Action', AAM_KEY); ?></th>
                                            <th>Level</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        <?php } ?>
                        <?php if (current_user_can('aam_manage_users')) { ?>
                            <div role="tabpanel" class="tab-pane<?php echo (!$active++ ? ' active' : ''); ?>" id="users">
                                <table id="user-list" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Roles</th>
                                            <th width="60%"><?php echo __('Username', AAM_KEY); ?></th>
                                            <th><?php echo __('Action', AAM_KEY); ?></th>
                                            <th>Level</th>
                                            <th>Expiration</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        <?php } ?>
                        <?php if (current_user_can('aam_manage_visitors')) { ?>
                            <div role="tabpanel" class="tab-pane<?php echo (!$active++ ? ' active' : ''); ?>" id="visitor">
                                <?php echo apply_filters('aam_visitor_subject_tab_filter', static::loadPartial('visitor-subject-tab', $params), $params); ?>
                            </div>
                        <?php } ?>
                        <?php if (current_user_can('aam_manage_default')) { ?>
                            <div role="tabpanel" class="tab-pane<?php echo (!$active++ ? ' active' : ''); ?>" id="default">
                                <?php echo apply_filters('aam_default_subject_tab_filter', static::loadPartial('default-subject-tab', $params), $params); ?>
                            </div>
                        <?php } ?>
                        <?php if ($active === 0) { ?>
                            <div role="tabpanel" class="tab-pane active" id="none">
                                <p class="alert alert-warning"><?php echo __('You are not allowed to manage any of the existing users, roles, visitors or default access settings.', AAM_KEY); ?></p>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }