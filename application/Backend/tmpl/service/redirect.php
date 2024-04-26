<?php
/**
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/359
 * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/309
 * @since 6.9.6  https://github.com/aamplugin/advanced-access-manager/issues/252
 * @since 6.8.0  https://github.com/aamplugin/advanced-access-manager/issues/195
 * @since 6.0.0  Initial implementation of the templates
 *
 * @version 6.9.26
 *
 */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php $subject = AAM_Backend_Subject::getInstance(); ?>

    <div class="aam-feature" id="redirect-content">
        <div class="row">
            <div class="col-xs-12">
                <?php if ($subject->isDefault()) {  ?>
                    <p class="aam-info">
                        <?php echo AAM_Backend_View_Helper::preparePhrase('Define the [default] redirect for all users, roles and visitors when access is denied to any restricted resources on your website.', 'strong'); ?>
                    </p>
                <?php } else { ?>
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Customize redirect for [%s] when access is denied to restricted resources like posts, categories, menus, etc.', 'b'), AAM_Backend_Subject::getInstance()->getName()); ?>
                    </p>
                <?php } ?>
                <div class="aam-overwrite" id="aam-redirect-overwrite" style="display: <?php echo ($this->isOverwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="redirect-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div>
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active"><a href="#frontend-redirect" aria-controls="frontend" role="tab" data-toggle="tab"><i class="icon-home"></i> <?php echo __('Frontend Redirect', AAM_KEY); ?></a></li>
                        <?php if (!$subject->isVisitor()) { ?><li role="presentation"><a href="#backend-redirect" aria-controls="backend" role="tab" data-toggle="tab"><i class="icon-circle"></i> <?php echo __('Backend Redirect', AAM_KEY); ?></a></li><?php } ?>
                    </ul>

                    <?php $frontendType = $this->getOption('frontend.redirect.type', 'default'); ?>
                    <?php $backendType  = $this->getOption('backend.redirect.type', 'default'); ?>

                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active" id="frontend-redirect">
                            <div class="radio">
                                <input type="radio" name="frontend.redirect.type" id="frontend-redirect-default" value="default" data-action="#frontend-default-action" data-group="frontend"<?php echo ($frontendType == 'default' ? ' checked' : ''); ?> />
                                <label for="frontend-redirect-default"><?php echo AAM_Backend_View_Helper::preparePhrase('Default [("Access Denied" message)]', 'small'); ?></label>
                            </div>
                            <div class="radio">
                                <input type="radio" name="frontend.redirect.type" id="frontend-redirect-message" data-action="#frontend-message-action" value="custom_message" data-group="frontend"<?php echo ($frontendType == 'message' ? ' checked' : ''); ?> />
                                <label for="frontend-redirect-message"><?php echo AAM_Backend_View_Helper::preparePhrase('Show customized message [(plain text or HTML)]', 'small'); ?></label>
                            </div>
                            <?php if ($subject->isVisitor()) { ?>
                                <div class="radio">
                                    <input type="radio" name="frontend.redirect.type" id="frontend-redirect-login" value="login_redirect" data-action="none" data-group="frontend"<?php echo ($frontendType == 'login' ? ' checked' : ''); ?> />
                                    <label for="frontend-redirect-login"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirect to the login page [(after login, user will be redirected back to the restricted page)]', 'small'); ?></label>
                                </div>
                            <?php } ?>
                            <div class="radio">
                                <input type="radio" name="frontend.redirect.type" id="frontend-redirect-page" data-action="#frontend-page-action" value="page_redirect" data-group="frontend"<?php echo ($frontendType == 'page' ? ' checked' : ''); ?> />
                                <label for="frontend-redirect-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to existing page [(select from the drop-down)]', 'small'); ?></label>
                            </div>
                            <div class="radio">
                                <input type="radio" name="frontend.redirect.type" id="frontend-redirect-url" data-action="#frontend-url-action" value="url_redirect" data-group="frontend"<?php echo ($frontendType == 'url' ? ' checked' : ''); ?> />
                                <label for="frontend-redirect-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to local URL [(enter valid URL starting from http or https)]', 'small'); ?></label>
                            </div>
                            <div class="radio">
                                <input type="radio" name="frontend.redirect.type" id="frontend-redirect-callback" data-action="#frontend-callback-action" value="trigger_callback" data-group="frontend"<?php echo ($frontendType == 'callback' ? ' checked' : ''); ?> />
                                <label for="frontend-redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                            </div>

                            <div class="form-group aam-redirect-action frontend" id="frontend-default-action" style="display: <?php echo ($frontendType == 'default' ? 'block' : 'none'); ?>;">
                                <label for="frontend-default-status-code"><?php echo __('HTTP Status Code', AAM_KEY); ?></label>
                                <?php $redirect_default_code = $this->getOption('frontend.redirect.default.code'); ?>
                                <select class="form-control form-clearable" id="frontend-default-status-code" data-group="frontend">
                                    <option value="401"><?php echo __('HTTP Code (Default 401 - Unauthorized)', AAM_KEY); ?></option>
                                    <option value="402"<?php echo $redirect_default_code == 402 ? ' selected' : ''; ?>><?php echo __('402 - Payment Required', AAM_KEY); ?></option>
                                    <option value="403"<?php echo $redirect_default_code == 403 ? ' selected' : ''; ?>><?php echo __('403 - Forbidden', AAM_KEY); ?></option>
                                    <option value="404"<?php echo $redirect_default_code == 404 ? ' selected' : ''; ?>><?php echo __('404 - Not Found', AAM_KEY); ?></option>
                                    <option value="500"<?php echo $redirect_default_code == 500 ? ' selected' : ''; ?>><?php echo __('500 - Internal Server Error', AAM_KEY); ?></option>
                                </select>
                            </div>

                            <div class="form-group aam-redirect-action frontend" id="frontend-message-action" style="display: <?php echo ($frontendType == 'message' ? 'block' : 'none'); ?>;">
                                <label for="frontend-message"><?php echo __('Customized Message', AAM_KEY); ?></label>
                                <?php $redirect_message = $this->getOption('frontend.redirect.message') ?>
                                <textarea class="form-control" name="frontend.redirect.message" data-group="frontend" rows="3" placeholder="<?php echo __('Enter message...', AAM_KEY); ?>"><?php echo is_string($redirect_message) ? stripslashes($redirect_message) : ''; ?></textarea>

                                <div class="aam-mt-1">
                                    <label for="frontend-message-status-code"><?php echo __('HTTP Status Code', AAM_KEY); ?></label>
                                    <?php $redirect_message_code = $this->getOption('frontend.redirect.message.code'); ?>
                                    <select class="form-control form-clearable" name="frontend.redirect.message.code" id="frontend-message-status-code" data-group="frontend">
                                        <option value="401"><?php echo __('HTTP Code (Default 401 - Unauthorized)', AAM_KEY); ?></option>
                                        <option value="402"<?php echo $redirect_message_code == 402 ? ' selected' : ''; ?>><?php echo __('402 - Payment Required', AAM_KEY); ?></option>
                                        <option value="403"<?php echo $redirect_message_code == 403 ? ' selected' : ''; ?>><?php echo __('403 - Forbidden', AAM_KEY); ?></option>
                                        <option value="404"<?php echo $redirect_message_code == 404 ? ' selected' : ''; ?>><?php echo __('404 - Not Found', AAM_KEY); ?></option>
                                        <option value="500"<?php echo $redirect_message_code == 500 ? ' selected' : ''; ?>><?php echo __('500 - Internal Server Error', AAM_KEY); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group aam-redirect-action frontend" id="frontend-page-action" style="display: <?php echo ($frontendType == 'page' ? 'block' : 'none'); ?>;">
                                <label for="frontend-page"><?php echo __('Existing Page', AAM_KEY); ?></label>
                                <?php
                                    wp_dropdown_pages(array(
                                        'depth' => 99,
                                        'selected' => $this->getOption('frontend.redirect.page'),
                                        'echo' => 1,
                                        'name' => 'frontend.redirect.page',
                                        'id' => 'frontend-page',
                                        'class' => 'form-control',
                                        'show_option_none' => __('-- Select Page --', AAM_KEY)
                                    ));
                                ?>
                            </div>

                            <div class="form-group aam-redirect-action frontend" id="frontend-url-action" style="display: <?php echo ($frontendType == 'url' ? 'block' : 'none'); ?>;">
                                <label for="frontend-url"><?php echo __('The URL', AAM_KEY); ?></label>
                                <input type="text" class="form-control" name="frontend.redirect.url" data-group="frontend" placeholder="https://" value="<?php echo stripslashes(esc_js($this->getOption('frontend.redirect.url'))); ?>" />
                            </div>

                            <div class="form-group aam-redirect-action frontend" id="frontend-callback-action" style="display: <?php echo ($frontendType == 'callback' ? 'block' : 'none'); ?>;">
                                <label for="frontend-url"><?php echo __('PHP Callback Function', AAM_KEY); ?></label>
                                <input type="text" class="form-control" placeholder="<?php echo __('Enter valid callback', AAM_KEY); ?>" data-group="frontend" name="frontend.redirect.callback" value="<?php echo stripslashes(esc_js($this->getOption('frontend.redirect.callback'))); ?>" />
                            </div>
                        </div>
                        <div role="tabpanel" class="tab-pane" id="backend-redirect">
                            <div class="radio">
                                <input type="radio" name="backend.redirect.type" id="backend-redirect-default" data-action="#backend-default" value="default" data-group="backend"<?php echo ($backendType == 'default' ? ' checked' : ''); ?> />
                                <label for="backend-redirect-default"><?php echo AAM_Backend_View_Helper::preparePhrase('Default [("Access Denied" message)]', 'small'); ?></label>
                            </div>
                            <div class="radio">
                                <input type="radio" name="backend.redirect.type" id="backend-redirect-message" data-action="#backend-message" value="custom_message" data-group="backend"<?php echo ($backendType == 'message' ? ' checked' : ''); ?> />
                                <label for="backend-redirect-message"><?php echo AAM_Backend_View_Helper::preparePhrase('Show customized message [(plain text or HTML)]', 'small'); ?></label>
                            </div>
                            <div class="radio">
                                <input type="radio" name="backend.redirect.type" id="backend-redirect-page" data-action="#backend-page-action" value="page_redirect" data-group="backend"<?php echo ($backendType == 'page' ? ' checked' : ''); ?> />
                                <label for="backend-redirect-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to existing frontend page [(select from the drop-down)]', 'small'); ?></label>
                            </div>
                            <div class="radio">
                                <input type="radio" name="backend.redirect.type" id="backend-redirect-url" data-action="#backend-url" value="url_redirect" data-group="backend"<?php echo ($backendType == 'url' ? ' checked' : ''); ?> />
                                <label for="backend-redirect-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to local URL [(enter valid URL starting from http or https)]', 'small'); ?></label>
                            </div>
                            <div class="radio">
                                <input type="radio" name="backend.redirect.type" id="backend-redirect-callback" data-action="#backend-callback-action" value="trigger_callback" data-group="backend"<?php echo ($backendType == 'callback' ? ' checked' : ''); ?> />
                                <label for="backend-redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                            </div>

                            <div class="form-group aam-redirect-action backend" id="backend-default" style="display: <?php echo ($backendType == 'default' ? 'block' : 'none'); ?>;">
                                <label for="frontend-default-status-code"><?php echo __('HTTP Status Code', AAM_KEY); ?></label>
                                <?php $redirect_default_code = $this->getOption('backend.redirect.default.code'); ?>
                                <select class="form-control form-clearable" id="backend-default-status-code" data-group="frontend">
                                    <option value="401"><?php echo __('HTTP Code (Default 401 - Unauthorized)', AAM_KEY); ?></option>
                                    <option value="402"<?php echo $redirect_default_code == 402 ? ' selected' : ''; ?>><?php echo __('402 - Payment Required', AAM_KEY); ?></option>
                                    <option value="403"<?php echo $redirect_default_code == 403 ? ' selected' : ''; ?>><?php echo __('403 - Forbidden', AAM_KEY); ?></option>
                                    <option value="404"<?php echo $redirect_default_code == 404 ? ' selected' : ''; ?>><?php echo __('404 - Not Found', AAM_KEY); ?></option>
                                    <option value="500"<?php echo $redirect_default_code == 500 ? ' selected' : ''; ?>><?php echo __('500 - Internal Server Error', AAM_KEY); ?></option>
                                </select>
                            </div>

                            <div class="form-group aam-redirect-action backend" id="backend-message" style="display: <?php echo ($backendType == 'message' ? 'block' : 'none'); ?>;">
                                <label for="backend-message"><?php echo __('Customized Message', AAM_KEY); ?></label>
                                <textarea class="form-control" rows="3" data-group="backend" placeholder="<?php echo __('Enter message...', AAM_KEY); ?>" name="backend.redirect.message"><?php $o = $this->getOption('backend.redirect.message'); echo esc_textarea(is_string($o) ? $o : ''); ?></textarea>

                                <div class="aam-mt-1">
                                    <label for="backend-message-status-code"><?php echo __('HTTP Status Code', AAM_KEY); ?></label>
                                    <?php $redirect_message_code = $this->getOption('backend.redirect.message.code'); ?>
                                    <select class="form-control form-clearable" name="backend.redirect.message.code" id="backend-message-status-code" data-group="backend">
                                        <option value="401"><?php echo __('HTTP Code (Default 401 - Unauthorized)', AAM_KEY); ?></option>
                                        <option value="402"<?php echo $redirect_message_code == 402 ? ' selected' : ''; ?>><?php echo __('402 - Payment Required', AAM_KEY); ?></option>
                                        <option value="403"<?php echo $redirect_message_code == 403 ? ' selected' : ''; ?>><?php echo __('403 - Forbidden', AAM_KEY); ?></option>
                                        <option value="404"<?php echo $redirect_message_code == 404 ? ' selected' : ''; ?>><?php echo __('404 - Not Found', AAM_KEY); ?></option>
                                        <option value="500"<?php echo $redirect_message_code == 500 ? ' selected' : ''; ?>><?php echo __('500 - Internal Server Error', AAM_KEY); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group aam-redirect-action backend" id="backend-page-action" style="display: <?php echo ($backendType == 'page' ? 'block' : 'none'); ?>;">
                                <label for="backend-page"><?php echo __('Existing Page', AAM_KEY); ?></label>
                                <?php
                                    wp_dropdown_pages(array(
                                        'depth' => 99,
                                        'selected' => $this->getOption('backend.redirect.page'),
                                        'echo' => 1,
                                        'name' => 'backend.redirect.page',
                                        'id' => 'backend-page',
                                        'class' => 'form-control',
                                        'show_option_none' => __('-- Select Page --', AAM_KEY)
                                    ));
                                ?>
                            </div>

                            <div class="form-group aam-redirect-action backend" id="backend-url" style="display: <?php echo ($backendType == 'url' ? 'block' : 'none'); ?>;">
                                <label for="backend-url"><?php echo __('The URL', AAM_KEY); ?></label>
                                <input type="text" class="form-control" data-group="backend" placeholder="https://" name="backend.redirect.url" value="<?php echo stripslashes(esc_js($this->getOption('backend.redirect.url'))); ?>" />
                            </div>

                            <div class="form-group aam-redirect-action backend" id="backend-callback-action" style="display: <?php echo ($backendType == 'callback' ? 'block' : 'none'); ?>;">
                                <label for="frontend-url"><?php echo __('PHP Callback Function', AAM_KEY); ?></label>
                                <input type="text" class="form-control" data-group="backend" placeholder="<?php echo __('Enter valid callback', AAM_KEY); ?>" name="backend.redirect.callback" value="<?php echo stripslashes(esc_js($this->getOption('backend.redirect.callback'))); ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }