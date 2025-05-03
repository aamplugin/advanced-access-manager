<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level      = AAM_Backend_AccessLevel::get_instance();
        $service           = $access_level->access_denied_redirect();
        $frontend_redirect = $service->get_redirect('frontend');
        $backend_redirect  = $service->get_redirect('backend');
        $api_redirect      = $service->get_redirect('api');
    ?>

    <div class="aam-feature" id="redirect-content">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo __('Automatically redirects users to a desired destination when access to restricted resources is denied, with options to customize redirects for specific users or roles for restricted areas like posts, categories, and menus.', 'advanced-access-manager'); ?>
                </p>

                <div
                    class="aam-overwrite"
                    id="aam-redirect-overwrite"
                    style="display: <?php echo ($service->is_customized() ? 'block' : 'none'); ?>"
                >
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', 'advanced-access-manager'); ?></span>
                    <span><a href="#" id="redirect-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', 'advanced-access-manager'); ?></a></span>
                </div>

                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active"><a href="#frontend-redirect" aria-controls="frontend" role="tab" data-toggle="tab"><i class="icon-home"></i> <?php echo __('Frontend Redirect', 'advanced-access-manager'); ?></a></li>
                    <?php if (!$access_level->is_visitor()) { ?><li role="presentation"><a href="#backend-redirect" aria-controls="backend" role="tab" data-toggle="tab"><i class="icon-circle"></i> <?php echo __('Backend Redirect', 'advanced-access-manager'); ?></a></li><?php } ?>
                    <li role="presentation"><a href="#api-redirect" aria-controls="api" role="tab" data-toggle="tab"><i class="icon-exchange"></i> <?php echo __('RESTful Redirect', 'advanced-access-manager'); ?></a></li>
                </ul>

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="frontend-redirect">
                        <div class="radio">
                            <input
                                type="radio"
                                name="frontend.redirect.type"
                                id="frontend-redirect-default"
                                value="default"
                                data-action="#frontend-default-action"
                                data-group="frontend"
                                <?php echo ($frontend_redirect['type'] === 'default' ? ' checked' : ''); ?>
                            />
                            <label for="frontend-redirect-default"><?php echo AAM_Backend_View_Helper::preparePhrase('Default [("Access Denied" message)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input
                                type="radio"
                                name="frontend.redirect.type"
                                id="frontend-redirect-message"
                                data-action="#frontend-message-action"
                                value="custom_message"
                                data-group="frontend"
                                <?php echo ($frontend_redirect['type'] === 'custom_message' ? ' checked' : ''); ?>
                            />
                            <label for="frontend-redirect-message"><?php echo AAM_Backend_View_Helper::preparePhrase('Show customized message [(plain text or HTML)]', 'small'); ?></label>
                        </div>
                        <?php if ($access_level->is_visitor()) { ?>
                            <div class="radio">
                                <input
                                    type="radio"
                                    name="frontend.redirect.type"
                                    id="frontend-redirect-login"
                                    value="login_redirect"
                                    data-action="none"
                                    data-group="frontend"
                                    <?php echo ($frontend_redirect['type'] === 'login_redirect' ? ' checked' : ''); ?>
                                />
                                <label for="frontend-redirect-login"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirect to the login page [(after login, user will be redirected back to the restricted page)]', 'small'); ?></label>
                            </div>
                        <?php } ?>
                        <div class="radio">
                            <input
                                type="radio"
                                name="frontend.redirect.type"
                                id="frontend-redirect-page"
                                data-action="#frontend-page-action"
                                value="page_redirect"
                                data-group="frontend"
                                <?php echo ($frontend_redirect['type'] === 'page_redirect' ? ' checked' : ''); ?>
                            />
                            <label for="frontend-redirect-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to existing page [(select from the drop-down)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input
                                type="radio"
                                name="frontend.redirect.type"
                                id="frontend-redirect-url"
                                data-action="#frontend-url-action"
                                value="url_redirect"
                                data-group="frontend"
                                <?php echo ($frontend_redirect['type'] === 'url_redirect' ? ' checked' : ''); ?>
                            />
                            <label for="frontend-redirect-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to local URL [(enter valid URL starting from http or https)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input
                                type="radio"
                                name="frontend.redirect.type"
                                id="frontend-redirect-callback"
                                data-action="#frontend-callback-action"
                                value="trigger_callback"
                                data-group="frontend"
                                <?php echo ($frontend_redirect['type'] === 'trigger_callback' ? ' checked' : ''); ?>
                            />
                            <label for="frontend-redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                        </div>

                        <div
                            class="form-group aam-redirect-action frontend"
                            id="frontend-default-action"
                            style="display: <?php echo ($frontend_redirect['type'] === 'default' ? 'block' : 'none'); ?>;"
                        >
                            <label for="frontend-default-status-code"><?php echo __('HTTP Status Code', 'advanced-access-manager'); ?></label>
                            <?php $redirect_code = isset($frontend_redirect['http_status_code']) ? $frontend_redirect['http_status_code'] : 401; ?>
                            <select class="form-control form-clearable" id="frontend-default-status-code" data-group="frontend">
                                <option value="401"><?php echo __('HTTP Code (Default 401 - Unauthorized)', 'advanced-access-manager'); ?></option>
                                <option value="402"<?php echo $redirect_code == 402 ? ' selected' : ''; ?>><?php echo __('402 - Payment Required', 'advanced-access-manager'); ?></option>
                                <option value="403"<?php echo $redirect_code == 403 ? ' selected' : ''; ?>><?php echo __('403 - Forbidden', 'advanced-access-manager'); ?></option>
                                <option value="404"<?php echo $redirect_code == 404 ? ' selected' : ''; ?>><?php echo __('404 - Not Found', 'advanced-access-manager'); ?></option>
                                <option value="500"<?php echo $redirect_code == 500 ? ' selected' : ''; ?>><?php echo __('500 - Internal Server Error (WordPress Default)', 'advanced-access-manager'); ?></option>
                            </select>
                        </div>

                        <div
                            class="form-group aam-redirect-action frontend"
                            id="frontend-message-action"
                            style="display: <?php echo ($frontend_redirect['type'] === 'custom_message' ? 'block' : 'none'); ?>;"
                        >
                            <label for="frontend-message"><?php echo __('Customized Message', 'advanced-access-manager'); ?></label>
                            <?php $redirect_message = isset($frontend_redirect['message']) ? $frontend_redirect['message'] : ''; ?>
                            <textarea
                                class="form-control"
                                name="frontend.redirect.message"
                                data-group="frontend"
                                rows="3"
                                placeholder="<?php echo __('Enter message...', 'advanced-access-manager'); ?>"
                            ><?php echo esc_textarea($redirect_message); ?></textarea>

                            <div class="aam-mt-1">
                                <label for="frontend-message-status-code"><?php echo __('HTTP Status Code', 'advanced-access-manager'); ?></label>
                                <?php $redirect_code = isset($frontend_redirect['http_status_code']) ? $frontend_redirect['http_status_code'] : 401; ?>
                                <select class="form-control form-clearable" name="frontend.redirect.message.code" id="frontend-message-status-code" data-group="frontend">
                                    <option value="401"><?php echo __('HTTP Code (Default 401 - Unauthorized)', 'advanced-access-manager'); ?></option>
                                    <option value="402"<?php echo $redirect_code == 402 ? ' selected' : ''; ?>><?php echo __('402 - Payment Required', 'advanced-access-manager'); ?></option>
                                    <option value="403"<?php echo $redirect_code == 403 ? ' selected' : ''; ?>><?php echo __('403 - Forbidden', 'advanced-access-manager'); ?></option>
                                    <option value="404"<?php echo $redirect_code == 404 ? ' selected' : ''; ?>><?php echo __('404 - Not Found', 'advanced-access-manager'); ?></option>
                                    <option value="500"<?php echo $redirect_code == 500 ? ' selected' : ''; ?>><?php echo __('500 - Internal Server Error (WordPress Default)', 'advanced-access-manager'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div
                            class="form-group aam-redirect-action frontend"
                            id="frontend-page-action"
                            style="display: <?php echo ($frontend_redirect['type'] === 'page_redirect' ? 'block' : 'none'); ?>;"
                        >
                            <label for="frontend-page"><?php echo __('Existing Page', 'advanced-access-manager'); ?></label>
                            <?php
                                wp_dropdown_pages(array(
                                    'depth'            => 99,
                                    'selected'         => isset($frontend_redirect['redirect_page_id']) ? $frontend_redirect['redirect_page_id'] : 0,
                                    'echo'             => 1,
                                    'id'               => 'frontend-page',
                                    'class'            => 'form-control',
                                    'show_option_none' => __('-- Select Page --', 'advanced-access-manager')
                                ));
                            ?>
                        </div>

                        <div
                            class="form-group aam-redirect-action frontend"
                            id="frontend-url-action"
                            style="display: <?php echo ($frontend_redirect['type'] === 'url_redirect' ? 'block' : 'none'); ?>;"
                        >
                            <label for="frontend-url"><?php echo __('The URL', 'advanced-access-manager'); ?></label>
                            <input
                                type="text"
                                class="form-control"
                                name="frontend.redirect.url"
                                data-group="frontend"
                                placeholder="https://"
                                value="<?php echo stripslashes(esc_js(isset($frontend_redirect['redirect_url']) ? $frontend_redirect['redirect_url'] : '')); ?>"
                            />
                        </div>

                        <div
                            class="form-group aam-redirect-action frontend"
                            id="frontend-callback-action"
                            style="display: <?php echo ($frontend_redirect['type'] === 'trigger_callback' ? 'block' : 'none'); ?>;"
                        >
                            <label for="frontend-url"><?php echo __('PHP Callback Function', 'advanced-access-manager'); ?></label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="<?php echo __('Enter valid callback', 'advanced-access-manager'); ?>"
                                data-group="frontend"
                                name="frontend.redirect.callback"
                                value="<?php echo stripslashes(esc_js(isset($frontend_redirect['callback']) ? $frontend_redirect['callback'] : '')); ?>"
                            />
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane" id="backend-redirect">
                        <div class="radio">
                            <input
                                type="radio"
                                name="backend.redirect.type"
                                id="backend-redirect-default"
                                data-action="#backend-default"
                                value="default"
                                data-group="backend"
                                <?php echo ($backend_redirect['type'] === 'default' ? ' checked' : ''); ?>
                            />
                            <label for="backend-redirect-default"><?php echo AAM_Backend_View_Helper::preparePhrase('Default [("Access Denied" message)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input
                                type="radio"
                                name="backend.redirect.type"
                                id="backend-redirect-message"
                                data-action="#backend-message"
                                value="custom_message"
                                data-group="backend"
                                <?php echo ($backend_redirect['type'] === 'custom_message' ? ' checked' : ''); ?>
                            />
                            <label for="backend-redirect-message"><?php echo AAM_Backend_View_Helper::preparePhrase('Show customized message [(plain text or HTML)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input
                                type="radio"
                                name="backend.redirect.type"
                                id="backend-redirect-page"
                                data-action="#backend-page-action"
                                value="page_redirect"
                                data-group="backend"
                                <?php echo ($backend_redirect['type'] === 'page_redirect' ? ' checked' : ''); ?>
                            />
                            <label for="backend-redirect-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to existing frontend page [(select from the drop-down)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input
                                type="radio"
                                name="backend.redirect.type"
                                id="backend-redirect-url"
                                data-action="#backend-url"
                                value="url_redirect"
                                data-group="backend"
                                <?php echo ($backend_redirect['type'] === 'url_redirect' ? ' checked' : ''); ?>
                            />
                            <label for="backend-redirect-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to local URL [(enter valid URL starting from http or https)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input
                                type="radio"
                                name="backend.redirect.type"
                                id="backend-redirect-callback"
                                data-action="#backend-callback-action"
                                value="trigger_callback"
                                data-group="backend"
                                <?php echo ($backend_redirect['type'] === 'trigger_callback' ? ' checked' : ''); ?>
                            />
                            <label for="backend-redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                        </div>

                        <div
                            class="form-group aam-redirect-action backend"
                            id="backend-default"
                            style="display: <?php echo ($backend_redirect['type'] === 'default' ? 'block' : 'none'); ?>;"
                        >
                            <label for="frontend-default-status-code"><?php echo __('HTTP Status Code', 'advanced-access-manager'); ?></label>
                            <?php $redirect_code = isset($backend_redirect['http_status_code']) ? $backend_redirect['http_status_code'] : 401; ?>
                            <select class="form-control form-clearable" id="backend-default-status-code" data-group="frontend">
                                <option value="401"><?php echo __('HTTP Code (Default 401 - Unauthorized)', 'advanced-access-manager'); ?></option>
                                <option value="402"<?php echo $redirect_code == 402 ? ' selected' : ''; ?>><?php echo __('402 - Payment Required', 'advanced-access-manager'); ?></option>
                                <option value="403"<?php echo $redirect_code == 403 ? ' selected' : ''; ?>><?php echo __('403 - Forbidden', 'advanced-access-manager'); ?></option>
                                <option value="404"<?php echo $redirect_code == 404 ? ' selected' : ''; ?>><?php echo __('404 - Not Found', 'advanced-access-manager'); ?></option>
                                <option value="500"<?php echo $redirect_code == 500 ? ' selected' : ''; ?>><?php echo __('500 - Internal Server Error (WordPress Default)', 'advanced-access-manager'); ?></option>
                            </select>
                        </div>

                        <div
                            class="form-group aam-redirect-action backend"
                            id="backend-message"
                            style="display: <?php echo ($backend_redirect['type'] === 'custom_message' ? 'block' : 'none'); ?>;"
                        >
                            <label for="backend-message"><?php echo __('Customized Message', 'advanced-access-manager'); ?></label>
                            <?php $redirect_message = isset($backend_redirect['message']) ? $backend_redirect['message'] : ''; ?>
                            <textarea
                                class="form-control"
                                rows="3"
                                data-group="backend"
                                placeholder="<?php echo __('Enter message...', 'advanced-access-manager'); ?>"
                                name="backend.redirect.message"
                            ><?php echo esc_textarea($redirect_message); ?></textarea>

                            <div class="aam-mt-1">
                                <label for="backend-message-status-code"><?php echo __('HTTP Status Code', 'advanced-access-manager'); ?></label>
                                <?php $redirect_code = isset($backend_redirect['http_status_code']) ? $backend_redirect['http_status_code'] : 401; ?>
                                <select class="form-control form-clearable" name="backend.redirect.message.code" id="backend-message-status-code" data-group="backend">
                                    <option value="401"><?php echo __('HTTP Code (Default 401 - Unauthorized)', 'advanced-access-manager'); ?></option>
                                    <option value="402"<?php echo $redirect_code == 402 ? ' selected' : ''; ?>><?php echo __('402 - Payment Required', 'advanced-access-manager'); ?></option>
                                    <option value="403"<?php echo $redirect_code == 403 ? ' selected' : ''; ?>><?php echo __('403 - Forbidden', 'advanced-access-manager'); ?></option>
                                    <option value="404"<?php echo $redirect_code == 404 ? ' selected' : ''; ?>><?php echo __('404 - Not Found', 'advanced-access-manager'); ?></option>
                                    <option value="500"<?php echo $redirect_code == 500 ? ' selected' : ''; ?>><?php echo __('500 - Internal Server Error (WordPress Default)', 'advanced-access-manager'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div
                            class="form-group aam-redirect-action backend"
                            id="backend-page-action"
                            style="display: <?php echo ($backend_redirect['type'] === 'page_redirect' ? 'block' : 'none'); ?>;"
                        >
                            <label for="backend-page"><?php echo __('Existing Page', 'advanced-access-manager'); ?></label>
                            <?php
                                wp_dropdown_pages(array(
                                    'depth'            => 99,
                                    'selected'         => isset($backend_redirect['redirect_page_id']) ? $backend_redirect['redirect_page_id'] : 0,
                                    'echo'             => 1,
                                    'id'               => 'backend-page',
                                    'class'            => 'form-control',
                                    'show_option_none' => __('-- Select Page --', 'advanced-access-manager')
                                ));
                            ?>
                        </div>

                        <div
                            class="form-group aam-redirect-action backend"
                            id="backend-url"
                            style="display: <?php echo ($backend_redirect['type'] === 'url_redirect' ? 'block' : 'none'); ?>;"
                        >
                            <label for="backend-url"><?php echo __('The URL', 'advanced-access-manager'); ?></label>
                            <input
                                type="text"
                                class="form-control"
                                data-group="backend"
                                placeholder="https://"
                                name="backend.redirect.url"
                                value="<?php echo stripslashes(esc_js(isset($backend_redirect['redirect_url']) ? $backend_redirect['redirect_url'] : '')); ?>"
                            />
                        </div>

                        <div
                            class="form-group aam-redirect-action backend"
                            id="backend-callback-action"
                            style="display: <?php echo ($backend_redirect['type'] === 'trigger_callback' ? 'block' : 'none'); ?>;"
                        >
                            <label for="frontend-url"><?php echo __('PHP Callback Function', 'advanced-access-manager'); ?></label>
                            <input
                                type="text"
                                class="form-control"
                                data-group="backend"
                                placeholder="<?php echo __('Enter valid callback', 'advanced-access-manager'); ?>"
                                name="backend.redirect.callback"
                                value="<?php echo stripslashes(esc_js(isset($backend_redirect['callback']) ? $backend_redirect['callback'] : '')); ?>"
                            />
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane" id="api-redirect">
                        <div class="radio">
                            <input
                                type="radio"
                                name="api.redirect.type"
                                id="api-redirect-default"
                                data-action="#api-default"
                                value="default"
                                data-group="api"
                                <?php echo ($api_redirect['type'] === 'default' ? ' checked' : ''); ?>
                            />
                            <label for="api-redirect-default"><?php echo AAM_Backend_View_Helper::preparePhrase('Default [(HTTP Status Code 401)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input
                                type="radio"
                                name="api.redirect.type"
                                id="api-redirect-message"
                                data-action="#api-message"
                                value="custom_message"
                                data-group="api"
                                <?php echo ($api_redirect['type'] === 'custom_message' ? ' checked' : ''); ?>
                            />
                            <label for="api-redirect-message"><?php echo AAM_Backend_View_Helper::preparePhrase('Show customized message', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input
                                type="radio"
                                name="api.redirect.type"
                                id="api-redirect-callback"
                                data-action="#api-callback-action"
                                value="trigger_callback"
                                data-group="api"
                                <?php echo ($api_redirect['type'] === 'trigger_callback' ? ' checked' : ''); ?>
                            />
                            <label for="api-redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                        </div>

                        <div
                            class="form-group aam-redirect-action api"
                            id="api-message"
                            style="display: <?php echo ($api_redirect['type'] === 'custom_message' ? 'block' : 'none'); ?>;"
                        >
                            <label for="api-message"><?php echo __('Customized Message', 'advanced-access-manager'); ?></label>
                            <?php $redirect_message = isset($api_redirect['message']) ? $api_redirect['message'] : ''; ?>
                            <textarea
                                class="form-control"
                                rows="3"
                                data-group="api"
                                placeholder="<?php echo __('Enter message...', 'advanced-access-manager'); ?>"
                                name="api.redirect.message"
                            ><?php echo esc_textarea($redirect_message); ?></textarea>

                            <div class="aam-mt-1">
                                <label for="api-message-status-code"><?php echo __('HTTP Status Code', 'advanced-access-manager'); ?></label>
                                <?php $redirect_code = isset($api_redirect['http_status_code']) ? $api_redirect['http_status_code'] : 401; ?>
                                <select class="form-control form-clearable" name="api.redirect.message.code" id="api-message-status-code" data-group="api">
                                    <option value="401"><?php echo __('HTTP Code (Default 401 - Unauthorized)', 'advanced-access-manager'); ?></option>
                                    <option value="402"<?php echo $redirect_code == 402 ? ' selected' : ''; ?>><?php echo __('402 - Payment Required', 'advanced-access-manager'); ?></option>
                                    <option value="403"<?php echo $redirect_code == 403 ? ' selected' : ''; ?>><?php echo __('403 - Forbidden', 'advanced-access-manager'); ?></option>
                                    <option value="404"<?php echo $redirect_code == 404 ? ' selected' : ''; ?>><?php echo __('404 - Not Found', 'advanced-access-manager'); ?></option>
                                    <option value="500"<?php echo $redirect_code == 500 ? ' selected' : ''; ?>><?php echo __('500 - Internal Server Error (WordPress Default)', 'advanced-access-manager'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div
                            class="form-group aam-redirect-action api"
                            id="api-callback-action"
                            style="display: <?php echo ($api_redirect['type'] === 'trigger_callback' ? 'block' : 'none'); ?>;"
                        >
                            <label for="frontend-url"><?php echo __('PHP Callback Function', 'advanced-access-manager'); ?></label>
                            <input
                                type="text"
                                class="form-control"
                                data-group="api"
                                placeholder="<?php echo __('Enter valid callback', 'advanced-access-manager'); ?>"
                                name="api.redirect.callback"
                                value="<?php echo stripslashes(esc_js(isset($api_redirect['callback']) ? $api_redirect['callback'] : '')); ?>"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }