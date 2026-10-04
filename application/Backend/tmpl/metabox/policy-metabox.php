<?php /** @version 7.0.6 **/

if (defined('AAM_KEY')) {
    $json = !empty($params->post->post_content)
        ? htmlspecialchars_decode($params->post->post_content)
        : AAM_Service_Policies::bootstrap()->get_boilerplate_policy();
    ?>
    <div class="aam-policy-document">
        <div class="aam-policy-document-intro">
            <span class="aam-policy-document-icon dashicons dashicons-editor-code" aria-hidden="true"></span>
            <div class="aam-policy-document-copy">
                <strong><?php esc_html_e('Write the access rules', 'advanced-access-manager'); ?></strong>
                <p><?php esc_html_e('Define resources and actions in JSON, then save the policy when the rules are ready.', 'advanced-access-manager'); ?></p>
            </div>
            <div class="aam-policy-document-intro-actions">
                <button type="button" id="aam-policy-guide-toggle" aria-controls="aam-policy-quick-guide" aria-expanded="true">
                    <span id="aam-policy-guide-toggle-icon" class="dashicons dashicons-hidden" aria-hidden="true"></span>
                    <span id="aam-policy-guide-toggle-label"><?php esc_html_e('Hide guide', 'advanced-access-manager'); ?></span>
                </button>
                <a href="https://aamportal.com/reference/json-access-policy/" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Policy reference', 'advanced-access-manager'); ?> <span aria-hidden="true">↗</span>
                </a>
            </div>
        </div>
        <div class="aam-policy-document-layout">
            <div class="aam-policy-document-main">
                <div class="aam-policy-document-toolbar">
                    <label for="aam-policy-editor"><?php esc_html_e('Policy JSON', 'advanced-access-manager'); ?></label>
                    <button type="button" class="button button-secondary" id="aam-policy-format">
                        <span class="dashicons dashicons-editor-alignleft" aria-hidden="true"></span>
                        <?php esc_html_e('Format JSON', 'advanced-access-manager'); ?>
                    </button>
                </div>
                <textarea id="aam-policy-editor" name="aam-policy" class="policy-editor" rows="20" spellcheck="false" aria-describedby="aam-policy-status"><?php echo esc_textarea($json); ?></textarea>
                <div id="aam-policy-status" class="aam-policy-document-status" role="status" aria-live="polite"></div>
            </div>
            <aside id="aam-policy-quick-guide" class="aam-policy-document-guide" aria-label="<?php esc_attr_e('Policy writing guide', 'advanced-access-manager'); ?>">
                <span class="aam-policy-document-guide-label"><?php esc_html_e('A QUICK GUIDE', 'advanced-access-manager'); ?></span>
                <h3><?php esc_html_e('Start with a statement', 'advanced-access-manager'); ?></h3>
                <p><?php esc_html_e('A policy contains a Statement object or an array of statements. Each statement names an effect and resource; actions depend on the resource.', 'advanced-access-manager'); ?></p>
                <ol>
                    <li><strong>Effect</strong> — <?php esc_html_e('allow or deny access', 'advanced-access-manager'); ?></li>
                    <li><strong>Resource</strong> — <?php esc_html_e('what the rule applies to', 'advanced-access-manager'); ?></li>
                    <li><strong>Action</strong> — <?php esc_html_e('the operation to control, when applicable', 'advanced-access-manager'); ?></li>
                </ol>
                <div class="aam-policy-document-example">
                    <div><strong><?php esc_html_e('Example: protect one page', 'advanced-access-manager'); ?></strong><button type="button" id="aam-policy-copy-example" aria-label="<?php esc_attr_e('Copy example policy', 'advanced-access-manager'); ?>" title="<?php esc_attr_e('Copy example policy', 'advanced-access-manager'); ?>"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button></div>
                    <pre id="aam-policy-example-code">{
  "Statement": {
    "Effect": "deny",
    "Resource": "Post:page:members-only",
    "Action": "Read"
  }
}</pre>
                    <small id="aam-policy-copy-status" aria-live="polite"><?php esc_html_e('Copy and adapt the resource before using it.', 'advanced-access-manager'); ?></small>
                </div>
                <div class="aam-policy-document-links">
                    <a href="https://aamportal.com/reference/json-access-policy/overview/statement" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Statements', 'advanced-access-manager'); ?> ↗</a>
                    <a href="https://aamportal.com/reference/json-access-policy/resource-action/post.html" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Post resources & actions', 'advanced-access-manager'); ?> ↗</a>
                    <a href="https://aamportal.com/reference/json-access-policy/overview/condition" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Conditions', 'advanced-access-manager'); ?> ↗</a>
                </div>
                <h3><?php esc_html_e('Abilities & MCP', 'advanced-access-manager'); ?></h3>
                <p><?php esc_html_e('Use an ability name, MCP server ID, or a server ID and tool name. Action is optional; Access is the only supported action.', 'advanced-access-manager'); ?></p>
                <ul>
                    <li><code>Ability:vendor/action</code></li>
                    <li><code>MCPServer:server-id</code></li>
                    <li><code>MCPTool:server-id:tool-name</code></li>
                </ul>
                <p><?php esc_html_e('The premium add-on also supports Ability:* and MCPServer:* for default access.', 'advanced-access-manager'); ?></p>
            </aside>
        </div>
    </div>
<?php }
