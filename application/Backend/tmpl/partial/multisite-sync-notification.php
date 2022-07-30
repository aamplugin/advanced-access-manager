<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="metabox-holder shared-metabox">
        <div class="postbox">
            <div class="inside">
                <div class="aam-postbox-inside">
                    <p class="alert alert-danger text-larger">
                        <?php echo AAM_Backend_View_Helper::preparePhrase('[Warning!] You are operating on the multisite network main blog. All the settings will be automatically synced across all the blogs in this network.', 'strong'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php }