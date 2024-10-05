<?php
/**
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
 * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/301
 *               https://github.com/aamplugin/advanced-access-manager/issues/298
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/290
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.34
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::getInstance();
        $service      = $access_level->metaboxes();
    ?>
    <div class="aam-feature" id="metabox-content">
        <?php if (AAM_Framework_Manager::configs()->get_config('core.settings.ui.tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo AAM_Backend_View::replace_aam_urls(
                            __('Manage access to WordPress classical metaboxes on post-edit screens. This service does not define access controls to Gutenberg blocks. The premium %sadd-on%s also allows defining default visibility for metaboxes.', AAM_KEY),
                            '/premium'
                        ); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="aam-feature-top-actions text-right">
            <a
                href="#"
                class="btn btn-xs btn-primary"
                id="refresh-metabox-list"
            ><i class="icon-arrows-cw"></i> <?php echo __('Refresh', AAM_KEY); ?></a>
            <a
                href="#init-url-modal"
                class="btn btn-xs btn-primary"
                data-toggle="modal"><i class="icon-link"
            ></i> <?php echo __('Init URL', AAM_KEY); ?></a>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-metabox-overwrite" style="display: <?php echo ($service->get_resource()->is_overwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="metabox-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <?php
            global $wp_post_types;

            $first = false;
            $list  = $service->get_item_list();

            // Group all the metaboxes by post type
            $grouped = array();
            foreach($list as $item) {
                $post_type = $item['post_type'];

                if (!isset($grouped[$post_type])) {
                    $grouped[$post_type] = array();
                }

                array_push($grouped[$post_type], $item);
            }
        ?>

        <?php if (!empty($list)) { ?>
            <div class="panel-group" id="metabox-list" role="tablist">
                <?php foreach ($grouped as $post_type => $metaboxes) { ?>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="group_<?php echo esc_attr($post_type); ?>_heading">
                            <h4 class="panel-title">
                                <a
                                    role="button"
                                    data-toggle="collapse"
                                    data-parent="#metabox-list"
                                    href="#group_<?php echo esc_attr($post_type); ?>"
                                    aria-controls="group_<?php echo esc_attr($post_type); ?>"
                                    <?php if (!$first) { echo 'aria-expanded="true"'; } ?>
                                >
                                    <?php echo $wp_post_types[$post_type]->labels->name; ?>
                                </a>
                            </h4>
                        </div>
                        <div
                            id="group_<?php echo esc_attr($post_type); ?>"
                            class="panel-collapse collapse<?php if (!$first) { echo ' in'; $first = true; } ?>"
                            role="tabpanel"
                            aria-labelledby="group_<?php echo esc_js($post_type); ?>_heading"
                        >
                            <div class="panel-body">
                                <div class="row">
                                    <?php foreach ($metaboxes as $metabox) { ?>
                                        <div class="col-xs-12 col-md-6 aam-submenu-item">
                                            <div class="aam-menu-details">
                                                <?php echo esc_js($metabox['title']); ?>
                                                <small><a
                                                    href="#metabox-details-modal"
                                                    data-toggle="modal"
                                                    data-title="<?php echo esc_attr($metabox['title']); ?>"
                                                    data-screen="<?php echo esc_attr($post_type); ?>"
                                                    data-id="<?php echo esc_attr($metabox['slug']); ?>"
                                                    class="aam-metabox-item"><?php echo __('more details', AAM_KEY); ?>
                                                </a></small>
                                            </div>

                                            <?php if ($metabox['is_hidden']) { ?>
                                                <i
                                                    class="aam-accordion-action icon-lock text-danger"
                                                    id="metabox_<?php echo esc_attr($metabox['slug']); ?>"
                                                    data-metabox="<?php echo esc_attr($metabox['slug']); ?>"
                                                ></i>
                                            <?php } else { ?>
                                                <i
                                                    class="aam-accordion-action icon-lock-open text-success"
                                                    id="metabox_<?php echo esc_attr($metabox['slug']); ?>"
                                                    data-metabox="<?php echo esc_attr($metabox['slug']); ?>"
                                                ></i>
                                            <?php } ?>

                                            <label
                                                for="metabox_<?php echo esc_attr($metabox['slug']); ?>"
                                                data-toggle="tooltip"
                                                title="<?php echo ($metabox['is_hidden'] ?  __('Uncheck to show', AAM_KEY) : __('Check to hide', AAM_KEY)); ?>"
                                            ></label>
                                        </div>
                                    <?php } ?>
                                </div>

                                <?php echo apply_filters(
                                    'aam_ui_metaboxes_post_type_mode_filter',
                                    '',
                                    AAM_Backend_AccessLevel::getInstance()->metaboxes(),
                                    $post_type
                                ); ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="row">
                <div class="col-xs-12 text-center">
                    <p class="alert alert-info text-larger">
                        <?php echo __('The list is not initialized. Select the "Refresh" button above.', AAM_KEY); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="hidden" id="aam_screen_list"><?php echo json_encode($this->get_screen_urls()); ?></div>

        <div class="modal fade" id="init-url-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Initialize URL', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="aam-info">
                            <?php echo __('Some metaboxes are "conditional" and appear on the edit screen when certain conditions are met. For example metabox "Comments" appears only for existing page and not for new page. If you do not see a desired metabox, try to copy & paste the full URL to the backend page where that metabox appears.'); ?>
                        </p>
                        <div class="form-group">
                            <label><?php echo __('Backend page URL', AAM_KEY); ?></label>
                            <input type="text" class="form-control" id="init-url" placeholder="<?php echo __('Insert valid URL', AAM_KEY); ?>" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="init-url-btn"><?php echo __('Initialize', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="metabox-details-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Metabox Details', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped table-bordered">
                            <tbody>
                                <tr>
                                    <th width="20%"><?php echo __('Title', AAM_KEY); ?></th>
                                    <td id="metabox-title"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Post Type', AAM_KEY); ?></th>
                                    <td id="metabox-screen-id"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Metabox ID', AAM_KEY); ?></th>
                                    <td id="metabox-id"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }