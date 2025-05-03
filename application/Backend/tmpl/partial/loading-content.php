<?php /** @version 7.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <p class="alert alert-info text-larger text-center" id="aam-initial-load">
        <?php echo AAM_Backend_View_Helper::preparePhrase(
            '[Loading AAM UI...] Please wait. If the content does not load within 30 seconds, try clearing your browser cache and reloading the page. If the issue persists, it may be a server-side error. Check your PHP error log and contact us immediately.',
            'strong'
        ); ?>
    </p>
<?php }