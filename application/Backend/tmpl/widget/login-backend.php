<?php
/**
 * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
 * @since 6.9.6  https://github.com/aamplugin/advanced-access-manager/issues/256
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.21
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <p>
        <label for="<?php echo esc_attr($this->get_field_id('login-title')); ?>"><?php echo __('Login Title', AAM_KEY); ?>: </label>
        <input type="text" class="widefat" id="<?php echo esc_attr($this->get_field_id('login-title')); ?>" name="<?php echo esc_attr($this->get_field_name('login-title')); ?>" value="<?php echo esc_attr($instance['login-title']); ?>" />
    </p>

    <p>
        <label for="<?php echo $this->get_field_id('user-title'); ?>"><?php echo __('Logged In Title', AAM_KEY); ?>: </label>
        <input type="text" class="widefat" id="<?php echo esc_attr($this->get_field_id('user-title')); ?>" name="<?php echo esc_attr($this->get_field_name('user-title')); ?>" value="<?php echo esc_attr($instance['user-title']); ?>" />
    </p>
<?php }