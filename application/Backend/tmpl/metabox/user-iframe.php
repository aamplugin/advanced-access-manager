<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) {
    wp_enqueue_style('aam-vendor', AAM_MEDIA . '/css/vendor.min.css', [], AAM_VERSION);
    wp_enqueue_style('aam', AAM_MEDIA . '/css/aam.css', array('aam-vendor'), AAM_VERSION);
    wp_enqueue_script('aam-iframe', AAM_MEDIA . '/js/iframe-content.js', [], AAM_VERSION);
}
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php echo static::loadTemplate(__DIR__ . '/iframe-header.php'); ?>

    <div class="row" style="margin: 10px 0 0 0;">
        <div class="col-sm-12">
            <div id="aam-content">
                <?php echo static::loadPartial('loading-content'); ?>
            </div>
        </div>
    </div>

    <!-- User specific attributes -->
    <input type="hidden" id="aam-subject-type" value="user" />
    <input type="hidden" id="aam-subject-id" value="<?php echo intval($params->user->ID); ?>" />
    <input type="hidden" id="aam-subject-name" value="<?php echo esc_js($params->user->display_name); ?>" />

    <?php echo static::loadTemplate(__DIR__ . '/iframe-footer.php'); ?>
<?php }