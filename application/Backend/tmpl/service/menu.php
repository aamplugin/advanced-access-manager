<?php
/**
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
 * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/298
 *               https://github.com/aamplugin/advanced-access-manager/issues/293
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/288
 * @since 6.6.0  https://github.com/aamplugin/advanced-access-manager/issues/114
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.34
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php $object = AAM_Backend_Subject::getInstance()->getObject(AAM_Core_Object_Menu::OBJECT_TYPE); ?>

    <div class="aam-feature" id="admin_menu-content">
        <?php if (AAM_Framework_Manager::configs()->get_config('core.settings.tips', true)) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access to the backend menu and submenu items. With the premium %sComplete Package%s, you can also enable the "restricted mode" to only whitelist allowed menu items. To learn more, refer to our official documentation page %shere%s.'), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/streamlining-wordpress-backend-menu-access?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-menu-overwrite" style="display: <?php echo ($this->isOverwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="menu-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <?php echo apply_filters('aam_backend_menu_mode_panel_filter', '', $object); ?>

        <div class="panel-group" id="admin-menu" role="tablist" aria-multiselectable="true">
            <?php
            $first = false;
            $menu  = AAM_Framework_Manager::backend_menu(array(
                'subject' => AAM_Backend_Subject::getInstance()->getSubject()
            ))->get_item_list();

            if (!empty($menu)) {
                foreach ($menu as $menu) {
            ?>
                    <div class="panel panel-default" style="opacity: <?php echo AAM_Backend_Subject::getInstance()->hasCapability($menu['capability']) ? 1 : '0.5'; ?>">
                        <div class="panel-heading" role="tab" id="menu-<?php echo esc_js($menu['id']); ?>-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#admin-menu" href="#menu-<?php echo esc_js($menu['id']); ?>" aria-controls="menu-<?php echo esc_js($menu['id']); ?>" <?php if (!$first) { echo 'aria-expanded="true"'; } ?>>
                                    <?php echo esc_js($menu['name']); ?> <small class="aam-menu-capability"><?php echo esc_js($menu['capability']); ?></small>
                                </a>
                                <?php if (!empty($menu['is_restricted'])) { ?>
                                    <i class="aam-panel-title-icon icon-lock text-danger"></i>
                                <?php } elseif (isset($menu['children']) && array_reduce($menu['children'], function($s, $i){ return $s + $i['is_restricted']; })) { ?>
                                    <i class="aam-panel-title-icon icon-attention-circled text-warning"></i>
                                <?php } ?>
                            </h4>
                        </div>

                        <div
                            id="menu-<?php echo esc_js($menu['id']); ?>"
                            class="panel-collapse collapse<?php if (!$first) {  echo ' in'; $first = true; } ?>"
                            role="tabpanel"
                            aria-labelledby="menu-<?php echo esc_js($menu['id']); ?>-heading"
                        >
                            <div class="panel-body">
                                <?php if ($menu['slug'] != 'menu-index.php') { ?>
                                    <div class="row aam-inner-tab">
                                        <div class="col-xs-12 text-center">
                                            <small class="aam-menu-capability"><?php echo __('Menu URI:', AAM_KEY); ?> <b><?php echo urldecode($menu['uri']); ?></b></small>
                                        </div>
                                    </div>
                                    <hr class="aam-divider" />
                                <?php } ?>

                                <?php if (!empty($menu['children'])) { ?>
                                    <div class="row aam-inner-tab aam-menu-expended-list">
                                        <?php echo ($menu['is_restricted'] ? '<div class="aam-lock">' . __('The entire menu is restricted with all submenus', AAM_KEY) . '</div>' : ''); ?>

                                        <?php foreach ($menu['children'] as $child) { ?>
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
                                                        <small><a href="#menu-details-modal" data-toggle="modal" data-uri="<?php echo esc_attr($child['uri']); ?>" data-cap="<?php echo esc_attr($child['capability']); ?>" data-name="<?php echo esc_attr($child['name']); ?>" data-id="<?php echo esc_attr($child['id']); ?>" class="aam-menu-item"><?php echo __('more details', AAM_KEY); ?></a></small>
                                                    </div>
                                                    <?php if ($child['is_restricted']) { ?>
                                                        <i class="aam-accordion-action icon-lock text-danger" id="menu-item-<?php echo esc_js($child['id']); ?>" data-menu-id="<?php echo esc_attr($child['id']); ?>"></i>
                                                    <?php } else { ?>
                                                        <i class="aam-accordion-action icon-lock-open text-success" id="menu-item-<?php echo esc_js($child['id']); ?>" data-menu-id="<?php echo esc_attr($child['id']); ?>"></i>
                                                    <?php } ?>
                                                    <label for="menu-item-<?php echo esc_js($child['id']); ?>" data-toggle="tooltip" title="<?php echo ($child['is_restricted'] ?  __('Uncheck to allow', AAM_KEY) : __('Check to restrict', AAM_KEY)); ?>"></label>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>

                                    <hr class="aam-divider" />
                                <?php } ?>

                                <?php if ($menu['slug'] != 'menu-index.php') { ?>
                                    <div class="row<?php echo (!empty($menu['children']) ? ' aam-margin-top-xs' : ''); ?>">
                                        <div class="col-xs-10 col-md-6 col-xs-offset-1 col-md-offset-3">
                                            <?php if ($menu['is_restricted']) { ?>
                                                <a href="#" class="btn btn-primary btn-sm btn-block aam-restrict-menu" data-menu-id="<?php echo esc_attr($menu['id']); ?>" data-target="#menu-<?php echo esc_js($menu['id']); ?>">
                                                    <i class="icon-lock-open"></i> <?php echo __('Show Menu', AAM_KEY); ?>
                                                </a>
                                            <?php } else { ?>
                                                <a href="#" class="btn btn-danger btn-sm btn-block aam-restrict-menu" data-menu-id="<?php echo esc_attr($menu['id']); ?>" data-target="#menu-<?php echo esc_js($menu['id']); ?>">
                                                    <i class="icon-lock"></i> <?php echo __('Restrict Menu', AAM_KEY); ?>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <p class="aam-info"><?php echo __('The "Dashboard" menu cannot be restricted because it is the default page all users are redirected to after login.', AAM_KEY); ?></p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php }
            } else { ?>
                <div class="row">
                    <div class="col-xs-12">
                        <p class="aam-notification">
                            <?php echo __('Try to refresh the page. If that doesn\'t resolve the issue, it\'s possible that the current user may lack the necessary privileges to access any backend menu items. Another frequently encountered problem is the deactivation of transients (a native caching method in WordPress). Often, third-party caching plugins offer the option to disable transients, so if you\'re using one, look for the settings that allow you to re-enable them.', AAM_KEY); ?>
                        </p>
                    </div>
                </div>
            <?php } ?>
        </div>

        <div class="modal fade" id="dashboard-lockout-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Dashboard Lockdown', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="text-center alert alert-warning text-larger">
                            <strong><?php echo __('You cannot restrict access to the Dashboard Home page.', AAM_KEY); ?></strong><br />
                            <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('The [Dashboard Home] is the default page every user redirects to after login. To restrict access to the entire backend, check the %sHow to lock down WordPress backend%s Q&A.', 'b'), '<a href="https://aamportal.com/question/how-to-lockdown-the-entire-wordpress-backend-area?ref=plugin" target="_blank">', '</a>'); ?>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('OK', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="menu-details-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Menu Details', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped table-bordered">
                            <tbody>
                                <tr>
                                    <th width="20%"><?php echo __('Name', AAM_KEY); ?></th>
                                    <td id="menu-item-name"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('Capability', AAM_KEY); ?></th>
                                    <td id="menu-item-cap"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('URI', AAM_KEY); ?></th>
                                    <td id="menu-item-uri"></td>
                                </tr>
                                <tr>
                                    <th width="20%"><?php echo __('ID', AAM_KEY); ?></th>
                                    <td id="menu-item-id"></td>
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