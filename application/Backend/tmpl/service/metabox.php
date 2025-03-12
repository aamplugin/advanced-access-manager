<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php $service = AAM_Backend_AccessLevel::get_instance()->metaboxes(); ?>
    <div class="aam-feature" id="metabox-content">
        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo AAM_Backend_View::replace_aam_urls(
                            __('Manage access to WordPress classical metaboxes on the content-edit screens. This service does not define access controls to Gutenberg blocks. The premium %sadd-on%s also allows defining default visibility for metaboxes.', 'advanced-access-manager'),
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
            ><i class="icon-arrows-cw"></i> <?php echo __('Refresh', 'advanced-access-manager'); ?></a>
            <a
                href="#init-url-modal"
                class="btn btn-xs btn-primary"
                data-toggle="modal"><i class="icon-link"
            ></i> <?php echo __('Init URL', 'advanced-access-manager'); ?></a>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-metabox-overwrite" style="display: <?php echo ($service->is_customized() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', 'advanced-access-manager'); ?></span>
                    <span><a href="#" id="metabox-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', 'advanced-access-manager'); ?></a></span>
                </div>
            </div>
        </div>

        <?php
            global $wp_post_types;

            $first = false;
            $list  = $service->get_items();

            // Group all the metaboxes by post type
            $grouped = [];

            foreach($list as $item) {
                $screen_id = $item['screen_id'];

                if (!isset($grouped[$screen_id])) {
                    $grouped[$screen_id] = [];
                }

                array_push($grouped[$screen_id], $item);
            }
        ?>

        <?php if (!empty($list)) { ?>
            <div class="panel-group" id="metabox-list" role="tablist">
                <?php foreach ($grouped as $screen_id => $metaboxes) { ?>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="group_<?php echo esc_attr($screen_id); ?>_heading">
                            <h4 class="panel-title">
                                <a
                                    role="button"
                                    data-toggle="collapse"
                                    data-parent="#metabox-list"
                                    href="#group_<?php echo esc_attr($screen_id); ?>"
                                    aria-controls="group_<?php echo esc_attr($screen_id); ?>"
                                    <?php if (!$first) { echo 'aria-expanded="true"'; } ?>
                                >
                                    <?php echo $wp_post_types[$screen_id]->labels->name; ?>
                                </a>
                            </h4>
                        </div>
                        <div
                            id="group_<?php echo esc_attr($screen_id); ?>"
                            class="panel-collapse collapse<?php if (!$first) { echo ' in'; $first = true; } ?>"
                            role="tabpanel"
                            aria-labelledby="group_<?php echo esc_js($screen_id); ?>_heading"
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
                                                    data-screen="<?php echo esc_attr($screen_id); ?>"
                                                    data-id="<?php echo esc_attr($metabox['slug']); ?>"
                                                    class="aam-metabox-item"><?php echo __('more details', 'advanced-access-manager'); ?>
                                                </a></small>
                                            </div>

                                            <?php if ($metabox['is_restricted']) { ?>
                                                <i
                                                    class="aam-accordion-action icon-lock text-danger"
                                                    id="metabox_<?php echo esc_attr($metabox['slug']); ?>"
                                                    data-metabox="<?php echo esc_attr($metabox['slug']); ?>"
                                                    data-screen="<?php echo esc_attr($metabox['screen_id']); ?>"
                                                ></i>
                                            <?php } else { ?>
                                                <i
                                                    class="aam-accordion-action icon-lock-open text-success"
                                                    id="metabox_<?php echo esc_attr($metabox['slug']); ?>"
                                                    data-metabox="<?php echo esc_attr($metabox['slug']); ?>"
                                                    data-screen="<?php echo esc_attr($metabox['screen_id']); ?>"
                                                ></i>
                                            <?php } ?>

                                            <label
                                                for="metabox_<?php echo esc_attr($metabox['slug']); ?>"
                                                data-toggle="tooltip"
                                                title="<?php echo ($metabox['is_restricted'] ?  __('Uncheck to show', 'advanced-access-manager') : __('Check to hide', 'advanced-access-manager')); ?>"
                                            ></label>
                                        </div>
                                    <?php } ?>
                                </div>

                                <?php echo apply_filters(
                                    'aam_ui_metaboxes_screen_mode_filter',
                                    '',
                                    AAM_Backend_AccessLevel::get_instance(),
                                    $screen_id
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
                        <?php echo __('The list is not initialized. Select the "Refresh" button above.', 'advanced-access-manager'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="hidden" id="aam_screen_list"><?php echo json_encode($this->get_screen_urls()); ?></div>

        <div class="modal fade" id="init-url-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Initialize URL', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="aam-info">
                            <?php echo __('Some metaboxes are "conditional" and appear on the edit screen when certain conditions are met. For example metabox "Comments" appears only for existing page and not for new page. If you do not see a desired metabox, try to copy & paste the full URL to the backend page where that metabox appears.', 'advanced-access-manager'); ?>
                        </p>
                        <div class="form-group">
                            <label><?php echo __('Backend page URL', 'advanced-access-manager'); ?></label>
                            <input type="text" class="form-control" id="init-url" placeholder="<?php echo __('Insert valid URL', 'advanced-access-manager'); ?>" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="init-url-btn"><?php echo __('Initialize', 'advanced-access-manager'); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="metabox-details-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Metabox Details', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped table-bordered">
                            <tbody>
                                <tr>
                                    <th width="20%"><?php echo __('Title', 'advanced-access-manager'); ?></th>
                                    <td id="metabox-title"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Screen ID', 'advanced-access-manager'); ?></th>
                                    <td id="metabox-screen-id"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Slug', 'advanced-access-manager'); ?></th>
                                    <td id="metabox-id"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'advanced-access-manager'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }