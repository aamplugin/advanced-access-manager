<?php /** @version 6.7.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="jwtnetwork-content">
        <?php $subject = AAM_Backend_Subject::getInstance(); ?>

        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo sprintf(AAM_Backend_View_Helper::preparePhrase('Manage a list of valid JWT claimed network site(s) for [%s] account.'), AAM_Backend_Subject::getInstance()->getName()); ?>
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <table id="jwt-list" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Site</th>
                        <th width="10%">URL</th>
                        <th width="45%">Role</th>
                        <th><?php echo __('Actions', AAM_KEY); ?></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="delete-jwt-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo __('Close', AAM_KEY); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo __('Delete Site of User', AAM_KEY); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p class="alert alert-danger text-larger"><?php echo __('You are about to delete sites and roles of a token. Any application or user that has this token, will no longer be able to use it. Please confirm.') ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="jwt-delete-btn"><?php echo __('Delete', AAM_KEY); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', AAM_KEY); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }