<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row">
        <div class="col-xs-12">
            <p class="aam-notification">
                <?php echo sprintf(
                    AAM_Backend_View_Helper::preparePhrase('Managing access to the %s "%s" is available with the premium %s[Complete Package]%s add-on only. It also allows to define default access to all child posts that are related to the %s "%s". Consider to purchase Complete Package add-on.', 'b'),
                    is_taxonomy_hierarchical($params->term->taxonomy) ? __('category', AAM_KEY) : __('tag', AAM_KEY),
                    $params->term->name,
                    '<a href="https://aamportal.com/premium">',
                    '</a>',
                    is_taxonomy_hierarchical($params->term->taxonomy) ? __('category', AAM_KEY) : __('tag', AAM_KEY),
                    $params->term->name
                ); ?>
            </p>
        </div>
    </div>
<?php }