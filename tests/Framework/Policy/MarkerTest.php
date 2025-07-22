<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Policy;

use AAM,
    AAM_Framework_Policy_Marker,
    AAM\UnitTest\Utility\TestCase,
    AAM_Framework_Service_Policies;

/**
 * Framework policy marker test
 */
final class MarkerTest extends TestCase
{

    /**
     * Test ${USER} marker
     *
     * @return void
     */
    public function testUserMarker()
    {
        $user_a = $this->createUser([ 'role' => 'editor' ]);

        // Set current user
        wp_set_current_user($user_a);

        $this->assertEquals(
            $user_a, AAM_Framework_Policy_Marker::get_marker_value('${USER.ID}')
        );
        $this->assertEquals(
            $user_a, AAM_Framework_Policy_Marker::get_marker_value('${USER.id}')
        );
        $this->assertTrue(
            AAM_Framework_Policy_Marker::get_marker_value('${USER.isAuthenticated}')
        );
        $this->assertTrue(
            AAM_Framework_Policy_Marker::get_marker_value('${USER.authenticated}')
        );
        $this->assertIsArray(
            AAM_Framework_Policy_Marker::get_marker_value('${USER.caps}')
        );
        $this->assertIsArray(
            AAM_Framework_Policy_Marker::get_marker_value('${USER.capabilities}')
        );

        // Emulating remote IP address
        $_SERVER['REMOTE_ADDR'] = '10.10.0.0';

        $this->assertEquals(
            '10.10.0.0',
            AAM_Framework_Policy_Marker::get_marker_value('${USER.ip}')
        );
        $this->assertEquals(
            '10.10.0.0',
            AAM_Framework_Policy_Marker::get_marker_value('${USER.ipAddress}'
        ));

        // Check that Xpath is handled correctly
        $this->assertTrue(
            AAM_Framework_Policy_Marker::get_marker_value('${USER.exists}')
        );

        unset($_SERVER['REMOTE_ADDR']);

        // Logout user
        wp_set_current_user(0);

        $this->assertFalse(
            AAM_Framework_Policy_Marker::get_marker_value('${USER.isAuthenticated}')
        );
        $this->assertFalse(
            AAM_Framework_Policy_Marker::get_marker_value('${USER.authenticated}')
        );
    }

    /**
     * Test ${USER_OPTION} marker
     *
     * @return void
     */
    public function testUserOptionMarker()
    {
        $user_a    = $this->createUser();
        $option_id = update_user_option($user_a, 'aam_test_option', 'hello');

        // Set current user
        wp_set_current_user($user_a);

        // Setting a dummy user option
        $this->assertIsInt($option_id);

        $this->assertEquals('hello', AAM_Framework_Policy_Marker::get_marker_value(
            '${USER_OPTION.aam_test_option}')
        );
    }

    /**
     * Test ${USER_META} marker
     *
     * @return void
     */
    public function testUserMetaMarker()
    {
        $user_a = $this->createUser();
        $meta_a = add_user_meta($user_a, 'aam_test_meta_a', 'hello', true);
        $meta_b = add_user_meta($user_a, 'aam_test_meta_m', 'a');
        $meta_c = add_user_meta($user_a, 'aam_test_meta_m', 'b');

        // Set current user
        wp_set_current_user($user_a);

        // Making sure meta was added
        $this->assertIsInt($meta_a);
        $this->assertIsInt($meta_b);
        $this->assertIsInt($meta_c);

        $this->assertEquals('hello', AAM_Framework_Policy_Marker::get_marker_value(
            '${USER_META.aam_test_meta_a}')
        );

        $this->assertEquals([ 'a', 'b' ], AAM_Framework_Policy_Marker::get_marker_value(
            '${USER_META.aam_test_meta_m}'
        ));
        $this->assertEquals('b', AAM_Framework_Policy_Marker::get_marker_value(
            '${USER_META.aam_test_meta_m[1]}'
        ));
    }

    /**
     * Test ${DATETIME} marker
     *
     * @return void
     */
    public function testDateTimeMarker()
    {
        $this->assertEquals(date('Y'), AAM_Framework_Policy_Marker::get_marker_value(
            '${DATETIME.Y}'
        ));

        $this->assertEquals(date('m-d'), AAM_Framework_Policy_Marker::get_marker_value(
            '${DATETIME.m-d}'
        ));

        $this->assertEquals(date('Y-m-d'), AAM_Framework_Policy_Marker::get_marker_value(
            '${DATETIME.Y-m-d}'
        ));
    }

