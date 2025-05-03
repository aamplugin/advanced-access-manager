<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::get_instance();
        $service      = $access_level->urls();
    ?>

    <div class="aam-feature" id="url-content">
        <div class="row">
            <div class="col-xs-12">
                <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access to an unlimited number of individual URLs. With the premium %sComplete Package%s, you can use the wildcard [*] denotation to manage access to a specific website section (e.g. [/members/*], [/premium*]) or make the entire website private. To learn more, refer to our official documentation page %shere%s.', 'strong', 'i', 'i'), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/managing-access-to-wordpress-website-urls?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                <?php } ?>

                <div
                    class="aam-overwrite"
                    id="aam-uri-overwrite"
                    style="display: <?php echo ($service->is_customized() ? 'block' : 'none'); ?>"
                >
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', 'advanced-access-manager'); ?></span>
                    <span><a href="#" id="uri-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', 'advanced-access-manager'); ?></a></span>
                </div>

                <table id="uri-list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th width="60%"><?php echo __('URL', 'advanced-access-manager'); ?></th>
                            <th width="20%"><?php echo __('Type', 'advanced-access-manager'); ?></th>
                            <th><?php echo __('Actions', 'advanced-access-manager'); ?></th>
                            <th>Rule</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="uri-model" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('URL Access Rule', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label><?php echo AAM_Backend_View_Helper::preparePhrase('Enter URL [(wildcard * is available with premium add-on)]', 'small'); ?></label>
                            <input
                                type="text"
                                class="form-control form-clearable"
                                id="url_rule_url"
                                placeholder="<?php echo __('Enter valid URL', 'advanced-access-manager'); ?>"
                            />
                        </div>

                        <label><?php echo __('What should be done when a URL is matched?', 'advanced-access-manager'); ?></label><br />

                        <?php
                            $restriction_types = apply_filters('aam_ui_url_restriction_types_filter', [
                                'allow' => [
                                    'title' => __('Allow direct access', 'advanced-access-manager')
                                ],
                                'deny' => [
                                    'title' => __('Restrict direct access (Access Denied Redirect preference is applied)', 'advanced-access-manager')
                                ],
                                'custom_message' => [
                                    'title' => __('Show customized message (plain text or HTML)', 'advanced-access-manager')
                                ],
                                'page_redirect' => [
                                    'title' => __('Redirected to existing page (select from the drop-down)', 'advanced-access-manager')
                                ],
                                'url_redirect' => [
                                    'title' => __('Redirected to a different URL (valid URL, starting with http or https is required)', 'advanced-access-manager')
                                ],
                                'trigger_callback' => [
                                    'title' => __('Trigger PHP callback function (valid HP callback is required)', 'advanced-access-manager')
                                ],
                            ]);

                            if ($access_level->is_visitor()) {
                                $restriction_types['login_redirect'] = [
                                    'title' => __('Redirect to the login page (after login, user will be redirected back to the restricted page)', 'advanced-access-manager')
                                ];
                            }
                        ?>

                        <?php foreach($restriction_types as $type => $props) { ?>
                            <div class="radio">
                                <input
                                    type="radio"
                                    name="uri.access.type"
                                    id="url_access_<?php echo esc_attr($type); ?>"
                                    value="<?php echo esc_attr($type); ?>"
                                />
                                <label for="url_access_<?php echo esc_attr($type); ?>">
                                    <?php echo esc_js($props['title']); ?>
                                </label>
                            </div>
                        <?php } ?>

                        <div class="form-group aam-uri-access-action" id="url_access_custom_message_attrs" style="display: none;">
                            <label><?php echo __('Customized Message', 'advanced-access-manager'); ?></label>
                            <textarea
                                class="form-control form-clearable"
                                rows="3"
                                id="url_access_custom_message_value"
                                placeholder="<?php echo __('Enter message...', 'advanced-access-manager'); ?>">
                            </textarea>
                        </div>

                        <div class="form-group aam-uri-access-action" id="url_access_page_redirect_attrs" style="display: none;">
                            <label><?php echo __('Existing Page', 'advanced-access-manager'); ?></label>
                            <?php
                                wp_dropdown_pages(array(
                                    'depth'            => 99,
                                    'echo'             => 1,
                                    'id'               => 'url_access_page_redirect_value',
                                    'class'            => 'form-control form-clearable',
                                    'show_option_none' => __('-- Select Page --', 'advanced-access-manager')
                                ));
                            ?>
                        </div>

                        <div class="form-group aam-uri-access-action" id="url_access_url_redirect_attrs" style="display: none;">
                            <label><?php echo __('The Valid Redirect URL', 'advanced-access-manager'); ?></label>
                            <input
                                type="text"
                                class="form-control form-clearable"
                                placeholder="https://"
                                id="url_access_url_redirect_value"
                            />
                        </div>

                        <div class="form-group aam-uri-access-action" id="url_access_http_status_code" style="display: none;">
                            <label><?php echo __('HTTP Redirect Code', 'advanced-access-manager'); ?></label>
                            <select class="form-control form-clearable" id="url_access_http_redirect_code">
                                <option value=""><?php echo __('HTTP Code (Default 307)', 'advanced-access-manager'); ?></option>
                                <option value="301"><?php echo __('301 - Moved Permanently', 'advanced-access-manager'); ?></option>
                                <option value="302"><?php echo __('302 - Found', 'advanced-access-manager'); ?></option>
                                <option value="303"><?php echo __('303 - See Other', 'advanced-access-manager'); ?></option>
                                <option value="307"><?php echo __('307 - Temporary Redirect', 'advanced-access-manager'); ?></option>
                            </select>
                        </div>

                        <div class="form-group aam-uri-access-action" id="url_access_trigger_callback_attrs" style="display: none;">
                            <label><?php echo __('PHP Callback Function', 'advanced-access-manager'); ?></label>
                            <input
                                type="text"
                                class="form-control form-clearable"
                                placeholder="Enter valid callback"
                                id="url_access_trigger_callback_value"
                            />
                        </div>

                        <?php do_action('aam_ui_url_access_restriction_modal_action'); ?>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-success"
                            id="url_save_btn"
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

        <div class="modal fade" id="uri-delete-model" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete URL Rule', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <p class="aam-notification">
                                <?php echo __('You are about to delete the URL Rule. Please confirm!', 'advanced-access-manager'); ?>
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="uri-delete-btn"><?php echo __('Delete', 'advanced-access-manager'); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }