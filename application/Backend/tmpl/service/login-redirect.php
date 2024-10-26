<?php
/**
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/285
 * @since 6.8.0  https://github.com/aamplugin/advanced-access-manager/issues/195
 * @since 6.0.0  Initial implementation of the templates
 *
 * @version 6.9.12
 */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::getInstance();
        $service      = $access_level->login_redirect();
        $settings     = $service->get_redirect();
    ?>
    <div class="aam-feature" id="login_redirect-content">
        <div class="row">
            <div class="col-xs-12">
                <?php if (AAM::api()->configs()->get_config('core.settings.ui.tips')) { ?>
                    <p class="aam-info">
                        <?php echo __('Seamlessly redirects users to their designated landing page post-login, ensuring personalized and efficient access to the website.', AAM_KEY); ?>
                    </p>
                <?php } ?>

                <div class="aam-overwrite" id="aam-login-redirect-overwrite" style="display: <?php echo ($service->get_resource()->is_overwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="login-redirect-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a></span>
                </div>

                <?php $type = $settings['type']; ?>

                <div class="radio">
                    <input
                        type="radio"
                        name="login.redirect.type"
                        id="login-redirect-default"
                        data-action="#default-redirect-action"
                        value="default"
                        <?php echo ($type === 'default' ? ' checked' : ''); ?>
                    />
                    <label for="login-redirect-default"><?php echo __('WordPress default behavior', AAM_KEY); ?></label>
                </div>
                <div class="radio">
                    <input
                        type="radio"
                        name="login.redirect.type"
                        id="login-redirect-page"
                        data-action="#page-login-redirect-action"
                        value="page_redirect"
                        <?php echo ($type === 'page_redirect' ? ' checked' : ''); ?>
                    />
                    <label for="login-redirect-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to an existing page [(select from the drop-down)]', 'small'); ?></label>
                </div>
                <div class="radio">
                    <input
                        type="radio"
                        name="login.redirect.type"
                        id="login-redirect-url"
                        data-action="#url-login-redirect-action"
                        value="url_redirect"
                        <?php echo ($type === 'url_redirect' ? ' checked' : ''); ?>
                    />
                    <label for="login-redirect-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to a URL [(enter a valid URL starting from http or https)]', 'small'); ?></label>
                </div>
                <div class="radio">
                    <input
                        type="radio"
                        name="login.redirect.type"
                        id="login-redirect-callback"
                        data-action="#callback-login-redirect-action"
                        value="trigger_callback"
                        <?php echo ($type === 'trigger_callback' ? ' checked' : ''); ?>
                    />
                    <label for="login-redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger a PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                </div>

                <div
                    class="form-group login-redirect-action"
                    id="page-login-redirect-action"
                    style="display: <?php echo ($type == 'page_redirect' ? 'block' : 'none'); ?>;"
                >
                    <label><?php echo __('Existing Page', AAM_KEY); ?></label>
                    <?php
                        wp_dropdown_pages(array(
                            'depth'            => 99,
                            'selected'         => isset($settings['redirect_page_id']) ? $settings['redirect_page_id'] : 0,
                            'echo'             => 1,
                            'id'               => 'login-redirect-page',
                            'class'            => 'form-control',
                            'show_option_none' => __('-- Select Page --', AAM_KEY)
                        ));
                    ?>
                </div>

                <div
                    class="form-group login-redirect-action"
                    id="url-login-redirect-action"
                    style="display: <?php echo ($type === 'url_redirect' ? 'block' : 'none'); ?>;"
                >
                    <label><?php echo __('The URL', AAM_KEY); ?></label>
                    <input
                        type="text"
                        class="form-control"
                        name="login.redirect.url"
                        placeholder="https://"
                        value="<?php echo stripslashes(esc_js(isset($settings['redirect_url']) ? $settings['redirect_url'] : '')); ?>"
                    />
                </div>

                <div
                    class="form-group login-redirect-action"
                    id="callback-login-redirect-action"
                    style="display: <?php echo ($type === 'trigger_callback' ? 'block' : 'none'); ?>;"
                >
                    <label><?php echo __('PHP Callback Function', AAM_KEY); ?></label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Enter valid callback"
                        name="login.redirect.callback"
                        value="<?php echo stripslashes(esc_js(isset($settings['callback']) ? $settings['callback'] : '')); ?>"
                    />
                </div>
            </div>
        </div>
    </div>
<?php }