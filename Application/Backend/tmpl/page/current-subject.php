<?php
    /**
     * @since 6.3.0 Refactored to support https://github.com/aamplugin/advanced-access-manager/issues/27
     * @since 6.2.0 Added `aam_top_subject_actions_filter` hook
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.3.0
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row" id="aam-subject-banner">
        <div class="col-xs-12 col-md-8">
            <div class="aam-current-subject"></div>
            <div class="subject-top-actions">
                <?php do_action('aam_top_subject_panel_action'); ?>
            </div>
        </div>
    </div>
<?php }