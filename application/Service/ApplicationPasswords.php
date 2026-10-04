<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Manage WordPress application passwords and show their usage on Users.
 *
 * @package AAM
 */
class AAM_Service_ApplicationPasswords
{
    use AAM_Service_BaseTrait;

    const COLUMN = 'aam_application_passwords';

    const APP_PASSWORD_CAPS = [
        'create_app_password',
        'list_app_passwords',
        'read_app_password',
        'edit_app_password',
        'delete_app_passwords',
        'delete_app_password'
    ];

    /**
     * Register the access checks and Users list enhancements.
     */
    protected function __construct()
    {
        add_filter(
            'wp_is_application_passwords_available_for_user',
            [$this, 'is_available_for_user'],
            10,
            2
        );
        add_filter('map_meta_cap', [$this, 'map_meta_cap'], 1000, 4);

        if (is_admin() && class_exists('WP_Application_Passwords')) {
            add_filter('manage_users_columns', [$this, 'add_users_column']);
            add_filter('manage_users_custom_column', [$this, 'render_users_column'], 10, 3);
            add_action('restrict_manage_users', [$this, 'render_users_filter']);
            add_filter('users_list_table_query_args', [$this, 'filter_users_query']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_users_assets']);
        }
    }

    /**
     * Respect existing WordPress and third-party availability restrictions.
     *
     * @param bool    $available Availability determined by earlier filters.
     * @param WP_User $user      Password owner.
     *
     * @return bool
     */
    public function is_available_for_user($available, $user)
    {
        if ($available && AAM::api()->caps->exists('aam_manage_application_passwords')) {
            $available = user_can($user, 'aam_manage_application_passwords');
        }

        return $available;
    }

    /**
     * Apply the AAM capability to WordPress application password operations.
     *
     * @param array  $caps    Primitive capabilities required by WordPress.
     * @param string $cap     Requested meta capability.
     * @param int    $user_id Acting user ID.
     *
     * @return array
     */
    public function map_meta_cap($caps, $cap, $user_id)
    {
        if (in_array($cap, self::APP_PASSWORD_CAPS, true)
            && !$this->is_available_for_user(true, $user_id)) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    /**
     * Place Application Passwords before Posts on the Users screen.
     */
    public function add_users_column($columns)
    {
        $result = [];

        foreach ($columns as $key => $label) {
            if ($key === 'posts') {
                $result[self::COLUMN] = __('Application Passwords', 'advanced-access-manager');
            }

            $result[$key] = $label;
        }

        if (!isset($result[self::COLUMN])) {
            $result[self::COLUMN] = __('Application Passwords', 'advanced-access-manager');
        }

        return $result;
    }

    /**
     * Render a compact usage summary and a short details popover.
     */
    public function render_users_column($output, $column_name, $user_id)
    {
        if ($column_name !== self::COLUMN) {
            return $output;
        }

        // The column exposes password names, so follow the core list capability.
        if (!current_user_can('list_app_passwords', $user_id)) {
            return '&mdash;';
        }

        $passwords = WP_Application_Passwords::get_user_application_passwords($user_id);
        $total     = count($passwords);
        $used      = 0;
        $last_used = 0;

        foreach ($passwords as $password) {
            $timestamp = (int) ($password['last_used'] ?? 0);

            if ($timestamp > 0) {
                $used++;
                $last_used = max($last_used, $timestamp);
            }
        }

        if (!$total) {
            return '<span class="aam-ap-empty">' . esc_html__(
                'No passwords', 'advanced-access-manager'
            ) . '</span>';
        }

        usort($passwords, static function($a, $b) {
            $usage = (int) ($b['last_used'] ?? 0) <=> (int) ($a['last_used'] ?? 0);

            return $usage ?: (int) ($b['created'] ?? 0) <=> (int) ($a['created'] ?? 0);
        });

        $preview = array_slice($passwords, 0, 4);
        $label   = sprintf(
            _n('%s password', '%s passwords', $total, 'advanced-access-manager'),
            number_format_i18n($total)
        );
        $summary = $last_used ? sprintf(
            __('Last used %s ago', 'advanced-access-manager'),
            human_time_diff($last_used, time())
        ) : __('Never used', 'advanced-access-manager');
        $exact = $last_used ? wp_date('M j, Y g:i a', $last_used) : $summary;

        $edit_url = get_edit_user_link($user_id);
        $manage_url = $edit_url && wp_is_application_passwords_available_for_user($user_id)
            ? $edit_url . '#application-passwords-section' : '';
        $panel_id = 'aam-ap-panel-' . absint($user_id);

        ob_start();
        ?>
        <div class="aam-ap-cell">
            <button type="button" class="aam-ap-trigger <?php echo $used ? 'aam-ap-used' : 'aam-ap-unused'; ?>"
                aria-expanded="false" aria-controls="<?php echo esc_attr($panel_id); ?>"
                aria-label="<?php echo esc_attr(sprintf(
                    __('%1$s, %2$s previously used. Show details.', 'advanced-access-manager'),
                    $label,
                    number_format_i18n($used)
                )); ?>">
                <span class="aam-ap-indicator" aria-hidden="true"><?php echo $used ? '&#10003;' : '!'; ?></span>
                <span class="aam-ap-summary">
                    <span class="aam-ap-title"><?php echo esc_html($label); ?>
                        <span class="aam-ap-used-count">&middot; <?php echo esc_html(sprintf(
                            _n('%s used', '%s used', $used, 'advanced-access-manager'),
                            number_format_i18n($used)
                        )); ?></span>
                    </span>
                    <span class="aam-ap-subtitle" title="<?php echo esc_attr($exact); ?>"><?php echo esc_html($summary); ?></span>
                </span>
            </button>
            <div id="<?php echo esc_attr($panel_id); ?>" class="aam-ap-popover" hidden>
                <div class="aam-ap-popover-header">
                    <strong><?php echo esc_html(sprintf(
                        __('Application Passwords (%s)', 'advanced-access-manager'),
                        number_format_i18n($total)
                    )); ?></strong>
                    <?php if ($manage_url) : ?>
                        <a href="<?php echo esc_url($manage_url); ?>"><?php esc_html_e('Manage', 'advanced-access-manager'); ?> &#8599;</a>
                    <?php endif; ?>
                </div>
                <div class="aam-ap-popover-list">
                    <?php foreach ($preview as $password) :
                        $timestamp = (int) ($password['last_used'] ?? 0);
                        $relative  = $timestamp ? sprintf(
                            __('%s ago', 'advanced-access-manager'),
                            human_time_diff($timestamp, time())
                        ) : __('Never used', 'advanced-access-manager');
                        $date = $timestamp ? wp_date('M j, Y g:i a', $timestamp) : $relative;
                    ?>
                        <div class="aam-ap-item">
                            <span class="aam-ap-dot <?php echo $timestamp ? 'is-used' : 'is-unused'; ?>" aria-hidden="true"></span>
                            <span class="aam-ap-item-name" title="<?php echo esc_attr($password['name'] ?? ''); ?>"><?php echo esc_html($password['name'] ?? ''); ?></span>
                            <span class="aam-ap-item-time" title="<?php echo esc_attr($date); ?>"><?php echo esc_html($relative); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="aam-ap-popover-footer">
                    <?php if ($manage_url) : ?>
                        <a href="<?php echo esc_url($manage_url); ?>"><?php esc_html_e('View all passwords', 'advanced-access-manager'); ?> &#8594;</a>
                    <?php else : ?>
                        <span><?php esc_html_e('Password management is unavailable for this user.', 'advanced-access-manager'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Add a filter for accounts that have application passwords.
     */
    public function render_users_filter($which)
    {
        if ($which !== 'top') {
            return;
        }

        $selected = $this->get_users_filter();
        ?>
        <label class="screen-reader-text" for="aam-ap-filter"><?php esc_html_e('Filter by application passwords', 'advanced-access-manager'); ?></label>
        <select name="aam_ap_filter" id="aam-ap-filter">
            <option value=""><?php esc_html_e('All application passwords', 'advanced-access-manager'); ?></option>
            <option value="has" <?php selected($selected, 'has'); ?>><?php esc_html_e('Has application passwords', 'advanced-access-manager'); ?></option>
        </select>
        <?php
        submit_button(__('Filter', 'advanced-access-manager'), '', 'aam_ap_apply', false);
    }

    /**
     * Limit the Users list table query without changing other user queries.
     */
    public function filter_users_query($args)
    {
        if ($this->get_users_filter() !== 'has') {
            return $args;
        }

        $condition = [
            'key'     => WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS,
            'value'   => [serialize([]), ''],
            'compare' => 'NOT IN'
        ];

        $args['meta_query'] = !empty($args['meta_query']) ? [
            'relation' => 'AND',
            $args['meta_query'],
            $condition
        ] : [$condition];

        return $args;
    }

    /**
     * Load the Users screen presentation only where the column appears.
     */
    public function enqueue_users_assets($hook)
    {
        if ($hook !== 'users.php') {
            return;
        }

        $base = plugins_url('media/', AAM_BASEDIR . '/aam.php');
        $css  = AAM_BASEDIR . '/media/css/application-passwords.css';
        $js   = AAM_BASEDIR . '/media/js/application-passwords.js';

        wp_enqueue_style('aam-application-passwords', $base . 'css/application-passwords.css', [], filemtime($css) ?: AAM_VERSION);
        wp_enqueue_script('aam-application-passwords', $base . 'js/application-passwords.js', [], filemtime($js) ?: AAM_VERSION, true);
    }

    private function get_users_filter()
    {
        return isset($_GET['aam_ap_filter']) && is_string($_GET['aam_ap_filter'])
            ? sanitize_key(wp_unslash($_GET['aam_ap_filter'])) : '';
    }
}
