<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="visitor-message">
        <span class="aam-bordered"><?php echo __('Attach current access &amp; security policy to visitors (any user that is not authenticated)', AAM_KEY); ?>.</span>
        <?php
            $visitor   = new AAM_Core_Subject_Visitor();
            $hasPolicy = $visitor->getObject(AAM_Core_Object_Policy::OBJECT_TYPE)->has($params->policyId);
            $btnStatus = $hasPolicy ? 'detach' : 'attach';
            ?>
        <?php if ($hasPolicy) { ?>
            <button class="btn btn-primary btn-block" id="attach-policy-visitor" data-has="1" <?php echo ($btnStatus ? '' : ' disabled'); ?>><?php echo __('Detach Policy From Visitors', AAM_KEY); ?></button>
        <?php } else { ?>
            <button class="btn btn-primary btn-block" id="attach-policy-visitor" data-has="0" <?php echo ($btnStatus ? '' : ' disabled'); ?>><?php echo __('Attach Policy To Visitors', AAM_KEY); ?></button>
        <?php } ?>
    </div>
<?php }