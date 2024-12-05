<?php
/**
 * @since 6.9.29 https://github.com/aamplugin/advanced-access-manager/issues/375
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.29
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="post-content">
        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
            <?php echo apply_filters(
                'aam_content_service_tips_filter',
                AAM_Backend_View::getInstance()->loadPartial('content-service-tips')
            ); ?>
        <?php } ?>

        <div class="aam-post-breadcrumb"></div>

        <div class="aam-container">
            <div id="content_list_container">
                <table id="type-list" class="table table-striped table-bordered hidden">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="5%">&nbsp;</th>
                            <th width="75%"><?php echo __('Title', AAM_KEY); ?></th>
                            <th><?php echo __('Actions', AAM_KEY); ?></th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <table id="taxonomy-list" class="table table-striped table-bordered hidden">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="5%">&nbsp;</th>
                            <th width="75%"><?php echo __('Title', AAM_KEY); ?></th>
                            <th><?php echo __('Actions', AAM_KEY); ?></th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <table id="post-list" class="table table-striped table-bordered hidden">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="5%">&nbsp;</th>
                            <th width="75%"><?php echo __('Title', AAM_KEY); ?></th>
                            <th><?php echo __('Actions', AAM_KEY); ?></th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <table id="term-list" class="table table-striped table-bordered hidden">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="5%">&nbsp;</th>
                            <th width="75%"><?php echo __('Title', AAM_KEY); ?></th>
                            <th><?php echo __('Actions', AAM_KEY); ?></th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="aam-slide-form aam-access-form">
                <a href="#" class="btn btn-xs btn-primary post-back btn-right">
                    &Lt; <?php echo __('Go Back', AAM_KEY); ?>
                </a>
                <span class="aam-clear"></span>

                <div id="aam_access_form_container"></div>

                <a href="#" class="btn btn-xs btn-primary post-back">
                    &Lt; <?php echo __('Go Back', AAM_KEY); ?>
                </a>
            </div>
        </div>
    </div>
<?php }