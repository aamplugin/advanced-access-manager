<?php /** @version 7.0.6 **/

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
        ><?php echo esc_textarea($json); ?></textarea>
        <style>#aam_policy .inside { padding: 0; margin: 0 } .CodeMirror-lines { padding: 0; }</style>
    </div>
<?php }