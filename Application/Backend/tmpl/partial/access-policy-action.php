<?php /** @version 6.3.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="dropdown">
        <a href="#" id="policy-generator" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-file-code"></i></a>
        <ul class="dropdown-menu">
            <li><a href="#" id="generate-access-policy"><?php echo __('Download as File', AAM_KEY); ?></a></li>
            <li><a href="#" id="create-access-policy"><?php echo __('Create New Policy', AAM_KEY); ?></a></li>
            <li role="separator" class="divider"></li>
            <li><a href="https://aamplugin.com/reference/policy" target="_blank"><?php echo __('Learn More', AAM_KEY); ?></a></li>
        </ul>
    </div>
<?php }