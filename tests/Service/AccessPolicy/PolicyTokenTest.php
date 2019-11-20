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
        wp_set_current_user(AAM_UNITTEST_AUTH_USER_ID);

        $caps = array();
        foreach ((array) AAM::getUser()->allcaps as $cap => $effect) {
            if (!empty($effect)) {
                $caps[] = $cap;
            }
        }

        $cases = array(
            array('${USER.ID}', 1),
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
        wp_set_current_user(AAM_UNITTEST_AUTH_USER_ID);

        update_user_meta(AAM_UNITTEST_AUTH_USER_ID, 'aam_unittest', 'hello');

        $this->assertEquals(
            'hello',
            AAM_Core_Policy_Token::evaluate(
                '${USER_META.aam_unittest}', array('${USER_META.aam_unittest}')
            )
        );

        // Reset user
        wp_set_current_user(0);
        unset($_SERVER['REMOTE_ADDR']);
        delete_user_meta(AAM_UNITTEST_AUTH_USER_ID, 'aam_unittest');
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
        wp_set_current_user(AAM_UNITTEST_AUTH_USER_ID);

        update_user_option(AAM_UNITTEST_AUTH_USER_ID, 'aam_unittest', 'hello');

        $this->assertEquals(
            'hello',
            AAM_Core_Policy_Token::evaluate(
                '${USER_OPTION.aam_unittest}', array('${USER_OPTION.aam_unittest}')
            )
        );

        // Reset user
        wp_set_current_user(0);
        unset($_SERVER['REMOTE_ADDR']);
        delete_user_option(AAM_UNITTEST_AUTH_USER_ID, 'aam_unittest');
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

}