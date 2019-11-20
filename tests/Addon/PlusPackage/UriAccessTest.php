<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Addon\PlusPackage;

use AAM,
    AAM_Service_Uri,
    AAM_Core_Object_Uri,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test URI access enhancement
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class UriAccessTest extends TestCase
{
    use ResetTrait;

    /**
     * Test the wild card URI access rule
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testWildCardMatch()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);
        $result = $object->updateOptionItem('*', array(
            'type'   => 'default',
            'action' => null
        ))->save();

        $this->assertTrue($result);

        // Override the default handlers so we can suppress die exit
        add_filter('wp_die_handler', function() {
            return function($message, $title) {
                _default_wp_die_handler($message, $title, array('exit' => false));
            };
        }, PHP_INT_MAX);
        $_SERVER['REQUEST_URI'] = '/';

        // Reset all internal cache
        $this->_resetSubjects();

        ob_start();
        AAM_Service_Uri::getInstance()->authorizeUri();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Access Denied', $content);
    }

    /**
     * Test the wild card override rule
     *
     * The entire website is denied but only one specific URI is allowed
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testWildCardOverride()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);

        // Deny access ot the entire site
        $this->assertTrue($object->updateOptionItem('*', array(
            'type'   => 'default',
            'action' => null
        ))->save());

        // Allow to only one specific URI
        $this->assertTrue($object->updateOptionItem('/hello-world', array(
            'type'   => 'allow',
            'action' => null
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $match = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE)->findMatch(
            '/hello-world'
        );

        $this->assertEquals($match['type'], 'allow');
    }

}