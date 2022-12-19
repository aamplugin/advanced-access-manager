<?php
    /**
     * @since 6.9.4 https://github.com/aamplugin/advanced-access-manager/issues/239
     * @since 6.9.2 https://github.com/aamplugin/advanced-access-manager/issues/229
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.9.4
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature settings" id="settings-security-content">
        <table class="table table-striped table-bordered">
            <tbody>
                <?php foreach($this->getList() as $id => $option) { ?>
                    <tr>
                        <td>
                            <span class='aam-setting-title'><?php echo esc_js($option['title']); ?></span>
                            <p class="aam-setting-description">
                                <?php echo $option['description']; // The values are already properly evaluated so esc_js is not needed ?>
                            </p>
                        </td>
                        <td class="text-center">
                            <input data-toggle="toggle" name="<?php echo esc_attr($id); ?>" id="utility-<?php echo esc_attr($id); ?>" <?php echo ($option['value'] ? 'checked' : ''); ?> type="checkbox" data-on="<?php echo __('Enabled', AAM_KEY); ?>" data-off="<?php echo __('Disabled', AAM_KEY); ?>" data-size="small" />
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php }