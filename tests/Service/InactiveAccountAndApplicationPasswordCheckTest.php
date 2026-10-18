<?php

declare(strict_types=1);

namespace AAM\UnitTest\Service;

use AAM;
use AAM_Audit_InactiveAccountAndApplicationPasswordCheck;
use AAM_Service_Core;
use AAM_Service_SecurityAudit;
use AAM_Service_SecureLogin;
use AAM\UnitTest\Utility\TestCase;
use WP_Application_Passwords;

final class InactiveAccountAndApplicationPasswordCheckTest extends TestCase
{
    public function testPaginatedScanHonorsActivityAndRotationThresholds(): void
    {
        $old = time() - 90 * DAY_IN_SECONDS;
        $aging = time() - 200 * DAY_IN_SECONDS;
        $recent = time() - DAY_IN_SECONDS;
        $inactive_id = $this->createUser([
            'user_registered' => gmdate('Y-m-d H:i:s', $old)
        ]);
        $untracked_id = $this->createUser([
            'user_registered' => gmdate('Y-m-d H:i:s', $old)
        ]);
        $recent_id = $this->createUser([
            'user_registered' => gmdate('Y-m-d H:i:s', $recent)
        ]);
        $new_untracked_id = $this->createUser([
            'user_registered' => gmdate('Y-m-d H:i:s', $recent)
        ]);
        $api_active_id = $this->createUser([
            'user_registered' => gmdate('Y-m-d H:i:s', $old)
        ]);

        update_user_meta(
            $inactive_id,
            AAM_Service_SecureLogin::LAST_ACCESS_TIME_META,
            gmdate('Y-m-d H:i:s', $old)
        );
        update_user_meta(
            $recent_id,
            AAM_Service_SecureLogin::LAST_ACCESS_TIME_META,
            gmdate('Y-m-d H:i:s', $recent)
        );
        update_user_meta($inactive_id, WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS, [
            [
                'uuid'      => wp_generate_uuid4(),
                'name'      => 'Old integration',
                'created'   => $old,
                'last_used' => $old
            ],
            [
                'uuid'      => wp_generate_uuid4(),
                'name'      => 'Active integration',
                'created'   => $aging,
                'last_used' => $recent
            ]
        ]);
        update_user_meta($untracked_id, WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS, [
            [
                'uuid'      => wp_generate_uuid4(),
                'name'      => 'Never used integration',
                'created'   => $old,
                'last_used' => null
            ]
        ]);
        update_user_meta($api_active_id, WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS, [
            [
                'uuid'      => wp_generate_uuid4(),
                'name'      => 'Recent API activity',
                'created'   => $old,
                'last_used' => $recent
            ]
        ]);

        $batch_filter = static function() { return 2; };
        add_filter('aam_security_audit_inactive_batch_size_filter', $batch_filter);
        $previous_tracker = get_option(
            AAM_Service_SecureLogin::TRACKER_STARTED_OPTION, null
        );
        update_option(AAM_Service_SecureLogin::TRACKER_STARTED_OPTION, $old, true);

        try {
            $service = AAM_Service_SecurityAudit::get_instance();
            $step = AAM_Audit_InactiveAccountAndApplicationPasswordCheck::ID;
            $result = $service->execute($step, true);

            $this->assertFalse($result['is_completed']);
            $this->assertSame(2, $result['offset']);

            for ($page = 0; !$result['is_completed'] && $page < 100; $page++) {
                $result = $service->execute($step);
            }

            $this->assertTrue($result['is_completed']);
            $this->assertSame('warning', $result['check_status']);

            $codes = [];
            foreach ($result['issues'] as $issue) {
                $id = (int) ($issue['metadata']['id'] ?? 0);
                if (in_array($id, [
                    $inactive_id, $untracked_id, $recent_id,
                    $new_untracked_id, $api_active_id
                ], true)) {
                    $codes[$id][] = $issue['code'];
                }
            }

            $this->assertEqualsCanonicalizing([
                'INACTIVE_USER_ACCOUNT', 'INACTIVE_APPLICATION_PASSWORD',
                'LONG_LIVED_APPLICATION_PASSWORD'
            ], $codes[$inactive_id]);
            $this->assertEqualsCanonicalizing([
                'NO_RECORDED_ACCOUNT_ACTIVITY', 'UNUSED_APPLICATION_PASSWORD'
            ], $codes[$untracked_id]);
            $this->assertArrayNotHasKey($recent_id, $codes);
            $this->assertArrayNotHasKey($new_untracked_id, $codes);
            $this->assertArrayNotHasKey($api_active_id, $codes);

            $shareable = AAM_Audit_InactiveAccountAndApplicationPasswordCheck::issues_to_shareable($result);
            $this->assertStringNotContainsString(
                'Old integration', wp_json_encode($shareable)
            );
            $this->assertStringNotContainsString(
                'Active integration', wp_json_encode($shareable)
            );

            $rotation_issue = current(array_filter($result['issues'], static function($issue) {
                return $issue['code'] === 'LONG_LIVED_APPLICATION_PASSWORD';
            }));
            $this->assertSame('Active integration', $rotation_issue['metadata']['password_name']);
            $this->assertStringContainsString(
                'rotation threshold',
                AAM_Audit_InactiveAccountAndApplicationPasswordCheck::issue_to_message($rotation_issue)
            );

            AAM::api()->config->set(
                'service.security_audit.inactive_user_account_threshold', 120
            );
            AAM::api()->config->set(
                'service.security_audit.inactive_application_password_threshold', 120
            );
            AAM::api()->config->set(
                'service.security_audit.application_password_ttl', 240
            );

            $result = $service->execute($step, true);
            for ($page = 0; !$result['is_completed'] && $page < 100; $page++) {
                $result = $service->execute($step);
            }

            $this->assertTrue($result['is_completed']);
            foreach ($result['issues'] as $issue) {
                $this->assertNotContains(
                    (int) ($issue['metadata']['id'] ?? 0),
                    [
                        $inactive_id, $untracked_id, $recent_id,
                        $new_untracked_id, $api_active_id
                    ]
                );
            }
        } finally {
            remove_filter('aam_security_audit_inactive_batch_size_filter', $batch_filter);
            if ($previous_tracker === null) {
                delete_option(AAM_Service_SecureLogin::TRACKER_STARTED_OPTION);
            } else {
                update_option(
                    AAM_Service_SecureLogin::TRACKER_STARTED_OPTION,
                    $previous_tracker,
                    true
                );
            }
            AAM::api()->config->reset('service.security_audit.inactive_user_account_threshold');
            AAM::api()->config->reset('service.security_audit.inactive_application_password_threshold');
            AAM::api()->config->reset('service.security_audit.application_password_ttl');
        }
    }

