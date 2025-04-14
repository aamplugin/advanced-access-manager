<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) { ?>
    <?php if (!is_user_logged_in()) { ?>
        <div
            id="aam-login-error-<?php echo esc_js($params->id); ?>"
            style="display: none; margin-bottom: 15px; border-left: 4px solid #dc3232; padding: 6px;"
        ></div>

        <div id="login-form-<?php echo esc_js($params->id); ?>">
            <p>
                <label for="user_login"><?php echo __('Username or Email Address', 'advanced-access-manager'); ?><br>
                    <input id="aam-login-username-<?php echo esc_js($params->id); ?>" class="input login-input" type="text" />
                </label>
            </p>

            <p>
                <label for="user_pass"><?php echo __('Password', 'advanced-access-manager'); ?><br>
                    <input id="aam-login-password-<?php echo esc_js($params->id); ?>" class="input login-input" type="password" />
                </label>
            </p>

            <?php do_action('login_form'); ?>

            <p class="forgetmenot">
                <label for="aam-login-remember-<?php echo esc_js($params->id); ?>">
                    <input id="aam-login-remember-<?php echo esc_js($params->id); ?>" value="forever" type="checkbox" /> <?php echo __('Remember Me', 'advanced-access-manager'); ?>
                </label>
            </p>

            <p class="submit">
                <input class="button button-primary button-large" id="aam-login-submit-<?php echo esc_js($params->id); ?>" value="<?php echo __('Log In', 'advanced-access-manager'); ?>" type="submit" />
                <input id="aam-login-redirect-<?php echo esc_js($params->id); ?>" value="<?php echo esc_js($params->redirect); ?>" type="hidden" />
            </p>
        </div>

        <p>
            <?php
            if (get_option('users_can_register')) {
                $registration_url = sprintf('<a href="%s">%s</a>', esc_url(wp_registration_url()), __('Register', 'advanced-access-manager'));
                echo apply_filters('register', $registration_url);
                echo esc_html(apply_filters('login_link_separator', ' | '));
            }
            ?>
            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php echo __('Lost your password?', 'advanced-access-manager'); ?></a>
        </p>
        <script>
            (function() {
                var c = document.getElementById("aam-login-submit-<?php echo esc_js($params->id); ?>"),
                    b = document.getElementById("aam-login-username-<?php echo esc_js($params->id); ?>"),
                    d = document.getElementById("aam-login-password-<?php echo esc_js($params->id); ?>");

                if (b) b.addEventListener("keyup", function(a) { 13 === a.which && c.click() });
                if (d) d.addEventListener("keyup", function(a) { 13 === a.which && c.click() });

                c && c.addEventListener("click", function() {
                    c.disabled = !0;
                    var a = new XMLHttpRequest;
                    a.addEventListener("readystatechange", function() {
                        if (4 === this.readyState) {
                            c.disabled = !1;
                            var a = JSON.parse(this.responseText);

                            if (200 === this.status) {
                                a.redirect_to ? location.href = a.redirect_to : location.reload();
                            } else {
                                var b = document.getElementById("aam-login-error-<?php echo esc_js($params->id); ?>");

                                if (a.reason !== undefined) {
                                    b.innerHTML = a.reason;
                                } else if (a.errors !== undefined) {
                                    b.innerHTML = a.errors[Object.keys(a.errors)[0]][0];
                                }

                                b.style.display = "block";
                            }
                        }
                    });
                    a.open("POST", "<?php echo get_rest_url(null, 'aam/v2/authenticate'); ?>");
                    a.setRequestHeader("Content-Type", "application/json");
                    a.setRequestHeader("Accept", "application/json");
                    a.send(JSON.stringify({
                        username: document.getElementById("aam-login-username-<?php echo esc_js($params->id); ?>").value,
                        password: document.getElementById("aam-login-password-<?php echo esc_js($params->id); ?>").value,
                        redirect: document.getElementById("aam-login-redirect-<?php echo esc_js($params->id); ?>").value,
                        remember: document.getElementById("aam-login-remember-<?php echo esc_js($params->id); ?>").checked,
                        return_auth_cookies: true,
                        return_login_redirect: true
                    }))
                })
            })();
        </script>

    <?php } else { ?>
        <div style="display: table; width: 100%;">
            <div style="display:table-cell; width: 30%; text-align: center; vertical-align: middle;">
                <?php echo get_avatar(AAM::current_user()->ID, 50); ?>
            </div>
            <div style="display:table-cell;">
                <?php if (AAM::api()->caps->exists('aam_access_dashboard') && current_user_can('aam_access_dashboard')) { ?>
                    <a href="<?php echo esc_url(get_admin_url()); ?>"><?php echo __('Dashboard', 'advanced-access-manager'); ?></a><br />
                    <a href="<?php echo esc_url(get_admin_url(null, 'profile.php')); ?>"><?php echo __('Edit My Profile', 'advanced-access-manager'); ?></a><br />
                <?php } ?>
                <a href="<?php echo esc_url(wp_logout_url()); ?>"><?php echo __('Log Out', 'advanced-access-manager'); ?></a>
            </div>
        </div>
    <?php } ?>
<?php }