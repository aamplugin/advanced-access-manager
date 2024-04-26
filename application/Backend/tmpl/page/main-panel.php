<?php
    /**
     * @since 6.9.2 https://github.com/aamplugin/advanced-access-manager/issues/229
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.9.2
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php $features = AAM_Backend_Feature::retrieveList($params->type); ?>
    <?php if (count($features)) { ?>
        <?php if (count($features) > 1) { ?>
            <div class="row">
                <div class="col-xs-12 col-md-4">
                    <ul class="list-group" id="feature-list">
                        <?php
                        foreach ($features as $i => $feature) {
                            echo '<li class="list-group-item' . (isset($feature->class) ? ' ' . esc_attr($feature->class) : '') . '" data-feature="' . esc_attr($feature->uid) . '">';
                            echo esc_js($feature->title);
                            echo (empty($feature->notification) ? '' : ' <span class="badge">' . esc_js($feature->notification) . '</span>');
                            echo '</li>';
                        }
                        ?>
                    </ul>
                </div>
                <div class="col-xs-12 col-md-8">
                    <?php
                    foreach ($features as $feature) {
                        echo $feature->view->getContent();
                    }
                    ?>
                </div>
            </div>
        <?php } else {
            echo array_pop($features)->view->getContent();
        } ?>
    <?php } else { ?>
        <div class="col-xs-12">
            <p class="aam-notification text-larger text-center">
                <?php echo __('You are not allowed to manage any of the existing services.', AAM_KEY); ?>
            </p>
        </div>
    <?php } ?>
<?php }