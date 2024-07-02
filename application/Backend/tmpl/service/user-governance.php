<?php
/**
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.28 Initial implementation of the template
 *
 * @version 6.9.34
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="user-governance-content">
        <?php if (AAM_Framework_Manager::configs()->get_config('core.settings.tips', true)) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Determine how [%s] can see and manager other users. With the premium %sadd-on%s, you have the ability to target all users at once. To learn more, refer to our official documentation page %shere%s.', 'strong', 'strong'), AAM_Backend_Subject::getInstance()->getName(), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/users-and-roles-governance?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-user-governance-overwrite" style="display: <?php echo ($this->isOverwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="user-governance-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <div class="modal fade" id="user-governance-model" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('User Governance Rules', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label><?php echo AAM_Backend_View_Helper::preparePhrase('Select your rule type [(what are you trying to manager?)]', 'small'); ?></label>
                            <select class="form-control form-clearable" id="user-governance-rule-type">
                                <option value=""><?php echo __('Rule Type', AAM_KEY); ?></option>
                                <?php foreach($this->get_allowed_rule_types() as $id => $label) { ?>
                                    <option value="<?php echo esc_attr($id); ?>"><?php echo esc_js($label); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group user-governance-targets ui-front hidden" data-rule-types="role,user_role">
                            <label><?php echo __('Enter comma-separated list of role slugs', AAM_KEY); ?></label>
                            <input type="text" class="form-control form-clearable" name="role_list" id="user-governance-role-targets" />
                        </div>
                        <div class="form-group user-governance-targets hidden" data-rule-types="role_level,user_level">
                            <label><?php echo __('Enter comma-separated list of levels', AAM_KEY); ?></label>
                            <input type="text" class="form-control form-clearable" name="level_list" id="user-governance-level-targets" />
                        </div>
                        <div class="form-group user-governance-targets ui-front hidden" data-rule-types="user">
                            <label><?php echo __('Enter comma-separated list of user logins or IDs', AAM_KEY); ?></label>
                            <input type="text" class="form-control form-clearable" name="user_list" id="user-governance-user-targets" />
                        </div>

                        <table class="table table-striped table-bordered user-governance-action-list">
                            <tbody>
                                <?php foreach($this->get_permission_list() as $type => $config) { ?>
                                <tr
                                    data-rule-types="<?php echo implode(',', $config['rule_types']); ?>"
                                    class="user-governance-control<?php echo $config['disabled'] ? ' aam-faded-row' : ''; ?>"
                                >
                                    <td width="90%">
                                        <strong class="aam-block aam-highlight text-uppercase">
                                            <?php echo $config['title']; ?>
                                        </strong>
                                        <p class="aam-hint">
                                            <?php echo sprintf($config['hint'], AAM_Backend_Subject::getInstance()->getName()); ?>
                                        </p>
                                    </td>
                                    <td>
                                        <input
                                            data-toggle="toggle"
                                            name="<?php echo $type; ?>"
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
                        <button type="button" class="btn btn-success" id="user-governance-save-btn"><?php echo __('Save', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="user-governance-edit-model" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Edit User Governance Rule', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped table-bordered user-governance-action-list">
                            <tbody>
                                <?php foreach($this->get_permission_list() as $type => $config) { ?>
                                <tr
                                    data-rule-types="<?php echo implode(',', $config['rule_types']); ?>"
                                    class="user-governance-control<?php echo $config['disabled'] ? ' aam-faded-row' : ''; ?>"
                                >
                                    <td width="90%">
                                        <strong class="aam-block aam-highlight text-uppercase">
                                            <?php echo $config['title']; ?>
                                        </strong>
                                        <p class="aam-hint">
                                            <?php echo sprintf($config['hint'], AAM_Backend_Subject::getInstance()->getName()); ?>
                                        </p>
                                    </td>
                                    <td>
                                        <input
                                            data-toggle="toggle"
                                            name="<?php echo $type; ?>"
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
                        <button type="button" class="btn btn-success" id="user-governance-update-btn"><?php echo __('Update', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="user-governance-delete-model" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete Rule', AAM_KEY); ?></h4>
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
                <table id="user-governance-list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="80%"><?php echo __('Rule', AAM_KEY); ?></th>
                            <th>Data</th>
                            <th><?php echo __('Actions', AAM_KEY); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
<?php }
