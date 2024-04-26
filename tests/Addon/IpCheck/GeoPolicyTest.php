<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Addon\IpCheck;

use AAM,
    AAM_Core_Object_Post,
    AAM_Core_Policy_Token,
    AAM_Core_Object_Policy,
    AAM_Core_Policy_Factory,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    PHPUnit\Framework\Attributes\DataProvider;

/**
 * Access Policy integration
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class GeoPolicyTest extends TestCase
{
    use ResetTrait;

    protected static $post_id;
    protected static $policy_id;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Define a dummy IP2Address adapter
        AAM::api()->updateConfig('geoapi.adapter', 'unittest');
        AAM::api()->updateConfig('core.service.geo-lookup.enabled', true);

        add_filter('aam_ipcheck_adapter_filter', function($adapter, $name) {
            if ($name === 'unittest') {
                $adapter = GeoAdapter::getInstance();
            }

            return $adapter;
        }, 10, 2);

        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Sample Post',
            'post_name'   => 'ipcheck-package-post',
            'post_status' => 'publish'
        ));

        self::$policy_id = wp_insert_post(array(
            'post_title'  => 'Unittest Policy Placeholder',
            'post_status' => 'publish',
            'post_type'   => 'aam_policy'
        ));
    }

    /**
     * @inheritdoc
     */
    private static function _tearDownAfterClass()
    {
        // Unset the forced user
        wp_set_current_user(0);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testAccessDeniedToPostByCountry()
    {
        AAM::api()->updateConfig('geoapi.test_ip', '98.26.4.6');

        $this->preparePlayground('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:ipcheck-package-post",
                "Action": "Read",
                "Condition": {
                    "Equals": {
                        "${GEO.country_code}": "US"
                    }
                }
            }
        }');

        $post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $this->assertFalse($post->isAllowedTo('read'));
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testAccessAllowedToPostByCountry()
    {
        AAM::api()->updateConfig('geoapi.test_ip', '109.177.0.0');

        $this->preparePlayground('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:ipcheck-package-post",
                "Action": "Read",
                "Condition": {
                    "Equals": {
                        "${GEO.country_code}": "US"
                    }
                }
            }
        }');

        $post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $this->assertTrue($post->isAllowedTo('read'));
    }

    /**
     * Undocumented function
     *
     * @param string $property
     * @param mixed  $expected
     *
     * @dataProvider geoProperties
     * @return void
     */
    public function testInformationRetrieval($property, $expected)
    {
        AAM::api()->updateConfig('geoapi.test_ip', '98.26.4.6');
        AAM::api()->updateConfig('geoapi.adapter', 'unittest');
        AAM::api()->updateConfig('core.service.geo-lookup.enabled', true);

        $token = '${GEO.' . $property . '}';

        $this->assertEquals(
            $expected,
            AAM_Core_Policy_Token::evaluate($token, [$token])
        );
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function geoProperties()
    {
        return [
            ['country_name', 'United States'],
            ['country_code', 'US'],
            ['continent_code', 'NA'],
            ['region_name', 'South Carolina'],
            ['city', 'Marion'],
            ['latitude', 34.156],
            ['longitude', -79.3906],
        ];
    }

    /**
     * Prepare the environment
     *
     * Update Unit Test access policy with proper policy
     *
     * @param string $policy
     *
     * @return void
     *
     * @access protected
     */
    protected function preparePlayground($policy)
    {
        global $wpdb;

        // Update existing Access Policy with new policy
        $wpdb->update(
            $wpdb->posts,
            array('post_content' => $policy),
            array('ID' => self::$policy_id)
        );

        $object = AAM::getUser()->getObject(AAM_Core_Object_Policy::OBJECT_TYPE);
        $this->assertTrue(
            $object->updateOptionItem(self::$policy_id, true)->save()
        );

        // Reset Access Policy Factory cache
        AAM_Core_Policy_Factory::reset();
    }

}