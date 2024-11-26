<?php
/**
 * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
 * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/298
 *               https://github.com/aamplugin/advanced-access-manager/issues/302
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/289
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.33
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::getInstance();
        $service      = $access_level->admin_toolbar();
    ?>
    <div class="aam-feature" id="toolbar-content">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo AAM_Backend_View_Helper::preparePhrase('[Note!] Toolbar service does not restrict direct access to linked pages. It is used only to remove unnecessary items from the top toolbar. Use the [Backend Menu] service to manage direct access to backend pages or customize it with capabilities.', 'b', 'b'); ?>
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div
                    class="aam-overwrite"
                    id="aam_toolbar_overwrite"
                    style="display: <?php echo ($service->is_customized() ? 'block' : 'none'); ?>"
                >
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="toolbar_reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <?php echo apply_filters('aam_ui_admin_toolbar_mode_panel_filter', '', $service); ?>

        <div class="panel-group" id="toolbar_list" role="tablist" aria-multiselectable="true">
            <?php
            $first   = false;
            $toolbar = $service->get_item_list();

            if (!empty($toolbar)) { ?>
                <?php foreach ($toolbar as $branch) { ?>
                    <div class="panel panel-default">
                        <div
                            class="panel-heading"
                            role="tab"
                            id="toolbar_<?php echo esc_attr($branch['id']); ?>_heading"
                        >
                            <h4 class="panel-title">
                                <a
                                    role="button"
                                    data-toggle="collapse"
                                    data-parent="#toolbar_list"
                                    href="#toolbar_<?php echo esc_attr($branch['id']); ?>"
                                    aria-controls="toolbar_<?php echo esc_attr($branch['id']); ?>"
                                    <?php if (!$first) { echo 'aria-expanded="true"'; } ?>
                                >
                                    <?php echo esc_js($branch['name']); ?> <small class="aam-menu-capability"><?php echo esc_js($branch['uri']); ?></small>
                                </a>
                                <?php if ($branch['is_hidden']) { ?>
                                    <i class="aam-panel-title-icon icon-lock text-danger"></i>
                                <?php } ?>
                            </h4>
                        </div>

                        <div
                            id="toolbar_<?php echo esc_attr($branch['id']); ?>"
                            class="panel-collapse collapse<?php if (!$first) { echo ' in'; $first = true; } ?>"
                            role="tabpanel"
                            aria-labelledby="toolbar_<?php echo esc_attr($branch['id']); ?>_heading"
                        >
                            <div class="panel-body">
                                <div class="row aam-inner-tab">
                                    <div class="col-xs-12 text-center">
                                        <small class="aam-menu-capability"><?php echo __('Item ID:', AAM_KEY); ?> <b><?php echo esc_js($branch['id']); ?></b></small>
                                    </div>
                                </div>

                                <hr class="aam-divider" />

                                <?php if (count($branch['children'])) { ?>
                                    <div class="row aam-inner-tab aam-menu-expended-list">
                                        <?php foreach ($branch['children'] as $child) { ?>
                                            <div class="col-xs-12 col-md-6 aam-submenu-item">
                                                <div class="aam-menu-details">
                                                    <?php echo esc_js($child['name']); ?>
                                                    <small>
                                                        <a
                                                            href="#toolbar-details-modal"
                                                            data-toggle="modal"
                                                            data-uri="<?php echo esc_attr($child['uri']); ?>"
                                                            data-id="<?php echo esc_attr($child['id']); ?>"
                                                            data-name="<?php echo esc_attr($child['name']); ?>"
                                                            class="aam-toolbar-item"
                                                        ><?php echo __('more details', AAM_KEY); ?></a>
                                                    </small>
                                                </div>

                                                <?php if ($child['is_hidden']) { ?>
                                                    <i
                                                        class="aam-accordion-action icon-lock text-danger"
                                                        data-toolbar="<?php echo esc_attr($child['id']); ?>"
                                                    ></i>
                                                <?php } else { ?>
                                                    <i
                                                        class="aam-accordion-action icon-lock-open text-success"
                                                        data-toolbar="<?php echo esc_attr($child['id']); ?>"
                                                    ></i>
                                                <?php } ?>

                                                <label
                                                    for="toolbar_<?php echo esc_attr($child['id']); ?>"
                                                    data-toggle="tooltip"
                                                    title="<?php echo ($child['is_hidden'] ?  __('Uncheck to allow', AAM_KEY) : __('Check to restrict', AAM_KEY)); ?>"
                                                ></label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <hr class="aam-divider" />
                                <?php } ?>

                                <div class="row aam-margin-top-xs">
                                    <div class="col-xs-10 col-md-6 col-xs-offset-1 col-md-offset-3">
                                        <?php if ($branch['is_hidden']) { ?>
                                            <a
                                                href="#"
                                                class="btn btn-primary btn-sm btn-block aam-restrict-toolbar"
                                                data-toolbar="<?php echo esc_attr($branch['id']); ?>"
                                                data-target="#toolbar_<?php echo esc_attr($branch['id']); ?>"
                                            >
                                                <i class="icon-lock-open"></i> <?php echo __('Show Menu Branch', AAM_KEY); ?>
                                            </a>
                                        <?php } else { ?>
                                            <a
                                                href="#"
                                                class="btn btn-danger btn-sm btn-block aam-restrict-toolbar"
                                                data-toolbar="<?php echo esc_attr($branch['id']); ?>"
                                                data-target="#toolbar_<?php echo esc_attr($branch['id']); ?>"
                                            >
                                                <i class="icon-lock"></i> <?php echo __('Hide Menu Branch', AAM_KEY); ?>
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="toolbar-details-modal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title"><?php echo __('Item Details', AAM_KEY); ?></h4>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-striped table-bordered">
                                        <tbody>
                                            <tr>
                                                <th width="35%"><?php echo __('Name', AAM_KEY); ?></th>
                                                <td id="toolbar-item-name"></td>
                                            </tr>
                                            <tr>
                                                <th width="35%"><?php echo __('Admin Page URL', AAM_KEY); ?></th>
                                                <td id="toolbar-item-uri"></td>
                                            </tr>
                                            <tr>
                                                <th width="35%"><?php echo __('ID', AAM_KEY); ?></th>
                                                <td id="toolbar-item-id"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="modal-footer">
                                    <button
                                        type="button"
                                        class="btn btn-default"
                                        data-dismiss="modal"
                                    ><?php echo __('Close', AAM_KEY); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="row">
                    <div class="col-xs-12">
                        <p class="aam-info">
                            <?php echo __('The list of admin toolbar items is not initialized. Reload the page.', AAM_KEY); ?>
                        </p>
                    </div>
                </div>
            <?php }
            ?>
        </div>
    </div>
<?php }