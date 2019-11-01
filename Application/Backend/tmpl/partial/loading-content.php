<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <p class="alert alert-info text-larger text-center" id="aam-initial-load">
        <?php echo AAM_Backend_View_Helper::preparePhrase('[Loading AAM UI]. Please wait. If content will not load within next 30 seconds, clear your browser cache and reload the page. If still nothing, it is most likely some sort of JavaScript or CSS conflict with one your active plugins or theme. Try to deactivate all plugins and switch to any default WordPress theme to find out what causes the issue.', 'strong'); ?>
    </p>
<?php }