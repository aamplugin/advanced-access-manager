<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature-top-actions text-right">
        <table class="table table-bordered">
            <tbody>
                <tr class="aam-info">
                    <td class="text-left">
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('[Premium Feature:] Operate the admin toolbar in a restricted access mode. When set to "Enabled," all current and future toolbar menu items are hidden by default unless explicitly allowed. %sLearn more%s.', 'strong'), '<a href="https://aamportal.com/article/understanding-admin-toolbar-restricted-mode-feature?ref=plugin" target="_blank">', '</a>'); ?>
                    </td>
                    <td class="text-center">
                        <input data-toggle="toggle" type="checkbox" data-on="<i class='icon-lock'></i> <?php echo __('Enabled', 'advanced-access-manager'); ?>" data-off="<?php echo __('Disabled', 'advanced-access-manager'); ?>" data-size="small" data-onstyle="danger" disabled />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
<?php }