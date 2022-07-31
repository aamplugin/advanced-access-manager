<?php
/**
 * @since 6.8.4 https://github.com/aamplugin/advanced-access-manager/issues/213
 * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/104
 * @since 6.0.0 Initial implementation of the template
 *
 * @version 6.8.4
 **/
?>

<?php if (defined('AAM_KEY')) { ?>
    <tr class="form-field term-access-manager-wrap">
        <th scope="row"><label for="term-access-manager"><?php _e('Access Manager', AAM_KEY); ?></label></th>
        <td>
            <div style="padding: 0px 10px; box-sizing: border-box; background-color: #FFFFFF; width: 95%;">
                <?php
                    AAM_Backend_View_Helper::loadIframe(
                        admin_url('admin.php?page=aam&aamframe=post&id=' . $params->term->term_id . '|' . $params->term->taxonomy . '|' . $params->postType . '&type=term'),
                        'margin-top:10px;',
                        'aam-term-iframe'
                    );
                ?>
            </div>
        </td>
    </tr>
<?php }