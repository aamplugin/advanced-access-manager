<?php /** @version 7.0.0 **/ ?>

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
                        echo call_user_func($feature->view);
                    }
                    ?>
                </div>
            </div>
        <?php } else {
            echo call_user_func(array_pop($features)->view);
        } ?>
    <?php } else { ?>
        <div class="col-xs-12">
            <p class="aam-notification text-larger text-center">
                <?php echo __('You are not allowed to manage any of the existing services.', 'advanced-access-manager'); ?>
            </p>
        </div>
    <?php } ?>
<?php }