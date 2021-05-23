<?php
/**
 *
 * @since 6.7.6 https://github.com/aamplugin/advanced-access-manager/issues/179
 * @since 6.0.0 Initial implementation of the template
 *
 * @version 6.7.6
 */
?>

<?php if (defined('AAM_KEY')) { ?>
    <table class="form-table">
        <tr>
            <th><?php echo esc_html('User Roles', AAM_KEY); ?></th>
            <td>
                <div class="wp-tab-panel">
                    <ul>
                        <?php $roles = (!empty($user) ? $user->roles : array(get_option('default_role'))); ?>
                        <?php foreach (get_editable_roles() as $id => $role) { ?>
                            <li>
                                <label>
                                    <input type="checkbox" name="aam_user_roles[]" value="<?php echo esc_attr($id); ?>" <?php checked(in_array($id, $roles)); ?> />
                                    <?php echo esc_html(translate_user_role($role['name'])); ?>
                                </label>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <input type="hidden" name="role" value="<?php echo get_option('default_role'); ?>" />
            </td>
        </tr>
    </table>

    <!-- Remove standard WordPress roles selector-->
    <script>
        (function($) {
            $(document).ready(function() {
                // Remove default role drop-down from User Edit page
                if ($('.user-role-wrap').length) {
                    $('.user-role-wrap').remove();
                }

                // Remove default role drop-down from Add New User page
                if ($('#role').length) {
                    $('#role').parent().parent().remove();
                }

                // Remove default role drop-down from Add Existing User page
                if ($('#adduser-role').length) {
                    $('#adduser-role').parent().parent().remove();
                }
            });
        })(jQuery);
    </script>
<?php }