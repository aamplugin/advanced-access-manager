<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <p>
        <label for="<?php echo esc_attr($this->get_field_id('login-title')); ?>"><?php echo __('Login Title', 'advanced-access-manager'); ?>: </label>
        <input type="text" class="widefat" id="<?php echo esc_attr($this->get_field_id('login-title')); ?>" name="<?php echo esc_attr($this->get_field_name('login-title')); ?>" value="<?php echo esc_attr($instance['login-title']); ?>" />
    </p>

    <p>
        <label for="<?php echo $this->get_field_id('user-title'); ?>"><?php echo __('Logged In Title', 'advanced-access-manager'); ?>: </label>
        <input type="text" class="widefat" id="<?php echo esc_attr($this->get_field_id('user-title')); ?>" name="<?php echo esc_attr($this->get_field_name('user-title')); ?>" value="<?php echo esc_attr($instance['user-title']); ?>" />
    </p>
<?php }