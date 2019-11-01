<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div class="aam-feature" id="post-content">
        <?php if (current_user_can('aam_page_help_tips')) { ?>
            <?php echo apply_filters('aam_posts_terms_help_tips_filter', AAM_Backend_View::getInstance()->loadPartial('posts-terms-help-tips')); ?>
        <?php } ?>

        <?php if ($this->isAllowedToManageCurrentSubject()) { ?>
            <div class="aam-post-breadcrumb">
                <a href="#" data-level="root"><i class="icon-home"></i> <?php echo __('Root', AAM_KEY); ?></a>
            </div>

            <div class="aam-container">
                <table id="post-list" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Link</th>
                            <th width="5%">&nbsp;</th>
                            <th width="75%"><?php echo __('Title', AAM_KEY); ?></th>
                            <th><?php echo __('Actions', AAM_KEY); ?></th>
                            <th>Parent</th>
                            <th>Overwritten</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div class="aam-slide-form aam-access-form">
                    <a href="#" class="btn btn-xs btn-primary post-back btn-right">&Lt; <?php echo __('Go Back', AAM_KEY); ?></a>
                    <span class="aam-clear"></span>
                    <div id="aam-access-form-container"></div>
                    <a href="#" class="btn btn-xs btn-primary post-back">&Lt; <?php echo __('Go Back', AAM_KEY); ?></a>
                </div>
            </div>
        <?php } ?>
    </div>
<?php }