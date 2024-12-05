<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::getInstance();
        $service      = $access_level->identities();
    ?>
    <div class="aam-feature" id="identity-content">
        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Determine how [%s] can see and manager other users. With the premium %sadd-on%s, you have the ability to target all users at once. To learn more, refer to our official documentation page %shere%s.', 'strong', 'strong'), $access_level->get_display_name(), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/users-and-roles-governance?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-xs-12">
                <div
                    class="aam-overwrite"
                    id="identity_overwrite"
                    style="display: <?php echo ($service->is_customized() ? 'block' : 'none'); ?>"
                >
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="identity_reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <div class="modal fade" id="identity_rule_create_model" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="<?php echo __('Close', AAM_KEY); ?>"
                        ><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Identity Governance Rules', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label><?php echo AAM_Backend_View_Helper::preparePhrase('Select your identity type [(what are you trying to manager?)]', 'small'); ?></label>
                            <select class="form-control form-clearable" id="identity_type">
                                <option value=""><?php echo __('Identity Type', AAM_KEY); ?></option>
                                <?php foreach($this->get_allowed_identity_types() as $id => $label) { ?>
                                    <option
                                        value="<?php echo esc_attr($id); ?>"
                                    ><?php echo esc_js($label); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group identity-targets ui-front hidden" data-identity-types="role,user_role">
                            <table id="identity_role_list" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th width="90%"><?php echo __('Select at least one role', AAM_KEY); ?></th>
                                        <th><?php echo __('Select', AAM_KEY); ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="form-group identity-targets hidden" data-identity-types="role_level,user_level">
                            <label><?php echo __('Enter comma-separated list of levels', AAM_KEY); ?></label>
                            <input
                                type="text"
                                class="form-control form-clearable"
                                id="identity_levels"
                            />
                        </div>

                        <div class="form-group identity-targets ui-front hidden" data-identity-types="user">
                            <table id="identity_user_list" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th width="90%"><?php echo __('Select at least one user', AAM_KEY); ?></th>
                                        <th><?php echo __('Select', AAM_KEY); ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <table class="table table-striped table-bordered" id="identity_permissions">
                            <tbody>
                                <?php foreach($this->get_permission_list() as $type => $config) { ?>
                                <tr
                                    data-identity-types="<?php echo esc_attr(implode(',', $config['identity_types'])); ?>"
                                    class="identity-action-control"
                                >
                                    <td width="90%">
                                        <strong class="aam-block aam-highlight text-uppercase">
                                            <?php echo esc_js($config['title']); ?>
                                        </strong>
                                        <p class="aam-hint">
                                            <?php echo esc_js(sprintf($config['hint'], $access_level->get_display_name())); ?>
                                        </p>
                                    </td>
                                    <td>
                                        <input
                                            data-toggle="toggle"
                                            name="<?php echo esc_attr($type); ?>"
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
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-success"
                            id="identity_rule_create_btn"
                        ><?php echo __('Create', AAM_KEY); ?></button>
                        <button
                            type="button"
                            class="btn btn-default"
                            data-dismiss="modal"
                        ><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="user-governance-delete-model" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete Identity Governance Rule', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <p class="aam-notification">
                                <?php echo __('You are about to delete the user governance rule. Please confirm!', AAM_KEY); ?>
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="user-governance-delete-btn"><?php echo __('Delete', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <table id="identity_list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="60%"><?php echo __('Identity', AAM_KEY); ?></th>
                            <th width="25%"><?php echo __('Permission', AAM_KEY); ?></th>
                            <th width="15%"><?php echo __('Actions', AAM_KEY); ?></th>
                            <th>Identity Type</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="hidden" id="aam_permission_list"><?php echo esc_js(wp_json_encode($this->get_permission_list())); ?></div>
    </div>
<?php }