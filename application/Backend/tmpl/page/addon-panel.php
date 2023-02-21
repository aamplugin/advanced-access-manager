<?php

/**
 * @since 6.9.5 https://github.com/aamplugin/advanced-access-manager/issues/243
 * @since 6.9.2 https://github.com/aamplugin/advanced-access-manager/issues/229
 * @since 6.8.1 https://github.com/aamplugin/advanced-access-manager/issues/203
 * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
 * @since 6.4.0 https://github.com/aamplugin/advanced-access-manager/issues/78
 * @since 6.2.0 Removed expiration date for license to avoid confusion
 * @since 6.0.5 Fixed typo in the license expiration property. Enriched plugin' status display
 * @since 6.0.0 Initial implementation of the template
 *
 * @version 6.9.5
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div id="extension-content" class="extension-container">
        <label for="extension-key"><?php echo __('License Key', AAM_KEY); ?> <a href="#license-key-info-modal" data-toggle="modal"><i class="icon-help-circled"></i></a></label>
        <div class="row">
            <div class="col-xs-6">
                <div class="form-group">
                    <input type="text" class="form-control" id="extension-key" placeholder="<?php echo __('Enter The License Key', AAM_KEY); ?>" />
                </div>
            </div>
            <div class="col-xs-3">
                <button class="btn btn-primary btn-block" id="download-extension"><i class="icon-download-cloud"></i> <?php echo __('Download Addon', AAM_KEY); ?></button>
            </div>
        </div>

        <?php $premium = AAM_Addon_Repository::getInstance()->getPremiumData(); ?>

        <div class="aam-outer-top-xs">
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#premium-extensions" aria-controls="premium-extensions" role="tab" data-toggle="tab"><i class='icon-basket'></i> <?php echo __('Premium', AAM_KEY); ?></a></li>
                <li role="presentation"><a href="#free-extensions" aria-controls="free-extensions" role="tab" data-toggle="tab"><i class='icon-cubes'></i> <?php echo __('Free', AAM_KEY); ?></a></li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="premium-extensions">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td width="80%">
                                    <span class='aam-setting-title'>
                                        <?php echo $premium['title'], (!empty($premium['version']) ? ' <small class="text-muted">' . $premium['version'] . '</small>' : ''); ?>
                                    </span>

                                    <p class="aam-extension-description">
                                        <?php echo $premium['description']; ?>
                                    </p>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo $premium['url']; ?>" target="_blank" class="btn btn-sm btn-primary btn-block"><i class="icon-link"></i> <?php echo __('Read More', AAM_KEY); ?></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div role="tabpanel" class="tab-pane" id="free-extensions">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td width="80%">
                                    <span class='aam-setting-title'>AAM Protected Media Files</span>
                                    <p class="aam-extension-description">
                                        Prevent direct access to the unlimited number of media library items either for visitors, individual users or groups of users (roles). This plugin does not modify a physical file’s location or URL.
                                    </p>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo admin_url('plugin-install.php?tab=plugin-information&plugin=aam-protected-media-files'); ?>" target="_blank" class="btn btn-sm btn-primary btn-block"><i class="icon-link"></i> <?php echo __('Read More', AAM_KEY); ?></a>
                                </td>
                            </tr>
                            <tr>
                                <td width="80%">
                                    <span class='aam-setting-title'>Noti - Activity Notification</span>
                                    <p class="aam-extension-description">
                                        Noti - Activity Notification (aka Noti) plugin is your single-stop shop for all you need to track any WordPress website activities. And it is completely free.
                                    </p>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo admin_url('plugin-install.php?tab=plugin-information&plugin=noti-activity-notification'); ?>" target="_blank" class="btn btn-sm btn-primary btn-block"><i class="icon-link"></i> <?php echo __('Read More', AAM_KEY); ?></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="license-key-info-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('License Key Info', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body aam-info-modal">
                        <p>
                            <?php echo __('Insert license key that you received after the payment. It might take up to 30 minutes to process the payment.', AAM_KEY); ?>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="downloaded-info-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Plugin Installation', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="alert alert-warning text-center">
                            <?php  echo AAM_Backend_View_Helper::preparePhrase('[NOTE!] There are still a couple steps that you need to do to install the plugin.', 'strong'); ?>
                        </p>

                        <p class="aam-info aam-outer-top-xs">
                            <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('With AAM v6.0.0 or higher, all premium addons are [regular WordPress plugins] that you can upload by going to the %sPlugins%s page or extract downloaded ZIP archive to the [/wp-content/plugins] folder.', 'b', 'i'), '<a href="' . admin_url('plugin-install.php?tab=upload') . '" target="_blank">', '</a>'); ?>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }
