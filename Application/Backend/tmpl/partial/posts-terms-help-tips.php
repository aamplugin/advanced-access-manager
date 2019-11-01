<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row">
        <div class="col-xs-12">
            <p class="aam-notification">
                <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('You are allowed to manage access to unlimited number of posts, pages or custom post types but only for any role, user or visitor. Consider to get %s[Plus Package]%s add-on to have the ability to manage access to categories and custom taxonomies or to define the default access to all posts, pages or custom post types. For more information about this functionality check %sHow to manage access to the WordPress content%s.', 'b'), '<a href="https://aamplugin.com/pricing/plus-package" target="_blank">', '</a>', '<a href="https://aamplugin.com/article/manage-access-to-the-wordpress-posts-and-terms" target="_blank">', '</a>'); ?>
            </p>
        </div>
    </div>
<?php }