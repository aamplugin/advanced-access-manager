<?php
/**
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/274
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.34
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::getInstance();
        $service      = $access_level->api_routes();
    ?>

    <div class="aam-feature" id="route-content">
        <?php $access_level = AAM_Backend_AccessLevel::getInstance(); ?>

        <?php if (AAM::api()->configs()->get_config('core.settings.ui.tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access to the RESTful API endpoints. With the premium %sadd-on%s, you can also enable the "restricted mode" to only whitelist allowed endpoints. To learn more, refer to our official documentation page %shere%s.'), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/underestimated-aspect-of-api-access-controls?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-xs-12">
                <div class="aam-overwrite<?php echo ($service->get_resource()->is_overwritten() ? '' : ' hidden'); ?>" id="aam-route-overwrite">
                    <span><i class="icon-check"></i> <?php echo __('Routes are customized', AAM_KEY); ?></span>
                    <span><a href="#" id="route-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', AAM_KEY); ?></a>
                </div>
            </div>
        </div>

        <?php echo apply_filters('aam_ui_api_route_mode_panel_filter', '', $service); ?>

        <table id="route-list" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th width="10%"><?php echo __('Method', AAM_KEY); ?></th>
                    <th width="80%"><?php echo __('Route', AAM_KEY); ?></th>
                    <th><?php echo __('Deny', AAM_KEY); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php }