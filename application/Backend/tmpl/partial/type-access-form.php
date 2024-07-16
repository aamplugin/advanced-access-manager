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
                    AAM_Backend_View_Helper::preparePhrase('Gain full control over the entire post type! Upgrade to our %s[premium add-on]%s to manage default access to all posts within the post type "%s". Discover the power of complete management today.', 'b', 'b'),
                    '<a href="https://aamportal.com/premium?ref=plugin">',
                    '</a>',
                    $params->postType->label
                ); ?>
            </p>
        </div>
    </div>
<?php }