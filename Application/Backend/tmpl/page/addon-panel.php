<?php
    /**
     * @since 6.2.0 Removed expiration date for license to avoid confusion
     * @since 6.0.5 Fixed typo in the license expiration property. Enriched plugin' status display
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.2.0
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div id="extension-content" class="extension-container">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo AAM_Backend_View_Helper::preparePhrase('By purchasing any of the premium addon(s) below, you obtain the license that allows you to install and use AAM software for one physical WordPress installation only. Exceptions are websites where URL is either [localhost] or starts with [dev.], [staging.], [test.] or [demo.] They are considered as development websites and you can use the purchased license unlimited number of times before it is activated on a production website. [Money back guaranteed] within 30 day from the time of purchase.', 'i', 'i', 'i', 'i', 'i', 'i', 'b'); ?><br />
                </p>
            </div>
        </div>

        <label for="extension-key"><?php echo __('Download Addon', AAM_KEY); ?> <a href="#license-key-info-modal" data-toggle="modal"><i class="icon-help-circled"></i></a></label>
        <div class="row">
            <div class="col-xs-8">
                <div class="form-group">
                    <input type="text" class="form-control" id="extension-key" placeholder="<?php echo __('Enter The License Key', AAM_KEY); ?>" />
                </div>
            </div>
            <div class="col-xs-4">
                <button class="btn btn-primary btn-block" id="download-extension"><i class="icon-download-cloud"></i> <?php echo __('Download', AAM_KEY); ?></button>
            </div>
        </div>

        <?php $commercial = AAM_Addon_Repository::getInstance()->getList(); ?>

        <div class="aam-outer-top-xs">
            <ul class="nav nav-tabs" role="tablist">
                <?php if (count($commercial)) { ?><li role="presentation" class="active"><a href="#premium-extensions" aria-controls="premium-extensions" role="tab" data-toggle="tab"><i class='icon-basket'></i> <?php echo __('Premium', AAM_KEY); ?></a></li><?php } ?>
                <li class="margin-right aam-update-check"><a href="#" id="check-for-updates"><i class='icon-arrows-cw'></i> <?php echo __('Check For Updates', AAM_KEY); ?></a></li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane<?php echo (count($commercial) ? ' active' : ''); ?>" id="premium-extensions">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <?php foreach ($commercial as $i => $product) { ?>
                                <tr>
                                    <td width="80%">
                                        <span class='aam-setting-title'><?php echo $product['title'], (!empty($product['tag']) ? '<sup><span class="badge sup">' . $product['tag'] . '</span></sup>' : ''), (!empty($product['version']) ? ' <small class="text-muted">' . $product['version'] . '</small>' : ''); ?></span>
                                        <?php if (!empty($product['license'])) { ?>
                                            <small class="aam-license-key"><b><?php echo __('License', AAM_KEY); ?>:</b> <a href="https://aamplugin.com/license/<?php echo $product['license']; ?>" target="_blank"><?php echo $product['license']; ?></a></small>
                                        <?php } elseif (!empty($product['version'])) { ?>
                                            <small class="aam-license-key"><b><?php echo __('License', AAM_KEY); ?>:</b> <span class="text-danger"><?php echo __('unregistered version', AAM_KEY); ?></span></small>
                                        <?php } ?>
                                        <p class="aam-extension-description">
                                            <?php echo $product['description']; ?>
                                        </p>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($product['hasUpdate'])) { ?>
                                            <a href="#" class="btn btn-sm btn-warning btn-block disabled"><i class="icon-attention-circled"></i> <?php echo __('Update Available', AAM_KEY); ?></a>
                                        <?php } elseif (!empty($product['isActive'])) { ?>
                                            <a href="#" class="btn btn-sm btn-success btn-block disabled"><i class="icon-check"></i> <?php echo __('Active', AAM_KEY); ?></a>
                                        <?php } elseif (!empty($product['version'])) { ?>
                                            <a href="#" class="btn btn-sm btn-default btn-block disabled"><i class="icon-info-circled"></i> <?php echo __('Inactive', AAM_KEY); ?></a>
                                        <?php } else { ?>
                                            <a href="<?php echo $product['url']; ?>" target="_blank" class="btn btn-sm btn-primary btn-block"><i class="icon-link"></i> <?php echo __('Read More', AAM_KEY); ?></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
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
                            <?php echo __('Insert license key that you received after the payment (find the email example below). It might take up to 2 hours to process the payment.', AAM_KEY); ?>
                            <br /> <br />
                            <img src="https://aamplugin.com/media/img/email-confirmation.jpg" class="img-responsive" />
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
                        <p class="alert alert-success text-center">
                            <?php echo __('The plugin has been successfully downloaded from our server.', AAM_KEY); ?>
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