<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="uri-content">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access to the website URL(s) for the [%s]. Note! All entered URLs have to belong to this particular website and processed by the WordPress core. For more information check %sHow to restrict access to any WordPress website URL%s.', 'b'), $this->getSubject()->getName(), '<a href="https://aamplugin.com/article/how-to-restrict-access-to-any-wordpress-website-url" target="_blank">', '</a>'); ?>
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-uri-overwrite" style="display: <?php echo ($this->isOverwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="uri-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <div class="modal fade" id="uri-model" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('URI Access Rule', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label><?php echo AAM_Backend_View_Helper::preparePhrase('Enter URL [(wildcard * is available with Plus Package extension)]', 'small'); ?></label>
                            <input type="text" class="form-control form-clearable" id="uri-rule" placeholder="Enter valid URL" />
                        </div>

                        <label><?php echo __('How to redirect user when match?', AAM_KEY); ?></label><br />

                        <div class="radio">
                            <input type="radio" name="uri.access.type" id="uri-access-allow" value="allow" data-action="none" />
                            <label for="uri-access-allow"><?php echo __('Allow Access', AAM_KEY); ?></label>
                        </div>
                        <div class="radio">
                            <input type="radio" name="uri.access.type" id="uri-access-default" value="default" data-action="none" />
                            <label for="uri-access-default"><?php echo AAM_Backend_View_Helper::preparePhrase('Deny Access [(show "Access Denied" message)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input type="radio" name="uri.access.type" id="uri-access-deny-message" data-action="#uri-access-deny-message-action" value="message" />
                            <label for="uri-access-deny-message"><?php echo AAM_Backend_View_Helper::preparePhrase('Show customized message [(plain text or HTML)]', 'small'); ?></label>
                        </div>
                        <?php if ($this->getSubject()->isVisitor()) { ?>
                            <div class="radio">
                                <input type="radio" name="uri.access.type" id="uri-access-deny-login" value="login" />
                                <label for="uri-access-deny-login"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirect to the login page [(after login, user will be redirected back to the restricted page)]', 'small'); ?></label>
                            </div>
                        <?php } ?>
                        <div class="radio">
                            <input type="radio" name="uri.access.type" id="uri-access-deny-page" data-action="#uri-access-deny-page-action" value="page" />
                            <label for="uri-access-deny-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to existing page [(select from the drop-down)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input type="radio" name="uri.access.type" id="uri-access-deny-url" data-action="#uri-access-deny-url-action" value="url" />
                            <label for="uri-access-deny-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to local URL [(enter valid URL starting from http or https)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input type="radio" name="uri.access.type" id="uri-access-deny-callback" data-action="#uri-access-deny-callback-action" value="callback" />
                            <label for="uri-access-deny-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                        </div>

                        <div class="form-group aam-uri-access-action" id="uri-access-deny-message-action" style="display: none;">
                            <label><?php echo __('Customized Message', AAM_KEY); ?></label>
                            <textarea class="form-control form-clearable" rows="3" id="uri-access-deny-message-value" placeholder="<?php echo __('Enter message...', AAM_KEY); ?>"></textarea>
                        </div>

                        <div class="form-group aam-uri-access-action" id="uri-access-deny-page-action" style="display: none;">
                            <label><?php echo __('Existing Page', AAM_KEY); ?></label>
                            <?php
                                wp_dropdown_pages(array(
                                    'depth' => 99,
                                    'echo' => 1,
                                    'id' => 'uri-access-deny-page-value',
                                    'class' => 'form-control form-clearable',
                                    'show_option_none' => __('-- Select Page --', AAM_KEY)
                                ));
                            ?>
                        </div>

                        <div class="form-group aam-uri-access-action" id="uri-access-deny-url-action" style="display: none;">
                            <label><?php echo __('The Valid Redirect URL', AAM_KEY); ?></label>
                            <input type="text" class="form-control form-clearable" placeholder="https://" id="uri-access-deny-url-value" />
                        </div>

                        <div class="form-group aam-uri-access-action" id="uri-access-deny-redirect-code" style="display: none;">
                            <label><?php echo __('HTTP Redirect Code', AAM_KEY); ?></label>
                            <select class="form-control form-clearable" id="uri-access-deny-redirect-code-value">
                                <option value=""><?php echo __('HTTP Code (Default 307)', AAM_KEY); ?></option>
                                <option value="301"><?php echo __('301 - Moved Permanently', AAM_KEY); ?></option>
                                <option value="302"><?php echo __('302 - Found', AAM_KEY); ?></option>
                                <option value="303"><?php echo __('303 - See Other', AAM_KEY); ?></option>
                                <option value="307"><?php echo __('307 - Temporary Redirect', AAM_KEY); ?></option>
                            </select>
                        </div>

                        <div class="form-group aam-uri-access-action" id="uri-access-deny-callback-action" style="display: none;">
                            <label><?php echo __('PHP Callback Function', AAM_KEY); ?></label>
                            <input type="text" class="form-control form-clearable" placeholder="Enter valid callback" id="uri-access-deny-callback-value" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="uri-save-btn"><?php echo __('Save', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="uri-delete-model" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete URI Rule', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <p class="aam-notification">
                                <?php echo __('You are about to delete the URI Rule. Please confirm!', AAM_KEY); ?>
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="uri-delete-btn"><?php echo __('Delete', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <table id="uri-list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th width="60%"><?php echo __('URI', AAM_KEY); ?></th>
                            <th width="20%"><?php echo __('Type', AAM_KEY); ?></th>
                            <th>Type Details</th>
                            <th>HTTP Code</th>
                            <th><?php echo __('Actions', AAM_KEY); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
<?php }