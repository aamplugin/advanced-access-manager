<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <p>
        <label for="<?php echo $this->get_field_id('login-title'); ?>"><?php echo __('Login Title', AAM_KEY); ?>: </label>
        <input type="text" class="widefat" id="<?php echo $this->get_field_id('login-title'); ?>" name="<?php echo $this->get_field_name('login-title'); ?>" value="<?php echo esc_attr($instance['login-title']); ?>" />
    </p>

    <p>
        <label for="<?php echo $this->get_field_id('user-title'); ?>"><?php echo __('Logged In Title', AAM_KEY); ?>: </label>
        <input type="text" class="widefat" id="<?php echo $this->get_field_id('user-title'); ?>" name="<?php echo $this->get_field_name('user-title'); ?>" value="<?php echo esc_attr($instance['user-title']); ?>" />
    </p>

    <p style="background-color: #fafafa; border-left: 3px solid #337ab7; font-size: 1em; line-height: 1.35em; margin-bottom: 1em; padding: 10px; font-size: 0.8em;">
        <?php echo sprintf(__('For more advanced setup like login/logout redirects, security enhancement or custom styling, please refer to %sHow does AAM Secure Login works%s article.', AAM_KEY), '<a href="https://aamplugin.com/article/how-does-aam-secure-login-works" target="_blank">', '</a>'); ?>
    </p>
<?php }