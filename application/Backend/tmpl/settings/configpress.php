<?php
/**
 * @since 6.8.4 https://github.com/aamplugin/advanced-access-manager/issues/212
 * @since 6.0.0 Initial implementation of the template
 *
 * @version 6.8.4
 * */

if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="configpress-content">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo sprintf(__('For detail about AAM configurations, refer to the %sConfigPress%s page.', AAM_KEY), '<a href="https://aamportal.com/plugin/advanced-access-manager/configpress/">', '</a>'); ?>
                </p>
            </div>
        </div>

        <textarea
            id="aam-configpress-editor"
            class="configpress-editor"
            style="border: 1px solid #CCCCCC; width: 100%"
            rows="10"
        ><?php echo AAM_Core_ConfigPress::getInstance()->read(); ?></textarea>
    </div>
<?php }