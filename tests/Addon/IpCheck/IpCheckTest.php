<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Addon\IpCheck;

use AAM,
    AAM_Service_Content,
    AAM_Core_Object_Post,
    AAM_Framework_Manager,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\AddOn\IPCheck\Object\IPCheck as IPCheckObject;

/**
 * Test cases for the IP Check addon
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class IpCheckTest extends TestCase
{

    use ResetTrait;

    /**
     * Targeting post ID
     *
     * @var int
     *
     * @access protected
     * @version 6.7.0
     */
    protected static $post_id;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Setup a default post
        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Core',
            'post_status' => 'publish'
        ));
    }

    /**
     * Test that entire website is restricted when IP matched
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testEntireWebsiteRestricted()
    {
        // Fake the IP address
        AAM_Framework_Manager::configs()->set_config('geoapi.test_ip', '3.77.207.0');

        $object = AAM::getUser()->getObject(IPCheckObject::OBJECT_TYPE);
        $this->assertTrue($object->updateOptionItem('ip|3.77.207.0', true)->save());

        // Capture the WP Die message
        ob_start();
        do_action('wp');
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Access Denied', $content);

        // Reset WP Query
        AAM_Framework_Manager::configs()->reset_config('geoapi.test_ip');
    }

    /**
     * Test that access is denied based on user IP address
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPageRestrictedByIp()
    {
        $object = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Set restriction
        $this->assertTrue($object->updateOptionItem('selective', array(
            'rules' => array(
                'ip|3.77.207.0' => true,
            ),
            'enabled' => true
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Verify that access is denied by IP address
        AAM_Framework_Manager::configs()->set_config('geoapi.test_ip', '3.77.207.0');

        $post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $result = AAM_Service_Content::getInstance()->isAuthorizedToReadPost($post);
        $this->assertEquals('WP_Error', get_class($result));
        $this->assertEquals(
            'User is unauthorized to access this post. Access Denied.',
            $result->get_error_message()
        );

        // Reset original state
        AAM_Framework_Manager::configs()->reset_config('geoapi.test_ip');
    }

    /**
     * Test that access is denied for wildcard IP address
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPageRestrictedByIpWildcard()
    {
        $object = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Set restriction
        $this->assertTrue($object->updateOptionItem('selective', array(
            'rules' => array(
                'ip|127.0.0.*' => true,
            ),
            'enabled' => true
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Verify that access is denied by IP address
        $_SERVER['REMOTE_ADDR'] = '127.0.0.3';

        $post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $result = AAM_Service_Content::getInstance()->isAuthorizedToReadPost($post);
        $this->assertEquals('WP_Error', get_class($result));
        $this->assertEquals(
            'User is unauthorized to access this post. Access Denied.',
            $result->get_error_message()
        );
    }

    /**
     * Test that access is denied for the IP range
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPageRestrictedByIpRange()
    {
        $object = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Set restriction
        $this->assertTrue($object->updateOptionItem('selective', array(
            'rules' => array(
                'ip|127.0.0.0-20' => true,
            ),
            'enabled' => true
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Verify that access is denied by IP address
        $_SERVER['REMOTE_ADDR'] = '127.0.0.5';

        $post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $result = AAM_Service_Content::getInstance()->isAuthorizedToReadPost($post);
        $this->assertEquals('WP_Error', get_class($result));
        $this->assertEquals(
            'User is unauthorized to access this post. Access Denied.',
            $result->get_error_message()
        );
    }

    /**
     * Test that access is denied by the referred host
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPageRestrictedByHost()
    {
        $object = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Set restriction
        $this->assertTrue($object->updateOptionItem('selective', array(
            'rules' => array(
                'host|example.local' => true,
            ),
            'enabled' => true
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Verify that access is denied by referred host
        $_SERVER['HTTP_REFERER'] = 'https://example.local';

        $post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $result = AAM_Service_Content::getInstance()->isAuthorizedToReadPost($post);
        $this->assertEquals('WP_Error', get_class($result));
        $this->assertEquals(
            'User is unauthorized to access this post. Access Denied.',
            $result->get_error_message()
        );
    }

    /**
     * Test that access is denied by query param
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPageRestrictedByRef()
    {
        $object = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Set restriction
        $this->assertTrue($object->updateOptionItem('selective', array(
            'rules' => array(
                'ref|test' => true,
            ),
            'enabled' => true
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Verify that access is denied by ref
        $_GET['ref'] = 'test';

        $post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $result = AAM_Service_Content::getInstance()->isAuthorizedToReadPost($post);
        $this->assertEquals('WP_Error', get_class($result));
        $this->assertEquals(
            'User is unauthorized to access this post. Access Denied.',
            $result->get_error_message()
        );
    }

    /**
     * Test that cookie with JWT is sent when access to page is granted
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPageAccessCookieSetup()
    {
        $object = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Set restriction
        $this->assertTrue($object->updateOptionItem('selective', array(
            'rules' => array(
                'ip|127.0.0.0-20' => false,
            ),
            'enabled' => true
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Verify that access is denied by IP address
        $_SERVER['REMOTE_ADDR'] = '127.0.0.5';

        $post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $this->assertTrue(
            AAM_Service_Content::getInstance()->isAuthorizedToReadPost($post)
        );

        // Reset WP Query
        unset($_SERVER['REMOTE_ADDR']);
    }

}