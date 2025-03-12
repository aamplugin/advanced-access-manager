<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="configpress-content">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo sprintf(__('For detail about AAM configurations, refer to the %sConfigPress%s official documentation.', 'advanced-access-manager'), '<a href="https://aamportal.com/reference/configpress?ref=plugin">', '</a>'); ?>
                </p>
            </div>
        </div>

        <textarea
            id="aam-configpress-editor"
            class="configpress-editor"
            style="border: 1px solid #CCCCCC; width: 100%"
            rows="10"
        ><?php $c = AAM::api()->db->read(AAM_Service_Core::DB_OPTION) ; echo esc_textarea(is_string($c) && !empty($c) ? $c : ''); ?></textarea>
    </div>
<?php }