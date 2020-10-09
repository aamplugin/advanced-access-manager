<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Addon\PlusPackage;

use AAM,
    WP_REST_Request,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\AddOn\PlusPackage\Object\Term;

/**
 * Test cases for the Plus Package term access management
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class TermRESTfulAccessTest extends TestCase
{
    use ResetTrait;

    protected static $post_id;
    protected static $top_term_id;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Sample Post',
            'post_name'   => 'plus-package-post',
            'post_status' => 'publish'
        ));

        self::$top_term_id = wp_insert_term('Category', 'category', array(
            'slug' => 'plus-package-category'
        ))['term_id'];
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
     * Test that term is hidden while going through RESTful API endpoint
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testVisibilityTermDirectly()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            Term::OBJECT_TYPE, self::$top_term_id . '|category'
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('term/hidden', true)->save());
        $this->_resetSubjects();

        $server = rest_get_server();

        // Verify that term is no longer in the list of terms
        $request = new WP_REST_Request('GET', '/wp/v2/categories');
        $request->set_param('context', 'view');

        $data = $server->dispatch($request)->get_data();

        // First, confirm that term is not in the array of terms
        $this->assertCount(0, array_filter($data, function($term) {
            return $term['id'] === self::$top_term_id;
        }));
    }

    /**
     * Test that term is restricted while going through RESTful API endpoint
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRestrictedTermDirectly()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            Term::OBJECT_TYPE, self::$top_term_id . '|category'
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('term/restricted', true)->save());

        $server = rest_get_server();

        // Verify that term is no longer in the list of terms
        $request = new WP_REST_Request(
            'GET', '/wp/v2/categories/' . self::$top_term_id
        );
        $request->set_param('context', 'view');

        $response = $server->dispatch($request);

        $this->assertEquals(401, $response->get_status());
        $this->assertEquals('term_access_restricted', $response->get_data()['code']);
    }

    /**
     * Test that term is not editable while going through RESTful API endpoint
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testEditableTermDirectly()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            Term::OBJECT_TYPE, self::$top_term_id . '|category'
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('term/edit', true)->save());

        $server = rest_get_server();

        // Verify that term is no longer in the list of terms
        $request = new WP_REST_Request(
            'POST', '/wp/v2/categories/' . self::$top_term_id
        );
        $request->set_param('description', 'Test');

        $response = $server->dispatch($request);

        $this->assertEquals(403, $response->get_status());
        $this->assertEquals('rest_cannot_update', $response->get_data()['code']);
    }

    /**
     * Test that term cannot be deleted while going through RESTful API endpoint
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDeleteTermDirectly()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            Term::OBJECT_TYPE, self::$top_term_id . '|category'
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('term/delete', true)->save());

        $server = rest_get_server();

        // Verify that term is no longer in the list of terms
        $request = new WP_REST_Request(
            'DELETE', '/wp/v2/categories/' . self::$top_term_id
        );

        $response = $server->dispatch($request);

        $this->assertEquals(403, $response->get_status());
        $this->assertEquals('rest_cannot_delete', $response->get_data()['code']);
    }

    /**
     * Test that term cannot be assigned to a post while going through RESTful
     * API endpoint
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAssignTermDirectly()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            Term::OBJECT_TYPE, self::$top_term_id . '|category'
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('term/assign', true)->save());

        $server = rest_get_server();

        // Verify that term is no longer in the list of terms
        $request = new WP_REST_Request('POST', '/wp/v2/posts/' . self::$post_id);
        $request->set_param('context', 'edit');
        $request->set_param('categories', array(self::$top_term_id));

        $response = $server->dispatch($request);

        $this->assertEquals(403, $response->get_status());
        $this->assertEquals('rest_cannot_assign_term', $response->get_data()['code']);
    }

}