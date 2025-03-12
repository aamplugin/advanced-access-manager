<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::get_instance();
        $service      = $access_level->not_found_redirect();
        $settings     = $service->get_redirect();
    ?>

    <div class="aam-feature" id="404redirect-content">
        <div class="row">
            <div class="col-xs-12">
                <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
                    <p class="aam-info">
                        <?php echo __('The "404 Redirect" service ensures seamless user experience by automatically managing 404 (Not Found) errors, redirecting users to relevant pages, URL or trigger a custom PHP callback function to maintain site engagement and reduce bounce rates.', 'advanced-access-manager'); ?>
                    </p>
                <?php } ?>

                <div
                    class="aam-overwrite"
                    id="aam-404redirect-overwrite"
                    style="display: <?php echo ($service->is_customized() ? 'block' : 'none'); ?>"
                >
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', 'advanced-access-manager'); ?></span>
                    <span><a href="#" id="404redirect-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', 'advanced-access-manager'); ?></a></span>
                </div>

                <?php $type = $settings['type'] ?>

                <div class="radio">
                    <input type="radio" name="not_found_redirect_type" id="404redirect-default" data-action="#default-redirect-action" value="default" <?php echo ($type === 'default' ? ' checked' : ''); ?> />
                    <label for="404redirect-default"><?php echo __('WordPress default behavior', 'advanced-access-manager'); ?></label>
                </div>
                <div class="radio">
                    <input type="radio" name="not_found_redirect_type" id="404redirect-page" data-action="#page-404redirect-action" value="page_redirect" <?php echo ($type === 'page_redirect' ? ' checked' : ''); ?> />
                    <label for="404redirect-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to existing page [(select from the drop-down)]', 'small'); ?></label>
                </div>
                <div class="radio">
                    <input type="radio" name="not_found_redirect_type" id="404redirect-url" data-action="#url-404redirect-action" value="url_redirect" <?php echo ($type === 'url_redirect' ? ' checked' : ''); ?> />
                    <label for="404redirect-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to the local URL [(enter full URL starting from http or https)]', 'small'); ?></label>
                </div>
                <?php if ($access_level->is_visitor()) { ?>
                    <div class="radio">
                        <input type="radio" name="not_found_redirect_type" id="404-redirect-login" value="login_redirect" data-action="none" <?php echo ($type === 'login_redirect' ? ' checked' : ''); ?> />
                        <label for="404-redirect-login"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirect to the login page [(after login, user will be redirected back to the restricted page)]', 'small'); ?></label>
                    </div>
                <?php } ?>
                <div class="radio">
                    <input type="radio" name="not_found_redirect_type" id="404redirect-callback" data-action="#callback-404redirect-action" value="trigger_callback" <?php echo ($type === 'trigger_callback' ? ' checked' : ''); ?> />
                    <label for="404redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                </div>

                <div class="form-group 404redirect-action" id="page-404redirect-action" style="display: <?php echo ($type === 'page_redirect' ? 'block' : 'none'); ?>;">
                    <label><?php echo __('Existing Page', 'advanced-access-manager'); ?></label>
                    <?php
                        wp_dropdown_pages(array(
                            'depth'            => 99,
                            'selected'         => isset($settings['redirect_page_id']) ? $settings['redirect_page_id'] : 0,
                            'echo'             => 1,
                            'id'               => '404redirect-page',
                            'class'            => 'form-control',
                            'show_option_none' => __('-- Select Page --', 'advanced-access-manager')
                        ));
                        ?>
                </div>

                <div class="form-group 404redirect-action" id="url-404redirect-action" style="display: <?php echo ($type === 'url_redirect' ? 'block' : 'none'); ?>;">
                    <label><?php echo __('The URL', 'advanced-access-manager'); ?></label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="https://"
                        value="<?php echo stripslashes(esc_js(isset($settings['redirect_url']) ? $settings['redirect_url'] : '')); ?>"
                    />
                </div>

                <div class="form-group 404-redirect-action" id="callback-404redirect-action" style="display: <?php echo ($type === 'trigger_callback' ? 'block' : 'none'); ?>;">
                    <label><?php echo __('PHP Callback Function', 'advanced-access-manager'); ?></label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Enter valid callback"
                        value="<?php echo stripslashes(esc_js(isset($settings['callback']) ? $settings['callback'] : '')); ?>"
                    />
                </div>
            </div>
        </div>
    </div>
<?php }