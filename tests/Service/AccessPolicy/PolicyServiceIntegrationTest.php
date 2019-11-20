<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM,
    AAM_Core_Object_Uri,
    AAM_Core_Object_Post,
    AAM_Core_Object_Menu,
    AAM_Core_Object_Route,
    AAM_Core_Object_Policy,
    AAM_Core_Policy_Factory,
    AAM_Core_Object_Toolbar,
    AAM_Core_Object_Metabox,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait;

/**
 * Test access policy integration with core AAM services
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class PolicyServiceIntegrationTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

    /**
     * Test that Access Policy integrates with Admin Menu service
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAdminMenuIntegration()
    {
        $this->preparePlayground('admin-menu');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);

        $this->assertTrue($object->isRestricted('edit.php'));
    }

    /**
     * Test that Access Policy integrates with Toolbar service
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testToolbarIntegration()
    {
        $this->preparePlayground('toolbar');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);

        $this->assertTrue($object->isHidden('about'));
    }

    /**
     * Test that Access Policy integrates with Metaboxes & Widgets service
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testMetaboxIntegration()
    {
        $this->preparePlayground('metabox');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

        $this->assertTrue($object->isHidden('widgets', 'WP_Widget_Pages'));
        $this->assertTrue($object->isHidden('aam_policy', 'revisionsdiv'));
    }

    /**
     * Test that Access Policy integrates with Content service for simple actions
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testContentSimpleActionsIntegration()
    {
        $this->preparePlayground('post-simple-actions');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Post::OBJECT_TYPE, 1);

        $this->assertFalse($object->isAllowedTo('edit'));
        $this->assertFalse($object->isAllowedTo('delete'));
        $this->assertFalse($object->isAllowedTo('publish'));
        $this->assertFalse($object->isAllowedTo('comment'));
    }

    /**
     * Test that Access Policy integrates with Content service for Restricted action
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testContentRestrictedIntegration()
    {
        $this->preparePlayground('post-restricted');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Post::OBJECT_TYPE, 1);

        $this->assertTrue($object->is('restricted'));
    }

    /**
     * Test that Access Policy integrates with Content service for Hidden action
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testContentHiddenIntegration()
    {
        $this->preparePlayground('post-hidden');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Post::OBJECT_TYPE, 1);

        $this->assertTrue($object->is('hidden'));

        // Verify that post is no longer in the list of posts
        $posts = get_posts(array(
            'post_type'        => 'post',
            'fields'           => 'ids',
            'suppress_filters' => false
        ));

        // First, confirm that post is in the array of posts
        $this->assertFalse(in_array(1, $posts));
    }

    /**
     * Test that Access Policy integrates with Content service for Password protected
     * action
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testContentComplexActionsIntegration()
    {
        $this->preparePlayground('post-complex-actions');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Post::OBJECT_TYPE, 1);

        $this->assertTrue($object->is('protected'));
        $this->assertEquals(array(
            'enabled'  => true,
            'password' => '123456'
        ), $object->get('protected'));

        $this->assertTrue($object->has('teaser'));
        $this->assertEquals(array(
            'enabled' => true,
            'message' => 'This is just a teaser message'
        ), $object->get('teaser'));
    }

    /**
     * Test that Access Policy integrates with Content service for Redirected action
     * where page ID is specified
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testContentRedirectPageIdIntegration()
    {
        $this->preparePlayground('post-redirect-page-id');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Post::OBJECT_TYPE, 1);

        $this->assertTrue($object->is('redirected'));
        $this->assertEquals(array(
            'enabled'     => true,
            'type'        => 'page',
            'httpCode'    => 301,
            'destination' => 2
        ), $object->get('redirected'));
    }

    /**
     * Test that Access Policy integrates with Content service for Redirected action
     * where page slug is specified
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testContentRedirectPageSlugIntegration()
    {
        $this->preparePlayground('post-redirect-page-slug');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Post::OBJECT_TYPE, 1);

        $this->assertTrue($object->is('redirected'));
        $this->assertEquals(array(
            'enabled'     => true,
            'type'        => 'page',
            'httpCode'    => 301,
            'destination' => get_page_by_path('sample-page', OBJECT)->ID
        ), $object->get('redirected'));
    }

    /**
     * Test that Access Policy integrates with Content service for Redirected action
     * where URL is specified
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testContentRedirectUrlIntegration()
    {
        $this->preparePlayground('post-redirect-url');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Post::OBJECT_TYPE, 1);

        $this->assertTrue($object->is('redirected'));
        $this->assertEquals(array(
            'enabled'     => true,
            'type'        => 'url',
            'httpCode'    => 307,
            'destination' => 'https://aamplugin.com'
        ), $object->get('redirected'));
    }

    /**
     * Test that Access Policy integrates with Content service for Redirected action
     * where callback is specified
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testContentRedirectCallbackIntegration()
    {
        $this->preparePlayground('post-redirect-callback');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Post::OBJECT_TYPE, 1);

        $this->assertTrue($object->is('redirected'));
        $this->assertEquals(array(
            'enabled'     => true,
            'type'        => 'callback',
            'httpCode'    => 307,
            'destination' => 'AAM\Callback\Main::helloWorld'
        ), $object->get('redirected'));
    }

    /**
     * Test that Access Policy integrates with URI service for all possible permutation
     * of actions
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testUriIntegration()
    {
        $this->preparePlayground('uri');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);

        $this->assertEquals(array(
            'type'   => 'default',
            'action' => null
        ), $object->findMatch('/hello-world-1'));

        $this->assertEquals(array(
            'type'   => 'message',
            'action' => 'Access Is Denied',
            'code'   => 307
        ), $object->findMatch('/hello-world-2/'));

        $this->assertEquals(array(
            'type'   => 'page',
            'action' => 2,
            'code'   => 307
        ), $object->findMatch('/hello-world-3/'));

        $this->assertEquals(array(
            'type'   => 'page',
            'action' => get_page_by_path('sample-page', OBJECT, 'page')->ID,
            'code'   => 307
        ), $object->findMatch('/hello-world-4'));

        $this->assertEquals(array(
            'type'   => 'url',
            'action' => '/another-location',
            'code'   => 303
        ), $object->findMatch('/hello-world-5'));

        $this->assertEquals(array(
            'type'   => 'callback',
            'action' => 'AAM\\Callback\\Main::helloWorld',
            'code'   => 307
        ), $object->findMatch('/hello-world-6'));

        $this->assertEquals(array(
            'type'   => 'login',
            'action' => null,
            'code'   => 401
        ), $object->findMatch('/hello-world-7/'));
    }

    /**
     * Test that Access Policy integrates with API Route service
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRouteIntegration()
    {
        $this->preparePlayground('route');

        $object = AAM::getUser()->getObject(AAM_Core_Object_Route::OBJECT_TYPE);

        $this->assertTrue($object->isRestricted('RESTful', '/posts', 'GET'));
    }

    /**
     * Test ability to toggle the ability activate/deactivate individual plugin with
     * Access Policy
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testSinglePluginIntegration()
    {
        // Making sure that current user can activate/deactivate plugin
        $this->assertTrue(current_user_can('activate_plugin', 'advanced-access-manager'));
        $this->assertTrue(current_user_can('deactivate_plugin', 'advanced-access-manager'));

        $this->preparePlayground('single-plugin');

        // Making sure that current user no longer has these privileges
        $this->assertFalse(current_user_can('activate_plugin', 'advanced-access-manager'));
        $this->assertFalse(current_user_can('deactivate_plugin', 'advanced-access-manager'));
    }

    /**
     * Test ability to toggle the ability activate/deactivate individual plugin with
     * Access Policy
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAllPluginsIntegration()
    {
        // Making sure that current user can perform all 4 basic actions
        $this->assertTrue(current_user_can('install_plugins'));
        $this->assertTrue(current_user_can('update_plugins'));
        $this->assertTrue(current_user_can('edit_plugins'));
        $this->assertTrue(current_user_can('delete_plugins'));

        $this->preparePlayground('plugins');

        // Making sure that current user no longer has these privileges
        $this->assertFalse(current_user_can('install_plugins'));
        $this->assertFalse(current_user_can('update_plugins'));
        $this->assertFalse(current_user_can('edit_plugins'));
        $this->assertFalse(current_user_can('delete_plugins'));
    }

    /**
     * Prepare the environment
     *
     * Update Unit Test access policy with proper policy
     *
     * @param string $policy_file
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function preparePlayground($policy_file)
    {
        global $wpdb;

        // Update existing Access Policy with new policy
        $wpdb->update($wpdb->posts, array('post_content' => file_get_contents(
            __DIR__ . '/policies/' . $policy_file . '.json'
        )), array('ID' => AAM_UNITTEST_ACCESS_POLICY_ID));

        $object = AAM::getUser()->getObject(AAM_Core_Object_Policy::OBJECT_TYPE);
        $this->assertTrue(
            $object->updateOptionItem(AAM_UNITTEST_ACCESS_POLICY_ID, true)->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        // Reset Access Policy Factory cache
        AAM_Core_Policy_Factory::reset();
    }

}