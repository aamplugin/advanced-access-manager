<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row">
        <div class="col-xs-12">
            <p class="aam-notification">
                <?php echo sprintf(
                    AAM_Backend_View_Helper::preparePhrase('Unlock advanced access management with our %s[premium add-on]%s! Manage access to the %s "%s" and set default access for posts related to the %s "%s." Upgrade to the premium add-on today.', 'b'),
                    '<a href="https://aamportal.com/premium">',
                    '</a>',
                    is_taxonomy_hierarchical($params->term->taxonomy) ? __('category', AAM_KEY) : __('tag', AAM_KEY),
                    $params->term->name,
                    is_taxonomy_hierarchical($params->term->taxonomy) ? __('category', AAM_KEY) : __('tag', AAM_KEY),
                    $params->term->name
                ); ?>
            </p>
        </div>
    </div>
<?php }