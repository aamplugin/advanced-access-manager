<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) {
    wp_enqueue_style('aam-vendor', AAM_MEDIA . '/css/vendor.min.css');
    wp_enqueue_style('aam', AAM_MEDIA . '/css/aam.css', array('aam-vendor'));
    wp_enqueue_script('aam-iframe', AAM_MEDIA . '/js/iframe-content.js');
}
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php echo static::loadTemplate(__DIR__ . '/iframe-header.php'); ?>

    <div class="wrap">
        <div class="row">
            <div class="col-xs-12 col-md-8">
                <?php echo static::loadTemplate(dirname(__DIR__) . '/page/current-subject.php'); ?>

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
                                            <li><?php echo $message; // Already properly handled in the AAM_Core_Console ?></li>
                                        <?php } ?>
                                    </ul>
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
                                <?php if (apply_filters('aam_security_scan_enabled_filter', false)) { ?>
                                    <a href="#" title="Security Scan" data-type="audit" class="aam-area">
                                        <i class="icon-eye"></i>
                                        <span><?php echo __('Security Scan', AAM_KEY); ?></span>
                                    </a>
                                <?php } ?>
                                <?php if (current_user_can('aam_manage_addons')) { ?>
                                    <a href="#" title="Premium" data-type="extensions" class="aam-area">
                                        <i class="icon-cubes"></i>
                                        <span><?php echo __('Premium', AAM_KEY); ?></span>
                                    </a>
                                <?php } ?>
                                <?php if (current_user_can('aam_view_help_btn')) { ?>
                                    <a href="https://aamportal.com/support?ref=plugin" target="_blank" title="Documentation">
                                        <i class="icon-help-circled"></i>
                                        <span><?php echo __('Docs', AAM_KEY); ?></span>
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
                                                    <p class="alert alert-info">
                                                        <?php echo sprintf(
                                                            __('Export AAM settings so they can be imported to a different location. To learn more about customizing exported data, refer to the %s"How to export/import AAM settings?"%s article.', AAM_KEY),
                                                            '<a href="https://aamportal.com/question/how-to-export-import-aam-settings?ref=plugin" target="_blank">',
                                                            '</a>'
                                                            );
                                                        ?>
                                                    </p>

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

                <?php $license = AAM_Addon_Repository::get_instance()->get_premium_license_key(); ?>

                <?php if (!empty($license)) { ?>
                    <div class="metabox-holder extensions-metabox" style="display:none;">
                        <div class="postbox">
                            <div class="inside">
                                <div class="aam-postbox-inside text-center">
                                    <table class="table table-striped table-bordered dataTable no-footer">
                                        <thead>
                                            <tr>
                                                <th><?php echo __('Registered License', AAM_KEY); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <a href="https://aamportal.com/license/<?php echo esc_attr($license); ?>?ref=plugin" target="_blank" class="aam-license-key">
                                                        <?php echo esc_js($license); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <div class="metabox-holder audit-metabox" style="display:none;">
                    <div class="postbox">
                        <div class="inside">
                            <div class="aam-postbox-inside text-center">
                                <p class="text-larger aam-info text-left">
                                    <strong>Need help interpreting your security scan report and identifying the next steps to address critical issues?</strong>
                                    Email us your report at <a href="mailto:support@aamplugin.com">support@aamplugin.com</a>, and we'll schedule a video consultation to guide you.
                                    Please note, this is a paid service, and we will send an invoice prior to the session.
                                </p>
                                <a href="#" class="btn btn-info btn-block download-latest-report""><?php echo __('Download Latest Report', AAM_KEY); ?></a>
                                <a href="mailto:support@aamplugin.com" class="btn btn-primary btn-block"><?php echo __('Contact Us', AAM_KEY); ?></a>
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