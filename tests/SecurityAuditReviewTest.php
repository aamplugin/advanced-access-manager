<?php

/**
 * Standalone review contract: php tests/SecurityAuditReviewTest.php
 */

function wp_json_encode($value) { return json_encode($value); }
function __($text, $domain = null) { return $text; }
function sanitize_textarea_field($value) { return strip_tags($value); }
function get_current_user_id() { return 17; }
function current_user_can($capability, $user_id = null) { return false; }

class AAM_Audit_ApplicationPasswordCheck { const ID = 'application_passwords'; }
class AAM_Audit_HighPrivilegeOrElevatedUserCheck { const ID = 'high_privilege_user'; }
class ReviewTestExecutor
{
    public static function issue_to_message($issue) { return 'Readable finding'; }
    public static function run($current) { return ['is_completed' => true, 'issues' => [$GLOBALS['scan_issue']]]; }
}

class ReviewTestDb
{
    public $values = [];
    public function read($key, $default = null)
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }
    public function write($key, $value, $autoload = true)
    {
        $this->values[$key] = $value;
        return true;
    }
    public function delete($key)
    {
        unset($this->values[$key]);
        return true;
    }
}
class ReviewTestApi
{
    public $db;
    public function __construct() { $this->db = new ReviewTestDb(); }
}
class AAM
{
    public static $api;
    public static function api() { return self::$api; }
}

require __DIR__ . '/../application/Service/BaseTrait.php';
require __DIR__ . '/../application/Service/SecurityAudit.php';

class ReviewTestService extends AAM_Service_SecurityAudit
{
    public function get_steps() { return ['roles' => ['executor' => ReviewTestExecutor::class]]; }
}

function verify($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

AAM::$api = new ReviewTestApi();
$service = (new ReflectionClass(AAM_Service_SecurityAudit::class))->newInstanceWithoutConstructor();
$first = ['code' => 'ROLE_RISK', 'type' => 'critical', 'metadata' => ['role' => 'editor', 'caps' => ['publish_posts', 'delete_others_posts']]];
$same = ['code' => 'ROLE_RISK', 'type' => 'critical', 'metadata' => ['caps' => ['publish_posts', 'delete_others_posts'], 'role' => 'editor']];
$second = ['code' => 'PASSWORD_RISK', 'type' => 'warning', 'metadata' => ['user' => 12]];
$third = ['code' => 'REST_RISK', 'type' => 'notice'];
$first_id = $service->get_issue_id('roles', $first);
$second_id = $service->get_issue_id('roles', $second);
$third_id = $service->get_issue_id('api', $third);
verify($first_id === $service->get_issue_id('roles', $same), 'Metadata key order must not change identity');
verify($first_id !== $service->get_issue_id('other', $first), 'Step must be part of identity');

AAM::$api->db->values[AAM_Service_SecurityAudit::DB_OPTION] = [
    'roles' => ['issues' => [$first, $second]],
    'api' => ['issues' => [$third]]
];
AAM::$api->db->values[AAM_Service_SecurityAudit::DB_SCOPE_OPTION] = 83;

$result = $service->update_reviews('issue', 'resolved', 'roles', $first_id, 'Fixed <b>permissions</b>');
verify($result['count'] === 1, 'One finding should be updated');
verify($result['updated'][$first_id]['note'] === 'Fixed permissions', 'Note must be sanitized');
verify($result['updated'][$first_id]['updated_by'] === 17, 'Reviewer must be recorded');
verify(AAM::$api->db->read(AAM_Service_SecurityAudit::DB_OPTION)['roles']['issues'][0] === $first, 'Raw evidence must remain unchanged');
verify($result['score'] === 93, 'Resolving a critical finding must remove its score penalty');

AAM::$api->db->values[AAM_Service_SecurityAudit::DB_SCOPE_OPTION] = 83;
verify($service->get_score() === 93, 'Reading an old report must repair a stale stored score');

$result = $service->update_reviews('step', 'acknowledged', 'roles', '', 'Reviewed');
verify($result['count'] === 1 && isset($result['updated'][$second_id]), 'Step action should acknowledge only open findings');
verify(AAM::$api->db->read(AAM_Service_SecurityAudit::DB_REVIEW_OPTION)[$first_id]['status'] === 'resolved', 'Bulk acknowledgement must preserve resolutions');
verify($result['score'] === 93, 'Acknowledgement must retain the finding penalty');

$ui = (new ReflectionClass(ReviewTestService::class))->newInstanceWithoutConstructor()->prepare_ui_result(
    'roles', AAM::$api->db->read(AAM_Service_SecurityAudit::DB_OPTION)['roles']
);
verify($ui['issues'][0]['id'] === $first_id, 'UI result must include a stable finding ID');
verify($ui['issues'][0]['review']['status'] === 'resolved', 'UI result must include persisted review');
verify($ui['issues'][0]['message'] === 'Readable finding', 'UI result must retain human-readable messages');

$result = $service->update_reviews('all', 'acknowledged');
verify($result['count'] === 1 && isset($result['updated'][$third_id]), 'All action should include other steps');

try {
    $service->update_reviews('issue', 'acknowledged', 'roles', 'missing');
    throw new RuntimeException('Unknown finding was accepted');
} catch (OutOfRangeException $expected) { }

$service->update_reviews('issue', 'open', 'roles', $second_id);
verify(!isset(AAM::$api->db->read(AAM_Service_SecurityAudit::DB_REVIEW_OPTION)[$second_id]), 'Reopening without a note should clear review state');
verify($service->get_score() === 93, 'An open warning must continue to count');

$duplicate = ['code' => 'ROLE_RISK', 'type' => 'critical', 'metadata' => ['role' => 'subscriber']];
$calculator = new ReflectionMethod(AAM_Service_SecurityAudit::class, '_calculate_score');
verify($calculator->invoke($service, ['roles' => ['issues' => [$first, $duplicate]]], [
    $first_id => ['status' => 'resolved']
]) === 90, 'Another unresolved finding with the same code must retain the penalty');

$reset = new ReflectionMethod(AAM_Service_SecurityAudit::class, '_reset_for_scan');
$reset->invoke($service);
$reviews = AAM::$api->db->read(AAM_Service_SecurityAudit::DB_REVIEW_OPTION);
verify(!isset($reviews[$first_id]), 'Resolved findings must reopen on a new scan');
verify(isset($reviews[$third_id]), 'Acknowledgements should survive a new scan');
verify(AAM::$api->db->read(AAM_Service_SecurityAudit::DB_OPTION) === null, 'New scan should clear the old report');

$GLOBALS['scan_issue'] = $first;
$scan_service = (new ReflectionClass(ReviewTestService::class))->newInstanceWithoutConstructor();
$scan_service->execute('roles', true);
verify($scan_service->get_score() === 90, 'A recurring resolved issue must count after a new scan');
$scan_service->update_reviews('issue', 'resolved', 'roles', $first_id);
verify($scan_service->get_score() === 100, 'Resolving the only scan finding should restore the score');

AAM::$api->db->values[AAM_Service_SecurityAudit::DB_OPTION] = [];
AAM::$api->db->values[AAM_Service_SecurityAudit::DB_SCOPE_OPTION] = 0;
verify($service->has_report(), 'A zero score must still count as an audit report');
verify($service->get_score_grade() === 'Poor', 'A zero score must have the Poor grade');

echo "Security audit review contract passed\n";
