<?php if (defined('AAM_KEY')) { ?>
    <div id="audit-content" class="audit-container">
        <h1><?php echo __('Security Scan', AAM_KEY); ?></h1>

        <p class="aam-info">
            This automated security scan will conduct a series of checks to verify the integrity of your website's configurations and detect any potential elevated privileges for users and roles.
            Below is a list of all the steps included in the audit. You can expand each item to learn more about its purpose and importance and we strongly advice conducting the automated security audit periodically to catch any potential issues.<br/><br/>
            To learn more about the AAM security scan, refer to the article <a href="https://aamportal.com/article/what-is-aam-security-audit-and-how-it-works" target="_blank">"What is AAM security audit and how it works?"</a>
        </p>
        <a href="#" class="btn btn-success" id="execute_security_audit">Run the Security Scan</a>
        <hr />

        <?php $has_report = AAM_Service_SecurityAudit::bootstrap()->has_report(); ?>

        <div class="panel-group" id="audit-checks" role="tablist" aria-multiselectable="true">
            <?php foreach(AAM_Service_SecurityAudit::bootstrap()->get_steps() as $i => $step) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="audit-check-<?php echo esc_attr($i); ?>-heading">
                        <h4 class="panel-title">
                            <a
                                role="button"
                                data-toggle="collapse"
                                data-parent="#audit-checks"
                                href="#audit-check-<?php echo esc_attr($i); ?>"
                                aria-controls="audit-check-<?php echo esc_attr($i); ?>"
                            >
                                <i
                                    class="icon-circle-thin text-info aam-security-audit-step"
                                    data-step="<?php echo esc_attr($step['step']); ?>"
                                ></i>
                                <span
                                    data-title="<?php echo esc_attr($step['title']); ?>"
                                    id="check_<?php echo esc_attr($step['step']); ?>_status"
                                    class="aam-check-status"
                                ><?php echo esc_js($step['title']); ?></span>
                            </a>
                        </h4>
                    </div>

                    <div
                        id="audit-check-<?php echo esc_attr($i); ?>"
                        class="panel-collapse collapse"
                        role="tabpanel"
                        aria-labelledby="audit-check-heading"
                    >
                        <div class="panel-body">
                            <p class="aam-highlighted text-larger">
                                <?php echo esc_js($step['description']); ?>
                                <?php if (!empty($step['article'])) { ?>
                                    <a href="<?php echo esc_url($step['article']); ?>" target="_blank"><?php echo __('Learn more', AAM_KEY); ?>.</a>
                                <?php } ?>
                            </p>

                            <table id="issue_list_<?php echo esc_attr($step['step']); ?>" class="table table-striped table-bordered hidden aam-detected-issues">
                                <thead>
                                    <tr>
                                        <th><?php echo __('Detected Issues', AAM_KEY); ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <div class="<?php echo $has_report ? '' : 'hidden '; ?>text-right" id="download_report_container">
            <hr />
            <a href="#" class="btn btn-primary download-latest-report"><?php echo __('Download Latest Report', AAM_KEY); ?></a>
        </div>
    </div>
<?php }