    public function testTrackerStartIsAutoloadedAndResetWithAamSettings(): void
    {
        $option = AAM_Service_SecureLogin::TRACKER_STARTED_OPTION;
        $config = AAM::api()->config;
        $initialize = new \ReflectionMethod(
            AAM_Service_SecureLogin::class, '_initialize_tracker'
        );
        $service = AAM_Service_SecureLogin::get_instance();
        delete_option($option);

        try {
            $config->set('service.secure_login.track_last_access', false);
            $initialize->invoke($service);
            $this->assertFalse(get_option($option));

            $config->set('service.secure_login.track_last_access', true);
            $before = time();
            $initialize->invoke($service);
            $started = get_option($option);
            $this->assertGreaterThanOrEqual($before, $started);
            $this->assertLessThanOrEqual(time(), $started);
            $this->assertArrayHasKey($option, wp_load_alloptions(true));

            update_option($option, $before - 90 * DAY_IN_SECONDS, true);
            $initialize->invoke($service);
            $this->assertSame(
                $before - 90 * DAY_IN_SECONDS, get_option($option)
            );

            AAM_Service_Core::get_instance()->reset();
            $this->assertFalse(get_option($option));

            $untracked_id = $this->createUser([
                'user_registered' => gmdate(
                    'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS
                )
            ]);
            $scan = AAM_Audit_InactiveAccountAndApplicationPasswordCheck::run();
            $this->assertTrue($scan['is_completed']);
            $account_issues = array_filter(
                $scan['issues'] ?? [],
                static function($issue) use ($untracked_id) {
                    return (int) ($issue['metadata']['id'] ?? 0) === $untracked_id
                        && $issue['code'] === 'NO_RECORDED_ACCOUNT_ACTIVITY';
                }
            );
            $this->assertSame([], array_values($account_issues));
        } finally {
            delete_option($option);
            $config->reset('service.secure_login.track_last_access');
        }
    }
}
