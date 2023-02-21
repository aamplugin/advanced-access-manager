<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php global $wpdb; ?>

    <div class="aam-feature" id="welcome-content">
        <div class="row">
            <div class="col-xs-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <p class="text-center"><img src="<?php echo AAM_MEDIA . '/armadillo.svg'; ?>" width="192" /></p>

                        <p class="text-larger"><?php echo __('Thank you for using the Advanced Access Manager (aka AAM) plugin. With knowledge and experience in WordPress core, AAM becomes a powerful collection of services to manage access to all your website areas.', AAM_KEY); ?></p>
                        <p class="text-larger"><?php echo sprintf(__('%sNote!%s Power comes with responsibility. AAM integrates with WordPress core, and any unintentional changes in roles and capabilities may affect you or your user experience. We recommend backup your database table %s%s%s before you start working with AAM. There is no need to back up your files. AAM does not modify any physical files on your server and never did.'), '<strong>', '</strong>', '<em>', $wpdb->options, '</em>'); ?></p>
                        <p class="text-larger"><?php echo __('AAM is thoroughly tested on the fresh installation of the latest WordPress and in the latest versions of Chrome, Safari, IE, and Firefox. If you have any issues, the most typical cause is a conflict with other plugins or themes.', AAM_KEY); ?></p>
                        <p class="text-larger"><?php echo sprintf(__('If you are unsure where to start, please check our %sGet Started%s page or schedule free Zoom call with us.', AAM_KEY), '<a href="https://aamportal.com/get-started" target="_blank">', '</a>'); ?></p>
                        <p class="text-center">
                            <a href="https://aamportal.com/support" class="btn btn-primary" target="_blank"><?php echo __('Schedule Free Meeting With Us', AAM_KEY); ?></a><br/><br/>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }