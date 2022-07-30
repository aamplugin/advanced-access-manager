<?php
    /**
     * @since 6.4.0 Enhancement https://github.com/aamplugin/advanced-access-manager/issues/79
     * @since 6.3.0 Initial implementation of the template
     *
     * @version 6.4.0
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="dropdown">
        <a href="#" id="policy-generator" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-placement="right" title="<?php echo __('Generate Access Policy', AAM_KEY); ?>"><i class="icon-file-code"></i></a>
        <ul class="dropdown-menu">
            <li><a href="#" id="generate-access-policy"><?php echo __('Download as File', AAM_KEY); ?></a></li>
            <li><a href="#" id="create-access-policy"><?php echo __('Create New Policy', AAM_KEY); ?></a></li>
            <li role="separator" class="divider"></li>
            <li><a href="https://aamplugin.com/reference/policy" target="_blank"><?php echo __('Learn More', AAM_KEY); ?></a></li>
        </ul>
    </div>
<?php }