<?php /** @version 6.0.0 */ ?>

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
    <input type="hidden" id="aam-subject-id" value="<?php echo $params->user->ID; ?>" />
    <input type="hidden" id="aam-subject-name" value="<?php echo esc_js($params->user->display_name); ?>" />
    <input type="hidden" id="aam-subject-level" value="<?php echo AAM_Core_API::maxLevel($params->user->allcaps); ?>" />

    <?php echo static::loadTemplate(__DIR__ . '/iframe-footer.php'); ?>
<?php }