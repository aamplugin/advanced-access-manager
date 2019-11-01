<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php $features = AAM_Backend_Feature::retrieveList($params->type); ?>
    <?php if (count($features)) { ?>
        <?php if (count($features) > 1) { ?>
            <div class="row">
                <div class="col-xs-12 col-md-4">
                    <ul class="list-group" id="feature-list">
                        <?php
                        foreach ($features as $i => $feature) {
                            echo '<li class="list-group-item' . (isset($feature->class) ? ' ' . $feature->class : '') . '" data-feature="' . $feature->uid . '">';
                            echo $feature->title;
                            echo (empty($feature->notification) ? '' : ' <span class="badge">' . $feature->notification . '</span>');
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
            <p class="aam-notification text-larger text-center"><?php echo __('You are not allowed to manage any of the existing services.', AAM_KEY); ?></p>
        </div>
    <?php } ?>
<?php }