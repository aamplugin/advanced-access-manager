<?php
/**
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/273
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.10
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="jwt-content">
        <?php $subject = AAM_Backend_Subject::getInstance(); ?>

        <div class="row">
            <div class="col-xs-12">
                <table id="jwt-list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Token</th>
                            <th>URL</th>
                            <th width="8%">&nbsp;</th>
                            <th width="70%"><?php echo __('ID/Status', AAM_KEY); ?></th>
                            <th><?php echo __('Actions', AAM_KEY); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="create-jwt-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Create JWT Token', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group aam-bordered">
                            <label for="jwt-expiration-datapicker" class="aam-block">
                                <?php echo __('JWT Expires', AAM_KEY); ?>
                            </label>
                            <div id="jwt-expiration-datapicker"></div>
                            <input type="hidden" id="jwt-expires" />
                        </div>

                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>
                                        <span class='aam-setting-title'><?php echo __('Is token refreshable?', AAM_KEY); ?></span>
                                        <p class="aam-setting-description">
                                            <?php echo __('Whether this token, before expires, can be used to obtain a new token for the same time duration or not.', AAM_KEY); ?>
                                        </p>
                                    </td>
                                    <td class="text-center">
                                    <input data-toggle="toggle" id="jwt-refreshable" type="checkbox" data-on="Yes" data-off="No" data-size="small" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="form-group jwt-claims-container">
                            <label for="aam-jwt-claims-editor" class="aam-block">
                                <?php echo __('JWT Additional Claims', AAM_KEY); ?>
                            </label>
                            <textarea
                                id="aam-jwt-claims-editor"
                                class="configpress-editor"
                                style="border: 1px solid #CCCCCC; width: 100%"
                                rows="5"
                            ></textarea>
                            <small><?php echo __('Additional claims to include in the token.', AAM_KEY); ?></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="create-jwt-btn"><?php echo __('Create', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="view-jwt-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('View JWT Token', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="view-jwt-token" class="aam-block">
                                <?php echo __('JWT Token (for API request)', AAM_KEY); ?>
                                <a href="#" class="aam-copy-clipboard" data-clipboard-target="#view-jwt-token"><?php echo __('Copy to clipboard', AAM_KEY); ?></a>
                            </label>
                            <textarea class="form-control" id="view-jwt-token" readonly rows="5"></textarea>
                        </div>

                        <hr/>

                        <div class="form-group">
                            <label for="view-jwt-url" class="aam-block">
                                <?php echo __('Passwordless Login URL (with JWT token)', AAM_KEY); ?>
                                <a href="#" class="aam-copy-clipboard" data-clipboard-target="#view-jwt-url"><?php echo __('Copy to clipboard', AAM_KEY); ?></a>
                            </label>
                            <textarea class="form-control" id="view-jwt-url" readonly rows="5"></textarea>
                            <small><?php echo __('Use this URL to authenticate account without the need to enter username/password.', AAM_KEY); ?></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="delete-jwt-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete JWT Token', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="alert alert-danger text-larger"><?php echo __('You are about to delete already issued JWT token. Any application or user that has this token, will no longer be able to use it. Please confirm.') ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="jwt-delete-btn"><?php echo __('Delete', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }