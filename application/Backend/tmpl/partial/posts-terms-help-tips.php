<?php
/**
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.14
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row">
        <div class="col-xs-12">
            <p class="aam-notification">
                <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage access to your posts, pages, custom post types, taxonomies, terms or even entire post types for any role, user, or visitor. To gain the full control, consider getting the %s[premium add-on]%s. To learn more, check our %s"About Posts & Terms service"%s article.', 'b'), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/about-posts-and-terms-service?ref=plugin" target="_blank">', '</a>'); ?>
            </p>
        </div>
    </div>
<?php }