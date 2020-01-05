<?php
    /**
     * @since 6.2.0 Added `aam_top_subject_actions_filter` hook
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.2.0
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row" id="aam-subject-banner">
        <div class="col-xs-12 col-md-8">
            <div class="aam-current-subject"></div>
            <div class="subject-top-actions">
                <?php foreach(apply_filters('aam_top_subject_actions_filter', array()) as $action) { ?>
                    <a href="#" id="<?php echo $action['id']; ?>" data-toggle="tooltip" title="<?php echo $action['tooltip']; ?>"><i class="<?php echo $action['icon']; ?>"></i></a>
                <?php } ?>
            </div>
        </div>
    </div>
<?php }