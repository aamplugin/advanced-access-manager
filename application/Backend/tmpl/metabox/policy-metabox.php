<?php
/**
 * @since 6.9.2 https://github.com/aamplugin/advanced-access-manager/issues/229
 * @since 6.9.1 https://github.com/aamplugin/advanced-access-manager/issues/228
 * @since 6.8.4 https://github.com/aamplugin/advanced-access-manager/issues/212
 * @since 6.2.2 Slightly changed the way errors are displayed
 * @since 6.2.0 Escaping backslashes to avoid issue with JSON validation
 * @since 6.1.1 Removing the backslashes before displaying the policy
 * @since 6.0.0 Initial implementation of the template
 *
 * @version 6.9.2
 */
if (defined('AAM_KEY')) { ?>
    <div>
        <style type="text/css">.aam-alert-danger{border-radius:0;margin:10px 0;color:#a94442;background-color:#f2dede;border-color:#ebccd1;padding:15px;border:1px solid transparent}.aam-infobox{border-left:5px solid #257fad;padding:20px;background-color:#d9edf7;margin-bottom:0}</style>

        <?php
            if (!empty($params->post->post_content)) {
                // Validate the policy
                $validator = new AAM_Core_Policy_Validator(htmlspecialchars_decode($params->post->post_content));
                $errors    = $validator->validate();
            } else {
                $params->post->post_content = AAM_Backend_Feature_Main_Policy::getDefaultPolicy();
                $errors = array();
            }
        ?>

        <div class="aam-alert-danger<?php echo (empty($errors) ? ' hidden' : ''); ?>" id="policy-parsing-error">
            <?php
                $list = array();
                foreach($errors as $error) {
                    $list[] = '<li>- ' . $error . ';</li>';
                }

                if (!empty($list)) {
                    echo '<ul>' . implode('', $list) . '</ul>';
                }
            ?>
        </div>

        <textarea id="aam-policy-editor" name="aam-policy" class="policy-editor" style="border: 1px solid #CCCCCC; width: 100%" rows="10"><?php echo $params->post->post_content; ?></textarea>

        <p class="aam-infobox">
            <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('To learn more about Access &amp; Security policy document, please check  [%sAccess &amp; Security Policy%s] page.', 'b'), '<a href="https://aamportal.com/advanced/access-policy/" target="_blank">', '</a>'); ?>
        </p>
    </div>
<?php }