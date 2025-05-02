<?php /** @version 7.0.0 **/

if (defined('AAM_KEY')) { ?>
    <div>
        <?php
            $policies = wp_dropdown_pages([
                'post_type'        => $params->post->post_type,
                'exclude_tree'     => $params->post->ID,
                'selected'         => $params->post->post_parent,
                'name'             => 'parent_id',
                'show_option_none' => __( '(no parent)' ),
                'sort_column'      => 'post_title',
                'echo'             => 0,
            ]);
        ?>

        <?php if (!empty($policies)) { ?>
            <p class="post-attributes-label-wrapper parent-id-label-wrapper">
                <label class="post-attributes-label" for="parent_id"><?php echo __('Parent', 'advanced-access-manager'); ?></label>
            </p>
            <?php echo $policies; ?>
		<?php } ?>
        <p class="post-attributes-label-wrapper menu-order-label-wrapper">
            <label class="post-attributes-label" for="menu_order"><?php echo __('Order', 'advanced-access-manager'); ?></label>
        </p>
        <input name="menu_order" type="text" size="4" id="menu_order" value="<?php echo intval($params->post->menu_order); ?>" />
    </div>
<?php }