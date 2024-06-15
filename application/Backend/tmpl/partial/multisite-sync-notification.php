<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="metabox-holder shared-metabox">
        <div class="postbox">
            <div class="inside">
                <div class="aam-postbox-inside">
                    <p class="alert alert-danger text-larger">
                        <?php echo AAM_Backend_View_Helper::preparePhrase('[Warning:] You are currently operating on the primary blog of the multisite network. Any settings changes will be automatically synced across all blogs in this network.', 'strong'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php }