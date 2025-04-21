<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::get_instance();
        $service      = $access_level->backend_menu();
    ?>
    <div class="aam-feature" id="admin_menu-content">
        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo sprintf(__('Manage access to the backend menu and its submenu items. With the premium %sadd-on%s, you can also activate "restricted mode," allowing you to whitelist specific menu items. For more details, refer to our official documentation page %shere%s.', 'advanced-access-manager'), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/streamlining-wordpress-backend-menu-access?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-xs-12">
                <div
                    class="aam-overwrite"
                    id="aam-menu-overwrite"
                    style="display: <?php echo ($service->is_customized() ? 'block' : 'none'); ?>"
                >
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', 'advanced-access-manager'); ?></span>
                    <span><a href="#" id="menu-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', 'advanced-access-manager'); ?></a></span>
                </div>
            </div>
        </div>

        <?php echo apply_filters('aam_ui_backend_menu_mode_panel_filter', '', AAM_Backend_AccessLevel::get_instance()->backend_menu()); ?>

        <div class="panel-group" id="admin-menu" role="tablist" aria-multiselectable="true">
            <?php
            $first = false;
            $menu  = AAM::api()->backend_menu(
                AAM_Backend_AccessLevel::get_instance()->get_access_level()
            )->get_items();

            if (!empty($menu)) {
                foreach ($menu as $i => $top_menu_item) {
            ?>
                    <div
                        class="panel panel-default"
                        style="opacity: <?php echo AAM_Backend_AccessLevel::get_instance()->has_cap($top_menu_item['capability']) ? 1 : '0.5'; ?>"
                    >
                        <div class="panel-heading" role="tab" id="menu-<?php echo $i; ?>-heading">
                            <h4 class="panel-title">
                                <a
                                    role="button"
                                    data-toggle="collapse"
                                    data-parent="#admin-menu"
                                    href="#menu-<?php echo $i; ?>"
                                    aria-controls="menu-<?php echo $i; ?>"
                                    <?php if (!$first) { echo 'aria-expanded="true"'; } ?>
                                >
                                    <?php echo esc_js($top_menu_item['name']); ?> <small class="aam-menu-capability"><?php echo esc_js($top_menu_item['capability']); ?></small>
                                </a>
                                <?php if (!empty($top_menu_item['is_restricted'])) { ?>
                                    <i class="aam-panel-title-icon icon-lock text-danger"></i>
                                <?php } elseif (isset($top_menu_item['children']) && array_reduce($top_menu_item['children'], function($s, $q){ return $s + $q['is_restricted']; })) { ?>
                                    <i class="aam-panel-title-icon icon-attention-circled text-warning"></i>
                                <?php } ?>
                            </h4>
                        </div>

                        <div
                            id="menu-<?php echo $i; ?>"
                            class="panel-collapse collapse<?php if (!$first) {  echo ' in'; $first = true; } ?>"
                            role="tabpanel"
                            aria-labelledby="menu-<?php echo $i; ?>-heading"
                        >
                            <div class="panel-body">
                                <?php if ($top_menu_item['slug'] != 'index.php') { ?>
                                    <div class="row aam-inner-tab">
                                        <div class="col-xs-12 text-center">
                                            <small class="aam-menu-capability"><?php echo __('Menu URL:', 'advanced-access-manager'); ?> <b><?php echo urldecode($top_menu_item['path']); ?></b></small>
                                        </div>
                                    </div>
                                    <hr class="aam-divider" />
                                <?php } ?>

                                <?php if (!empty($top_menu_item['children'])) { ?>
                                    <div class="row aam-inner-tab aam-menu-expended-list">
                                        <?php foreach ($top_menu_item['children'] as $j => $child) { ?>
                                            <?php if ($child['slug'] == 'index.php') { ?>
                                                <div class="col-xs-12 col-md-6 aam-submenu-item">
                                                    <div class="aam-menu-details">
                                                        <?php echo esc_js($child['name']); ?>
                                                    </div>
                                                    <a href="#dashboard-lockout-modal" data-toggle="modal"><i class="icon-help-circled"></i></a>
                                                </div>
                                            <?php } else { ?>
                                                <div class="col-xs-12 col-md-6 aam-submenu-item">
                                                    <div class="aam-menu-details">
                                                        <?php echo esc_js($child['name']); ?>
                                                        <small>
                                                            <a
                                                                href="#menu-details-modal"
                                                                data-toggle="modal"
                                                                data-path="<?php echo esc_attr($child['path']); ?>"
                                                                data-cap="<?php echo esc_attr($child['capability']); ?>"
                                                                data-name="<?php echo esc_attr($child['name']); ?>"
                                                                data-slug="<?php echo esc_attr($child['slug']); ?>"
                                                                data-id="<?php echo esc_attr(base64_encode($child['slug'])); ?>"
                                                                class="aam-menu-item"
                                                            ><?php echo __('more details', 'advanced-access-manager'); ?></a>
                                                        </small>
                                                    </div>
                                                    <?php if ($child['is_restricted']) { ?>
                                                        <i
                                                            class="aam-accordion-action icon-lock text-danger"
                                                            id="menu-item-<?php echo $i . $j; ?>"
                                                            data-menu-id="<?php echo esc_attr(base64_encode($child['slug'])); ?>"
                                                        ></i>
                                                    <?php } else { ?>
                                                        <i
                                                            class="aam-accordion-action icon-lock-open text-success"
                                                            id="menu-item-<?php echo $i . $j; ?>"
                                                            data-menu-id="<?php echo esc_attr(base64_encode($child['slug'])); ?>"
                                                        ></i>
                                                    <?php } ?>
                                                    <label
                                                        data-toggle="tooltip"
                                                        title="<?php echo ($child['is_restricted'] ?  __('Uncheck to allow', 'advanced-access-manager') : __('Check to restrict', 'advanced-access-manager')); ?>"
                                                    ></label>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>

                                    <hr class="aam-divider" />
                                <?php } ?>

                                <?php if ($top_menu_item['slug'] != 'index.php') { ?>
                                    <div class="row<?php echo (!empty($top_menu_item['children']) ? ' aam-margin-top-xs' : ''); ?>">
                                        <div class="col-xs-10 col-md-6 col-xs-offset-1 col-md-offset-3">
                                            <?php if ($top_menu_item['is_restricted']) { ?>
                                                <a
                                                    href="#"
                                                    class="btn btn-primary btn-sm btn-block aam-restrict-menu"
                                                    data-menu-id="<?php echo esc_attr(base64_encode($top_menu_item['slug'])); ?>"
                                                    data-target="#menu-<?php echo $i; ?>"
                                                >
                                                    <i class="icon-lock-open"></i> <?php echo __('Show Menu', 'advanced-access-manager'); ?>
                                                </a>
                                            <?php } else { ?>
                                                <a
                                                    href="#"
                                                    class="btn btn-danger btn-sm btn-block aam-restrict-menu"
                                                    data-menu-id="<?php echo esc_attr(base64_encode($top_menu_item['slug'])); ?>"
                                                    data-target="#menu-<?php echo $i; ?>"
                                                >
                                                    <i class="icon-lock"></i> <?php echo __('Restrict Menu', 'advanced-access-manager'); ?>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <p class="aam-info">
                                        <?php echo __('The "Dashboard" menu cannot be restricted because it is the default page all users are redirected to after login.', 'advanced-access-manager'); ?>
                                    </p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php }
            } else { ?>
                <div class="row">
                    <div class="col-xs-12">
                        <p class="aam-notification">
                            <?php echo __('Please try refreshing the page. If the issue persists, the current user might not have the required privileges to access backend menu items. Another common issue could be insufficient database storage on your server, preventing AAM from storing a proper snapshot of your backend menu. If you\'re unable to resolve the problem, feel free to contact us, and we\'ll do our best to assist you.', 'advanced-access-manager'); ?>
                        </p>
                    </div>
                </div>
            <?php } ?>
        </div>

        <div class="modal fade" id="dashboard-lockout-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Dashboard Lockdown', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="text-center alert alert-warning text-larger">
                            <strong><?php echo __('You cannot restrict access to the Dashboard Home page.', 'advanced-access-manager'); ?></strong><br />
                            <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('The [Dashboard Home] is the default page every user redirects to after login. To restrict access to the entire backend, check the %sHow to lock down WordPress backend%s Q&A.', 'b'), '<a href="https://aamportal.com/question/how-to-lockdown-the-entire-wordpress-backend-area?ref=plugin" target="_blank">', '</a>'); ?>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('OK', 'advanced-access-manager'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="menu-details-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', 'advanced-access-manager'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Menu Details', 'advanced-access-manager'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped table-bordered">
                            <tbody>
                                <tr>
                                    <th width="20%"><?php echo __('Name', 'advanced-access-manager'); ?></th>
                                    <td id="menu-item-name"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Slug', 'advanced-access-manager'); ?></th>
                                    <td id="menu-item-slug"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Capability', 'advanced-access-manager'); ?></th>
                                    <td id="menu-item-cap"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Path', 'advanced-access-manager'); ?></th>
                                    <td id="menu-item-path"></td>
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