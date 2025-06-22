<?php /** @version 7.0.6 **/

$perks = array(
    array(
        'title' => __('60-day refund policy', 'advanced-access-manager'),
        'description' => __('Your satisfaction is our top priority. We are so confident in the value our Premium Add-On brings that we offer a 60-day refund policy. Try it, and if it does not meet your expectations, we will gladly refund your purchase—no questions asked.', 'advanced-access-manager')
    ),
    array(
        'title' => __('Prioritized support via email, Zoom, or Telegram', 'advanced-access-manager'),
        'description' => __('Gain peace of mind with our dedicated support team ready to assist you via email, Zoom, or Telegram. Whether you have questions, need guidance, or encounter any issues, our experts are here to ensure you make the most of Advanced Access Manager and keep your WordPress site running smoothly.', 'advanced-access-manager')
    ),
    array(
        'title' => __('Setup granular access to the WordPress backend area', 'advanced-access-manager'),
        'description' => __('Customize user permissions with precision, tailoring access to the WordPress backend area based on your unique requirements. From limiting dashboard access to specific roles to defining nuanced permissions, our Premium Add-On puts you in complete control, enhancing security and efficiency.', 'advanced-access-manager')
    ),
    array(
        'title' => __('Create a private WordPress website', 'advanced-access-manager'),
        'description' => __('Transform your WordPress site into a secure, private environment with ease. Our Premium Add-On empowers you to restrict access to selected users, creating an exclusive space for collaboration, development, or confidential content that remains hidden from unauthorized eyes.', 'advanced-access-manager')
    ),
    array(
        'title' => __('The most sophisticated tools for restricted content', 'advanced-access-manager'),
        'description' => __('Elevate your content control with cutting-edge tools designed for restricted content. Manage who sees what, ensuring that sensitive information or premium content is accessible only to those with the right permissions, providing you with unmatched content security.', 'advanced-access-manager')
    ),
    array(
        'title' => __('Secure WordPress RESTful API', 'advanced-access-manager'),
        'description' => __('Safeguard your WordPress site with our secure RESTful API integration. Our Premium Add-On ensures that API access is protected, reducing the risk of unauthorized access and potential security threats, giving you peace of mind in an increasingly interconnected digital landscape.', 'advanced-access-manager')
    ),
    array(
        'title' => __('IP & Geolocation access controls to the website', 'advanced-access-manager'),
        'description' => __('Enhance your website security by implementing IP and Geolocation access controls. With our Premium Add-On, you have the tools to restrict or grant access based on specific IP addresses or geographic locations, fortifying your website against unauthorized access attempts.', 'advanced-access-manager')
    ),
    array(
        'title' => __('Define multi-level access management with roles', 'advanced-access-manager'),
        'description' => __('Tailor access management to your organizational structure with multi-level roles. Our Premium Add-On enables you to define roles with precision, ensuring that each user has access only to the features and content relevant to their responsibilities, optimizing workflow efficiency and security.', 'advanced-access-manager')
    )
 )
?>

<?php if (defined('AAM_KEY')) { ?>
    <div id="extension-content" class="extension-container">
        <?php $license = AAM_Addon_Repository::get_instance()->get_premium_license_key(); ?>

        <?php if (!empty($license)) { ?>
            <h1><?php echo __('You have premium add-on already installed!', 'advanced-access-manager'); ?></h1>

            <p class="aam-info aam-mt-2 text-larger">
                <?php echo sprintf(__('To manage domain activations or subscription go to your %slicense page%s.', 'advanced-access-manager'), '<a href="https://aamportal.com/license/' . esc_attr($license) . '?ref=plugin" target="_blank">', '</a>'); ?>
            </p>

            <div>
                <h3>FAQs</h3>

                <ul>
                    <li><a href="https://aamportal.com/question/when-can-i-find-changelog-for-premium-addon" target="_blank">Where can I find the changelog for premium add-on?</a></li>
                    <li><a href="http://aamportal.com/question/how-can-i-download-an-invoice-for-my-purchase" target="_blank">How can I download an invoice for my purchase?</a></li>
                    <li><a href="http://aamportal.com/question/how-does-the-pricing-work" target="_blank">How does the pricing work?</a></li>
                </ul>
            </div>
        <?php } else { ?>
            <h1><?php echo __('Upgrade to Premium Risk Free and Enjoy All The Features!', 'advanced-access-manager'); ?></h1>

            <hr />

            <h3><?php echo __('Here is the list of perks that you get with out premium add-on:', 'advanced-access-manager'); ?></h3>

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

            <p class="text-center">
                <a href="https://aamportal.com/premium?ref=plugin" target="_blank" class="btn btn-danger"><?php echo __('Get Premium Addon', 'advanced-access-manager'); ?></a>
            </p>
        <?php } ?>
    </div>
<?php }
