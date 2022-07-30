<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature settings" id="settings-content-content">
        <table class="table table-striped table-bordered">
            <tbody>
                <?php $list = $this->getList(); ?>

                <?php if (count($list)) { ?>
                    <?php foreach($list as $id => $option) { ?>
                        <tr>
                            <td>
                                <span class='aam-setting-title'><?php echo $option['title']; ?></span>
                                <p class="aam-setting-description">
                                    <?php echo $option['description']; ?>
                                </p>
                            </td>
                            <td class="text-center">
                                <input data-toggle="toggle" name="<?php echo $id; ?>" id="utility-<?php echo $id; ?>" <?php echo ($option['value'] ? 'checked' : ''); ?> type="checkbox" data-on="<?php echo __('Enabled', AAM_KEY); ?>" data-off="<?php echo __('Disabled', AAM_KEY); ?>" data-size="small" />
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <p class="alert alert-info text-center"><?php echo __('There are no settings associated with content service.', AAM_KEY); ?></p>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php }