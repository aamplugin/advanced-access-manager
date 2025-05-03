<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="form-group aam-bordered aam-outer-top-xs">
        <label for="login-url-preview" class="aam-block">
            <?php echo __('Login with URL', 'advanced-access-manager'); ?>
            <a href="#" class="aam-copy-clipboard" data-clipboard-target="#login-url-preview"><?php echo __('Copy to clipboard', 'advanced-access-manager'); ?></a>
        </label>
        <div class="input-group">
            <input type="text" class="form-control" id="login-url-preview" data-url="<?php echo add_query_arg('aam-jwt', '%s', site_url()); ?>" value="<?php echo __('Login URL has not been requested', 'advanced-access-manager'); ?>" readonly />
            <span class="input-group-btn">
                <a href="#" class="btn btn-primary" id="request-login-url"><?php echo __('Request URL', 'advanced-access-manager'); ?></a>
            </span>
        </div>
        <small><?php echo AAM_Backend_View_Helper::preparePhrase('With this URL user will be automatically logged in until defined date and time. The JWT token associated with URL is [revokable] however not [refreshable].', 'i', 'i'); ?></small>
    </div>
<?php }