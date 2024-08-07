<?php /** @version 7.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-overwrite<?php echo $params->resource->is_overwritten() ? '' : ' hidden'; ?>">
        <span>
            <i class="icon-check"></i>
            <?php echo __('Settings are customized', AAM_KEY); ?>
        </span>
        <span>
            <a
                href="#"
                id="content-reset"
                data-type="<?php echo $params->resource::TYPE; ?>"
                data-id="<?php echo esc_attr($params->resource->ID); ?>"
                class="btn btn-xs btn-primary"
            ><?php echo __('Reset to default', AAM_KEY); ?></a>
        </span>
    </div>

    <input
        type="hidden"
        value="<?php echo esc_attr($params->resource::TYPE); ?>"
        id="content_resource_type"
    />

    <?php
        $internal_id = $params->resource->get_internal_id();

        // Determine the correct resource ID. Terms typically have compound ID
        if ($params->resource::TYPE === AAM_Framework_Type_Resource::TERM) {
            $id = is_array($internal_id) ? $internal_id['id'] : intval($internal_id);
        } else {
            $id = $internal_id;
        }
    ?>
    <input
        type="hidden"
        value="<?php echo esc_attr($id); ?>"
        id="content_resource_id"
    />
    <div id="content_resource_settings" class="hidden"><?php echo json_encode($params->resource->get_permissions()); ?></div>

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
                                    ><?php echo __('customize', AAM_KEY); ?></a>
                                </small>
                            <?php } ?>

                            <?php if (AAM_Framework_Manager::configs()->get_config('core.settings.ui.tips')) { ?>
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
                                <?php if (isset($settings['attributes'])) { ?>
                                    <?php foreach($settings['attributes'] as $key => $value) {
                                        echo 'data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
                                    } ?>
                                <?php } ?>
                                data-on="<?php echo __('Deny', AAM_KEY); ?>"
                                data-off="<?php echo __('Allow', AAM_KEY); ?>"
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
                                if ($params->resource::TYPE === AAM_Framework_Type_Resource::POST_TYPE) {
                                    $resource_type = __('post type', AAM_KEY);
                                    $resource_name = $params->resource->label;
                                } elseif ($params->resource::TYPE === AAM_Framework_Type_Resource::TAXONOMY) {
                                    $resource_type = __('taxonomy', AAM_KEY);
                                    $resource_name = $params->resource->label;
                                } elseif ($params->resource::TYPE === AAM_Framework_Type_Resource::TERM) {
                                    $resource_type = __('term', AAM_KEY);
                                    $resource_name = $params->resource->name;
                                }

                                echo sprintf(
                                    __('Upgrade to our %spremium add-on%s in order to be able to manage access controls for the %s %s.', AAM_KEY),
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

    <?php if ($params->resource::TYPE === AAM_Framework_Type_Resource::POST) { ?>
        <div class="modal fade" data-backdrop="false" id="modal_post_hidden" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Customize Visibility', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php if (AAM_Framework_Manager::configs()->get_config('core.settings.ui.tips')) { ?>
                            <p class="aam-info">
                                <?php echo sprintf(
                                    __('Customize the visibility of "%s" separately for each section of your website. It\'s crucial to thoughtfully select which areas will have hidden content. For instance, you might choose to hide certain posts in the backend for content editors, while still allowing them to be visible on the frontend for general users.', AAM_KEY),
                                    $params->resource->post_title
                                ); ?>
                            </p>
                        <?php } ?>

                        <table class="table table-striped table-bordered">
                            <tbody>
                                <tr>
                                    <td>
                                        <span class='aam-setting-title'><?php echo __('Frontend', AAM_KEY); ?></span>
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
                                            <?php echo ($params->resource->is_hidden_on('frontend') ? 'checked' : ''); ?>
                                            data-on="<?php echo __('Hidden', AAM_KEY); ?>"
                                            data-off="<?php echo __('Visible', AAM_KEY); ?>"
                                            data-size="small"
                                            data-onstyle="danger"
                                            data-offstyle="success"
                                        />
                                    </td>
                                </tr>
                                <?php if (!$params->access_level->is_visitor()) { ?>
                                <tr>
                                    <td>
                                        <span class='aam-setting-title'><?php echo __('Backend', AAM_KEY); ?></span>
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
                                            <?php echo ($params->resource->is_hidden_on('backend') ? 'checked' : ''); ?>
                                            data-on="<?php echo __('Hidden', AAM_KEY); ?>"
                                            data-off="<?php echo __('Visible', AAM_KEY); ?>"
                                            data-size="small"
                                            data-onstyle="danger"
                                            data-offstyle="success"
                                        />
                                    </td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <td>
                                        <span class='aam-setting-title'><?php echo __('RESTful API', AAM_KEY); ?></span>
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
                                            <?php echo ($params->resource->is_hidden_on('api') ? 'checked' : ''); ?>
                                            data-on="<?php echo __('Hidden', AAM_KEY); ?>"
                                            data-off="<?php echo __('Visible', AAM_KEY); ?>"
                                            data-size="small"
                                            data-onstyle="danger"
                                            data-offstyle="success"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <?php do_action('aam_ui_content_access_form_visibility_action', $params->resource); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success btn-save" id="save_list_permission"><?php echo __('Save', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php if ($params->resource::TYPE === AAM_Framework_Type_Resource::POST) { ?>
        <div class="modal fade" data-backdrop="false" id="modal_post_restriction" tabindex="-1" role="dialog">
            <?php $restriction_settings = $this->get_permission_settings('read', $params->resource); ?>
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Customize Restriction', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php
                            $restriction_type  = isset($restriction_settings['restriction_type']) ? $restriction_settings['restriction_type'] : 'default';
                            $restriction_types = apply_filters('aam_ui_content_restriction_types_filter', [
                                'default' => [
                                    'title' => __('Restrict direct access', AAM_KEY)
                                ],
                                'teaser_message' => [
                                    'title' => __('Show a teaser message instead of the content', AAM_KEY)
                                ],
                                'redirect' => [
                                    'title' => __('Redirect to a different location', AAM_KEY)
                                ],
                                'password_protected' => [
                                    'title' => __('Protect access with a password', AAM_KEY)
                                ],
                                'expire' => [
                                    'title' => __('Deny direct access after defined date/time', AAM_KEY)
                                ],
                            ], $params->resource);
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

                        <hr />

                        <div id="restriction_type_extra">
                            <div data-type="teaser_message" class="hidden">
                                <div class="form-group">
                                    <label><?php echo __('Plain text or valid HTML', AAM_KEY); ?></label>
                                    <textarea
                                        class="form-control"
                                        placeholder="<?php echo __('Enter your teaser message...', AAM_KEY); ?>"
                                        rows="5"
                                        id="aam-teaser-message"
                                    ><?php echo esc_textarea(isset($restriction_settings['teaser_message']) ? $restriction_settings['teaser_message'] : ''); ?></textarea>
                                    <span class="hint text-muted"><?php echo AAM_Backend_View_Helper::preparePhrase('Use [&#91;excerpt&#93;] shortcode to insert post excerpt to the teaser message.', 'strong'); ?></span>
                                </div>
                            </div>

                            <div data-type="redirect" class="hidden">
                                <?php $redirect_type = isset($restriction_settings['redirect']['type']) ? $restriction_settings['redirect']['type'] : 'default'; ?>

                                <?php
                                    $redirect_types = [
                                        'page_redirect'    => AAM_Backend_View_Helper::preparePhrase('Redirected to existing page [(select from the drop-down)]', 'small'),
                                        'url_redirect'     => AAM_Backend_View_Helper::preparePhrase('Redirected to the URL [(enter full URL starting from http or https)]', 'small'),
                                        'trigger_callback' => sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>')
                                    ];

                                    if ($params->access_level->is_visitor()) {
                                        $redirect_types['login_redirect'] = AAM_Backend_View_Helper::preparePhrase('Redirect to the login page [(after login, user will be redirected back to the restricted page)]', 'small');
                                    }
                                ?>
                                <div class="form-group">
                                    <label><?php echo __('Select Redirect Type', AAM_KEY); ?></label>
                                    <select class="form-control" id="restricted_redirect_type">
                                        <option value=""><?php echo __('-- Redirect Type --', AAM_KEY); ?></option>
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
                                        <label><?php echo __('Existing Page', AAM_KEY); ?></label>
                                        <?php wp_dropdown_pages([
                                            'depth'            => 99,
                                            'echo'             => 1,
                                            'selected'         => (isset($restricted_settings['redirect']['redirect_page_id']) ? $restricted_settings['redirect']['redirect_page_id'] : 0),
                                            'class'            => 'form-control',
                                            'show_option_none' => __('-- Select Page --', AAM_KEY)
                                        ]); ?>
                                    </div>

                                    <div
                                        class="form-group aam-mt-2 restricted-redirect-type<?php echo ($redirect_type === 'url_redirect' ? 'block' : ' hidden'); ?>"
                                        data-type="url_redirect"
                                    >
                                        <label><?php echo __('The URL', AAM_KEY); ?></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            placeholder="https://"
                                            value="<?php echo esc_attr(isset($restricted_settings['redirect']['redirect_url']) ? $restricted_settings['redirect']['redirect_url'] : ''); ?>"
                                        />
                                    </div>

                                    <div
                                        class="form-group aam-mt-2 restricted-redirect-type<?php echo ($redirect_type === 'trigger_callback' ? 'block' : ' hidden'); ?>"
                                        data-type="trigger_callback"
                                    >
                                        <label><?php echo __('PHP Callback Function', AAM_KEY); ?></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            placeholder="<?php echo __('Enter valid callback', AAM_KEY); ?>"
                                            value="<?php echo esc_attr(isset($restricted_settings['redirect']['callback']) ? $restricted_settings['redirect']['callback'] : ''); ?>"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div data-type="password_protected" class="hidden">
                                <div class="form-group">
                                    <label><?php echo __('Password', AAM_KEY); ?></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="<?php echo __('Enter Password', AAM_KEY); ?>"
                                        value="<?php echo esc_attr(isset($restricted_settings['password']) ? $restricted_settings['password'] : ''); ?>"
                                    />
                                </div>
                            </div>

                            <div data-type="expire" class="hidden">
                                <div class="form-group">
                                    <div id="post_expire_datapicker"></div>
                                    <input
                                        type="hidden"
                                        id="aam_expire_datetime"
                                        value="<?php echo esc_attr(isset($redirect_type['expires_after']) ? $redirect_type['expires_after'] : strtotime('tomorrow')); ?>"
                                    />
                                </div>
                            </div>

                            <?php do_action('aam_ui_content_access_form_restriction_action', $params); ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success btn-save" id="save_read_permission"><?php echo __('Save Settings', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php do_action('aam_ui_append_content_access_form_action', $params); ?>
<?php }