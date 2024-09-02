<?php
/**
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/320
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/298
 * @since 6.9.9  https://github.com/aamplugin/advanced-access-manager/issues/266
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.34
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::getInstance();
        $service      = $access_level->urls();
    ?>

    <div class="aam-feature" id="url-content">
        <div class="row">
            <div class="col-xs-12">
                <?php if (AAM_Framework_Manager::configs()->get_config('core.settings.ui.tips')) { ?>
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access to an unlimited number of individual URLs. With the premium %sComplete Package%s, you can use the wildcard [*] denotation to manage access to a specific website section (e.g. [/members/*], [/premium*]) or make the entire website private. To learn more, refer to our official documentation page %shere%s.', 'strong', 'i', 'i'), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/managing-access-to-wordpress-website-urls?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                <?php } ?>

                <div
                    class="aam-overwrite"
                    id="aam-uri-overwrite"
                    style="display: <?php echo ($service->get_resource()->is_overwritten() ? 'block' : 'none'); ?>"
                >
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="uri-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>

                <table id="uri-list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="60%"><?php echo __('URL', AAM_KEY); ?></th>
                            <th width="20%"><?php echo __('Type', AAM_KEY); ?></th>
                            <th><?php echo __('Actions', AAM_KEY); ?></th>
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('URL Access Rule', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label><?php echo AAM_Backend_View_Helper::preparePhrase('Enter URL [(wildcard * is available with premium add-on)]', 'small'); ?></label>
                            <input
                                type="text"
                                class="form-control form-clearable"
                                id="url_rule_url"
                                placeholder="<?php echo __('Enter valid URL', AAM_KEY); ?>"
                            />
                        </div>

                        <label><?php echo __('What should be done when a URL is matched?', AAM_KEY); ?></label><br />

                        <?php
                            $restriction_types = apply_filters('aam_ui_url_restriction_types_filter', [
                                'allow' => [
                                    'title' => __('Allow direct access', AAM_KEY)
                                ],
                                'deny' => [
                                    'title' => __('Restrict direct access (Access Denied Redirect preference is applied)', AAM_KEY)
                                ],
                                'custom_message' => [
                                    'title' => __('Show customized message (plain text or HTML)', AAM_KEY)
                                ],
                                'page_redirect' => [
                                    'title' => __('Redirected to existing page (select from the drop-down)', AAM_KEY)
                                ],
                                'url_redirect' => [
                                    'title' => __('Redirected to a different URL (valid URL, starting with http or https is required)', AAM_KEY)
                                ],
                                'trigger_callback' => [
                                    'title' => __('Trigger PHP callback function (valid HP callback is required)', AAM_KEY)
                                ],
                            ]);

                            if ($access_level->is_visitor()) {
                                $restriction_types['login_redirect'] = [
                                    'title' => __('Redirect to the login page (after login, user will be redirected back to the restricted page)', AAM_KEY)
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
                            <label><?php echo __('Customized Message', AAM_KEY); ?></label>
                            <textarea
                                class="form-control form-clearable"
                                rows="3"
                                id="url_access_custom_message"
                                placeholder="<?php echo __('Enter message...', AAM_KEY); ?>">
                            </textarea>
                        </div>

                        <div class="form-group aam-uri-access-action" id="url_access_page_redirect_attrs" style="display: none;">
                            <label><?php echo __('Existing Page', AAM_KEY); ?></label>
                            <?php
                                wp_dropdown_pages(array(
                                    'depth'            => 99,
                                    'echo'             => 1,
                                    'id'               => 'url_access_page_redirect',
                                    'class'            => 'form-control form-clearable',
                                    'show_option_none' => __('-- Select Page --', AAM_KEY)
                                ));
                            ?>
                        </div>

                        <div class="form-group aam-uri-access-action" id="url_access_url_redirect_attrs" style="display: none;">
                            <label><?php echo __('The Valid Redirect URL', AAM_KEY); ?></label>
                            <input
                                type="text"
                                class="form-control form-clearable"
                                placeholder="https://"
                                id="url_access_url_redirect"
                            />
                        </div>

                        <div class="form-group aam-uri-access-action" id="url_access_http_status_code" style="display: none;">
                            <label><?php echo __('HTTP Redirect Code', AAM_KEY); ?></label>
                            <select class="form-control form-clearable" id="url_access_http_redirect_code">
                                <option value=""><?php echo __('HTTP Code (Default 307)', AAM_KEY); ?></option>
                                <option value="301"><?php echo __('301 - Moved Permanently', AAM_KEY); ?></option>
                                <option value="302"><?php echo __('302 - Found', AAM_KEY); ?></option>
                                <option value="303"><?php echo __('303 - See Other', AAM_KEY); ?></option>
                                <option value="307"><?php echo __('307 - Temporary Redirect', AAM_KEY); ?></option>
                            </select>
                        </div>

                        <div class="form-group aam-uri-access-action" id="url_access_callback_attrs" style="display: none;">
                            <label><?php echo __('PHP Callback Function', AAM_KEY); ?></label>
                            <input
                                type="text"
                                class="form-control form-clearable"
                                placeholder="Enter valid callback"
                                id="url_access_trigger_callback"
                            />
                        </div>

                        <?php do_action('aam_ui_url_access_restriction_modal_action'); ?>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-success"
                            id="url_save_btn"
                        ><?php echo __('Save', AAM_KEY); ?></button>
                        <button
                            type="button"
                            class="btn btn-default"
                            data-dismiss="modal"
                        ><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="uri-delete-model" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete URL Rule', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <p class="aam-notification">
                                <?php echo __('You are about to delete the URL Rule. Please confirm!', AAM_KEY); ?>
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="uri-delete-btn"><?php echo __('Delete', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }