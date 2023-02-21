<?php
    /**
     * @since 6.8.0 https://github.com/aamplugin/advanced-access-manager/issues/195
     * @since 6.0.0 Initial implementation of the templates
     *
     * @version 6.8.0
     *
     */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="login_redirect-content">
        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-login-redirect-overwrite" style="display: <?php echo ($this->isOverwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="login-redirect-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <?php $type = $this->getOption('login.redirect.type', 'default'); ?>

                <div class="radio">
                    <input type="radio" name="login.redirect.type" id="login-redirect-default" data-action="#default-redirect-action" value="default" <?php echo ($type === 'default' ? ' checked' : ''); ?> />
                    <label for="login-redirect-default"><?php echo __('WordPress default behavior', AAM_KEY); ?></label>
                </div>
                <div class="radio">
                    <input type="radio" name="login.redirect.type" id="login-redirect-page" data-action="#page-login-redirect-action" value="page" <?php echo ($type === 'page' ? ' checked' : ''); ?> />
                    <label for="login-redirect-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to existing page [(select from the drop-down)]', 'small'); ?></label>
                </div>
                <div class="radio">
                    <input type="radio" name="login.redirect.type" id="login-redirect-url" data-action="#url-login-redirect-action" value="url" <?php echo ($type === 'url' ? ' checked' : ''); ?> />
                    <label for="login-redirect-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to the local URL [(enter full URL starting from http or https)]', 'small'); ?></label>
                </div>
                <div class="radio">
                    <input type="radio" name="login.redirect.type" id="login-redirect-callback" data-action="#callback-login-redirect-action" value="callback" <?php echo ($type === 'callback' ? ' checked' : ''); ?> />
                    <label for="login-redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                </div>

                <div class="form-group login-redirect-action" id="page-login-redirect-action" style="display: <?php echo ($type == 'page' ? 'block' : 'none'); ?>;">
                    <label><?php echo __('Existing Page', AAM_KEY); ?></label>
                    <?php
                        wp_dropdown_pages(array(
                            'depth' => 99,
                            'selected' => $this->getOption('login.redirect.page'),
                            'echo' => 1,
                            'name' => 'login.redirect.page',
                            'id' => 'login-redirect-page',
                            'class' => 'form-control',
                            'show_option_none' => __('-- Select Page --', AAM_KEY)
                        ));
                        ?>
                </div>

                <div class="form-group login-redirect-action" id="url-login-redirect-action" style="display: <?php echo ($type === 'url' ? 'block' : 'none'); ?>;">
                    <label><?php echo __('The URL', AAM_KEY); ?></label>
                    <input type="text" class="form-control" name="login.redirect.url" placeholder="https://" value="<?php echo stripslashes(esc_js($this->getOption('login.redirect.url'))); ?>" />
                </div>

                <div class="form-group login-redirect-action" id="callback-login-redirect-action" style="display: <?php echo ($type === 'callback' ? 'block' : 'none'); ?>;">
                    <label><?php echo __('PHP Callback Function', AAM_KEY); ?></label>
                    <input type="text" class="form-control" placeholder="Enter valid callback" name="login.redirect.callback" value="<?php echo stripslashes(esc_js($this->getOption('login.redirect.callback'))); ?>" />
                </div>
            </div>
        </div>
    </div>
<?php }