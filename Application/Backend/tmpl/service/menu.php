<?php
/**
 * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/114
 * @since 6.0.0 Initial implementation of the template
 *
 * @version 6.6.0
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="admin_menu-content">
        <?php if (current_user_can('aam_page_help_tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access to the backend main menu for [%s]. Any menu that is lighter, indicates that [%s] does not have capability to access it. For more information check %sHow to manage WordPress backend menu%s.', 'b', 'b', 'b'), AAM_Backend_Subject::getInstance()->getName(), AAM_Backend_Subject::getInstance()->getName(), '<a href="https://aamplugin.com/article/how-to-manage-wordpress-backend-menu" target="_blank">', '</a>'); ?>
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

        <div class="panel-group" id="admin-menu" role="tablist" aria-multiselectable="true">
            <?php
            $first  = false;
            $object = AAM_Backend_Subject::getInstance()->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);
            $menuList = $this->getMenu();

            if (!empty($menuList)) {
                foreach ($menuList as $i => $menu) {
            ?>
                    <div class="panel panel-default" style="opacity: <?php echo AAM_Backend_Subject::getInstance()->hasCapability($menu['capability']) ? 1 : '0.5'; ?>">
                        <div class="panel-heading" role="tab" id="menu-<?php echo $i; ?>-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#admin-menu" href="#menu-<?php echo $i; ?>" aria-controls="menu-<?php echo $i; ?>" <?php if (!$first) { echo 'aria-expanded="true"'; } ?>>
                                    <?php echo $menu['name']; ?> <small class="aam-menu-capability"><?php echo $menu['capability']; ?></small>
                                </a>
                                <?php if ($menu['checked']) { ?>
                                    <i class="aam-panel-title-icon icon-eye-off text-danger"></i>
                                <?php } elseif ($this->hasSubmenuChecked($menu['submenu'])) { ?>
                                    <i class="aam-panel-title-icon icon-attention-circled text-warning"></i>
                                <?php } ?>
                            </h4>
                        </div>

                        <div id="menu-<?php echo $i; ?>" class="panel-collapse collapse<?php if (!$first) {
                                                                                            echo ' in';
                                                                                            $first = true;
                                                                                        } ?>" role="tabpanel" aria-labelledby="menu-<?php echo $i; ?>-heading">
                            <div class="panel-body">
                                <?php if ($menu['id'] != 'menu-index.php') { ?>
                                    <div class="row aam-inner-tab">
                                        <div class="col-xs-12 text-center">
                                            <small class="aam-menu-capability"><?php echo __('Menu URI:', AAM_KEY); ?> <b><?php echo urldecode($menu['uri']); ?></b></small>
                                        </div>
                                    </div>
                                    <hr class="aam-divider" />
                                <?php } ?>
                                <?php if (!empty($menu['submenu'])) { ?>
                                    <div class="row aam-inner-tab">
                                        <?php echo ($menu['checked'] ? '<div class="aam-lock"></div>' : ''); ?>
                                        <?php foreach ($menu['submenu'] as $j => $submenu) { ?>
                                            <?php if ($submenu['id'] == 'index.php') { ?>
                                                <div class="col-xs-12 col-md-6 aam-submenu-item">
                                                    <div class="aam-menu-details">
                                                        <?php echo $submenu['name']; ?>
                                                    </div>
                                                    <a href="#dashboard-lockout-modal" data-toggle="modal"><i class="icon-help-circled"></i></a>
                                                </div>
                                            <?php } else { ?>
                                                <div class="col-xs-12 col-md-6 aam-submenu-item">
                                                    <div class="aam-menu-details">
                                                        <?php echo $submenu['name']; ?>
                                                        <small><a href="#menu-details-modal" data-toggle="modal" data-uri="<?php echo urldecode($submenu['uri']); ?>" data-cap="<?php echo $submenu['capability']; ?>" data-name="<?php echo $submenu['name']; ?>" data-id="<?php echo $submenu['id']; ?>" class="aam-menu-item"><?php echo __('more details', AAM_KEY); ?></a></small>
                                                    </div>
                                                    <input type="checkbox" class="aam-checkbox-danger" id="menu-item-<?php echo $i . $j; ?>" data-menu-id="<?php echo $submenu['id']; ?>" <?php echo ($submenu['checked'] ? ' checked="checked"' : ''); ?> />
                                                    <label for="menu-item-<?php echo $i . $j; ?>" data-toggle="tooltip" title="<?php echo ($object->isRestricted($submenu['id']) ?  __('Uncheck to allow', AAM_KEY) : __('Check to restrict', AAM_KEY)); ?>"></label>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>

                                    <hr class="aam-divider" />
                                <?php } ?>

                                <?php if ($menu['id'] != 'menu-index.php') { ?>
                                    <div class="row<?php echo (!empty($menu['submenu']) ? ' aam-margin-top-xs' : ''); ?>">
                                        <div class="col-xs-10 col-md-6 col-xs-offset-1 col-md-offset-3">
                                            <?php if ($menu['checked']) { ?>
                                                <a href="#" class="btn btn-primary btn-sm btn-block aam-restrict-menu" data-menu-id="<?php echo $menu['id']; ?>" data-target="#menu-<?php echo $i; ?>">
                                                    <i class="icon-eye"></i> <?php echo __('Show Menu', AAM_KEY); ?>
                                                </a>
                                            <?php } else { ?>
                                                <a href="#" class="btn btn-danger btn-sm btn-block aam-restrict-menu" data-menu-id="<?php echo $menu['id']; ?>" data-target="#menu-<?php echo $i; ?>">
                                                    <i class="icon-eye-off"></i> <?php echo __('Restrict Menu', AAM_KEY); ?>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <p class="aam-info"><?php echo __('Dashboard menu cannot be restricted because it is the default page all users are redirected after login. You can restrict only Dashboard submenus if any.', AAM_KEY); ?></p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php }
            } else { ?>
                <div class="row">
                    <div class="col-xs-12">
                        <p class="aam-notification">
                            <?php echo __('Current user does not have enough capabilities to access any available backend menu.', AAM_KEY); ?>
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
                            <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('The [Dashboard Home] is the default page that every user is redirected to after login. To restrict access to the entire backend, check %sHow to lockdown WordPress backend%s article.', 'b'), '<a href="https://aamplugin.com/article/how-to-lockdown-wordpress-backend" target="_blank">', '</a>'); ?>
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
