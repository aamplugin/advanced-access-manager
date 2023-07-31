<?php
/**
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/311
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.14
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature settings" id="settings-core-content">
        <table class="table table-striped table-bordered">
            <tbody>
                <?php foreach($this->getList() as $id => $option) { ?>
                    <tr>
                        <td>
                            <span class='aam-setting-title'><?php echo esc_js($option['title']); ?></span>
                            <p class="aam-setting-description">
                                <?php echo $option['description']; ?>
                            </p>
                        </td>
                        <td class="text-center">
                            <input
                                data-toggle="toggle"
                                name="<?php echo esc_attr($id); ?>"
                                id="utility-<?php echo esc_attr($id); ?>"
                                <?php echo ($option['value'] ? 'checked' : ''); ?>
                                type="checkbox"
                                data-on="<?php echo isset($option['optionOn']) ? $option['optionOn'] : __('Enabled', AAM_KEY); ?>"
                                data-off="<?php echo isset($option['optionOff']) ? $option['optionOff'] : __('Disable', AAM_KEY); ?>"
                                data-size="small"
                                <?php echo (isset($option['valueOn']) ? 'data-value-on="' . esc_attr($option['valueOn']). '"' : ''); ?>
                                <?php echo (isset($option['valueOff']) ? 'data-value-off="' . esc_attr($option['valueOff']). '"' : ''); ?>
                            />
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php }