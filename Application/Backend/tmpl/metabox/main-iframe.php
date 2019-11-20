<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php echo static::loadTemplate(__DIR__ . '/iframe-header.php'); ?>

    <div class="wrap" id="aam-container">
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
                    <div class="metabox-holder shared-metabox">
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
                                    <a href="https://aamplugin.com/support" title="Help" target="_blank">
                                        <i class="icon-help-circled"></i>
                                        <span><?php echo __('Help', AAM_KEY); ?></span>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (current_user_can('aam_manage_settings')) { ?>
                    <div class="metabox-holder settings-metabox" style="display:none;">
                        <div class="postbox">
                            <div class="inside">
                                <div class="row">
                                    <div class="col-xs-12 col-md-12">
                                        <a href="#clear-settings-modal" data-toggle="modal" class="btn btn-danger btn-block"><?php echo __('Reset AAM Settings', AAM_KEY); ?></a>
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
                                <p class="alert alert-info text-larger highlighted-italic"><?php echo AAM_Backend_View_Helper::preparePhrase('With the [Enterprise Package] get our dedicated support channel and all the premium add-ons for [50+ live websites]', 'i', 'b'); ?></p>
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