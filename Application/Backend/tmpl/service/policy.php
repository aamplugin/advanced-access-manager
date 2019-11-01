<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="policy-content">
        <?php if (defined('AAM_PLUS_PACKAGE') || !AAM_Backend_Subject::getInstance()->isDefault()) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access and security policies for [%s]. For more information check %sAccess &amp; Security Policy%s page.', 'b'), AAM_Backend_Subject::getInstance()->getName(), '<a href="https://aamplugin.com/reference/policy" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <div class="aam-overwrite" id="aam-policy-overwrite" style="display: <?php echo ($this->isOverwritten() ? 'block' : 'none'); ?>">
                        <span><i class="icon-check"></i> <?php echo __('Policies are customized', AAM_KEY); ?></span>
                        <span><a href="#" id="policy-reset" class="btn btn-xs btn-primary"><?php echo __('Reset To Default', AAM_KEY); ?></a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <table id="policy-list" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th width="80%"><?php echo __('Policy', AAM_KEY); ?></th>
                                <th><?php echo __('Actions', AAM_KEY); ?></th>
                                <th>Edit Link</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        <?php } else { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-notification">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('%s[AAM Plus Package]%s extension is required in order to apply Access &amp; Security Policies to everybody all together.', 'b'), '<a href="https://aamplugin.com/extension/plus-package" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>
    </div>
<?php }
