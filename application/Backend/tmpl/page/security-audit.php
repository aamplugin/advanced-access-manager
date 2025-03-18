<?php if (defined('AAM_KEY')) { ?>
    <div id="audit-content" class="audit-container">
        <p class="aam-info">
            This automated security scan will conduct a series of checks to verify the integrity of your website's configurations and detect any potential elevated privileges for users and roles.
            Below is a list of all the steps included in the audit. You can expand each item to learn more about its purpose and importance and we strongly advice conducting the automated security audit periodically to catch any potential issues.<br/><br/>
            To learn more about the AAM security scan, refer to the article <a href="https://aamportal.com/article/what-is-aam-security-audit-and-how-it-works" target="_blank">"What is AAM security audit and how it works?"</a>
        </p>
        <a href="#" class="btn btn-lg btn-primary" id="execute_security_audit">
            <?php echo __('Run the Security Scan', AAM_KEY); ?>
        </a>
        <hr />

        <?php
            $has_report = AAM_Service_SecurityAudit::bootstrap()->has_report();
            $report     = AAM_Service_SecurityAudit::bootstrap()->read();
        ?>

        <div class="panel-group" id="audit-checks" role="tablist" aria-multiselectable="true">
            <?php foreach(AAM_Service_SecurityAudit::bootstrap()->get_steps() as $i => $step) { ?>
                <?php
                    $indicator = 'icon-circle-thin text-info aam-security-audit-step';
                    $summary   = '';

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
                                    <a href="<?php echo esc_url($step['article']); ?>" target="_blank"><?php echo __('Learn more', AAM_KEY); ?>.</a>
                                <?php } ?>
                            </p>

                            <table id="issue_list_<?php echo esc_attr($step['step']); ?>" class="table table-striped table-bordered aam-detected-issues <?php echo empty($report[$step['step']]['issues']) ? 'hidden' : ''; ?>">
                                <thead>
                                    <tr>
                                        <th><?php echo __('Detected Issues', AAM_KEY); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($report[$step['step']]['issues'])) {
                                        foreach($report[$step['step']]['issues'] as $issue) {
                                            echo '<tr><td><strong>' . esc_js(strtoupper($issue['type'])) . ':</strong> ' . esc_js($issue['reason']) . '</td></tr>';
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