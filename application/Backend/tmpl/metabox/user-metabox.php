<?php /** @version 7.1.4 **/

if (defined('AAM_KEY')) {
    $user = $params->user;
    $name = $user->display_name ?: $user->user_login;
    $url = add_query_arg([
        'page'        => 'aam',
        'aam_level'   => 'user',
        'aam_subject' => $user->ID,
        'aam_service' => 'admin_menu'
    ], admin_url('admin.php'));
    ?>
    <section class="aam-user-access-card" aria-labelledby="aam-user-access-title">
        <div class="aam-user-access-icon" aria-hidden="true">
            <span class="dashicons dashicons-shield"></span>
        </div>
        <div class="aam-user-access-content">
            <span class="aam-user-access-eyebrow">
                <?php esc_html_e('ADVANCED ACCESS MANAGER', 'advanced-access-manager'); ?>
            </span>
            <h2 id="aam-user-access-title">
                <?php echo esc_html(sprintf(
                    __('Manage access for %s', 'advanced-access-manager'),
                    $name
                )); ?>
            </h2>
            <p>
                <?php esc_html_e(
                    'Set this user’s capabilities, content permissions, and other access rules in AAM.',
                    'advanced-access-manager'
                ); ?>
            </p>
        </div>
        <div class="aam-user-access-action">
            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Manage access in AAM', 'advanced-access-manager'); ?>
                <span class="dashicons dashicons-arrow-up-right-alt" aria-hidden="true"></span>
            </a>
            <span><?php esc_html_e('Opens in a new tab', 'advanced-access-manager'); ?></span>
        </div>
    </section>
    <?php
}
