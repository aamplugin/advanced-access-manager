<?php
    /**
     * @since 6.5.0 Fixed https://github.com/aamplugin/advanced-access-manager/issues/107
     * @since 6.2.0 Added "Hidden" modal for more granular access controls
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.5.0
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-overwrite<?php echo $params->object->isOverwritten() ? '' : ' hidden'; ?>" id="post-term-overwritten">
        <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
        <span><a href="#" id="content-reset" data-type="post" data-id="<?php echo $params->object->getId(); ?>" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a></span>
    </div>

    <input type="hidden" value="<?php echo $params->type; ?>" id="content-object-type" />
    <input type="hidden" value="<?php echo $params->id; ?>" id="content-object-id" />

    <?php if ($params->object->post_type === 'attachment') { ?>
        <div class="alert alert-warning aam-outer-bottom-xxs">
            <?php echo sprintf(__('To fully protect your media library files, please refer to the %sHow to manage access to WordPress media library%s article.', AAM_KEY), '<a href="https://aamplugin.com/article/how-to-manage-access-to-the-wordpress-media-library" target="_blank">', '</a>');  ?>
        </div>
    <?php } ?>

    <table class="table table-striped table-bordered">
        <tbody>
            <?php foreach ($params->options as $option => $data) { ?>
                <tr>
                    <?php $id = 'advanced-' . $option; ?>
                    <td width="90%">
                        <strong class="aam-block aam-highlight text-uppercase"><?php echo $data['title']; ?></strong>
                        <?php if (!empty($data['sub'])) { ?>
                            <small class="aam-small-highlighted">
                                <?php echo $data['sub']; ?>: <b class="option-preview"><?php echo (isset($params->previews[$option]) ? $params->previews[$option] : '...') ?></b>
                                <a href="#<?php echo $data['modal']; ?>" data-toggle="modal" class="advanced-post-option" data-ref="<?php echo $option; ?>" id="<?php echo $id; ?>">
                                    <?php echo __('change', AAM_KEY); ?>
                                </a>
                            </small>
                        <?php } ?>
                        <p class="aam-hint">
                            <?php echo str_replace(
                                array('{postType}'),
                                array(get_post_type_labels($params->postType)->singular_name),
                                $data['description']
                            ); ?>
                        </p>
                    </td>
                    <td>
                        <div class="aam-row-actions">
                            <i class="aam-row-action <?php echo ($params->object->is($option) ? 'text-danger icon-check' : 'text-muted icon-check-empty'); ?>" data-property="<?php echo $option; ?>" <?php echo (!empty($data['sub']) ? 'data-trigger="' . $id . '"' : ''); ?>></i>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="modal fade" data-backdrop="false" id="modal-hidden" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Hidden Areas', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td>
                                    <span class='aam-setting-title'><?php echo __('Frontend', AAM_KEY); ?></span>
                                    <p class="aam-setting-description">
                                        <?php echo __('Hide post on the frontend site of the website'); ?>
                                    </p>
                                </td>
                                <td class="text-center">
                                    <input data-toggle="toggle" name="hidden.frontend" id="hidden-frontend" type="checkbox" <?php echo ($params->object->get('hidden.frontend') ? 'checked' : ''); ?> data-on="<?php echo __('Hidden', AAM_KEY); ?>" data-off="<?php echo __('Visible', AAM_KEY); ?>" data-size="small" data-onstyle="danger" data-offstyle="success" />
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class='aam-setting-title'><?php echo __('Backend', AAM_KEY); ?></span>
                                    <p class="aam-setting-description">
                                        <?php echo __('Hide post on the backend site of the website'); ?>
                                    </p>
                                </td>
                                <td class="text-center">
                                    <input data-toggle="toggle" name="hidden.backend" id="hidden-backend" type="checkbox" <?php echo ($params->object->get('hidden.backend') ? 'checked' : ''); ?> data-on="<?php echo __('Hidden', AAM_KEY); ?>" data-off="<?php echo __('Visible', AAM_KEY); ?>" data-size="small" data-onstyle="danger" data-offstyle="success" />
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class='aam-setting-title'><?php echo __('RESTful API', AAM_KEY); ?></span>
                                    <p class="aam-setting-description">
                                        <?php echo __('Hide post in the RESTful API response'); ?>
                                    </p>
                                </td>
                                <td class="text-center">
                                    <input data-toggle="toggle" name="hidden.api" id="hidden-api" type="checkbox" <?php echo ($params->object->get('hidden.api') ? 'checked' : ''); ?> data-on="<?php echo __('Hidden', AAM_KEY); ?>" data-off="<?php echo __('Visible', AAM_KEY); ?>" data-size="small" data-onstyle="danger" data-offstyle="success" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-save" id="save-hidden-btn"><?php echo __('Save', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" data-backdrop="false" id="modal-teaser" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Teaser Message', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo __('Plain text or valid HTML', AAM_KEY); ?></label>
                        <textarea class="form-control" placeholder="<?php echo __('Enter your teaser message...', AAM_KEY); ?>" rows="5" id="aam-teaser-message"><?php echo $params->object->get('teaser.message'); ?></textarea>
                        <span class="hint text-muted"><?php echo AAM_Backend_View_Helper::preparePhrase('Use [&#91;excerpt&#93;] shortcode to insert post excerpt to the teaser message.', 'strong'); ?></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-save" id="save-teaser-btn"><?php echo __('Save', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" data-backdrop="false" id="modal-limited" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Define Access Limit', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo __('Access Limit Threshold', AAM_KEY); ?></label>
                        <input type="number" class="form-control" placeholder="<?php echo __('Enter digital number', AAM_KEY); ?>" id="aam-access-threshold" value="<?php echo $params->object->get('limited.threshold'); ?>" />
                    </div>
                    <?php if ($params->subject->isUser()) { ?>
                        <?php $counter   = intval(get_user_option(sprintf(AAM_Service_Content::POST_COUNTER_DB_OPTION, $params->object->ID), $params->subject->getId())); ?>
                        <?php $remaining = $params->object->get('limited.threshold') - $counter; ?>

                        <div class="form-group">
                            <p class="alert alert-info"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('The user can access content [%d] times.', 'b'), $remaining >= 0 ? $remaining : 0); ?></p>
                        </div>
                    <?php } ?>
                </div>
                <div class="modal-footer">
                    <?php if (!empty($counter)) { ?><button type="button" class="btn btn-warning btn-save" id="reset-limited-btn"><?php echo __('Reset', AAM_KEY); ?></button><?php } ?>
                    <button type="button" class="btn btn-success btn-save" id="save-limited-btn"><?php echo __('Save', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" data-backdrop="false" id="modal-redirect" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Access Redirect', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <p class="aam-info"><?php echo __('Use REDIRECT option only if you want to redirect user to a different location either temporary or permanently. Do not use it as a way to protect access to avoid inconsistent user experience.'); ?></p>
                    <div class="form-group aam-outer-top-xs">
                        <?php $type = $params->object->get('redirected.type'); ?>
                        <div class="radio">
                            <input type="radio" id="post-redirect-page" name="post-redirect-type" class="post-redirect-type" data-action="#post-redirect-page-action" value="page" <?php echo ($type === 'page' ? 'checked' : ''); ?> />
                            <label for="post-redirect-page"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to existing page [(select from the drop-down)]', 'small'); ?></label>
                        </div>
                        <div class="radio">
                            <input type="radio" id="post-redirect-url" name="post-redirect-type" class="post-redirect-type" data-action="#post-redirect-url-action" value="url" <?php echo ($type === 'url' ? 'checked' : ''); ?> />
                            <label for="post-redirect-url"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirected to the URL [(enter full URL starting from http or https)]', 'small'); ?></label>
                        </div>
                        <?php if ($params->subject->isVisitor()) { ?>
                            <div class="radio">
                                <input type="radio" id="post-redirect-login" name="post-redirect-type" class="post-redirect-type" value="login" data-action="none" <?php echo ($type === 'login' ? 'checked' : ''); ?> />
                                <label for="post-redirect-login"><?php echo AAM_Backend_View_Helper::preparePhrase('Redirect to the login page [(after login, user will be redirected back to the restricted page)]', 'small'); ?></label>
                            </div>
                        <?php } ?>
                        <div class="radio">
                            <input type="radio" id="post-redirect-callback" name="post-redirect-type" class="post-redirect-type" data-action="#post-redirect-callback-action" value="callback" <?php echo ($type === 'callback' ? 'checked' : ''); ?> />
                            <label for="post-redirect-callback"><?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Trigger PHP callback function [(valid %sPHP callback%s is required)]', 'small'), '<a href="https://php.net/manual/en/language.types.callable.php" target="_blank">', '</a>'); ?></label>
                        </div>

                        <div class="form-group post-redirect-value" id="post-redirect-page-value-container" style="display: <?php echo ($type === 'page' ? 'block' : 'none'); ?>;">
                            <label><?php echo __('Existing Page', AAM_KEY); ?></label>
                            <?php
                            wp_dropdown_pages(array(
                                'depth'            => 99,
                                'echo'             => 1,
                                'selected'         => ($type === 'page' ? $params->object->get('redirected.destination') : null),
                                'id'               => 'post-redirect-page-value',
                                'class'            => 'form-control',
                                'show_option_none' => __('-- Select Page --', AAM_KEY)
                            ));
                            ?>
                        </div>

                        <div class="form-group post-redirect-value" id="post-redirect-url-value-container" style="display: <?php echo ($type === 'url' ? 'block' : 'none'); ?>;">
                            <label><?php echo __('The URL', AAM_KEY); ?></label>
                            <input type="text" class="form-control" id="post-redirect-url-value" placeholder="https://" value="<?php echo ($type === 'url' ? $params->object->get('redirected.destination') : null); ?>" />
                        </div>

                        <div class="form-group post-redirect-value" id="post-redirect-callback-value-container" style="display: <?php echo ($type === 'callback' ? 'block' : 'none'); ?>;">
                            <label><?php echo __('PHP Callback Function', AAM_KEY); ?></label>
                            <input type="text" class="form-control" id="post-redirect-callback-value" placeholder="<?php echo __('Enter valid callback', AAM_KEY); ?>" value="<?php echo ($type === 'callback' ? $params->object->get('redirected.destination') : null); ?>" />
                        </div>

                        <div class="form-group post-redirect-value" id="post-redirect-code-value-container" style="display: <?php echo (!empty($type) ? 'block' : 'none'); ?>;">
                            <label><?php echo __('HTTP Redirect Code', AAM_KEY); ?></label>
                            <select class="form-control" id="post-redirect-code-value">
                                <?php foreach ($params->httpCodes as $code => $label) { ?>
                                    <option value="<?php echo $code; ?>" <?php echo ((string) $code === $params->object->get('redirected.httpCode') ? 'selected' : ''); ?>><?php echo $label; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-save" id="save-redirect-btn"><?php echo __('Save', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" data-backdrop="false" id="modal-password" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Password Protected', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo __('Password', AAM_KEY); ?></label>
                        <input type="text" class="form-control" placeholder="<?php echo __('Enter Password', AAM_KEY); ?>" id="aam-access-password" value="<?php echo $params->object->get('protected.password'); ?>" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-save" id="save-password-btn"><?php echo __('Save', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" data-backdrop="false" id="modal-cease" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo __('Expiration Date/Time', AAM_KEY); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <div id="post-expiration-datapicker"></div>
                        <?php $ceased = $params->object->get('ceased.after'); ?>
                        <input type="hidden" id="aam-expire-datetime" value="<?php echo ($ceased ? $ceased : strtotime('tomorrow')); ?>" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-save" id="save-ceased-btn"><?php echo __('Save', AAM_KEY); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                </div>
            </div>
        </div>
    </div>

    <?php do_action('aam_post_access_form_action', $params); ?>
<?php }