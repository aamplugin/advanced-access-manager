<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Toolbar;

use AAM,
    AAM_Core_Object_Toolbar,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait;

/**
 * Test AAM access settings inheritance mechanism for the Toolbar service
 * 
 * Toolbar is available only for authenticated users so no Visitors are tested
 * 
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class SingleRoleInheritanceTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

    /**
     * Test to insure that access settings are stored property on the User level
     * 
     * @return void
     * 
     * @access public
     * @see AAM_Core_Subject_User::updateOption
     * @version 6.0.0
     */
    public function testSaveToolbarOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('new-page', true)->save());

        // Read from the database saved values and assert that we have
        // Array (
        //  index.php => true
        // )
        $option = $user->readOption(AAM_Core_Object_Toolbar::OBJECT_TYPE);
        $this->assertSame(array('new-page' => true), $option);
    }

    /**
     * Test that access settings are inherited from the parent role property
     * 
     * This test is designed to verify that access settings are propagated property
     * when there is only one role assigned to a user. 
     * 
     * @return void
     * 
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceFromSingleRole()
    {
        $user   = AAM::getUser();
        $parent = $user->getParent();
        $object = $parent->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);

        // Make sure that we have parent role defined
        $this->assertEquals('AAM_Core_Subject_Role', get_class($parent));

        // Save access settings for the role and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('new-page', true)->save());

        // Read from the database saved values and assert that we have
        // Array (
        //  index.php => true
        // )
        $option = $parent->readOption(AAM_Core_Object_Toolbar::OBJECT_TYPE);
        $this->assertSame(array('new-page' => true), $option);

        // Finally verify that access settings are propagated property to the User
        // Level
        $menu = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);
        $this->assertSame(array('new-page' => true), $menu->getOption());
    }

    /**
     * Test that access settings are propagated and merged properly
     * 
     * The test is designed to verify that access settings are propagated properly
     * from the parent role and merged well with explicitly defined access settings on
     * the User level.
     * 
     * The expected result is to have combined array of access settings from the parent
     * role and specific user.
     * 
     * @return void
     * 
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceMergeFromSingleRole()
    {
        $user   = AAM::getUser();
        $parent = $user->getParent();

        $object = $parent->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);

        // Save access settings for the role and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('new-page', true)->save());

        // Save access setting for the user and make sure they are saved property
        $menu = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true);
        $this->assertTrue($menu->updateOptionItem('new-post', false)->save());

        // Reset cache and try to kick-in the inheritance mechanism
        $this->_resetSubjects();

        $menu = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);
        $this->assertSame(
            array('new-page' => true, 'new-post' => false),
            $menu->getOption()
        );
    }

    /**
     * Test that the full inheritance mechanism is working as expected
     * 
     * Make sure that access settings are propagated and merged properly from the top
     * (Default Level) to the bottom (User Level).
     * 
     * @return void
     * 
     * @access public
     * @version 6.0.0
     */
    public function testFullInheritanceChainSingeRole()
    {
        $user    = AAM::getUser();
        $role    = $user->getParent();
        $default = $role->getParent();

        $userMenu    = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true);
        $roleMenu    = $role->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true);
        $defaultMenu = $default->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true);

        // Save access settings for all subjects
        $this->assertTrue($userMenu->updateOptionItem('new-post', true)->save());
        $this->assertTrue($roleMenu->updateOptionItem('new-page', true)->save());
        $this->assertTrue($defaultMenu->updateOptionItem('new-media', true)->save());

        // Reset cache and try to kick-in the inheritance mechanism
        $this->_resetSubjects();

        // All settings has to be merged into one array
        $menu = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);
        $this->assertSame(
            array(
                'new-media' => true,
                'new-page' => true,
                'new-post' => true
            ),
            $menu->getOption()
        );
    }

    /**
     * Test that access settings overwrite works as expected
     * 
     * The expected result is lower Access Level overwrite access settings from the
     * higher Access Level.
     * 
     * @return void
     * 
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceOverrideForSingleRole()
    {
        $user   = AAM::getUser();
        $parent = $user->getParent();

        $object = $parent->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);

        // Save access settings for the role and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('new-post', true)->save());

        // Save access setting for the user and make sure they are saved property
        $menu = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true);
        $this->assertTrue($menu->updateOptionItem('new-post', false)->save());

        // Reset cache and try to kick-in the inheritance mechanism
        $this->_resetSubjects();

        $menu = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);
        $this->assertSame(array('new-post' => false), $menu->getOption());
    }

    public function testToolbarRendering()
    {
        $_SERVER['HTTP_HOST'] = 'aamplugin.com';
        $_SERVER['REQUEST_URI'] = '/wp-admin';

        // Restrict access to the Log Out menu and make sure it is not rendered
        $object = AAM::getUser()->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);
        $this->assertTrue($object->updateOptionItem('logout', true)->save());

        ob_start();
        _wp_admin_bar_init();
        wp_admin_bar_render();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(false, strpos($content, "id='wp-admin-bar-logout'"));
    }
    
}