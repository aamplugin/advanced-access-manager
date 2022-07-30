<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="configpress-content">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo sprintf(__('Fore more information about AAM configurations check %sAAM Configurations%s article.', AAM_KEY), '<a href="https://aamplugin.com/article/aam-configurations">', '</a>'); ?>
                </p>
            </div>
        </div>

        <textarea id="configpress-editor" class="configpress-editor" rows="10"><?php echo AAM_Core_ConfigPress::getInstance()->read(); ?></textarea>
    </div>
<?php }