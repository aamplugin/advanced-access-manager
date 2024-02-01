<?php
    /**
     * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
     * @since 6.9.2  https://github.com/aamplugin/advanced-access-manager/issues/229
     * @since 6.0.0  Initial implementation of the template
     *
     * @version 6.9.21
     * */

    if (defined('AAM_KEY')) {
        wp_enqueue_style('aam-vendor', AAM_MEDIA . '/css/vendor.min.css');
        wp_enqueue_style('aam', AAM_MEDIA . '/css/aam.css', array('aam-vendor'));
        wp_enqueue_script('aam-iframe', AAM_MEDIA . '/js/iframe-content.js');
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
    <input type="hidden" id="aam-subject-level" value="<?php echo esc_js(AAM_Core_API::maxLevel($params->user->allcaps)); ?>" />

    <?php echo static::loadTemplate(__DIR__ . '/iframe-footer.php'); ?>
<?php }