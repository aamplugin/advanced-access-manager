<?php

declare(strict_types=1);

namespace AAM\UnitTest\Service;

use AAM;
use AAM_Backend_Feature_Settings_Security;
use AAM_Service_SecureLogin;
use AAM\UnitTest\Utility\TestCase;

final class SecureLoginTrackingTest extends TestCase
{
    public function testLastLoginTrackingCanBeDisabled(): void
    {
        $user_id = $this->createUser([ 'role' => 'subscriber' ]);
        $user = get_user_by('id', $user_id);
        $config = AAM::api()->config;
        $previous_ip = $_SERVER['REMOTE_ADDR'] ?? null;

        try {
            $config->set(AAM_Service_SecureLogin::TRACK_LAST_LOGIN_CONFIG, true);
            $settings = AAM_Backend_Feature_Settings_Security::getList();
            $this->assertTrue($settings[AAM_Service_SecureLogin::TRACK_LAST_LOGIN_CONFIG]['value']);
            $_SERVER['REMOTE_ADDR'] = '203.0.113.24';
            do_action('wp_login', $user->user_login, $user);

            $time = get_user_meta($user_id, AAM_Service_SecureLogin::LAST_LOGIN_TIME_META, true);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $time);
            $this->assertLessThanOrEqual(5, abs(time() - strtotime($time . ' UTC')));
            $this->assertSame('203.0.113.24', get_user_meta(
                $user_id, AAM_Service_SecureLogin::LAST_LOGIN_IP_META, true
            ));
            $this->assertSame('', get_user_meta(
                $user_id, AAM_Service_SecureLogin::LAST_LOGIN_COUNTRY_META, true
            ));

            $config->set(AAM_Service_SecureLogin::TRACK_LAST_LOGIN_CONFIG, false);
            $settings = AAM_Backend_Feature_Settings_Security::getList();
            $this->assertFalse($settings[AAM_Service_SecureLogin::TRACK_LAST_LOGIN_CONFIG]['value']);
            $_SERVER['REMOTE_ADDR'] = '198.51.100.7';
            do_action('wp_login', $user->user_login, $user);

            $this->assertSame($time, get_user_meta(
                $user_id, AAM_Service_SecureLogin::LAST_LOGIN_TIME_META, true
            ));
            $this->assertSame('203.0.113.24', get_user_meta(
                $user_id, AAM_Service_SecureLogin::LAST_LOGIN_IP_META, true
            ));

            $config->set(AAM_Service_SecureLogin::TRACK_LAST_LOGIN_CONFIG, true);
            update_user_meta($user_id, AAM_Service_SecureLogin::LAST_LOGIN_COUNTRY_META, 'Old country');
            update_user_meta($user_id, AAM_Service_SecureLogin::LAST_LOGIN_CITY_META, 'Old city');
            $_SERVER['REMOTE_ADDR'] = 'not-an-ip';
            do_action('wp_login', $user->user_login, $user);

            $this->assertSame('', get_user_meta(
                $user_id, AAM_Service_SecureLogin::LAST_LOGIN_IP_META, true
            ));
            $this->assertSame('', get_user_meta(
                $user_id, AAM_Service_SecureLogin::LAST_LOGIN_COUNTRY_META, true
            ));
            $this->assertSame('', get_user_meta(
                $user_id, AAM_Service_SecureLogin::LAST_LOGIN_CITY_META, true
            ));
        } finally {
            if ($previous_ip === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $previous_ip;
            }
            $config->reset(AAM_Service_SecureLogin::TRACK_LAST_LOGIN_CONFIG);
        }
    }
}