    /**
     * Test ${HTTP_GET} & ${HTTP_QUERY} markers
     *
     * @return void
     */
    public function testHttpGetMarker()
    {
        // Emulate GET data
        $_GET['test']  = '34';
        $_GET['array'] = [ 'a', 'b', 'c' ];

        $this->assertEquals(
            '34', AAM_Framework_Policy_Marker::get_marker_value('${HTTP_GET.test}')
        );
        $this->assertEquals(
            '34', AAM_Framework_Policy_Marker::get_marker_value('${HTTP_QUERY.test}')
        );

        $this->assertEquals(
            'c', AAM_Framework_Policy_Marker::get_marker_value('${HTTP_GET.array.2}')
        );
        $this->assertEquals(
            'b', AAM_Framework_Policy_Marker::get_marker_value('${HTTP_QUERY.array[1]}')
        );

        // Unset dummy data
        unset($_GET['test']);
        unset($_GET['array']);
    }

    /**
     * Test ${HTTP_POST} marker
     *
     * @return void
     */
    public function testHttpPostMarker()
    {
        // Emulate POST data
        $_POST['test']  = '34';
        $_POST['array'] = [ 'a', 'b', 'c' ];

        $this->assertEquals(
            '34', AAM_Framework_Policy_Marker::get_marker_value('${HTTP_POST.test}')
        );
        $this->assertEquals(
            'c', AAM_Framework_Policy_Marker::get_marker_value('${HTTP_POST.array.2}')
        );
        $this->assertEquals(
            'b', AAM_Framework_Policy_Marker::get_marker_value('${HTTP_POST.array[1]}')
        );

        // Unset dummy data
        unset($_POST['test']);
        unset($_POST['array']);
    }

    /**
     * Test ${HTTP_COOKIE} marker
     *
     * @return void
     */
    public function testHttpCookieMarker()
    {
        // Emulate cookie data
        $_COOKIE['test']  = '34';
        $_COOKIE['array'] = [ 'a', 'b', 'c' ];

        $this->assertEquals(
            '34', AAM_Framework_Policy_Marker::get_marker_value('${HTTP_COOKIE.test}')
        );
        $this->assertEquals(
            'c', AAM_Framework_Policy_Marker::get_marker_value('${HTTP_COOKIE.array.2}')
        );
        $this->assertEquals(
            'b', AAM_Framework_Policy_Marker::get_marker_value('${HTTP_COOKIE.array[1]}')
        );

        // Unset dummy data
        unset($_COOKIE['test']);
        unset($_COOKIE['array']);
    }

    /**
     * Test ${PHP_SERVER} marker
     *
     * @return void
     */
    public function testPhpServerMarker()
    {
        // Emulate POST data
        $_SERVER['test']  = '34';
        $_SERVER['array'] = [ 'a', 'b', 'c' ];

        $this->assertEquals(
            '34', AAM_Framework_Policy_Marker::get_marker_value('${PHP_SERVER.test}')
        );
        $this->assertEquals(
            'c', AAM_Framework_Policy_Marker::get_marker_value('${PHP_SERVER.array.2}')
        );
        $this->assertEquals(
            'b', AAM_Framework_Policy_Marker::get_marker_value('${PHP_SERVER.array[1]}')
        );

        // Unset dummy data
        unset($_SERVER['test']);
        unset($_SERVER['array']);
    }

    /**
     * Test ${PHP_GLOBAL} marker
     *
     * @return void
     */
    public function testGlobalsMarker()
    {
        // Emulate GLOBALS data
        $GLOBALS['test']  = '34';
        $GLOBALS['array'] = [ 'a', 'b', 'c' ];

        $this->assertEquals(
            '34', AAM_Framework_Policy_Marker::get_marker_value('${PHP_GLOBAL.test}')
        );
        $this->assertEquals(
            'c', AAM_Framework_Policy_Marker::get_marker_value('${PHP_GLOBAL.array.2}')
        );
        $this->assertEquals(
            'b', AAM_Framework_Policy_Marker::get_marker_value('${PHP_GLOBAL.array[1]}')
        );

        // Unset dummy data
        unset($GLOBALS['test']);
        unset($GLOBALS['array']);
    }

    /**
     * Test ${ARGS} marker
     *
     * @return void
     */
    public function testArgsMarker()
    {
        $args = (object) [
            'a' => 4,
            'b' => (object) [
                'prop' => 'yes'
            ],
            'c' => [ 'a', 'b' ],
            'd' => true
        ];

        $this->assertEquals(
            4, AAM_Framework_Policy_Marker::get_marker_value('${ARGS.a}', $args)
        );
        $this->assertEquals(
            'yes', AAM_Framework_Policy_Marker::get_marker_value('${ARGS.b.prop}', $args)
        );
        $this->assertEquals(
            'b', AAM_Framework_Policy_Marker::get_marker_value('${ARGS.c[1]}', $args)
        );
        $this->assertTrue(
            AAM_Framework_Policy_Marker::get_marker_value('${ARGS.d}', $args)
        );
    }

