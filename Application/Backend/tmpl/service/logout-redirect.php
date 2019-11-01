<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="logout_redirect-content">
        <div class="row">
            <div class="col-xs-12">
                <?php if ($this->getSubject()->isDefault()) {  ?>
                    <p class="aam-info">
                        <?php echo AAM_Backend_View_Helper::preparePhrase('Define the [default] logout redirect for all the users and roles.', 'strong'); ?>
                    </p>
                <?php } else { ?>
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Customize logout redirect for [%s].', 'strong'), $this->getSubject()->getName()); ?>
                    </p>
                <?php } ?>
                <div class="aam-overwrite" id="aam-logout-redirect-overwrite" style="display: <?php echo ($this->isOverwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="logout-redirect-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <?php $type = $this->getOption('logout.redirect.type', 'default'); ?>

                <div class="radio">
                    <input type="radio" name="logout.redirect.type" id="logout-redirect-default" data-action="#default-redirect-action" value="default" <?php echo ($type === 'default' ? ' checked' : ''); ?> />
                    <label for="logout-redirect-default"><?php echo __('WordPress default behavior', AAM_KEY); ?></label>
                </div>
                <div class="radio">
                    <input type="radio" name="logout.redirect.type" id="logout-redirect-page" data-action="#page-logout-redirect-action" value="page" <?php echo ($type === 'page' ? ' checked' : ''); ?> />
                    <label for="logout-redirect-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to existing page [(select from the drop-down)]', 'small'); ?></label>
                </div>
                <div class="radio">
                    <input type="radio" name="logout.redirect.type" id="logout-redirect-url" data-action="#url-logout-redirect-action" value="url" <?php echo ($type === 'url' ? ' checked' : ''); ?> />
                    <label for="logout-redirect-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to the URL [(enter full URL starting from http or https)]', 'small'); ?></label>
                </div>
                <div class="radio">
                    <input type="radio" name="logout.redirect.type" id="logout-redirect-callback" data-action="#callback-logout-redirect-action" value="callback" <?php echo ($type === 'callback' ? ' checked' : ''); ?> />
                    <label for="logout-redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                </div>

                <div class="form-group logout-redirect-action" id="page-logout-redirect-action" style="display: <?php echo ($type === 'page' ? 'block' : 'none'); ?>;">
                    <label><?php echo __('Existing Page', AAM_KEY); ?></label>
                    <?php
                        wp_dropdown_pages(array(
                            'depth' => 99,
                            'selected' => $this->getOption('logout.redirect.page'),
                            'echo' => 1,
                            'name' => 'logout.redirect.page',
                            'id' => 'logout-redirect-page',
                            'class' => 'form-control',
                            'show_option_none' => __('-- Select Page --', AAM_KEY)
                        ));
                        ?>
                </div>

                <div class="form-group logout-redirect-action" id="url-logout-redirect-action" style="display: <?php echo ($type === 'url' ? 'block' : 'none'); ?>;">
                    <label><?php echo __('The URL', AAM_KEY); ?></label>
                    <input type="text" class="form-control" name="logout.redirect.url" placeholder="https://" value="<?php echo $this->getOption('logout.redirect.url'); ?>" />
                </div>

                <div class="form-group logout-redirect-action" id="callback-logout-redirect-action" style="display: <?php echo ($type === 'callback' ? 'block' : 'none'); ?>;">
                    <label><?php echo __('PHP Callback Function', AAM_KEY); ?></label>
                    <input type="text" class="form-control" placeholder="Enter valid callback" name="logout.redirect.callback" value="<?php echo $this->getOption('logout.redirect.callback'); ?>" />
                </div>
            </div>
        </div>
    </div>
<?php }