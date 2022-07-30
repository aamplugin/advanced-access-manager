<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="route-content">
        <?php $subject = AAM_Backend_Subject::getInstance(); ?>

        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access to the website API routes for [%s]. For the full RESTful API experience, consider to use %sJWT authentication%s that is already available in AAM.', 'b'), AAM_Backend_Subject::getInstance()->getName(), '<a href="https://aamplugin.com/article/ultimate-guide-to-wordpress-jwt-authentication" target="_blank">', '</a>'); ?>
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite<?php echo ($this->isOverwritten() ? '' : ' hidden'); ?>" id="aam-route-overwrite">
                    <span><i class="icon-check"></i> <?php echo __('Routes are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="route-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <table id="route-list" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Route Raw</th>
                    <th>Type</th>
                    <th width="10%"><?php echo __('Method', AAM_KEY); ?></th>
                    <th width="80%"><?php echo __('Route', AAM_KEY); ?></th>
                    <th><?php echo __('Deny', AAM_KEY); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php }