<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) {
    wp_enqueue_style('aam-vendor', AAM_MEDIA . '/css/vendor.min.css', [], AAM_VERSION);
    wp_enqueue_style('aam', AAM_MEDIA . '/css/aam.css', array('aam-vendor'), AAM_VERSION);
    wp_enqueue_script('aam-iframe', AAM_MEDIA . '/js/iframe-content.js', [], AAM_VERSION);
}
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php echo static::loadTemplate(__DIR__ . '/iframe-header.php'); ?>

    <div class="row" style="margin: 10px 0 0 0;" id="aam_post_access_metabox">
        <div class="col-sm-4" style="padding: 0;">
            <?php echo static::loadTemplate(dirname(__DIR__) . '/page/subject-panel.php'); ?>
        </div>

        <div class="col-sm-8" style="padding: 0 0 0 15px;" id="aam_content_access_form">
            <div id="aam_access_form_container">
                <?php echo $params->postManager->render_content_access_form(
                    $params->objectId,
                    $params->objectType
                ); ?>
            </div>
        </div>
    </div>

    <?php echo static::loadTemplate(__DIR__ . '/iframe-footer.php'); ?>
<?php }