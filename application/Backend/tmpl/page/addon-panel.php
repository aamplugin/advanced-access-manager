<?php

/**
 * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/341
 * @since 6.9.20 https://github.com/aamplugin/advanced-access-manager/issues/335
 * @since 6.9.5  https://github.com/aamplugin/advanced-access-manager/issues/243
 * @since 6.9.2  https://github.com/aamplugin/advanced-access-manager/issues/229
 * @since 6.8.1  https://github.com/aamplugin/advanced-access-manager/issues/203
 * @since 6.7.5  https://github.com/aamplugin/advanced-access-manager/issues/173
 * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/78
 * @since 6.2.0  Removed expiration date for license to avoid confusion
 * @since 6.0.5  Fixed typo in the license expiration property. Enriched plugin' status display
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.21
 * */

 $perks = array(
    array(
        'title' => __('60-day refund policy', AAM_KEY),
        'description' => __('Your satisfaction is our top priority. We are so confident in the value our Premium Add-On brings that we offer a 60-day refund policy. Try it, and if it does not meet your expectations, we will gladly refund your purchase—no questions asked.', AAM_KEY)
    ),
    array(
        'title' => __('Prioritized support via email, Zoom, or Telegram', AAM_KEY),
        'description' => __('Gain peace of mind with our dedicated support team ready to assist you via email, Zoom, or Telegram. Whether you have questions, need guidance, or encounter any issues, our experts are here to ensure you make the most of Advanced Access Manager and keep your WordPress site running smoothly.', AAM_KEY)
    ),
    array(
        'title' => __('Setup granular access to the WordPress backend area', AAM_KEY),
        'description' => __('Customize user permissions with precision, tailoring access to the WordPress backend area based on your unique requirements. From limiting dashboard access to specific roles to defining nuanced permissions, our Premium Add-On puts you in complete control, enhancing security and efficiency.', AAM_KEY)
    ),
    array(
        'title' => __('Create a private WordPress website', AAM_KEY),
        'description' => __('Transform your WordPress site into a secure, private environment with ease. Our Premium Add-On empowers you to restrict access to selected users, creating an exclusive space for collaboration, development, or confidential content that remains hidden from unauthorized eyes.', AAM_KEY)
    ),
    array(
        'title' => __('The most sophisticated tools for restricted content', AAM_KEY),
        'description' => __('Elevate your content control with cutting-edge tools designed for restricted content. Manage who sees what, ensuring that sensitive information or premium content is accessible only to those with the right permissions, providing you with unmatched content security.', AAM_KEY)
    ),
    array(
        'title' => __('Secure WordPress RESTful API', AAM_KEY),
        'description' => __('Safeguard your WordPress site with our secure RESTful API integration. Our Premium Add-On ensures that API access is protected, reducing the risk of unauthorized access and potential security threats, giving you peace of mind in an increasingly interconnected digital landscape.', AAM_KEY)
    ),
    array(
        'title' => __('IP & Geolocation access controls to the website', AAM_KEY),
        'description' => __('Enhance your website security by implementing IP and Geolocation access controls. With our Premium Add-On, you have the tools to restrict or grant access based on specific IP addresses or geographic locations, fortifying your website against unauthorized access attempts.', AAM_KEY)
    ),
    array(
        'title' => __('Define multi-level access management with roles', AAM_KEY),
        'description' => __('Tailor access management to your organizational structure with multi-level roles. Our Premium Add-On enables you to define roles with precision, ensuring that each user has access only to the features and content relevant to their responsibilities, optimizing workflow efficiency and security.', AAM_KEY)
    )
 )
?>

<?php if (defined('AAM_KEY')) { ?>
    <div id="extension-content" class="extension-container">
        <h1>Upgrade to Premium Risk Free and Enjoy All The Features!</h1>

        <hr />

        <h3>Here is the list of perks that you get with out premium add-on:</h3>

        <div class="panel-group" id="premium-perks" role="tablist" aria-multiselectable="true">
            <?php foreach($perks as $i => $perk) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="menu-perk-<?php echo intval($i); ?>-heading">
                        <h4 class="panel-title">
                            <a
                                role="button"
                                data-toggle="collapse"
                                data-parent="#premium-perks"
                                href="#menu-perk-<?php echo intval($i); ?>"
                                aria-controls="menu-perk-<?php echo intval($i); ?>"
                            >
                                <i class="icon-ok-circled text-success"></i>
                                <?php echo esc_js($perk['title']); ?>
                            </a>
                        </h4>
                    </div>

                    <div
                        id="menu-perk-<?php echo intval($i); ?>"
                        class="panel-collapse collapse"
                        role="tabpanel"
                        aria-labelledby="menu-perk-a-heading"
                    >
                        <div class="panel-body text-larger">
                            <?php echo esc_js($perk['description']); ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php if (!defined('AAM_COMPLETE_PACKAGE')) { ?>
            <p class="text-center">
                <a href="https://aamportal.com/premium?ref=plugin" target="_blank" class="btn btn-danger"><?php echo __('Get Premium Addon', AAM_KEY); ?></a>
            </p>
        <?php } ?>

        <div class="">
            <h3>FAQs</h3>

            <ul>
                <li><a href="https://aamportal.com/question/how-to-install-premium-complete-package-addon" target="_blank">How to install the premium Complete Package add-on?</a></li>
                <li><a href="https://aamportal.com/question/when-can-i-find-changelog-for-premium-addon" target="_blank">Where can I find the changelog for premium Complete Package add-on?</a></li>
                <li><a href="http://aamportal.com/question/how-can-i-download-an-invoice-for-my-purchase" target="_blank">How can I download an invoice for my purchase?</a></li>
                <li><a href="http://aamportal.com/question/how-does-the-pricing-work" target="_blank">How does the pricing work?</a></li>
            </ul>
        </div>
    </div>
<?php }
