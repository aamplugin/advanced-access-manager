<?php /** @version 7.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature settings" id="settings-services-content">
        <table id="service-list" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th width="80%"><?php echo __('Service Name/Description', 'advanced-access-manager'); ?></th>
                    <th><?php echo __('Status', 'advanced-access-manager'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div class="hidden" id="service-list-json"><?php echo wp_json_encode($this->getList(), JSON_HEX_QUOT); ?></div>
    </div>
<?php }