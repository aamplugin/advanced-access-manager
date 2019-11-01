<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="visitor-message">
        <p class="aam-notification">
            <?php echo AAM_Backend_View_Helper::preparePhrase('This feature is allowed only with [Plus Package] addon.', 'b'); ?>
        </p>
    </div>
<?php }