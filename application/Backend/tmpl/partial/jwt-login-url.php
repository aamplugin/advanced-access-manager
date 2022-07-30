<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="form-group aam-bordered aam-outer-top-xs">
        <label for="login-url-preview" class="aam-block">
            <?php echo __('Login with URL', AAM_KEY); ?>
            <a href="#" class="aam-copy-clipboard" data-clipboard-target="#login-url-preview"><?php echo __('Copy to clipboard', AAM_KEY); ?></a>
        </label>
        <div class="input-group">
            <input type="text" class="form-control" id="login-url-preview" data-url="<?php echo add_query_arg('aam-jwt', '%s', site_url()); ?>" value="<?php echo __('Login URL has not been requested', AAM_KEY); ?>" readonly />
            <span class="input-group-btn">
                <a href="#" class="btn btn-primary" id="request-login-url"><?php echo __('Request URL', AAM_KEY); ?></a>
            </span>
            <input type="hidden" id="login-jwt" />
        </div>
        <small><?php echo AAM_Backend_View_Helper::preparePhrase('With this URL user will be automatically logged in until defined date and time. The JWT token associated with URL is [revokable] however not [refreshable].', 'i', 'i'); ?></small>
    </div>
<?php }