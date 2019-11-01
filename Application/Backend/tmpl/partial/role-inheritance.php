<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="form-group">
        <label><?php echo __('Inherit capabilities from', AAM_KEY); ?></label>
        <select class="form-control inherit-role-list" name="inherit" id="inherit-role">
            <option value=""><?php echo __('Select Role', AAM_KEY); ?></option>
        </select>
    </div>
    <div class="checkbox">
        <label for="clone">
            <input type="checkbox" value="1" id="clone-role" name="clone" />
            <?php echo __('Also clone all AAM access settings (admin menu, metaboxes, redirects, etc.)', AAM_KEY); ?>
        </label>
    </div>
<?php }