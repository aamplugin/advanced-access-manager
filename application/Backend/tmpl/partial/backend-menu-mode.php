<?php
/**
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
 * @since 6.9.13 Initial implementation of the template
 *
 * @version 6.9.14
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature-top-actions text-right">
        <table class="table table-bordered">
            <tbody>
                <tr class="aam-info">
                    <td class="text-left">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('[Premium Feature:] Operate the backend menu in a restricted access mode. When set to "Enabled," all current and future backend menu items are restricted by default unless explicitly permitted. %sLearn more%s.', 'strong'), '<a href="https://aamportal.com/plugin/premium-complete-package/backend-access/backend-menu-restricted-mode?ref=plugin" target="_blank">', '</a>'); ?>
                    </td>
                    <td class="text-center">
                        <input data-toggle="toggle" type="checkbox" data-on="<i class='icon-lock'></i> <?php echo __('Enabled', AAM_KEY); ?>" data-off="<?php echo __('Disabled', AAM_KEY); ?>" data-size="small" data-onstyle="danger" disabled />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
<?php }