<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature settings" id="settings-content-content">
        <table class="table table-striped table-bordered">
            <tbody>
                <?php $list = $this->getList(); ?>

                <?php if (count($list)) { ?>
                    <?php foreach($list as $id => $option) { ?>
                        <tr>
                            <td>
                                <span class='aam-setting-title'><?php echo esc_js($option['title']); ?></span>
                                <p class="aam-setting-description">
                                    <?php echo esc_js($option['description']); ?>
                                </p>
                            </td>
                            <td class="text-center">
                                <input data-toggle="toggle" name="<?php echo esc_attr($id); ?>" id="utility-<?php echo esc_attr($id); ?>" <?php echo ($option['value'] ? 'checked' : ''); ?> type="checkbox" data-on="<?php echo __('Enabled', 'advanced-access-manager'); ?>" data-off="<?php echo __('Disabled', 'advanced-access-manager'); ?>" data-size="small" />
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <p class="alert alert-info text-center"><?php echo __('There are no settings associated with content service.', 'advanced-access-manager'); ?></p>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php }