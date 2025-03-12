<?php /** @version 7.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $params           = isset($params) ? $params : (object) [];
        $resource         = $params->resource;
        $permission_scope = []; // Additional attributes to add

        if ($resource->type === AAM_Framework_Type_Resource::TERM) {
            $permission_scope['taxonomy'] = $params->resource_identifier->taxonomy;

            if (!empty($params->resource_identifier->post_type)) {
                $permission_scope['post_type'] = $params->resource_identifier->post_type;
            }
        }

        $id = $params->resource_id;
    ?>

    <div class="aam-overwrite<?php echo $resource->is_customized($params->resource_identifier) ? '' : ' hidden'; ?>">
        <span>
            <i class="icon-check"></i>
            <?php echo __('Settings are customized', 'advanced-access-manager'); ?>
        </span>
        <span>
            <a
                href="#"
                id="content_reset"
                class="btn btn-xs btn-primary"
            ><?php echo __('Reset to default', 'advanced-access-manager'); ?></a>
        </span>
    </div>

    <input
        type="hidden"
        value="<?php echo esc_attr($resource->type); ?>"
        id="content_resource_type"
    />
    <input
        type="hidden"
        value="<?php echo esc_attr($id); ?>"
        id="content_resource_id"
    />
    <input
        type="hidden"
        value="<?php echo esc_attr(json_encode($permission_scope)); ?>"
        id="content_permission_scope"
    />
    <div id="content_resource_settings" class="hidden"><?php echo json_encode($resource->get_permissions($params->resource_identifier)); ?></div>

    <table class="table table-striped table-bordered" id="permission_toggles">
        <tbody>
            <?php if (count($params->access_controls) > 0) { ?>
                <?php foreach ($params->access_controls as $control => $settings) { ?>
                    <tr>
                        <td>
                            <strong class="aam-block aam-highlight text-uppercase"><?php echo esc_js($settings['title']); ?></strong>
                            <?php if (!empty($settings['customize'])) { ?>
                                <small class="aam-small-highlighted">
                                    <?php echo esc_js($settings['customize']); ?>:
                                    <a
                                        href="#<?php echo esc_attr($settings['modal']); ?>"
                                        data-toggle="modal"
                                        class="advanced-post-option"
                                    ><?php echo __('customize', 'advanced-access-manager'); ?></a>
                                </small>
                            <?php } ?>

                            <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
                                <p class="aam-hint">
                                    <?php echo esc_js($settings['description']); ?>
                                </p>
                            <?php } ?>
                        </td>
                        <td class="text-center">
                            <input
                                data-toggle="toggle"
                                id="access_control_<?php echo esc_attr($control); ?>"
                                type="checkbox"
                                <?php echo ($settings['is_denied'] ? 'checked' : ''); ?>
                                data-permission="<?php echo esc_attr($control); ?>"
                                data-on="<?php echo __('Deny', 'advanced-access-manager'); ?>"
                                data-off="<?php echo __('Allow', 'advanced-access-manager'); ?>"
                                data-size="small"
                                data-onstyle="danger"
                                data-offstyle="success"
                            />
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <div class="row">
                    <div class="col-xs-12">
                        <p class="aam-notification">
                            <?php
                                if ($resource->type === AAM_Framework_Type_Resource::POST_TYPE) {
                                    $resource_type = __('post type', 'advanced-access-manager');
                                    $resource_name = $params->resource_identifier->label;
                                } elseif ($resource->type === AAM_Framework_Type_Resource::TAXONOMY) {
                                    $resource_type = __('taxonomy', 'advanced-access-manager');
                                    $resource_name = $params->resource_identifier->label;
                                } elseif ($resource->type === AAM_Framework_Type_Resource::TERM) {
                                    $resource_type = __('term', 'advanced-access-manager');
                                    $resource_name = $params->resource_identifier->name;
                                }

                                echo sprintf(
                                    __('Upgrade to our %spremium add-on%s in order to be able to manage access controls for the %s %s.', 'advanced-access-manager'),
                                    '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">',
                                    '</a>',
                                    $resource_type,
                                    $resource_name
                                );
                            ?>
                        </p>
                    </div>
                </div>
            <?php } ?>
        </tbody>
    </table>

    <?php if (!empty($params->access_controls['list'])) { ?>
    <div class="modal fade" data-backdrop="false" id="modal_content_visibility" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"
                    ><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Customize Visibility', 'advanced-access-manager'); ?></h4>
                </div>
                <div class="modal-body">
                    <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
                        <p class="aam-info">
                            <?php echo $params->access_controls['list']['tooltip']; ?>
                        </p>
                    <?php } ?>

                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td>
                                    <span class='aam-setting-title'><?php echo __('Frontend', 'advanced-access-manager'); ?></span>
                                    <p class="aam-setting-description">
                                        <?php echo esc_js($params->access_controls['list']['on']['frontend']); ?>
                                    </p>
                                </td>
                                <td class="text-center">
                                    <input
                                        data-toggle="toggle"
                                        name="frontend"
                                        id="hidden_frontend"
                                        type="checkbox"
                                        <?php echo ($params->access_controls['list']['is_denied'] && in_array('frontend', $params->access_controls['list']['areas']) ? 'checked' : ''); ?>
                                        data-on="<?php echo __('Hidden', 'advanced-access-manager'); ?>"
                                        data-off="<?php echo __('Visible', 'advanced-access-manager'); ?>"
                                        data-size="small"
                                        data-onstyle="danger"
                                        data-offstyle="success"
                                    />
                                </td>
                            </tr>
                            <?php if (!$params->access_level->is_visitor()) { ?>
                            <tr>
                                <td>
                                    <span class='aam-setting-title'><?php echo __('Backend', 'advanced-access-manager'); ?></span>
                                    <p class="aam-setting-description">
                                        <?php echo esc_js($params->access_controls['list']['on']['backend']); ?>
                                    </p>
                                </td>
                                <td class="text-center">
                                    <input
                                        data-toggle="toggle"
                                        name="backend"
                                        id="hidden_backend"
                                        type="checkbox"
                                        <?php echo ($params->access_controls['list']['is_denied'] && in_array('backend', $params->access_controls['list']['areas']) ? 'checked' : ''); ?>
                                        data-on="<?php echo __('Hidden', 'advanced-access-manager'); ?>"
                                        data-off="<?php echo __('Visible', 'advanced-access-manager'); ?>"
                                        data-size="small"
                                        data-onstyle="danger"
                                        data-offstyle="success"
                                    />
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <td>
                                    <span class='aam-setting-title'><?php echo __('RESTful API', 'advanced-access-manager'); ?></span>
                                    <p class="aam-setting-description">
                                        <?php echo esc_js($params->access_controls['list']['on']['api']); ?>
                                    </p>
                                </td>
                                <td class="text-center">
                                    <input
                                        data-toggle="toggle"
                                        name="api"
                                        id="hidden_api"
                                        type="checkbox"
                                        <?php echo ($params->access_controls['list']['is_denied'] && in_array('api', $params->access_controls['list']['areas']) ? 'checked' : ''); ?>
                                        data-on="<?php echo __('Hidden', 'advanced-access-manager'); ?>"
                                        data-off="<?php echo __('Visible', 'advanced-access-manager'); ?>"
                                        data-size="small"
                                        data-onstyle="danger"
                                        data-offstyle="success"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php do_action('aam_ui_content_access_form_visibility_action', $params); ?>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-success btn-save"
                        id="save_list_permission"
                    ><?php echo __('Save', 'advanced-access-manager'); ?></button>
                    <button
                        type="button"
                        class="btn btn-default"
                        data-dismiss="modal"
                    ><?php echo __('Close', 'advanced-access-manager'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <?php if (!empty($params->access_controls['read'])) { ?>
        <div class="modal fade" data-backdrop="false" id="modal_content_restriction" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"
                        ><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Customize Restriction', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
                            <p class="aam-info">
                                <?php echo $params->access_controls['read']['tooltip']; ?>
                            </p>
                        <?php } ?>

                        <?php
                            $restriction_settings = $resource->get_permission($params->resource_identifier, 'read');
                            $restriction_type     = isset($restriction_settings['restriction_type']) ? $restriction_settings['restriction_type'] : 'default';
                            $restriction_types    = apply_filters('aam_ui_content_restriction_types_filter', [
                                'default' => [
                                    'title' => __('Restrict direct access', 'advanced-access-manager')
                                ],
                                'teaser_message' => [
                                    'title' => __('Show a teaser message instead of the content', 'advanced-access-manager')
                                ],
                                'redirect' => [
                                    'title' => __('Redirect to a different location', 'advanced-access-manager')
                                ],
                                'password_protected' => [
                                    'title' => __('Protect access with a password', 'advanced-access-manager')
                                ],
                                'expire' => [
                                    'title' => __('Deny direct access after defined date/time', 'advanced-access-manager')
                                ],
                            ], $resource);
                        ?>
                        <div id="restriction_types">
                            <?php foreach($restriction_types as $type => $settings) { ?>
                                <div class="radio red">
                                    <input
                                        type="radio"
                                        name="content_restriction_type"
                                        id="content_restriction_type_<?php echo esc_attr($type); ?>"
                                        value="<?php echo esc_attr($type); ?>"
                                        <?php echo ($restriction_type === $type ? 'checked' : ''); ?>
                                    />
                                    <label for="content_restriction_type_<?php echo $type; ?>">
                                        <?php echo esc_js($settings['title']); ?>
                                    </label>
                                </div>
                            <?php } ?>
                        </div>

                        <div id="restriction_type_extra">
                            <div data-type="teaser_message" class="<?php echo $restriction_type !== 'teaser_message' ? 'hidden' : ''; ?>">
                                <hr />

                                <div class="form-group">
                                    <label><?php echo __('Plain text or valid HTML', 'advanced-access-manager'); ?></label>
                                    <textarea
                                        class="form-control"
                                        placeholder="<?php echo __('Enter your teaser message...', 'advanced-access-manager'); ?>"
                                        rows="5"
                                        id="aam_teaser_message"
                                    ><?php echo esc_textarea(isset($restriction_settings['message']) ? $restriction_settings['message'] : ''); ?></textarea>
                                    <span class="hint text-muted"><?php echo AAM_Backend_View_Helper::preparePhrase('Use [&#91;excerpt&#93;] shortcode to insert post excerpt to the teaser message.', 'strong'); ?></span>
                                </div>
                            </div>

                            <div data-type="redirect" class="<?php echo $restriction_type !== 'redirect' ? 'hidden' : ''; ?>">
                                <?php $redirect_type = isset($restriction_settings['redirect']['type']) ? $restriction_settings['redirect']['type'] : 'default'; ?>

                                <?php
                                    $redirect_types = [
                                        'page_redirect'    => __('Redirected to existing page (select from the drop-down)', 'advanced-access-manager'),
                                        'url_redirect'     => __('Redirected to the URL (enter full URL starting from http or https)', 'advanced-access-manager'),
                                        'trigger_callback' => __('Trigger PHP callback function (valid PHP callback is required)', 'advanced-access-manager')
                                    ];

                                    if ($params->access_level->is_visitor()) {
                                        $redirect_types['login_redirect'] = __('Redirect to the login page (after login, user will be redirected back to the restricted page)', 'advanced-access-manager');
                                    }
                                ?>

                                <hr />

                                <div class="form-group">
                                    <label><?php echo __('Select Redirect Type', 'advanced-access-manager'); ?></label>
                                    <select class="form-control" id="restricted_redirect_type">
                                        <option value=""><?php echo __('-- Redirect Type --', 'advanced-access-manager'); ?></option>
                                        <?php foreach($redirect_types as $type => $label) { ?>
                                            <option
                                                value="<?php echo $type; ?>"
                                                <?php echo $type === $redirect_type ? 'selected' : ''; ?>
                                            ><?php echo $label; ?></option>
                                        <?php } ?>
                                    </select>

                                    <div
                                        class="form-group aam-mt-2 restricted-redirect-type<?php echo ($redirect_type === 'page_redirect' ? '' : ' hidden'); ?>"
                                        data-type="page_redirect"
                                    >
                                        <label><?php echo __('Existing Page', 'advanced-access-manager'); ?></label>
                                        <?php wp_dropdown_pages([
                                            'depth'            => 99,
                                            'echo'             => 1,
                                            'selected'         => (isset($restriction_settings['redirect']['redirect_page_id']) ? $restriction_settings['redirect']['redirect_page_id'] : 0),
                                            'class'            => 'form-control',
                                            'id'               => 'restricted_redirect_page_id',
                                            'show_option_none' => __('-- Select Page --', 'advanced-access-manager')
                                        ]); ?>
                                    </div>

                                    <div
                                        class="form-group aam-mt-2 restricted-redirect-type<?php echo ($redirect_type === 'url_redirect' ? 'block' : ' hidden'); ?>"
                                        data-type="url_redirect"
                                    >
                                        <label><?php echo __('The URL', 'advanced-access-manager'); ?></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            placeholder="https://"
                                            id="restricted_redirect_url"
                                            value="<?php echo esc_attr(isset($restriction_settings['redirect']['redirect_url']) ? $restriction_settings['redirect']['redirect_url'] : ''); ?>"
                                        />
                                    </div>

                                    <div
                                        class="form-group aam-mt-2 restricted-redirect-type<?php echo ($redirect_type === 'trigger_callback' ? 'block' : ' hidden'); ?>"
                                        data-type="trigger_callback"
                                    >
                                        <label><?php echo __('PHP Callback Function', 'advanced-access-manager'); ?></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            placeholder="<?php echo __('Enter valid callback', 'advanced-access-manager'); ?>"
                                            id="restricted_callback"
                                            value="<?php echo esc_attr(isset($restriction_settings['redirect']['callback']) ? $restriction_settings['redirect']['callback'] : ''); ?>"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div data-type="password_protected" class="<?php echo $restriction_type !== 'password_protected' ? 'hidden' : ''; ?>">
                                <hr />

                                <div class="form-group">
                                    <label><?php echo __('Password', 'advanced-access-manager'); ?></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="<?php echo __('Enter Password', 'advanced-access-manager'); ?>"
                                        id="restricted_password"
                                        value="<?php echo esc_attr(isset($restriction_settings['password']) ? $restriction_settings['password'] : ''); ?>"
                                    />
                                </div>
                            </div>

                            <div data-type="expire" class="<?php echo $restriction_type !== 'expire' ? 'hidden' : ''; ?>">
                                <hr />

                                <div class="form-group">
                                    <div id="content_expire_datepicker"></div>
                                    <input
                                        type="hidden"
                                        id="aam_expire_datetime"
                                        value="<?php echo esc_attr(isset($restriction_settings['expires_after']) ? $restriction_settings['expires_after'] : strtotime('tomorrow')); ?>"
                                    />
                                </div>
                            </div>

                            <?php do_action('aam_ui_content_restriction_type_extra_action', $params); ?>
                        </div>

                        <?php do_action('aam_ui_content_access_form_restriction_action', $params); ?>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-success btn-save"
                            id="save_read_permission"
                        ><?php echo __('Save Settings', 'advanced-access-manager'); ?></button>
                        <button
                            type="button"
                            class="btn btn-default"
                            data-dismiss="modal"
                        ><?php echo __('Close', 'advanced-access-manager'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php do_action('aam_ui_append_content_access_form_action', $params); ?>
<?php }