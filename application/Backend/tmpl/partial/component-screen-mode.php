<?php /** @version 6.9.13 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="row aam-margin-top-xs">
        <div class="col-xs-10 col-md-6 col-xs-offset-1 col-md-offset-3">
            <a href="#" class="btn btn-danger btn-sm btn-block" data-toggle="tooltip" data-placement="top" title="<?php echo __('This is the premium feature that is available with Complete Package.', AAM_KEY); ?>" disabled>
                <i class="icon-lock"></i> <?php echo __('Hide All (Premium Feature)', AAM_KEY); ?>
            </a>
        </div>
    </div>
<?php }