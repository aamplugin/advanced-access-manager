<?php /** @version 7.0.0 **/ ?>

<?php if (defined('AAM_KEY')) { ?>
    <div id="audit-content" class="audit-container">
        <p class="aam-info">
            This automated security scan will conduct a series of checks to verify the integrity of your website's configurations and detect any potential elevated privileges for users and roles.
            Below is a list of all the steps included in the audit. You can expand each item to learn more about its purpose and importance and we strongly advice conducting the automated security audit periodically to catch any potential issues.<br/><br/>
            To learn more about the AAM security scan, refer to the article <a href="https://aamportal.com/article/what-is-aam-security-audit-and-how-it-works" target="_blank">"What is AAM security audit and how it works?"</a>
        </p>

        <?php
            $audit_service = AAM_Service_SecurityAudit::bootstrap();
            $has_report    = $audit_service->has_report();
            $has_summary   = $audit_service->has_summary();
            $summary       = $audit_service->get_summary();
            $report        = $audit_service->read();
        ?>

        <a href="#" class="btn btn-lg btn-primary" id="run_security_scan">
            <?php echo __('Run the Security Scan', 'advanced-access-manager'); ?>
        </a>
        <?php if ($has_report) {  ?>
        <a href="#executive_summary" class="btn btn-lg btn-success" role="button" data-toggle="collapse" aria-expanded="false" aria-controls="executive_summary">
            <?php echo __('Get Executive Summary', 'advanced-access-manager'); ?>
        </a>
        <a href="#" class="btn btn-lg btn-info download-latest-report">
            <?php echo __('Download Latest Report', 'advanced-access-manager'); ?>
        </a>
        <?php } ?>

        <hr />

        <div class="collapse" id="executive_summary">
            <div class="well">
                <div id="executive_summary_prompt" class="<?php echo empty($has_summary) ? '' : 'hidden'; ?>">
                    <div class="alert alert-info text-larger">
                        <?php echo __('This service provides an executive-level security overview, helping you strengthen access controls with clear, actionable insights. The data we use is fully anonymized and cannot be linked back to your website.', 'advanced-access-manager'); ?><br/><br/>
                        <?php echo __('To generate this report, we only collect partial audit results and a list of installed plugins — nothing about your server, website, or users is shared.', 'advanced-access-manager'); ?><br/><br/>
                        <?php echo __('This highly aggregated data ensures you get valuable security recommendations without exposing any sensitive information, making it a safe and effective way to assess your website’s security posture.', 'advanced-access-manager'); ?>
                    </div>

                    <a href="#" class="btn aam-mt-2 btn-lg btn-success" id="prepare_executive_summary">
                        <?php echo __('Prepare My Executive Summary', 'advanced-access-manager'); ?>
                    </a>

                    <div class="alert alert-danger text-larger aam-mt-2 hidden" id="executive_summary_error"></div>
                </div>

                <div id="executive_summary_container" class="<?php echo empty($has_summary) ? 'hidden' : ''; ?>">
                    <p class="text-larger alert alert-info" id="executive_summary_overview">
                        <?php echo !empty($summary['summary']) ? stripslashes(esc_js($summary['summary'])) : ''; ?>
                    </p>

                    <hr />

                    <div id="executive_summary_critical" class="<?php echo empty($summary['critical']) ? 'hidden' : ''; ?>">
                        <h3 class="aam-mt-4"><?php echo __('Critical Findings', 'advanced-access-manager'); ?></h3>
                        <ul class="list-of-items">
                            <?php if (!empty($summary['critical'])) { ?>
                                <?php foreach($summary['critical'] as $critical_issue) { ?>
                                <li><?php echo stripslashes(esc_js($critical_issue)); ?></li>
                                <?php } ?>
                            <?php } ?>
                        </ul>
                    </div>

                    <div id="executive_summary_concerns" class="<?php echo empty($summary['concerns']) ? 'hidden' : ''; ?>">
                        <h3 class="aam-mt-4"><?php echo __('Additional Concerns', 'advanced-access-manager'); ?></h3>
                        <ul class="list-of-items">
                            <?php if (!empty($summary['concerns'])) { ?>
                                <?php foreach($summary['concerns'] as $concern) { ?>
                                <li><?php echo stripslashes(esc_js($concern)); ?></li>
                                <?php } ?>
                            <?php } ?>
                        </ul>
                    </div>

                    <div id="executive_summary_recommendations" class="<?php echo empty($summary['recommendations']) ? 'hidden' : ''; ?>">
                        <h3 class="aam-mt-4"><?php echo __('Recommendations', 'advanced-access-manager'); ?></h3>
                        <ul class="list-of-items">
                            <?php if (!empty($summary['recommendations'])) { ?>
                                <?php foreach($summary['recommendations'] as $recommendation) { ?>
                                <li><?php echo stripslashes(esc_js($recommendation)); ?></li>
                                <?php } ?>
                            <?php } ?>
                        </ul>
                    </div>

                    <div id="executive_summary_references" class="<?php echo empty($summary['references']) ? 'hidden' : ''; ?>">
                        <h3 class="aam-mt-4"><?php echo __('References', 'advanced-access-manager'); ?></h3>
                        <ul class="list-of-items">
                            <?php if (!empty($summary['references'])) { ?>
                                <?php foreach($summary['references'] as $reference) { ?>
                                <li><a href="<?php echo esc_attr($reference); ?>" target="_blank"><?php echo esc_js($reference); ?></a></li>
                                <?php } ?>
                            <?php } ?>
                        </ul>
                    </div>

                    <p class="text-larger aam-mt-4 aam-info text-left">
                        <strong><?php echo __('Need help interpreting your security scan report and identifying the next steps to address critical issues?', 'advanced-access-manager'); ?></strong>
                        <?php echo __('Schedule a free 30-minute consultation with us, and we’ll work with you to find the best possible solution tailored to your specific WordPress website requirements.', 'advanced-access-manager'); ?><br/><br/>
                        <a href="https://aamportal.com/consultation/security-audit" target="_blank" class="btn btn-primary"><?php echo __('Schedule a Consultation', 'advanced-access-manager'); ?></a>
                    </p>
                </div>
            </div>
        </div>

        <?php if (empty($has_summary) && !empty($has_report)) { ?>
        <div class="alert alert-info text-larger aam-mb-2">
            <strong><?php echo __('What\'s next?', 'advanced-access-manager'); ?></strong><br/>
            <ol class="list-of-items">
                <li><?php echo __('Review your scan results below. Click each item for details.', 'advanced-access-manager'); ?></li>
                <li><?php echo __('Get the executive summary with the list of highlighted issues and recommendations to mitigate them.', 'advanced-access-manager'); ?></li>
            </ol>
        </div>
        <?php } ?>

        <div class="panel-group" id="audit-checks" role="tablist" aria-multiselectable="true">
            <?php foreach(AAM_Service_SecurityAudit::bootstrap()->get_steps() as $i => $step) { ?>
                <?php
                    $indicator = 'icon-circle-thin text-info aam-security-audit-step';
                    $summary   = '';
                    $executor  = $step['executor'];

                    // Determine the icon
                    if (!empty($report[$step['step']]['is_completed'])) {
                        $status_check = $report[$step['step']]['check_status'];

                        if ($status_check === 'ok') {
                            $indicator = 'icon-ok-circled text-success aam-security-audit-step';
                        } else if ($status_check === 'critical') {
                            $indicator = 'icon-cancel-circled text-danger aam-security-audit-step';
                        } else if ($status_check === 'warning') {
                            $indicator = 'icon-attention-circled text-warning aam-security-audit-step';
                        } else if ($status_check === 'notice') {
                            $indicator = 'icon-info-circled text-info aam-security-audit-step';
                        }

                        $totals = [];

                        foreach($report[$step['step']]['issues'] as $issue) {
                            if (!isset($totals[$issue['type']])) {
                                $totals[$issue['type']] = 0;
                            }
                            $totals[$issue['type']]++;
                        }

                        $aggregated = [];

                        foreach($totals as $type => $count) {
                            array_push(
                                $aggregated,
                                $count . ' ' . $type . ($count === 1 ? '' : 's')
                            );
                        }

                        $summary .= ' - <b>DONE ' . (!empty($totals) ? '(' . implode(', ', $aggregated) . ')' : '(OK)' ) . '</b>';
                    }
                ?>
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
                                    class="<?php echo esc_attr($indicator); ?>"
                                    data-step="<?php echo esc_attr($step['step']); ?>"
                                ></i>
                                <span
                                    data-title="<?php echo esc_attr($step['title']); ?>"
                                    id="check_<?php echo esc_attr($step['step']); ?>_status"
                                    class="aam-check-status"
                                ><?php echo esc_js($step['title']) . $summary; ?></span>
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
                                    <a href="<?php echo esc_url($step['article']); ?>" target="_blank">
                                        <?php echo __('Learn more', 'advanced-access-manager'); ?>.
                                    </a>
                                <?php } ?>
                            </p>

                            <table
                                id="issue_list_<?php echo esc_attr($step['step']); ?>"
                                class="table table-striped table-bordered aam-detected-issues <?php echo empty($report[$step['step']]['issues']) ? 'hidden' : ''; ?>"
                            >
                                <thead>
                                    <tr>
                                        <th><?php echo __('Detected Issues', 'advanced-access-manager'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($report[$step['step']]['issues'])) {
                                        foreach($report[$step['step']]['issues'] as $issue) {
                                            echo '<tr><td><strong>' . esc_js(strtoupper($issue['type'])) . ':</strong> ' . esc_js(call_user_func("{$executor}::issue_to_message", $issue)) . '</td></tr>';
                                        }
                                    } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
<?php }