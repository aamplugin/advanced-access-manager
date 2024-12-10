<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="identity-content">
        <?php if (AAM::api()->config->get('core.settings.ui.tips')) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <p class="aam-info">
                        <?php $access_level = AAM_Backend_AccessLevel::getInstance(); ?>
                        <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Determine how [%s] can see and manager other users and roles (aka identities). With the premium %sadd-on%s, you have the ability to target all identities at once. To learn more, refer to our official documentation page %shere%s.', 'strong', 'strong'), $access_level->get_display_name(), '<a href="https://aamportal.com/premium?ref=plugin" target="_blank">', '</a>', '<a href="https://aamportal.com/article/users-and-roles-governance?ref=plugin" target="_blank">', '</a>'); ?>
                    </p>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-xs-12" id="identity_list_container">
                <table id="role_identity_list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="85%"><?php echo __('Role Name', AAM_KEY); ?></th>
                            <th width="15%"><?php echo __('Manage', AAM_KEY); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <table id="user_identity_list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th width="85%"><?php echo __('Role Name', AAM_KEY); ?></th>
                            <th width="15%"><?php echo __('Manage', AAM_KEY); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
<?php }