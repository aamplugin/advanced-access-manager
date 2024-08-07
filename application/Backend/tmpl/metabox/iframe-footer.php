<?php
/**
 * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/369
 * @since 6.9.2  https://github.com/aamplugin/advanced-access-manager/issues/229
 * @since 6.8.4  https://github.com/aamplugin/advanced-access-manager/issues/212
 * @since 6.8.4  https://github.com/aamplugin/advanced-access-manager/issues/213
 * @since 6.0.5  Changed the way core libraries are loaded to avoid issue with
 *               concatenated scripts with PHP
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.28
 **/
if (defined('AAM_KEY')) { ?>
        <?php global $wp_scripts; ?>

        <?php $wp_scripts->do_items(array('jquery-core', 'jquery-ui-autocomplete', 'jquery-migrate', 'code-editor', 'aam-iframe')); ?>
        <?php do_action('aam_iframe_footer_action'); ?>

        <?php $wp_scripts->do_item('aam-iframe'); ?>

        <div class="modal fade" data-backdrop="false" id="report_issue_modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Report Issue', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label><?php echo __('Your Email Address (Optional)', AAM_KEY); ?></label>
                            <input
                                type="email"
                                class="form-control"
                                id="issue_reporter_email"
                                placeholder="<?php echo __('Enter email we can use to follow-up with you', AAM_KEY); ?>"
                            />
                        </div>

                        <div class="panel-group" id="report_issue_block" role="tablist" aria-multiselectable="true">
                            <div class="panel panel-default">
                                <div class="panel-heading" role="tab" id="sending_info-heading">
                                    <h4 class="panel-title">
                                        <a role="button" data-toggle="collapse" data-parent="#report_issue_block" href="#sending_info" aria-controls="sending_info">
                                            <?php echo __('What information are you sending?', AAM_KEY); ?>
                                        </a>
                                    </h4>
                                </div>

                                <div id="sending_info" class="panel-collapse collapse" role="tabpanel" aria-labelledby="sending_info-heading">
                                    <div class="panel-body">
                                        <pre id="sending_info_preview"></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success btn-save" id="send_report_btn"><?php echo __('Send Report', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
<?php }