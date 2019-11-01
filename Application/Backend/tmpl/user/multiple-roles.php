<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php $user = ($param === 'add-new-user' ? null : $param); ?>

    <?php if ((!defined('IS_PROFILE_PAGE') || !IS_PROFILE_PAGE) && !is_network_admin() && (empty($user) || current_user_can('promote_user', $user->ID))) { ?>
        <table class="form-table">
            <tr>
                <th><?php echo esc_html('User Roles', AAM_KEY); ?></th>
                <td>
                    <div class="wp-tab-panel">
                        <ul>
                            <?php $roles = (!empty($user) ? $user->roles : array('subscriber')); ?>
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
                </td>
            </tr>
        </table>

        <!-- Remove standard WordPress roles selector-->
        <script>
            (function($) {
                $(document).ready(function() {
                    if ($('.user-role-wrap').length) {
                        $('.user-role-wrap').remove();
                    } else if ($('#role').length) {
                        $('#role').parent().parent().remove();
                    }
                });
            })(jQuery);
        </script>
    <?php } ?>
<?php }