    /**
     * Test ${ENV} marker
     *
     * @return void
     */
    public function getEnvMarker()
    {
        putenv('AAM_TEST=abc');

        $this->assertEquals(
            'abc', AAM_Framework_Policy_Marker::get_marker_value('${ENV.AAM_TEST}')
        );
    }

    /**
     * Test ${CONST} marker
     *
     * @return void
     */
    public function getConstMarker()
    {
        define('AAM_TEST_CONST_A', 45);
        define('AAM_TEST_CONST_B', [ 'a', 'b', 'c' ]);

        $this->assertEquals(
            45,
            AAM_Framework_Policy_Marker::get_marker_value('${CONST.AAM_TEST_CONST_A}')
        );
        $this->assertEquals(
            'b',
            AAM_Framework_Policy_Marker::get_marker_value('${CONST.AAM_TEST_CONST_B[1]}')
        );
    }

    /**
     * Test ${WP_OPTION} marker
     *
     * @return void
     */
    public function testWpOption()
    {
        $this->assertEquals(
            get_option('siteurl'),
            AAM_Framework_Policy_Marker::get_marker_value('${WP_OPTION.siteurl}')
        );

        // Making sure that xpath is also handled correctly
        $this->assertEquals(
            'Administrator',
            AAM_Framework_Policy_Marker::get_marker_value(
                '${WP_OPTION.' . wp_roles()->role_key .'.administrator.name}'
            )
        );
    }

    /**
     * Test ${AAM_CONFIG} marker
     *
     * @return void
     */
    public function getAamConfigMarker()
    {
        // Set dummy config
        $this->assertTrue(AAM::api()->config->set('aam.test.a', 3));

        $this->assertEquals(
            3,
            AAM_Framework_Policy_Marker::get_marker_value('${AAM_CONFIG.aam.test.a}')
        );
    }

