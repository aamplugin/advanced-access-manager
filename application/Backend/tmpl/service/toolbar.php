<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="toolbar-content">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo AAM_Backend_View_Helper::preparePhrase('[Note!] Toolbar service is not intended to restrict direct access to linked pages. It used only to remove unnecessary items from the top toolbar. Use [Backend Menu] tab to restrict direct access to backend pages or utilize the great power of capabilities.', 'b', 'b'); ?>
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite" id="aam-toolbar-overwrite" style="display: <?php echo ($this->isOverwritten() ? 'block' : 'none'); ?>">
                    <span><i class="icon-check"></i> <?php echo __('Settings are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="toolbar-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <div class="panel-group" id="toolbar-list" role="tablist" aria-multiselectable="true">
            <?php
            $first   = false;
            $toolbar = $this->getToolbar();
            $object  = AAM_Backend_Subject::getInstance()->getObject('toolbar');

            if (!empty($toolbar)) { ?>
                <?php foreach ($toolbar as $i => $branch) { ?>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="toolbar-<?php echo $branch->id; ?>-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#toolbar-list" href="#toolbar-<?php echo $branch->id; ?>" aria-controls="toolbar-<?php echo $branch->id; ?>" <?php if (!$first) { echo 'aria-expanded="true"'; } ?>>
                                    <?php echo $this->normalizeTitle($branch); ?> <small class="aam-menu-capability"><?php echo str_replace(site_url(), '', $branch->href); ?></small>
                                </a>
                                <?php if ($object->isHidden('toolbar-' . $branch->id)) { ?>
                                    <i class="aam-panel-title-icon icon-eye-off text-danger"></i>
                                <?php } ?>
                            </h4>
                        </div>

                        <div id="toolbar-<?php echo $branch->id; ?>" class="panel-collapse collapse<?php if (!$first) { echo ' in'; $first = true; } ?>" role="tabpanel" aria-labelledby="toolbar-<?php echo $branch->id; ?>-heading">
                            <div class="panel-body">
                                <div class="row aam-inner-tab">
                                    <div class="col-xs-12 text-center">
                                        <small class="aam-menu-capability"><?php echo __('Item ID:', AAM_KEY); ?> <b><?php echo $branch->id; ?></b></small>
                                    </div>
                                </div>
                                <hr class="aam-divider" />
                                <?php if (!empty($branch->children)) { ?>
                                    <div class="row aam-inner-tab">
                                        <?php echo ($object->isHidden('toolbar-' . $branch->id) ? '<div class="aam-lock"></div>' : ''); ?>
                                        <?php foreach ($this->getAllChildren($branch) as $child) { ?>
                                            <div class="col-xs-12 col-md-6 aam-submenu-item">
                                                <div class="aam-menu-details">
                                                    <?php echo $this->normalizeTitle($child); ?>
                                                    <small><a href="#toolbar-details-modal" data-toggle="modal" data-uri="<?php echo urldecode(str_replace(site_url(), '', $child->href)); ?>" data-id="<?php echo esc_js($child->id); ?>" data-name="<?php echo esc_js($this->normalizeTitle($child)); ?>" class="aam-toolbar-item"><?php echo __('more details', AAM_KEY); ?></a></small>
                                                </div>
                                                <input type="checkbox" class="aam-checkbox-danger" id="toolbar-<?php echo $child->id; ?>" data-toolbar="<?php echo $child->id; ?>" <?php echo ($object->isHidden($child->id) ? ' checked="checked"' : ''); ?> />
                                                <label for="toolbar-<?php echo $child->id; ?>" data-toggle="tooltip" title="<?php echo ($object->isHidden($child->id) ?  __('Uncheck to allow', AAM_KEY) : __('Check to restrict', AAM_KEY)); ?>"></label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <hr class="aam-divider" />
                                <?php } ?>
                                <div class="row<?php echo (!empty($branch->children) ? ' aam-margin-top-xs' : ''); ?>">
                                    <div class="col-xs-10 col-md-6 col-xs-offset-1 col-md-offset-3">
                                        <?php if ($object->isHidden('toolbar-' . $branch->id)) { ?>
                                            <a href="#" class="btn btn-primary btn-sm btn-block aam-restrict-toolbar" data-toolbar="toolbar-<?php echo $branch->id; ?>" data-target="#toolbar-<?php echo $branch->id; ?>">
                                                <i class="icon-eye"></i> <?php echo __('Show Menu', AAM_KEY); ?>
                                            </a>
                                        <?php } else { ?>
                                            <a href="#" class="btn btn-danger btn-sm btn-block aam-restrict-toolbar" data-toolbar="toolbar-<?php echo $branch->id; ?>" data-target="#toolbar-<?php echo $branch->id; ?>">
                                                <i class="icon-eye-off"></i> <?php echo __('Restrict Menu', AAM_KEY); ?>
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
                                                <th width="20%"><?php echo __('Name', AAM_KEY); ?></th>
                                                <td id="toolbar-item-name"></td>
                                            </tr>
                                            <tr>
                                                <th width="20%"><?php echo __('URI', AAM_KEY); ?></th>
                                                <td id="toolbar-item-uri"></td>
                                            </tr>
                                            <tr>
                                                <th width="20%"><?php echo __('ID', AAM_KEY); ?></th>
                                                <td id="toolbar-item-id"></td>
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
                <?php } ?>
            <?php } else { ?>
                <div class="row">
                    <div class="col-xs-12">
                        <p class="aam-info">
                            <?php echo __('The list of top admin bar items is not initialized. Reload the page.', AAM_KEY); ?>
                        </p>
                    </div>
                </div>
            <?php }
            ?>
        </div>
    </div>
<?php }