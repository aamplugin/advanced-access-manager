<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) {
    echo $this->args['before_widget'];

    if (!is_user_logged_in()) {
        echo $this->args['before_title'];
        echo apply_filters('widget_title', esc_js($this->args['login-title']), $this->args, $this->id_base);
        echo $this->args['after_title'];
    } elseif (is_user_logged_in()) {
        echo $this->args['before_title'];
        echo str_replace('%username%', AAM::current_user()->display_name, esc_js($this->args['user-title']));
        echo $this->args['after_title'];
    }

    echo AAM_Backend_View::loadPartial('login-form', array(
        'id'       => $this->get_field_id('loginform'),
        'redirect' => $this->args['redirect']
    ));

    echo $this->args['after_widget'];
}