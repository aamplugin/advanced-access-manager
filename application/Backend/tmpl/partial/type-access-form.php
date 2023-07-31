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
                <?php echo sprintf(
                    AAM_Backend_View_Helper::preparePhrase('Manage default access to all posts that belong to the post type %s. This feature is available only with the premium %s[Complete Package]%s add-on.', 'b', 'b'),
                    $params->postType->label,
                    '<a href="https://aamportal.com/premium?ref=plugin">',
                    '</a>'
                ); ?>
            </p>
        </div>
    </div>
<?php }