    /**
     * Test ${POLICY_PARAM} marker
     *
     * @return void
     */
    public function testParamMarker()
    {
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Param": [
                {
                    "Key": "aamTestPropA",
                    "Value": true
                },
                {
                    "Key": "aamTestPropB",
                    "Value": {
                        "prop": "hello"
                    }
                }
            ]
        }'));

        $this->assertTrue(AAM_Framework_Policy_Marker::get_marker_value(
            '${POLICY_PARAM.aamTestPropA}'
        ));
        $this->assertEquals('hello', AAM_Framework_Policy_Marker::get_marker_value(
            '${POLICY_PARAM.aamTestPropB.prop}'
        ));
    }

    /**
     * Test ${POLICY_META} marker
     *
     * @return void
     */
    public function testPolicyMetaMarker()
    {
        $policy_id = $this->createPost([
            'post_type'    => AAM_Framework_Service_Policies::CPT,
            'post_content' => json_encode([
                'Param' => [
                    [
                        'Key'   => 'aamTestPropC',
                        'Value' => '${POLICY_META.aam_test_data_a}'
                    ],
                    [
                        'Key'   => 'aamTestPropD',
                        'Value' => '${POLICY_META.aam_test_data_b.a}'
                    ],
                ]
            ]),
            'post_status'  => 'publish'
        ]);

        $this->assertTrue(AAM::api()->policies()->attach($policy_id));

        // Add dummy data
        add_post_meta($policy_id, 'aam_test_data_a', 45);
        add_post_meta($policy_id, 'aam_test_data_b', [ 'a' => 'yes' ]);

        $this->assertEquals(
            45,
            AAM_Framework_Policy_Marker::get_marker_value('${POLICY_PARAM.aamTestPropC}')
        );
        $this->assertEquals(
            'yes',
            AAM_Framework_Policy_Marker::get_marker_value('${POLICY_PARAM.aamTestPropD.a}')
        );
    }

    /**
     * Test ${WP_SITE} marker
     *
     * @return void
     */
    public function testSiteMarker()
    {
        $this->assertEquals(
            get_current_blog_id(),
            AAM_Framework_Policy_Marker::get_marker_value('${WP_SITE.blog_id}')
        );
    }

    /**
     * Test ${WP_NETWORK_OPTION} marker
     *
     * @return void
     */
    public function testNetworkOptionMarker()
    {
        $this->assertEquals(
            get_option('blogname'),
            AAM_Framework_Policy_Marker::get_marker_value('${WP_NETWORK_OPTION.blogname}')
        );
    }

    /**
     * Test ${THE_POST} marker
     *
     * @return void
     */
    public function testPostMarker()
    {
        global $post;

        // Emulate current post
        $post = get_post($this->createPost([ 'post_name' => 'test-post' ]));

        $this->assertEquals(
            'test-post',
            AAM_Framework_Policy_Marker::get_marker_value('${THE_POST.post_name}')
        );
    }

    /**
     * Test ${JWT} marker
     *
     * @return void
     */
    public function testJwtMarker()
    {
        $user_a = $this->createUser();

        // Let's issue a token
        $token = AAM::api()->jwts('user:' . $user_a)->issue([ 'test' => 'yes' ]);

        // Emulate JWT token to be passed in POST data
        $_POST['aam-jwt'] = $token['token'];

        $this->assertEquals('yes', AAM_Framework_Policy_Marker::get_marker_value(
            '${JWT.test}'
        ));

        // Unset test data
        unset($_POST['aam-jwt']);
    }

    /**
     * Testing literal values execution
     *
     * @return void
     */
    public function testLiteralMarkersExecution()
    {
        $this->assertEquals(5, AAM_Framework_Policy_Marker::execute('(*int)5'));
        $this->assertTrue(AAM_Framework_Policy_Marker::execute('(*bool)true'));
        $this->assertEquals('a', AAM_Framework_Policy_Marker::execute('a'));
        $this->assertEquals([1,2], AAM_Framework_Policy_Marker::execute([1,2]));
        $this->assertEquals([3,4], AAM_Framework_Policy_Marker::execute('(*array)[3,4]'));
    }

    /**
     * Testing single marker execution
     *
     * @return void
     */
    public function testSingleMarkerExecution()
    {
        $GLOBALS['single']   = 4;
        $GLOBALS['single_b'] = true;

        $this->assertEquals(4, AAM_Framework_Policy_Marker::execute('(*int)${PHP_GLOBAL.single}'));
        $this->assertEquals('4', AAM_Framework_Policy_Marker::execute('${PHP_GLOBAL.single}'));
        $this->assertTrue(AAM_Framework_Policy_Marker::execute('(*bool)${PHP_GLOBAL.single_b}'));
        $this->assertTrue(AAM_Framework_Policy_Marker::execute('${PHP_GLOBAL.single_b}'));

        // Reset to default
        unset($GLOBALS['single']);
        unset($GLOBALS['single_b']);
    }

    /**
     * Testing single marker with static addition execution
     *
     * @return void
     */
    public function testSingleMarkerWithAdditionExecution()
    {
        $GLOBALS['single'] = 4;

        $this->assertEquals('4-3', AAM_Framework_Policy_Marker::execute('${PHP_GLOBAL.single}-3'));
        $this->assertEquals(43, AAM_Framework_Policy_Marker::execute('(*int)${PHP_GLOBAL.single}3'));
        $this->assertEquals('ab-4', AAM_Framework_Policy_Marker::execute('ab-${PHP_GLOBAL.single}'));

        // Reset to default
        unset($GLOBALS['single']);
    }

    /**
     * Testing multi-marker with and without static addition execution
     *
     * @return void
     */
    public function testMultiMarkerExecution()
    {
        $GLOBALS['a'] = 'a';
        $GLOBALS['b'] = 'b';
        $GLOBALS['c'] = [1,2];

        $this->assertEquals('a-test-b', AAM_Framework_Policy_Marker::execute('${PHP_GLOBAL.a}-test-${PHP_GLOBAL.b}'));
        $this->assertEquals('a-[1,2]', AAM_Framework_Policy_Marker::execute('${PHP_GLOBAL.a}-${PHP_GLOBAL.c}'));

        // Reset to default
        unset($GLOBALS['a']);
        unset($GLOBALS['b']);
        unset($GLOBALS['c']);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testComplexMarkers()
    {
        $post_a = $this->createPost();

        // Set permissions to a post A
        AAM::api()->posts()->hide($post_a, 'frontend');

        $this->assertTrue(AAM_Framework_Policy_Marker::get_marker_value(
            sprintf('AAM_API.posts().is_hidden_on(%d, frontend)', $post_a)
        ));

        $this->assertFalse(AAM_Framework_Policy_Marker::get_marker_value(
            sprintf('AAM_API.posts().is_hidden_on(%d, "backend")', $post_a)
        ));
    }

}