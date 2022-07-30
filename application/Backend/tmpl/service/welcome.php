<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php global $wpdb; ?>

    <div class="aam-feature" id="welcome-content">
        <div class="row">
            <div class="col-xs-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <p class="text-center"><img src="<?php echo AAM_MEDIA . '/armadillo.svg'; ?>" width="192" /></p>

                        <p class="text-larger"><?php echo __('Thank you for using the Advanced Access Manager (aka AAM) plugin. With strong knowledge and experience in WordPress core, AAM becomes a very powerful collection of services to manage access to the website frontend, backend, and RESTful API.', AAM_KEY); ?></p>
                        <p class="text-larger"><span class="aam-highlight"><?php echo __('Note!', AAM_KEY); ?></span> <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Power comes with responsibility. Make sure you have a good understanding of %sWordPress Roles & Capabilities%s because AAM is very closely integrated with WordPress core. It is also recommended to have a backup of your database table wp_options before you start working with AAM. There is no need to back up your files. AAM does not modify any physical files on your server and never did.'), '<a href="https://aamplugin.com/article/wordpress-roles-and-capabilities" target="_blank">', '</a>', $wpdb->options); ?></p>
                        <p class="text-larger"><?php echo __('AAM is thoroughly tested on the fresh installation of the latest WordPress and in the latest versions of Chrome, Safari, IE, and Firefox. If you have any issues, the most typical cause is a conflict with other plugins or themes.', AAM_KEY); ?></p>
                        <p class="text-larger"><?php echo sprintf(__('If you are not sure where to start, please check our %s"Get Started"%s page to learn more about core concepts that may help you to manage access to your WordPress website more effectively.', AAM_KEY), '<a href="https://aamplugin.com/get-started" target="_blank">', '</a>'); ?></p>
                        <p class="text-center">
                            <a href="https://aamplugin.com/get-started" class="btn btn-primary" target="_blank"><?php echo __('Go To The "Get Started" Page', AAM_KEY); ?></a><br/><br/>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }