<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="404redirect-content">
        <?php if ($this->getSubject()->isDefault()) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo AAM_Backend_View_Helper::preparePhrase('Setup [default] 404 redirect for all none-existing pages.', 'strong'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-xs-12">
                <?php if ($this->getSubject()->isDefault()) { ?>
                    <?php $type = AAM_Core_Config::get('frontend.404redirect.type', 'default'); ?>

                    <div class="radio">
                        <input type="radio" name="frontend.404redirect.type" id="frontend-404redirect-default" value="default" data-action="none" <?php echo ($type === 'default' ? ' checked' : ''); ?> />
                        <label for="frontend-404redirect-default"><?php echo AAM_Backend_View_Helper::preparePhrase('Default WordPress 404 handler', 'small'); ?></label>
                    </div>
                    <div class="radio">
                        <input type="radio" name="frontend.404redirect.type" id="frontend-404redirect-page" data-action="#404redirect-page-action" value="page" <?php echo ($type === 'page' ? ' checked' : ''); ?> />
                        <label for="frontend-404redirect-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to existing page [(select from the drop-down)]', 'small'); ?></label>
                    </div>
                    <div class="radio">
                        <input type="radio" name="frontend.404redirect.type" id="frontend-404redirect-url" data-action="#404redirect-url-action" value="url" <?php echo ($type === 'url' ? ' checked' : ''); ?> />
                        <label for="frontend-404redirect-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to the URL [(enter valid URL starting from http or https)]', 'small'); ?></label>
                    </div>
                    <div class="radio">
                        <input type="radio" name="frontend.404redirect.type" id="frontend-404redirect-callback" data-action="#404redirect-callback-action" value="callback" <?php echo ($type === 'callback' ? ' checked' : ''); ?> />
                        <label for="frontend-404redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                    </div>

                    <div class="form-group aam-404redirect-action" id="404redirect-page-action" style="display: <?php echo ($type === 'page' ? 'block' : 'none'); ?>;">
                        <label for="frontend-page"><?php echo __('Existing Page', AAM_KEY); ?></label>
                        <?php
                        wp_dropdown_pages(array(
                            'depth' => 99,
                            'selected' => AAM_Core_Config::get('frontend.404redirect.page'),
                            'echo' => 1,
                            'name' => 'frontend.404redirect.page',
                            'id' => '404-redirect-page', // string
                            'class' => 'form-control', // string
                            'show_option_none' => __('-- Select Page --', AAM_KEY) // string
                        ));
                        ?>
                    </div>

                    <div class="form-group aam-404redirect-action" id="404redirect-url-action" style="display: <?php echo ($type === 'url' ? 'block' : 'none'); ?>;">
                        <label for="frontend-url"><?php echo __('The URL', AAM_KEY); ?></label>
                        <input type="text" class="form-control" name="frontend.404redirect.url" placeholder="https://" value="<?php echo AAM_Core_Config::get('frontend.404redirect.url'); ?>" />
                    </div>

                    <div class="form-group aam-404redirect-action" id="404redirect-callback-action" style="display: <?php echo ($type === 'callback' ? 'block' : 'none'); ?>;">
                        <label for="frontend-url"><?php echo __('PHP Callback Function', AAM_KEY); ?></label>
                        <input type="text" class="form-control" placeholder="Enter valid callback" name="frontend.404redirect.callback" value="<?php echo AAM_Core_Config::get('frontend.404redirect.callback'); ?>" />
                    </div>
                <?php } else { ?>
                    <p class="alert alert-info text-center"><?php echo AAM_Backend_View_Helper::preparePhrase('You cannot setup 404 redirect for specific user, role or visitors. Switch to [Manage Default Access] and define default 404 redirect for everybody.', 'strong'); ?></p>
                <?php } ?>
            </div>
        </div>
    </div>
<?php }