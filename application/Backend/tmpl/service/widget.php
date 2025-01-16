<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::get_instance();
        $service      = $access_level->widgets();
    ?>
    <div class="aam-feature" id="widget-content">
        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo AAM_Backend_View::replace_aam_urls(
                            __('Manage access to WordPress widgets on both admin dashboard and frontend. The premium %sadd-on%s also allows defining default visibility for all widgets.', AAM_KEY),
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
                id="refresh_widget_list"
            ><i class="icon-arrows-cw"></i> <?php echo __('Refresh', AAM_KEY); ?></a>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-widget-overwrite" style="display: <?php echo ($service->is_customized() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="widget_reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a></span>
                </div>
            </div>
        </div>

        <?php
            $first = false;
            $list  = AAM_Service_Widgets::get_instance()->get_widget_list($service);

            // Group all the components by screen
            $grouped = [
                'dashboard' => [],
                'frontend'  => []
            ];

            foreach($list as $item) {
                $screen = $item['area'];

                array_push($grouped[$screen], $item);
            }
        ?>

        <?php if (!empty($list)) { ?>
            <div class="panel-group" id="widget-list" role="tablist">
                <?php foreach ($grouped as $area => $widgets) { ?>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="group-<?php echo esc_js($area); ?>-heading">
                            <h4 class="panel-title">
                                <a
                                    role="button"
                                    data-toggle="collapse"
                                    data-parent="#widget-list"
                                    href="#group-<?php echo esc_js($area); ?>"
                                    aria-controls="group-<?php echo esc_js($area); ?>"
                                    <?php if (!$first) { echo 'aria-expanded="true"'; } ?>
                                >
                                    <?php
                                        switch ($area) {
                                            case 'dashboard':
                                                echo __('Dashboard Widgets', AAM_KEY);
                                                break;

                                            case 'frontend':
                                                echo AAM_Backend_View_Helper::preparePhrase('Frontend Widgets [(including Appearance->Widgets)]', 'small');
                                                break;

                                            default:
                                                break;
                                        }
                                    ?>
                                </a>
                            </h4>
                        </div>
                        <div
                            id="group-<?php echo esc_js($area); ?>"
                            class="panel-collapse collapse<?php if (!$first) { echo ' in'; $first = true; } ?>"
                            role="tabpanel"
                            aria-labelledby="group-<?php echo esc_js($area); ?>-heading"
                        >
                            <div class="panel-body">
                                <div class="row">
                                    <?php foreach ($widgets as $widget) { ?>
                                        <div class="col-xs-12 col-md-6 aam-submenu-item">
                                            <div class="aam-menu-details">
                                                <?php echo esc_js($widget['title']); ?>
                                                <small><a
                                                    href="#widget_details_modal"
                                                    data-toggle="modal"
                                                    data-title="<?php echo esc_attr($widget['title']); ?>"
                                                    data-screen="<?php echo esc_attr($area); ?>"
                                                    data-id="<?php echo esc_attr($widget['slug']); ?>"
                                                    class="aam-widget-item"><?php echo __('more details', AAM_KEY); ?>
                                                </a></small>
                                            </div>

                                            <?php if ($widget['is_restricted']) { ?>
                                                <i
                                                    class="aam-accordion-action icon-lock text-danger"
                                                    id="widget_<?php echo esc_attr($widget['slug']); ?>"
                                                    data-widget="<?php echo esc_attr($widget['slug']); ?>"
                                                ></i>
                                            <?php } else { ?>
                                                <i
                                                    class="aam-accordion-action icon-lock-open text-success"
                                                    id="widget_<?php echo esc_attr($widget['slug']); ?>"
                                                    data-widget="<?php echo esc_attr($widget['slug']); ?>"
                                                ></i>
                                            <?php } ?>

                                            <label
                                                for="widget_<?php echo esc_attr($widget['slug']); ?>"
                                                data-toggle="tooltip"
                                                title="<?php echo ($widget['is_restricted'] ?  __('Uncheck to show', AAM_KEY) : __('Check to hide', AAM_KEY)); ?>"
                                            ></label>
                                        </div>
                                    <?php } ?>
                                </div>

                                <?php echo apply_filters(
                                    'aam_ui_widgets_screen_mode_filter',
                                    '',
                                    AAM_Backend_AccessLevel::get_instance(),
                                    $area
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

        <div class="hidden" id="aam_widget_screen_list"><?php echo json_encode($this->get_screen_urls()); ?></div>

        <div class="modal fade" id="widget_details_modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Widget Details', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped table-bordered">
                            <tbody>
                                <tr>
                                    <th width="20%"><?php echo __('Title', AAM_KEY); ?></th>
                                    <td id="widget_title"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Area', AAM_KEY); ?></th>
                                    <td id="widget_screen_id"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Widget Slug', AAM_KEY); ?></th>
                                    <td id="widget_id"></td>
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