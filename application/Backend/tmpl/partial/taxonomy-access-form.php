<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row">
        <div class="col-xs-12">
            <p class="aam-notification">
                <?php echo sprintf(
                    AAM_Backend_View_Helper::preparePhrase('Managing access to the taxonomy "%s" is available with the premium %s[Complete Package]%s add-on only. It also allows to define the default access to all terms that are associated with this taxonomy. Consider to purchase Complete Package add-on.', 'b'),
                    $params->taxonomy->labels->name,
                    '<a href="https://aamportal.com/premium">',
                    '</a>'
                ); ?>
            </p>
        </div>
    </div>
<?php }