<?php /**  @version 6.9.21 **/ ?>

<?php
    if (defined('AAM_KEY')) {
        wp_enqueue_style('aam-vendor', AAM_MEDIA . '/css/vendor.min.css');
        wp_enqueue_style('aam', AAM_MEDIA . '/css/aam.css', array('aam-vendor'));
        wp_enqueue_script('aam-iframe', AAM_MEDIA . '/js/iframe-content.js');
    }
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php echo static::loadTemplate(__DIR__ . '/iframe-header.php', $params); ?>

    <div class="metabox-holder main-metabox">
        <div class="postbox" style="border: none !important">
            <h3 class="hndle">
                <span><?php echo __('Users & Roles', AAM_KEY); ?></span>
            </h3>
            <div class="inside" style="padding: 0 0 12px 0" id="policy_principle_selector">
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
                                <table id="policy_principle_role_list" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th width="80%"><?php echo __('Role', AAM_KEY); ?></th>
                                            <th><?php echo __('Apply', AAM_KEY); ?></th>
                                            <th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        <?php } ?>
                        <?php if (current_user_can('aam_manage_users')) { ?>
                            <div role="tabpanel" class="tab-pane<?php echo (!$active++ ? ' active' : ''); ?>" id="users">
                                <table id="policy_principle_user_list" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th width="80%"><?php echo __('Username', AAM_KEY); ?></th>
                                            <th><?php echo __('Apply', AAM_KEY); ?></th>
                                            <th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        <?php } ?>
                        <?php if (current_user_can('aam_manage_visitors')) { ?>
                            <div role="tabpanel" class="tab-pane<?php echo (!$active++ ? ' active' : ''); ?>" id="visitor">
                                <div class="visitor-message">
                                    <span class="aam-bordered"><?php echo __('Attach current policy to visitors (any user that is not authenticated).', AAM_KEY); ?></span>
                                    <?php
                                        $is_attached = AAM::api()->policies('visitor')->is_attached($params->policyId);
                                        $btn_status   = $is_attached === true ? 'detach' : 'attach';
                                    ?>

                                    <?php if ($is_attached) { ?>
                                        <button
                                            class="btn btn-primary btn-block"
                                            id="toggle_visitor_policy"
                                            data-has="1"
                                            <?php echo ($btn_status ? '' : ' disabled'); ?>
                                        ><?php echo __('Detach Policy From Visitors', AAM_KEY); ?></button>
                                    <?php } else { ?>
                                        <button
                                            class="btn btn-primary btn-block"
                                            id="toggle_visitor_policy"
                                            data-has="0"
                                            <?php echo ($btn_status ? '' : ' disabled'); ?>
                                        ><?php echo __('Attach Policy To Visitors', AAM_KEY); ?></button>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if (current_user_can('aam_manage_default')) { ?>
                            <div role="tabpanel" class="tab-pane<?php echo (!$active++ ? ' active' : ''); ?>" id="default">
                                <div class="visitor-message">
                                    <span class="aam-bordered"><?php echo __('Attach current policy to all users, roles and visitors.', AAM_KEY); ?></span>
                                    <?php
                                        $is_attached = AAM::api()->policies('default')->is_attached($params->policyId);
                                        $btn_status  = $is_attached === true ? 'detach' : 'attach';
                                    ?>

                                    <?php if ($is_attached) { ?>
                                        <button
                                            class="btn btn-primary btn-block"
                                            id="attach-policy-default"
                                            data-has="1"
                                            <?php echo ($btn_status ? '' : ' disabled'); ?>
                                        ><?php echo __('Detach Policy From Everyone', AAM_KEY); ?></button>
                                    <?php } else { ?>
                                        <button
                                            class="btn btn-primary btn-block"
                                            id="attach-policy-default"
                                            data-has="0"
                                            <?php echo ($btn_status ? '' : ' disabled'); ?>
                                        ><?php echo __('Attach Policy To Everyone', AAM_KEY); ?></button>
                                    <?php } ?>
                                </div>
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

    <!-- Additional attributes -->
    <input type="hidden" id="aam-policy-id" value="<?php echo intval($params->policyId); ?>" />

    <?php echo static::loadTemplate(__DIR__ . '/iframe-footer.php', $params); ?>
<?php }