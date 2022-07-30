<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row">
        <div class="col-xs-12">
            <p class="aam-notification">
                <?php echo sprintf(
                    AAM_Backend_View_Helper::preparePhrase('Manage default access to all posts that belong to the post type %s. This feature is available only with the premium %s[Plus Package]%s add-on.', 'b', 'b'),
                    $params->postType->label,
                    '<a href="https://aamplugin.com/pricing/plus-package">',
                    '</a>'
                ); ?>
            </p>
        </div>
    </div>
<?php }