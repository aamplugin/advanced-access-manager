<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM_Framework_Manager,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM_Service_AccessPolicy_HookController;

/**
 * Test access policy Hook controller
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.9.25
 */
class HookControllerTest extends TestCase
{

    use ResetTrait;

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
     * Test setup
     *
     * @return void
     *
     * @access private
     * @static
     * @version 6.7.0
     */
    private static function _setUpBeforeClass()
    {
        // Setup a default policy placeholder
        self::$policy_id = wp_insert_post(array(
            'post_title'  => 'Unittest Policy Placeholder',
            'post_status' => 'publish',
            'post_type'   => 'aam_policy'
        ));
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testDenyWithExactPriority()
    {
        global $wp_filter;

        // Adding dummy filter
        add_filter('aam_test_filter', function() {
            return 5;
        }, 20);

        $this->assertEquals(5, apply_filters('aam_test_filter', null));
        $this->assertTrue(isset($wp_filter['aam_test_filter']));

        $this->preparePlayground('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Hook:aam_test_filter:20"
            }
        }');

        $this->assertEquals(null, apply_filters('aam_test_filter', null));

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testDenyWithHigherPriority()
    {
        // Adding dummy filter
        add_filter('aam_test_filter', function() {
            return 5;
        }, 10);

        $this->assertEquals(5, apply_filters('aam_test_filter', null));

        $this->preparePlayground('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Hook:aam_test_filter:20"
            }
        }');

        $this->assertEquals(5, apply_filters('aam_test_filter', null));

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testMergeEffect()
    {
        global $wp_filter;

        // Also testing that the order of the registered filter is correct
        add_filter('aam_test_filter', function($v) {
            return $v;
        }, 1);

        $this->preparePlayground('{
            "Statement": {
                "Effect": "merge",
                "Resource": "Hook:aam_test_filter:10",
                "Response": [3, 4]
            }
        }');

        $this->assertFalse(isset($wp_filter['aam_test_filter']->callbacks[10]));
        $this->assertEquals([1, 2, 3, 4], apply_filters('aam_test_filter', [1, 2]));
        $this->assertTrue(isset($wp_filter['aam_test_filter']->callbacks[10]));

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testApplyEffect()
    {
        $GLOBALS['test_replace'] = 0;

        add_filter('aam_test_filter', function($v) {
            $GLOBALS['test_replace']++;

            return $v;
        });

        $this->preparePlayground('{
            "Statement": {
                "Effect": "apply",
                "Resource": "Hook:aam_test_filter:10",
                "Response": [3, 4]
            }
        }');

        $this->assertEquals([3, 4], apply_filters('aam_test_filter', 2));
        $this->assertEquals(1, $GLOBALS['test_replace']);

        // Also testing that the order of the registered filter is correct
        add_filter('aam_test_filter', function($v) {
            return 5;
        }, 20);

        $this->assertEquals(5, apply_filters('aam_test_filter', 2));

        remove_all_filters('aam_test_filter');
        unset($GLOBALS['test_replace']);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testOverrideEffect()
    {
        $this->preparePlayground('{
            "Statement": {
                "Effect": "override",
                "Resource": "Hook:aam_test_filter:10",
                "Response": [3, 4]
            }
        }');

        $this->assertEquals([3, 4], apply_filters('aam_test_filter', 2));

        // Also testing that the order of the registered filter is correct
        add_filter('aam_test_filter', function($v) {
            return 5;
        }, 20);

        $this->assertEquals(5, apply_filters('aam_test_filter', 2));

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testOverrideRealignedEffect()
    {
        $GLOBALS['test_counter'] = 0;

        add_filter('aam_test_filter', function($v) {
            array_push($v, 2);

            $GLOBALS['test_counter']++;

            return $v;
        });

        $this->preparePlayground('{
            "Statement": {
                "Effect": "override",
                "Resource": "Hook:aam_test_filter:10",
                "Response": [3, 4]
            }
        }');

        $this->assertEquals([3, 4], apply_filters('aam_test_filter', [1]));
        $this->assertEquals(1, $GLOBALS['test_counter']);

        // Also testing that the order of the registered filter is correct
        add_filter('aam_test_filter', function($v) {
            array_push($v, 3);

            $GLOBALS['test_counter']++;

            return $v;
        });

        $this->assertEquals([3, 4], apply_filters('aam_test_filter', [1]));
        $this->assertEquals(3, $GLOBALS['test_counter']);

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterEquals()
    {
        $this->preparePlayground('{
            "Statement": {
                "Effect": "override",
                "Resource": "Hook:aam_test_filter:10",
                "Response": "&:filter($key == a)"
            }
        }');

        $this->assertEquals(
            ['a' => 1], apply_filters('aam_test_filter', ['c' => 0, 'a' => 1])
        );

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterNotEquals()
    {
        $this->preparePlayground('{
            "Statement": {
                "Effect": "override",
                "Resource": "Hook:aam_test_filter:10",
                "Response": "&:filter($key != a)"
            }
        }');

        $this->assertEquals(
            ['c' => 0], apply_filters('aam_test_filter', ['c' => 0, 'a' => 1])
        );

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterContains()
    {
        $this->preparePlayground('{
            "Statement": {
                "Effect": "override",
                "Resource": "Hook:aam_test_filter:10",
                "Response": "&:filter($key *= te)"
            }
        }');

        $this->assertEquals(
            ['te1' => 0, 'uite2' => 1],
            apply_filters('aam_test_filter', ['te1' => 0, 'uite2' => 1, 'hjd' => 2])
        );

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterStartWith()
    {
        $this->preparePlayground('{
            "Statement": {
                "Effect": "override",
                "Resource": "Hook:aam_test_filter:10",
                "Response": "&:filter($key ^= te)"
            }
        }');

        $this->assertEquals(
            ['te1' => 0],
            apply_filters('aam_test_filter', ['te1' => 0, 'uite2' => 1, 'hjd' => 2])
        );

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterEndWith()
    {
        $this->preparePlayground('{
            "Statement": {
                "Effect": "override",
                "Resource": "Hook:aam_test_filter:10",
                "Response": "&:filter($key $= 2)"
            }
        }');

        $this->assertEquals(
            ['uite2' => 1],
            apply_filters('aam_test_filter', ['te1' => 0, 'uite2' => 1, 'hjd' => 2])
        );

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterValueComparison()
    {
        $this->preparePlayground('{
            "Statement": {
                "Effect": "override",
                "Resource": "Hook:aam_test_filter:10",
                "Response": "&:filter($value != 2)"
            }
        }');

        $this->assertEquals(
            ['te1' => 0, 'uite2' => 1],
            apply_filters('aam_test_filter', ['te1' => 0, 'uite2' => 1, 'hjd' => 2])
        );

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterComplexValueComparison()
    {
        $this->preparePlayground('{
            "Statement": {
                "Effect": "override",
                "Resource": "Hook:aam_test_filter:10",
                "Response": "&:filter($value.test[0].test != hello)"
            }
        }');

        $this->assertEquals(
            [[ 'test' => [ [ 'test' => 'no' ] ] ]],
            apply_filters('aam_test_filter', [[ 'test' => [ [ 'test' => 'no' ] ] ], [ 'test' => [ [ 'test' => 'hello' ] ] ]])
        );

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideArrayOfFilters()
    {
        $this->preparePlayground('{
            "Statement": {
                "Effect": "override",
                "Resource": "Hook:aam_test_filter:10",
                "Response": [
                    "&:filter($key != hello)",
                    "&:filter($value > 5)"
                ]
            }
        }');

        $this->assertEquals(
            ['c' => 10],
            apply_filters('aam_test_filter', ['hello' => 1, 'a' => 2, 'b' => 5, 'c' => 10])
        );

        remove_all_filters('aam_test_filter');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testReplaceEffect()
    {
        $GLOBALS['test_replace'] = 0;

        add_filter('aam_test_filter', function() {
            $GLOBALS['test_replace']++;
        });

        $this->preparePlayground('{
            "Statement": {
                "Effect": "replace",
                "Resource": "Hook:aam_test_filter:10",
                "Response": "yes"
            }
        }');

        $this->assertEquals('yes', apply_filters('aam_test_filter', false));
        $this->assertEquals(0, $GLOBALS['test_replace'], false);

        remove_all_filters('aam_test_filter');
        unset($GLOBALS['test_replace']);
    }

    /**
     * Prepare the environment
     *
     * @param string $policy
     *
     * @return void
     *
     * @access protected
     * @version 6.5.3
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

        // Resetting all settings as $wpdb->update already initializes it with
        // settings
        \AAM_Core_Policy_Factory::reset();
        $this->_resetSubjects();

        $settings = AAM_Framework_Manager::settings([
            'access_level' => 'visitor'
        ]);

        $settings->set_setting('policy', [
            self::$policy_id => true
        ]);

        // Reset the Hook Controller
        AAM_Service_AccessPolicy_HookController::bootstrap(true);
    }

}