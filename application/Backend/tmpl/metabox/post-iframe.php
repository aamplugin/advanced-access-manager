<?php
    /**
     * @since 6.9.2 https://github.com/aamplugin/advanced-access-manager/issues/229
     * @since 6.5.1 Improved styling
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.9.2
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
        <div class="col-sm-4" style="padding: 0;">
            <?php echo static::loadTemplate(dirname(__DIR__) . '/page/subject-panel.php'); ?>
        </div>

        <div class="col-sm-8" style="padding: 0;">
            <div id="aam-access-form-container">
                <?php echo $params->postManager->getAccessForm($params->objectId, $params->objectType); ?>
            </div>
        </div>
    </div>

    <?php echo static::loadTemplate(__DIR__ . '/iframe-footer.php'); ?>
<?php }