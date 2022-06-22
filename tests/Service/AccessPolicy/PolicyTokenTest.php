<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM,
    AAM_Core_Jwt_Issuer,
    AAM_Core_Policy_Token,
    AAM_Core_Object_Policy,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test policy token evaluator
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class PolicyTokenTest extends TestCase
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
     * Policy ID placeholder
     *
     * @var int
     *
     * @access protected
     * @version 6.7.0
     */
    protected static $policy_id;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        // Setup a default post
        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Access Policy Service Post',
            'post_name'   => 'access-policy-service-post',
            'post_status' => 'publish'
        ));

        // Setup a default policy placeholder
        self::$policy_id = wp_insert_post(array(
            'post_title'  => 'Unittest Policy Placeholder',
            'post_status' => 'publish',
            'post_type'   => 'aam_policy'
        ));
    }

    /**
     * Validate correct USER token evaluation
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testUserTokenEvaluation()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $caps = array();
        foreach ((array) AAM::getUser()->allcaps as $cap => $effect) {
            if (!empty($effect)) {
                $caps[] = $cap;
            }
        }

        $cases = array(
            array('${USER.ID}', AAM_UNITTEST_ADMIN_USER_ID),
            array('${USER.ip}', '127.0.0.1'),
            array('${USER.ipAddress}', '127.0.0.1'),
            array('${USER.authenticated}', true),
            array('${USER.isAuthenticated}', true),
            array('${USER.capabilities}', json_encode($caps)),
            array('${USER.caps}', json_encode($caps)),
        );

        foreach($cases as $case) {
            $this->assertEquals(
                $case[1], AAM_Core_Policy_Token::evaluate($case[0], array($case[0]))
            );
        }

        // Reset user
        wp_set_current_user(0);
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Validate correct USER_META token evaluation
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testUserMetaTokenEvaluation()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        update_user_meta(AAM_UNITTEST_ADMIN_USER_ID, 'aam_unittest', 'hello');

        $this->assertEquals(
            'hello',
            AAM_Core_Policy_Token::evaluate(
                '${USER_META.aam_unittest}', array('${USER_META.aam_unittest}')
            )
        );

        // Reset user
        wp_set_current_user(0);
        unset($_SERVER['REMOTE_ADDR']);
        delete_user_meta(AAM_UNITTEST_ADMIN_USER_ID, 'aam_unittest');
    }

    /**
     * Validate correct USER_OPTION token evaluation
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testUserOptionTokenEvaluation()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        update_user_option(AAM_UNITTEST_ADMIN_USER_ID, 'aam_unittest', 'hello');

        $this->assertEquals(
            'hello',
            AAM_Core_Policy_Token::evaluate(
                '${USER_OPTION.aam_unittest}', array('${USER_OPTION.aam_unittest}')
            )
        );

        // Reset user
        wp_set_current_user(0);
        unset($_SERVER['REMOTE_ADDR']);
        delete_user_option(AAM_UNITTEST_ADMIN_USER_ID, 'aam_unittest');
    }

    /**
     * Test DATETIME token evaluation
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDateTimeTokenEvaluation()
    {
        $this->assertEquals(
            date('Y-m-d'),
            AAM_Core_Policy_Token::evaluate(
                '${DATETIME.Y-m-d}', array('${DATETIME.Y-m-d}')
            )
        );
    }

    /**
     * Test HTTP_* and PHP_* tokens evaluation
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testHttpTokensEvaluation()
    {
        // Fake data
        $_GET['aam_test'] = "1a";
        $_POST['aam_test'] = "1b";
        $_COOKIE['aam_test'] = "1c";
        $_SERVER['aam_test'] = "1d";

        $this->assertEquals(
            '1a', AAM_Core_Policy_Token::evaluate('${HTTP_GET.aam_test}', array('${HTTP_GET.aam_test}'))
        );

        $this->assertEquals(
            '1a', AAM_Core_Policy_Token::evaluate('${HTTP_QUERY.aam_test}', array('${HTTP_QUERY.aam_test}'))
        );

        $this->assertEquals(
            '1b', AAM_Core_Policy_Token::evaluate('${HTTP_POST.aam_test}', array('${HTTP_POST.aam_test}'))
        );

        $this->assertEquals(
            '1c', AAM_Core_Policy_Token::evaluate('${HTTP_COOKIE.aam_test}', array('${HTTP_COOKIE.aam_test}'))
        );

        $this->assertEquals(
            '1d', AAM_Core_Policy_Token::evaluate('${PHP_SERVER.aam_test}', array('${PHP_SERVER.aam_test}'))
        );
    }

    /**
     * Test ARGS token evaluation
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testArgTokenEvaluation()
    {
        $this->assertEquals(
            '1a',
            AAM_Core_Policy_Token::evaluate(
                '${ARGS.test}', array('${ARGS.test}'), array('test' => '1a')
            )
        );
    }

    /**
     * Test CONST token evaluation
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testConstTokenEvaluation()
    {
        $this->assertEquals(
            AAM_VERSION,
            AAM_Core_Policy_Token::evaluate(
                '${CONST.AAM_VERSION}', array('${CONST.AAM_VERSION}')
            )
        );
    }

    /**
     * Test WP_OPTION token evaluation
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testWpOptionTokenEvaluation()
    {
        $this->assertEquals(
            get_option('siteurl'),
            AAM_Core_Policy_Token::evaluate(
                '${WP_OPTION.siteurl}', array('${WP_OPTION.siteurl}')
            )
        );
    }

    /**
     * Test JWT token evaluation
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testJwtTokenEvaluation()
    {
        // generate token
        $result = AAM_Core_Jwt_Issuer::getInstance()->issueToken(
            array('testProp' => 'helloWorld')
        );

        $_SERVER['HTTP_AUTHENTICATION'] = $result->token;

        $this->assertEquals(
            'helloWorld',
            AAM_Core_Policy_Token::evaluate(
                '${JWT.testProp}', array('${JWT.testProp}')
            )
        );

        unset($_SERVER['HTTP_AUTHENTICATION']);
    }

    /**
     * Test THE_POST token evaluation
     *
     * @return void
     *
     * @access public
     * @version 6.8.3
     */
    public function testThePostTokenEvaluation()
    {
        $GLOBALS['post'] = get_post(self::$post_id);

        $this->assertEquals(
            self::$post_id,
            AAM_Core_Policy_Token::evaluate(
                '${THE_POST.ID}', array('${THE_POST.ID}')
            )
        );

        unset($GLOBALS['post']);
    }

    /**
     * Test CALLBACK token
     *
     * @return void
     *
     * @access public
     * @version 6.8.3
     */
    public function testCallbackToken()
    {
        $this->preparePlayground('callback-token');

        // A simple CALLBACK token
        $this->assertEquals(
            'hello',
            AAM::api()->getAccessPolicyManager()->getParam('callback-simple')
        );

        // CALLBACK with inline arguments
        $this->assertEquals(
            1,
            AAM::api()->getAccessPolicyManager()->getParam('callback-conditional-a')
        );
        $this->assertEquals(
            2,
            AAM::api()->getAccessPolicyManager()->getParam('callback-conditional-b')
        );

        // CALLBACK with xpath
        $this->assertEquals(
            'test',
            AAM::api()->getAccessPolicyManager()->getParam('callback-complex-a')
        );
        $this->assertEquals(
            'another-test',
            AAM::api()->getAccessPolicyManager()->getParam('callback-complex-b')
        );

        // CALLBACK function
        $this->assertEquals(
            false,
            AAM::api()->getAccessPolicyManager()->getParam('callback-function-a')
        );

        $this->assertEquals(
            '1 KB',
            AAM::api()->getAccessPolicyManager()->getParam('callback-function-b')
        );
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
     * @version 6.8.3
     */
    protected function preparePlayground($policy_file)
    {
        global $wpdb;

        // Update existing Access Policy with new policy
        $wpdb->update($wpdb->posts, array('post_content' => file_get_contents(
            __DIR__ . '/policies/' . $policy_file . '.json'
        )), array('ID' => self::$policy_id));

        $object = AAM::getUser()->getObject(AAM_Core_Object_Policy::OBJECT_TYPE);
        $this->assertTrue(
            $object->updateOptionItem(self::$policy_id, true)->save()
        );

        // Resetting all settings as $wpdb->update already initializes it with
        // settings
        \AAM_Core_Policy_Factory::reset();
        $this->_resetSubjects();
    }

}