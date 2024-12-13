<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="identity-content">
        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php $access_level = AAM_Backend_AccessLevel::get_instance(); ?>
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Determine how [%s] can see and manager other users and roles (aka identities). With the premium %sadd-on%s, you have the ability to target all identities at once. To learn more, refer to our official documentation page %shere%s.', 'strong', 'strong'), $access_level->get_display_name(), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/users-and-roles-governance?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-xs-12 aam-container" id="identity_list_container">
                <table id="role_identity_list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="85%"><?php echo __('Role Name', AAM_KEY); ?></th>
                            <th width="15%"><?php echo __('Manage', AAM_KEY); ?></th>
                            <th>Role Data</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <table id="user_identity_list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="85%"><?php echo __('User Display Name', AAM_KEY); ?></th>
                            <th width="15%"><?php echo __('Manage', AAM_KEY); ?></th>
                            <th>User Data</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div class="aam-slide-form aam-access-form" id="aam_role_permissions_form">
                    <a href="#" class="btn btn-xs btn-primary btn-right" id="role_identity_go_back">
                        &Lt; <?php echo __('Go Back', AAM_KEY); ?>
                    </a>
                    <span class="aam-clear"></span>

                    <div class="aam-overwrite hidden" id="aam_role_identity_overwrite">
                        <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                        <span><a href="#" id="role_identity_reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a></span>
                    </div>

                    <table class="table table-striped table-bordered">
                        <tbody>
                        <?php foreach($this->get_role_permission_list() as $permission => $config) { ?>
                            <tr class="identity-action-control">
                                <td width="90%">
                                    <strong class="aam-block aam-highlight text-uppercase">
                                        <?php echo esc_js($config['title']); ?>
                                    </strong>
                                    <p class="aam-hint">
                                        <?php echo esc_js($config['hint']); ?>
                                    </p>
                                </td>
                                <td>
                                    <input
                                        data-toggle="toggle"
                                        name="<?php echo esc_attr($permission); ?>"
                                        id="aam_role_identity_permission_<?php echo esc_attr($permission); ?>"
                                        type="checkbox"
                                        data-off="<?php echo __('Allow', AAM_KEY); ?>"
                                        data-on="<?php echo __('Deny', AAM_KEY); ?>"
                                        data-size="small"
                                        data-onstyle="danger"
                                        data-offstyle="success"
                                        data-value-on="deny"
                                        data-value-off="allow"
                                    />
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="aam-slide-form aam-access-form" id="aam_user_permissions_form">
                    <a href="#" class="btn btn-xs btn-primary btn-right" id="user_identity_go_back">
                        &Lt; <?php echo __('Go Back', AAM_KEY); ?>
                    </a>
                    <span class="aam-clear"></span>

                    <div class="aam-overwrite hidden" id="aam_user_identity_overwrite">
                        <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                        <span><a href="#" id="user_identity_reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a></span>
                    </div>

                    <table class="table table-striped table-bordered">
                        <tbody>
                        <?php foreach($this->get_user_permission_list() as $permission => $config) { ?>
                            <tr class="identity-action-control">
                                <td width="90%">
                                    <strong class="aam-block aam-highlight text-uppercase">
                                        <?php echo esc_js($config['title']); ?>
                                    </strong>
                                    <p class="aam-hint">
                                        <?php echo esc_js($config['hint']); ?>
                                    </p>
                                </td>
                                <td>
                                    <input
                                        data-toggle="toggle"
                                        name="<?php echo esc_attr($permission); ?>"
                                        id="aam_user_identity_permission_<?php echo esc_attr($permission); ?>"
                                        type="checkbox"
                                        data-off="<?php echo __('Allow', AAM_KEY); ?>"
                                        data-on="<?php echo __('Deny', AAM_KEY); ?>"
                                        data-size="small"
                                        data-onstyle="danger"
                                        data-offstyle="success"
                                        data-value-on="deny"
                                        data-value-off="allow"
                                    />
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

                <?php do_action('aam_ui_identity_list_action', $access_level); ?>
            </div>
        </div>
    </div>
<?php }