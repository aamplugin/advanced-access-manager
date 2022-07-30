<?php
    /**
     * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/104
     * @since 6.0.0 Initial implementation of the template
     *
     * @version 6.5.0
     * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <tr class="form-field term-access-manager-wrap">
        <th scope="row"><label for="term-access-manager"><?php _e('Access Manager', AAM_KEY); ?></label></th>
        <td>
            <div style="padding: 0px 10px; box-sizing: border-box; background-color: #FFFFFF; width: 95%;">
                <iframe src="<?php echo admin_url('admin.php?page=aam&aamframe=post&id=' . $params->term->term_id . '|' . $params->term->taxonomy . '|' . $params->postType . '&type=term'); ?>" width="100%" id="aam-iframe" style="margin-top:10px;"></iframe>
                <script><?php echo file_get_contents(AAM_BASEDIR . '/media/js/iframe-resizer.js'); ?></script>
            </div>
        </td>
    </tr>
<?php }