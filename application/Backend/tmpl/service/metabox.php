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
    <div class="aam-feature" id="metabox-content">
        <?php if (AAM_Framework_Manager::configs()->get_config('core.settings.tips', true)) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access to WordPress metaboxes on post-edit screens or manage access to widgets on the backend dashboard screen and the frontend. This service does not define access controls to Gutenberg blocks. The premium %sComplete Package%s also allows defining default visibility for metaboxes and widgets for each screen.'), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="aam-feature-top-actions text-right">
            <a href="#" class="btn btn-xs btn-primary" id="refresh-metabox-list"><i class="icon-arrows-cw"></i> <?php echo __('Refresh', AAM_KEY); ?></a>
            <a href="#init-url-modal" class="btn btn-xs btn-primary" data-toggle="modal"><i class="icon-link"></i> <?php echo __('Init URL', AAM_KEY); ?></a>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-metabox-overwrite" style="display: <?php echo ($this->isOverwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="metabox-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <?php
            global $wp_post_types;

            $first      = false;
            $components = AAM_Framework_Manager::components(array(
                'subject' => AAM_Backend_Subject::getInstance()->getSubject()
            ))->get_item_list();

            // Group all the components by screen
            $grouped = array();
            foreach($components as $component) {
                $screen = $component['screen_id'];

                if (!isset($grouped[$screen])) {
                    $grouped[$screen] = array();
                }

                array_push($grouped[$screen], $component);
            }
        ?>

        <?php if (!empty($components)) { ?>
            <div class="panel-group" id="metabox-list" role="tablist">
                <?php foreach ($grouped as $screen_id => $components) { ?>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="group-<?php echo esc_js($screen_id); ?>-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#metabox-list" href="#group-<?php echo esc_js($screen_id); ?>" aria-controls="group-<?php echo esc_js($screen_id); ?>" <?php if (!$first) { echo 'aria-expanded="true"'; } ?>>
                                    <?php
                                        switch ($screen_id) {
                                            case 'dashboard':
                                                echo __('Dashboard Widgets', AAM_KEY);
                                                break;

                                            case 'frontend':
                                                echo AAM_Backend_View_Helper::preparePhrase('Frontend Widgets [(including Appearance->Widgets)]', 'small');
                                                break;

                                            default:
                                                echo $wp_post_types[$screen_id]->labels->name;
                                                break;
                                        }
                                    ?>
                                </a>
                            </h4>
                        </div>
                        <div
                            id="group-<?php echo esc_js($screen_id); ?>"
                            class="panel-collapse collapse<?php if (!$first) { echo ' in'; $first = true; } ?>"
                            role="tabpanel"
                            aria-labelledby="group-<?php echo esc_js($screen_id); ?>-heading"
                        >
                            <div class="panel-body">
                                <div class="row">
                                    <?php foreach ($components as $component) { ?>
                                        <div class="col-xs-12 col-md-6 aam-submenu-item">
                                            <div class="aam-menu-details">
                                                <?php echo esc_js($component['name']); ?>
                                                <small><a
                                                    href="#metabox-details-modal"
                                                    data-toggle="modal"
                                                    data-title="<?php echo esc_attr($component['name']); ?>"
                                                    data-screen="<?php echo esc_attr($screen_id); ?>"
                                                    data-id="<?php echo esc_attr(strtolower($screen_id . '|' . $component['slug'])); ?>"
                                                    class="aam-metabox-item"><?php echo __('more details', AAM_KEY); ?>
                                                </a></small>
                                            </div>

                                            <?php if ($component['is_hidden']) { ?>
                                                <i class="aam-accordion-action icon-lock text-danger" id="metabox-<?php echo esc_js($component['id']); ?>" data-metabox="<?php echo esc_attr($component['id']); ?>"></i>
                                            <?php } else { ?>
                                                <i class="aam-accordion-action icon-lock-open text-success" id="metabox-<?php echo esc_js($component['id']); ?>" data-metabox="<?php echo esc_js($component['id']); ?>"></i>
                                            <?php } ?>

                                            <label for="metabox-<?php echo esc_js($component['id']); ?>" data-toggle="tooltip" title="<?php echo ($component['is_hidden'] ?  __('Uncheck to show', AAM_KEY) : __('Check to hide', AAM_KEY)); ?>"></label>
                                        </div>
                                    <?php } ?>
                                </div>

                                <?php echo apply_filters(
                                    'aam_component_screen_mode_panel_filter',
                                    '',
                                    $screen_id,
                                    AAM_Backend_Subject::getInstance()->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE)
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
                        <?php echo __('The list is not initialized. Click Refresh button above.', AAM_KEY); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

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
                        <h4 class="modal-title"><?php echo __('Metabox/Widget Details', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped table-bordered">
                            <tbody>
                                <tr>
                                    <th width="20%"><?php echo __('Title', AAM_KEY); ?></th>
                                    <td id="metabox-title"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Screen ID', AAM_KEY); ?></th>
                                    <td id="metabox-screen-id"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Internal ID', AAM_KEY); ?></th>
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