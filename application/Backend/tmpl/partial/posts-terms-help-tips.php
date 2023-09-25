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
                <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('You can manage access to any individual post or page for any role, user, or visitor. Consider getting the %s[Complete Package]%s premium add-on to have the ability to manage access to categories and custom taxonomies or to define the default access to all posts, pages, or custom post types.', 'b'), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>'); ?>
            </p>
        </div>
    </div>
<?php }