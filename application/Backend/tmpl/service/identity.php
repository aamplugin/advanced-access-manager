<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php $access_level = AAM_Backend_AccessLevel::get_instance(); ?>

    <div class="aam-feature" id="identity-content">
        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Determine how [%s] can see and manager other users and roles (aka identities). With the premium %sadd-on%s, you have the ability to target all identities at once. To learn more, refer to our official documentation page %shere%s.', 'strong', 'strong'), $access_level->get_display_name(), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/users-and-roles-governance?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-xs-12 aam-container" id="identity_list_container">
                <table id="role_identity_list" class="table table-striped table-bordered" data-has-default="<?php echo apply_filters('aam_identity_role_default_defined_filter', false, $access_level) ? 'true' : 'false'; ?>">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="85%"><?php echo __('Role Name', 'advanced-access-manager'); ?></th>
                            <th width="15%"><?php echo __('Manage', 'advanced-access-manager'); ?></th>
                            <th>Role Data</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <table id="user_identity_list" class="table table-striped table-bordered" data-has-default="<?php echo apply_filters('aam_identity_user_default_defined_filter', false, $access_level) ? 'true' : 'false'; ?>">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="85%"><?php echo __('User Display Name', 'advanced-access-manager'); ?></th>
                            <th width="15%"><?php echo __('Manage', 'advanced-access-manager'); ?></th>
                            <th>User Data</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div class="aam-slide-form aam-access-form" id="aam_role_permissions_form">
                    <a href="#" class="btn btn-xs btn-primary btn-right aam-identity-go-back">
                        &Lt; <?php echo __('Go Back', 'advanced-access-manager'); ?>
                    </a>
                    <span class="aam-clear"></span>

                    <div class="aam-overwrite hidden aam-identity-overwrite" id="aam_role_identity_overwrite">
                        <span><i class="icon-check"></i> <?php echo __('Settings are customized', 'advanced-access-manager'); ?></span>
                        <span><a href="#" class="btn btn-xs btn-primary aam-identity-reset"><?php echo __('Reset to default', 'advanced-access-manager'); ?></a></span>
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
                                        data-off="<?php echo __('Allow', 'advanced-access-manager'); ?>"
                                        data-on="<?php echo __('Deny', 'advanced-access-manager'); ?>"
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
                    <a href="#" class="btn btn-xs btn-primary btn-right aam-identity-go-back">
                        &Lt; <?php echo __('Go Back', 'advanced-access-manager'); ?>
                    </a>
                    <span class="aam-clear"></span>

                    <div class="aam-overwrite hidden aam-identity-overwrite" id="aam_user_identity_overwrite">
                        <span><i class="icon-check"></i> <?php echo __('Settings are customized', 'advanced-access-manager'); ?></span>
                        <span><a href="#" class="btn btn-xs btn-primary aam-identity-reset"><?php echo __('Reset to default', 'advanced-access-manager'); ?></a></span>
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
                                        data-off="<?php echo __('Allow', 'advanced-access-manager'); ?>"
                                        data-on="<?php echo __('Deny', 'advanced-access-manager'); ?>"
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
            </div>
        </div>
    </div>
<?php }