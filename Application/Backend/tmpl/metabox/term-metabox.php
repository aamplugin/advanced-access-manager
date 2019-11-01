<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <tr class="form-field term-access-manager-wrap">
        <th scope="row"><label for="term-access-manager"><?php _e('Access Manager', AAM_KEY); ?></label></th>
        <td>
            <div style="padding: 0px 10px; box-sizing: border-box; background-color: #FFFFFF; width: 95%;">
                <iframe src="<?php echo admin_url('admin.php?page=aam&aamframe=post&id=' . $params->term->term_id . '|' . $params->term->taxonomy . '|' . $params->postType . '&type=term'); ?>" width="100%" height="450" style="border-bottom: 1px solid #e5e5e5; margin-top:10px;"></iframe>
            </div>
        </td>
    </tr>
<?php }