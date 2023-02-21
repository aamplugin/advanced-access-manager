<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row">
        <div class="col-xs-12">
            <p class="aam-notification">
                <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('You can manage access to any individual post or page for any role, user, or visitor. Consider getting the %s[Complete Package]%s premium add-on to have the ability to manage access to categories and custom taxonomies or to define the default access to all posts, pages, or custom post types. For more detail, refer to the %sPremium Complete Package%s page.', 'b'), '<a href="https://aamportal.com/premium" target="_blank">', '</a>', '<a href="https://aamportal.com/plugin/premium-complete-package/" target="_blank">', '</a>'); ?>
            </p>
        </div>
    </div>
<?php }