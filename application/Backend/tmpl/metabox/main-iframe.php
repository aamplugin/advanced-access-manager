<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) {
    wp_enqueue_style('aam-vendor', AAM_MEDIA . '/css/vendor.min.css', [], AAM_VERSION);
    wp_enqueue_style('aam', AAM_MEDIA . '/css/aam.css', array('aam-vendor'), AAM_VERSION);
    wp_enqueue_script('aam-iframe', AAM_MEDIA . '/js/iframe-content.js', [], AAM_VERSION);
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
                                <i class='icon-attention-circled'></i> <span><?php echo __('Notifications', 'advanced-access-manager'); ?></span>
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

                <?php if (apply_filters('aam_security_scan_enabled_filter', false)) { ?>
                <?php
                    $score = AAM_Service_SecurityAudit::get_instance()->get_score();
                    $grade = AAM_Service_SecurityAudit::get_instance()->get_score_grade()
                ?>
                <div class="metabox-holder shared-metabox">
                    <div class="postbox" style="border:none !important;">
                        <div class="panel-group" style="margin-bottom:0" id="security-score-block" role="tablist" aria-multiselectable="true">
                            <div class="panel panel-default" style="border-radius: 0">
                                <div class="panel-heading" role="tab" id="security-score-heading">
                                    <h4 class="panel-title">
                                        <a role="button" data-toggle="collapse" data-parent="#security-score-block" href="#security-score" aria-controls="security-score" style="font-size: 2rem;">
                                            <?php echo sprintf(
                                                __('Your Security Score: %s %s', 'advanced-access-manager'),
                                                empty($score) ? 'Unknown' : $score,
                                                empty($grade) ? '' : "({$grade})"
                                            ); ?>
                                        </a>
                                    </h4>
                                </div>

                                <div id="security-score" class="panel-collapse collapse" role="tabpanel" aria-labelledby="security-score-heading">
                                    <div class="panel-body">
                                        <?php if (!empty($score)) {  ?>
                                        <div class="gauge-wrapper">
                                            <div id="security_gauge" class="gauge-container" data-score="<?php echo esc_attr(AAM_Service_SecurityAudit::get_instance()->get_score()); ?>"></div>
                                        </div>
                                        <?php } else { ?>
                                            <p class="aam-info"><?php echo __('Run first security scan to identify your website AAM security score', 'advanced-access-manager'); ?></p>
                                        <?php } ?>

                                        <a href="#" target="_blank" id="security_audit_tab" class="btn btn-primary btn-block">Learn More →</a>
                                    </div>
                                </div>
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
                                    <span><?php echo __('Access', 'advanced-access-manager'); ?></span>
                                </a>
                                <?php if (current_user_can('aam_manage_settings')) { ?>
                                    <a href="#" title="Settings" data-type="settings" class="aam-area">
                                        <i class="icon-wrench"></i>
                                        <span><?php echo __('Settings', 'advanced-access-manager'); ?></span>
                                    </a>
                                <?php } ?>
                                <?php if (current_user_can('aam_manage_addons')) { ?>
                                    <a href="#" title="Premium" data-type="extensions" class="aam-area">
                                        <i class="icon-cubes"></i>
                                        <span><?php echo __('Premium', 'advanced-access-manager'); ?></span>
                                    </a>
                                <?php } ?>
                                <?php if (current_user_can('aam_view_help_btn')) { ?>
                                    <a href="https://aamportal.com/support?ref=plugin" target="_blank" title="Documentation">
                                        <i class="icon-help-circled"></i>
                                        <span><?php echo __('Docs', 'advanced-access-manager'); ?></span>
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
                                        <a href="#transfer-settings-modal" data-toggle="modal" class="btn btn-warning btn-block"><?php echo __('Export/Import AAM Settings', 'advanced-access-manager'); ?></a>
                                    </div>
                                </div>
                                <div class="row aam-outer-top-xxs">
                                    <div class="col-xs-12">
                                        <a href="#clear-settings-modal" data-toggle="modal" class="btn btn-danger btn-block"><?php echo __('Reset AAM Settings', 'advanced-access-manager'); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="transfer-settings-modal" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <p class="alert alert-danger" id="file-api-error"><?php echo __('Your browser does not support modern way to work with files. The Export/Import feature will not work properly. Consider to use the latest Chrome, Firefox or Safari browser instead.', 'advanced-access-manager'); ?></p>

                                        <div id="import-export-container">
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li role="presentation" class="active"><a href="#export-tab" aria-controls="export-tab" role="tab" data-toggle="tab"><?php echo __('Export', 'advanced-access-manager'); ?></a></li>
                                                <li role="presentation"><a href="#import-tab" aria-controls="import-tab" role="tab" data-toggle="tab"><?php echo __('Import', 'advanced-access-manager'); ?></a></li>
                                            </ul>

                                            <div class="tab-content">
                                                <div role="tabpanel" class="tab-pane active" id="export-tab">
                                                    <p class="alert alert-info">
                                                        <?php echo sprintf(
                                                            __('Export AAM settings so they can be imported to a different location. To learn more about customizing exported data, refer to the %s"How to export/import AAM settings?"%s article.', 'advanced-access-manager'),
                                                            '<a href="https://aamportal.com/question/how-to-export-import-aam-settings?ref=plugin" target="_blank">',
                                                            '</a>'
                                                            );
                                                        ?>
                                                    </p>

                                                    <div class="form-group aam-bordered aam-outer-top-xxs text-center">
                                                        <a href="#" id="export-settings" class="btn btn-primary"><?php echo __('Download Exported Settings', 'advanced-access-manager'); ?></a>
                                                    </div>
                                                </div>

                                                <div role="tabpanel" class="tab-pane" id="import-tab">
                                                    <p class="alert alert-warning"><?php echo __('Select a *.json file with valid AAM settings. All the current AAM settings will be lost and replaced with imported settings.', 'advanced-access-manager'); ?></p>
                                                    <div class="form-group aam-bordered aam-outer-top-xxs">
                                                        <input type="file" id="aam-settings" name="aam-settings" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="clear-settings-modal" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-sm" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title"><?php echo __('Clear all settings', 'advanced-access-manager'); ?></h4>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-center alert alert-danger text-larger"><?php echo __('All AAM settings will be removed.', 'advanced-access-manager'); ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" id="clear-settings"><?php echo __('Clear', 'advanced-access-manager'); ?></button>
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Cancel', 'advanced-access-manager'); ?></button>
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
                                                <th><?php echo __('Registered License', 'advanced-access-manager'); ?></th>
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

                <?php if (AAM_Service_SecurityAudit::bootstrap()->has_report()) { ?>
                <div class="metabox-holder audit-metabox" style="display:none;">
                    <div class="postbox">
                        <div class="inside">
                            <div class="aam-postbox-inside text-center">
                                <p class="text-larger aam-info text-left">
                                    <strong><?php echo __('Need help interpreting your security scan report and identifying the next steps to address critical issues?', 'advanced-access-manager'); ?></strong>
                                    <?php echo __('Share your report with us, and we\'ll get back to you with the next actionable steps. Please note that this service is not free and may incur additional charges based on the number of issues identified.', 'advanced-access-manager'); ?>
                                </p>
                                <a href="#" class="btn btn-info btn-block download-latest-report"><?php echo __('Download Latest Report', 'advanced-access-manager'); ?></a>
                                <a href="#share_audit_confirmation_modal" data-toggle="modal" class="btn btn-primary btn-block"><?php echo __('Share Your Report', 'advanced-access-manager'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="share_audit_confirmation_modal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title"><?php echo __('Share Audit Report', 'advanced-access-manager'); ?></h4>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info text-larger">
                                    <?php echo __('You are about to share the full AAM security audit report with us, which includes the following details:', 'advanced-access-manager'); ?>
                                    <ul class="list-of-items">
                                        <li><?php echo __('List of roles and their capabilities.', 'advanced-access-manager'); ?></li>
                                        <li><?php echo __('Total number of users per role (without individual user details).', 'advanced-access-manager'); ?></li>
                                        <li><?php echo __('Security audit results as outlined on this page.', 'advanced-access-manager'); ?></li>
                                        <li><?php echo __('AAM settings and configurations.', 'advanced-access-manager'); ?></li>
                                        <li><?php echo __('List of installed plugins.', 'advanced-access-manager'); ?></li>
                                    </ul>
                                </div>

                                <div class="form-group aam-mt-2">
                                    <label><?php echo __('Email', 'advanced-access-manager');?><span class="aam-asterix">*</span></label>
                                    <input type="text" class="form-control" id="audit_report_email" name="email" placeholder="<?php echo __('Enter Your Email Address', 'advanced-access-manager'); ?>" />
                                    <span class="aam-hint"><?php echo __('Provide valid email address we can use to contact you back', 'advanced-access-manager'); ?></span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" id="share_audit_report" disabled><?php echo __('Share', 'advanced-access-manager'); ?></button>
                                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Cancel', 'advanced-access-manager'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <?php echo static::loadTemplate(dirname(__DIR__) . '/page/subject-panel.php'); ?>
                <?php echo static::loadTemplate(dirname(__DIR__) . '/page/subject-panel-advanced.php'); ?>
            </div>
        </div>
    </div>

    <?php echo static::loadTemplate(__DIR__ . '/iframe-footer.php'); ?>
<?php }