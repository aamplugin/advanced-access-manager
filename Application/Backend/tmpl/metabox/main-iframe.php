<?php
    /**
     * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/104
     * @since 6.4.2 Styling notification metabox
     * @since 6.2.0 Added support & import/export modals
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.5.0
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php echo static::loadTemplate(__DIR__ . '/iframe-header.php'); ?>

    <div class="wrap">
        <?php echo static::loadTemplate(dirname(__DIR__) . '/page/current-subject.php'); ?>

        <div class="row">
            <div class="col-xs-12 col-md-8">
                <div class="metabox-holder">
                    <div class="postbox">
                        <div class="inside" id="access-manager-inside">
                            <div class="aam-postbox-inside" id="aam-content">
                                <?php echo static::loadPartial('loading-content'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xs-12 col-md-4 aam-sidebar">
                <?php if (AAM_Core_Console::count() && current_user_can('aam_show_notifications')) { ?>
                    <div class="metabox-holder shared-metabox aam-notification-metabox">
                        <div class="postbox">
                            <h3 class="hndle text-danger">
                                <i class='icon-attention-circled'></i> <span><?php echo __('Notifications', AAM_KEY); ?></span>
                            </h3>
                            <div class="inside">
                                <div class="aam-postbox-inside">
                                    <ul class="aam-error-list">
                                        <?php foreach (AAM_Core_Console::getAll() as $message) { ?>
                                            <li><?php echo $message; ?></li>
                                        <?php } ?>
                                    </ul>
                                    <div class="hidden" id="migration-errors-container"><?php echo base64_encode(print_r(AAM_Core_Migration::getFailureLog(), 1)); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <?php do_action('aam_top_right_column_action'); ?>

                <div class="metabox-holder shared-metabox">
                    <div class="postbox">
                        <div class="inside">
                            <div class="aam-social">
                                <a href="#" title="Access" data-type="main" class="aam-area text-danger">
                                    <i class="icon-cog-alt"></i>
                                    <span><?php echo __('Access', AAM_KEY); ?></span>
                                </a>
                                <?php if (current_user_can('aam_manage_settings')) { ?>
                                    <a href="#" title="Settings" data-type="settings" class="aam-area">
                                        <i class="icon-wrench"></i>
                                        <span><?php echo __('Settings', AAM_KEY); ?></span>
                                    </a>
                                <?php } ?>
                                <?php if (current_user_can('aam_manage_addons')) { ?>
                                    <a href="#" title="Add-ons" data-type="extensions" class="aam-area">
                                        <i class="icon-cubes"></i>
                                        <span><?php echo __('Add-Ons', AAM_KEY); ?></span>
                                    </a>
                                <?php } ?>
                                <?php if (current_user_can('aam_view_help_btn')) { ?>
                                    <a href="#modal-support" data-toggle="modal" title="Ask For Help">
                                        <i class="icon-chat"></i>
                                        <span><?php echo __('Help', AAM_KEY); ?></span>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (current_user_can('aam_view_help_btn')) { ?>
                    <div class="modal fade" id="modal-support" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title text-left"><?php echo __('Ask For Help', AAM_KEY); ?></h4>
                                </div>
                                <div class="modal-body">
                                    <p class="alert alert-warning"><?php echo sprintf(__('Before submitting a support request, please get familiar with %show AAM support works%s so you can set the right expectations. Especially pay attention to how do we prioritize support.', AAM_KEY), '<a href="https://aamplugin.com/support" target="_blank">', '</a>'); ?></p>

                                    <div class="form-group aam-outer-top-xxs">
                                        <label><?php echo __('Name', AAM_KEY); ?></label>
                                        <input type="text" class="form-control" placeholder="<?php echo __('How should we call you', AAM_KEY); ?>" id="support-name" />
                                    </div>

                                    <div class="form-group">
                                        <label><?php echo __('Email', AAM_KEY); ?> <sup class="text-danger">*</sup></label>
                                        <input type="email" class="form-control" placeholder="<?php echo __('Enter your email', AAM_KEY); ?>" id="support-email" />
                                        <span class="hint text-muted"><?php echo __('The rest of the conversation will be conducted via provided email', AAM_KEY); ?></span>
                                    </div>

                                    <div class="form-group">
                                        <label><?php echo __('Message', AAM_KEY); ?> <sup class="text-danger">*</sup></label>
                                        <textarea class="form-control" placeholder="<?php echo __('Enter your message here...', AAM_KEY); ?>" rows="5" id="support-message"></textarea>
                                        <span class="hint text-muted"><?php echo AAM_Backend_View_Helper::preparePhrase('Please be [kind], [specific] and [patient], and let us do the rest', 'strong', 'strong', 'strong'); ?></span>
                                    </div>

                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" checked id="support-details" /> <?php echo sprintf(__('Attach system details (%slearn more here%s)', AAM_KEY), '<a href="https://forum.aamplugin.com/d/454-support-request-with-attached-system-details" target="_blank">', '</a>'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-success" id="submit-support"><?php echo __('Request Support', AAM_KEY); ?></button>
                                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <?php if (current_user_can('aam_manage_settings')) { ?>
                    <div class="metabox-holder settings-metabox" style="display:none;">
                        <div class="postbox">
                            <div class="inside">
                                <div class="row">
                                    <div class="col-xs-12">
                                        <a href="#transfer-settings-modal" data-toggle="modal" class="btn btn-warning btn-block"><?php echo __('Export/Import AAM Settings', AAM_KEY); ?></a>
                                    </div>
                                </div>
                                <div class="row aam-outer-top-xxs">
                                    <div class="col-xs-12">
                                        <a href="#clear-settings-modal" data-toggle="modal" class="btn btn-danger btn-block"><?php echo __('Reset AAM Settings', AAM_KEY); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="transfer-settings-modal" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <p class="alert alert-danger" id="file-api-error"><?php echo __('Your browser does not support modern way to work with files. The Export/Import feature will not work properly. Consider to use the latest Chrome, Firefox or Safari browser instead.', AAM_KEY); ?></p>

                                        <div id="import-export-container">
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li role="presentation" class="active"><a href="#export-tab" aria-controls="export-tab" role="tab" data-toggle="tab"><?php echo __('Export', AAM_KEY); ?></a></li>
                                                <li role="presentation"><a href="#import-tab" aria-controls="import-tab" role="tab" data-toggle="tab"><?php echo __('Import', AAM_KEY); ?></a></li>
                                            </ul>

                                            <div class="tab-content">
                                                <div role="tabpanel" class="tab-pane active" id="export-tab">
                                                    <p class="alert alert-info"><?php echo __('Export AAM settings so they can be imported to a different location. To learn more about customizing exported data, refer to the "How Import/Export feature works" article.', AAM_KEY); ?></p>
                                                    <div class="form-group aam-bordered aam-outer-top-xxs text-center">
                                                        <a href="#" id="export-settings" class="btn btn-primary"><?php echo __('Download Exported Settings', AAM_KEY); ?></a>
                                                    </div>
                                                </div>

                                                <div role="tabpanel" class="tab-pane" id="import-tab">
                                                    <p class="alert alert-warning"><?php echo __('Select a *.json file with valid AAM settings. All the current AAM settings will be lost and replaced with imported settings.', AAM_KEY); ?></p>
                                                    <div class="form-group aam-bordered aam-outer-top-xxs">
                                                        <input type="file" id="aam-settings" name="aam-settings" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="clear-settings-modal" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-sm" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title"><?php echo __('Clear all settings', AAM_KEY); ?></h4>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-center alert alert-danger text-larger"><?php echo __('All AAM settings will be removed.', AAM_KEY); ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" id="clear-settings"><?php echo __('Clear', AAM_KEY); ?></button>
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Cancel', AAM_KEY); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <div class="metabox-holder extensions-metabox" style="display:none;">
                    <div class="postbox">
                        <div class="inside">
                            <div class="aam-postbox-inside text-center">
                                <p class="alert alert-info text-larger highlighted-italic"><?php echo AAM_Backend_View_Helper::preparePhrase('With the [Enterprise Package] get dedicated support channel and all the premium add-ons for a [bulk number of live websites]', 'i', 'b'); ?></p>
                                <a href="https://aamplugin.com/pricing/enterprise-package" target="_blank" class="btn btn-sm btn-primary btn-block"><i class="icon-link"></i> <?php echo __('Read More', AAM_KEY); ?></a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php echo static::loadTemplate(dirname(__DIR__) . '/page/subject-panel.php'); ?>
                <?php echo static::loadTemplate(dirname(__DIR__) . '/page/subject-panel-advanced.php'); ?>
            </div>
        </div>
    </div>

    <?php echo static::loadTemplate(__DIR__ . '/iframe-footer.php'); ?>
<?php }