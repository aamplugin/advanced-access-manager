<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) { ?>
    <div>
        <?php
            if (!empty($params->post->post_content)) {
                $json = htmlspecialchars_decode($params->post->post_content);
            } else {
                $json = AAM_Service_Policies::bootstrap()->get_boilerplate_policy();
            }
        ?>

        <textarea
            id="aam-policy-editor"
            name="aam-policy"
            class="policy-editor"
            style="border: 1px solid #CCCCCC; width: 100%"
            rows="10"
        ><?php echo esc_textarea(stripslashes($json)); ?></textarea>

        <p class="aam-infobox">
            <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('To learn more about Access &amp; Security policy document, please check  [%sAccess &amp; Security Policy%s] page.', 'b'), '<a href="https://aamportal.com/reference/json-access-policy/?ref=plugin" target="_blank">', '</a>'); ?>
        </p>
    </div>
<?php }