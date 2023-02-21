<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="checkbox">
        <label for="clone">
            <input type="checkbox" id="clone-role" name="clone_role_settings" />
            <?php echo __('Also clone all AAM access settings from selected role (admin menu, metaboxes, redirects, etc.)', AAM_KEY); ?>
        </label>
    </div>
<?php }