<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php
        $access_level = AAM_Backend_AccessLevel::get_instance();
        $service      = $access_level->api_routes();
    ?>

    <div class="aam-feature" id="route-content">
        <?php $access_level = AAM_Backend_AccessLevel::get_instance(); ?>

        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
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
                <div class="aam-overwrite<?php echo ($service->is_customized() ? '' : ' hidden'); ?>" id="aam-route-overwrite">
                    <span><i class="icon-check"></i> <?php echo __('Routes are customized', 'advanced-access-manager'); ?></span>
                    <span><a href="#" id="route-reset" class="btn btn-xs btn-primary"><?php echo __('Reset to default', 'advanced-access-manager'); ?></a></span>
                </div>
            </div>
        </div>

        <?php echo apply_filters('aam_ui_api_route_mode_panel_filter', '', $service); ?>

        <table id="route-list" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th width="10%"><?php echo __('Method', 'advanced-access-manager'); ?></th>
                    <th width="80%"><?php echo __('Route', 'advanced-access-manager'); ?></th>
                    <th><?php echo __('Deny', 'advanced-access-manager'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php }