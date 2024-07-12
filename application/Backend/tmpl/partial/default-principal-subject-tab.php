<?php /** @version 6.3.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="visitor-message">
        <span class="aam-bordered"><?php echo __('Attach current access &amp; security policy to all users, roles and visitors', AAM_KEY); ?>.</span>
        <?php
            $hasPolicy = AAM::api()->default()->get_resource(
                AAM_Framework_Type_Resource::ACCESS_POLICY
            )->get_setting($params->policyId);

            $btnStatus = $hasPolicy === true ? 'detach' : 'attach';
        ?>

        <?php if ($hasPolicy) { ?>
            <button class="btn btn-primary btn-block" id="attach-policy-default" data-has="1" <?php echo ($btnStatus ? '' : ' disabled'); ?>><?php echo __('Detach Policy From Everybody', AAM_KEY); ?></button>
        <?php } else { ?>
            <button class="btn btn-primary btn-block" id="attach-policy-default" data-has="0" <?php echo ($btnStatus ? '' : ' disabled'); ?>><?php echo __('Attach Policy To Everybody', AAM_KEY); ?></button>
        <?php } ?>
    </div>
<?php }