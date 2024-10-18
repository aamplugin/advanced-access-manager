<?php
/**
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/327
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.34
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php global $wpdb; ?>

    <div class="aam-feature" id="welcome-content">
        <div class="row">
            <div class="col-xs-12">
                <?php if (AAM_Framework_Manager::configs()->get_config('core.settings.tips', true)) { ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <p class="aam-info"><?php echo __("Here, we've outlined several typical scenarios where you can leverage AAM, along with some key features. If you require assistance, don't hesitate to reach out by filling out the contact form below. We'll get back to you as soon as possible.", AAM_KEY); ?></p>
                        </div>
                    </div>
                <?php } ?>

                <span class="aam-common-use-cases aam-mt-2"><?php echo __('Announcements', AAM_KEY); ?></span>

                <div class="panel-group aam-mb-6" id="announcement-block" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="announcement-a-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#announcement-block" href="#announcement-a" aria-controls="announcement-a">
                                    <?php echo __('NEW: Enhancing WordPress Security Beyond the Basics', AAM_KEY); ?>
                                </a>
                            </h4>
                        </div>

                        <div id="announcement-a" class="panel-collapse collapse" role="tabpanel" aria-labelledby="announcement-a-heading">
                            <div class="panel-body">
                                <p>
                                    WordPress security often focuses on external threats, such as brute force or DDoS attacks, malware, and outdated plugins.
                                    However, internal security is equally important, as authenticated users can pose risks if granted inappropriate access.
                                    Just as setting boundaries for house guests is crucial, site owners must manage user roles and permissions.
                                </p>
                                <p>
                                    Advanced Access Manager (AAM) plugin addresses this need with its <strong>Security Scan</strong> feature, identifying misconfigurations and potential vulnerabilities, such as over-assigned administrative roles or escalated privileges.
                                    By managing internal access and roles, site owners can ensure comprehensive security for their WordPress sites.
                                </p>

                                <a href="https://aamportal.com/article/enhancing-wordPress-security-beyond-basics" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="announcement-b-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#announcement-block" href="#announcement-b" aria-controls="announcement-b">
                                    <?php echo __('Announcing AAM Version 7', AAM_KEY); ?>
                                </a>
                            </h4>
                        </div>

                        <div id="announcement-b" class="panel-collapse collapse" role="tabpanel" aria-labelledby="announcement-b-heading">
                            <div class="panel-body">
                                <p>
                                    We are excited to announce that Advanced Access Manager (AAM) version 7 is coming soon, with the alpha release planned for the end of October 2024.
                                    This version introduces one-of-a-kind access management framework designed for developers, addressing user requests for expanded API functionality.
                                    The new PHP framework simplifies access control for WordPress, building on the current AAM system that already saves developers significant time.
                                </p>
                                <p>
                                    To encourage testing, we're offering a limited number of free premium add-ons to those who are interested.
                                    We appreciate your support and look forward to your feedback in shaping the future of access management for WordPress!
                                </p>

                                <a href="https://aamportal.com/announcement/aam-v7" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                </div>

                <span class="aam-common-use-cases aam-mt-2"><?php echo __('Introduction', AAM_KEY); ?></span>

                <div class="panel-group" id="intro-block" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-intro-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#intro-block" href="#menu-intro" aria-controls="menu-intro">
                                    <?php echo __('Introduction to Advanced Access Manager (aka AAM)', AAM_KEY); ?> <small class="aam-menu-capability">6 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-intro" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-intro-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/introduction-to-aam" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/introduction-to-aam.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/introduction-to-aam" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                </div>

                <span class="aam-common-use-cases aam-mt-4"><?php echo __('5 Most Common Use Cases', AAM_KEY); ?></span>

                <div class="panel-group" id="common-features" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-common-a-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#common-features" href="#menu-common-a" aria-controls="menu-common-a">
                                    <?php echo __('Make My WordPress Website Private', AAM_KEY); ?> <small class="aam-menu-capability">2 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-common-a" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-common-a-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/make-wordpress-website-private" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/private-wordpress-website.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/make-wordpress-website-private" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-common-b-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#common-features" href="#menu-common-b" aria-controls="menu-common-b">
                                    <?php echo __('3 Simple Steps to Enhance your Website Security', AAM_KEY); ?> <small class="aam-menu-capability">3 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-common-b" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-common-b-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/3-simple-steps-to-enhance-wordpress-security" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/3-simple-steps-for-website-security.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/3-simple-steps-to-enhance-wordpress-security" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-common-c-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#common-features" href="#menu-common-c" aria-controls="menu-common-c">
                                    <?php echo __('Manage Access to Admin Menu', AAM_KEY); ?> <small class="aam-menu-capability">3 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-common-c" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-common-c-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/manage-access-to-wordpress-admin-menu" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/manage-access-to-admin-menu.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/manage-access-to-wordpress-admin-menu" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-common-d-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#common-features" href="#menu-common-d" aria-controls="menu-common-d">
                                    <?php echo __('Protect Posts, Pages, CPTs, Terms & Taxonomies', AAM_KEY); ?> <small class="aam-menu-capability">3 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-common-d" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-common-d-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/protect-wordpress-content" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/protected-content.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/protect-wordpress-content" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-common-e-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#common-features" href="#menu-common-e" aria-controls="menu-common-e">
                                    <?php echo __('Manage Roles, Users & Capabilities', AAM_KEY); ?> <small class="aam-menu-capability">3 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-common-e" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-common-e-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/manage-wordpress-roles-users-capabilities" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/manage-roles-users-capabilities.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/manage-wordpress-roles-users-capabilities" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                </div>

                <span class="aam-common-use-cases aam-mt-4">Highlighted Use Cases</span>

                <div class="panel-group" id="ht-features" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-ht-a-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#ht-features" href="#menu-ht-a" aria-controls="menu-ht-a">
                                    <?php echo __('Customize Editorial Team Permissions to Content', AAM_KEY); ?> <small class="aam-menu-capability">4 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-ht-a" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-ht-a-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/customize-wordpress-editorial-team-permissions" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/customize-editorial-workflow.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/customize-wordpress-editorial-team-permissions" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-ht-b-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#ht-features" href="#menu-ht-b" aria-controls="menu-ht-b">
                                    <?php echo __('Explore Enterprise-Level Access Management with JSON Policies', AAM_KEY); ?> <small class="aam-menu-capability">6 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-ht-b" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-ht-b-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/wordpress-enterprise-level-access-management" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/json-policy.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/wordpress-enterprise-level-access-management" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-ht-c-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#ht-features" href="#menu-ht-c" aria-controls="menu-ht-c">
                                    <?php echo __('Customize Widgets & Metaboxes Visibility', AAM_KEY); ?> <small class="aam-menu-capability">2 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-ht-c" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-ht-c-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/customize-wordpress-widgets-metaboxes-visibility" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/customize-widgets-visibility.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/customize-wordpress-widgets-metaboxes-visibility" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div> -->
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-ht-d-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#ht-features" href="#menu-ht-d" aria-controls="menu-ht-d">
                                    <?php echo __('Protect Media Library, Files & Static Pages', AAM_KEY); ?> <small class="aam-menu-capability">4 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-ht-d" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-ht-d-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/protect-wordpress-media-library-files-static-pages" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/protect-media-library.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/protect-wordpress-media-library-files-static-pages" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-ht-e-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#ht-features" href="#menu-ht-e" aria-controls="menu-ht-e">
                                    <?php echo __('Redefine Login, Logout, 404 and Access Denied Redirects', AAM_KEY); ?> <small class="aam-menu-capability">2 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-ht-e" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-ht-e-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/redefine-wordpress-login-logout-404-401-redirects" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/redefine-redirects.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/redefine-wordpress-login-logout-404-401-redirects" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-ht-f-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#ht-features" href="#menu-ht-f" aria-controls="menu-ht-f">
                                    <?php echo __('Manage Passwordless Login and Temporary User Accounts', AAM_KEY); ?> <small class="aam-menu-capability">3 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-ht-f" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-ht-f-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/manage-wordpress-passwordless-login-and-temporary-user-accounts" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/passwordless-login-temp-accounts.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/manage-wordpress-passwordless-login-and-temporary-user-accounts" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-ht-g-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#ht-features" href="#menu-ht-g" aria-controls="menu-ht-g">
                                    <?php echo __('Manage JWT Tokens and RESTful API Authentication', AAM_KEY); ?> <small class="aam-menu-capability">5 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-ht-g" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-ht-g-heading">
                            <div class="panel-body">
                                <a href="https://aamportal.com/video/manage-wordpress-jwt-tokens-and-restful-api-authentication" target="_blank">
                                    <img src="<?php echo AAM_MEDIA . '/material/jwt.png'; ?>" width="100%" />
                                </a>

                                <a href="https://aamportal.com/video/manage-wordpress-jwt-tokens-and-restful-api-authentication" target="_blank" class="btn btn-danger btn-block aam-mt-1">Learn More →</a>
                            </div>
                        </div>
                    </div>
                </div>

                <span class="aam-common-use-cases aam-mt-4">Need for Guidance?</span>

                <div class="panel-group" id="support-feature" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="menu-support-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#support-feature" href="#menu-support" aria-controls="menu-support">
                                    <?php echo __('Contact Us', AAM_KEY); ?>
                                </a>
                            </h4>
                        </div>

                        <div id="menu-support" class="panel-collapse collapse" role="tabpanel" aria-labelledby="menu-support-heading">
                            <div class="panel-body">
                                <div class="form-group">
                                    <label>Your Name (optional)</label>
                                    <input type="text" id="support-fullname" class="form-control" placeholder="How should we call you?" />
                                </div>

                                <div class="form-group">
                                    <label>Email Address<span class="aam-asterix">*</span></label>
                                    <input type="email" class="form-control" id="support-email" placeholder="Enter email that we can use to follow-up with you" />
                                </div>

                                <div class="form-group">
                                    <label>Message<span class="aam-asterix">*</span></label>
                                    <textarea class="form-control" id="support-message" rows="7" placeholder="Enter your message..."></textarea>
                                    <small class="text-muted">
                                        <span id="message-countdown">700</span> characters
                                    </small>
                                </div>

                                <p>
                                    <a href="#" class="btn btn-primary" id="send-message-btn" disabled>
                                        <?php echo __('Send the Message', AAM_KEY); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }