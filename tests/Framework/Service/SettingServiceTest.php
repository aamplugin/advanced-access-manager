<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Service;

use AAM_Framework_Manager,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM_Framework_Service_Settings;

/**
 * Test for the Settings Service in the AAM Framework
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.9.34
 */
class SettingServiceTest extends TestCase
{
    use ResetTrait;

    /**
     * Test set settings in bulk (import)
     *
     * @return void
     *
     * @access public
     * @version 6.9.17
     */
    public function testSetBulkSettings()
    {
        $service  = AAM_Framework_Manager::settings();
        $settings = [
            'role' => [
                'subscriber' => [
                    'menu' => [
                        'test' => true
                    ],
                    'toolbar' => [
                        'test' => true
                    ]
                ]
            ],
            'user' => [
                '1' => [
                    'menu' => [
                        'test-b' => false
                    ],
                    'toolbar' => [
                        'test-c' => false
                    ]
                ]
            ]
        ];

        $service->set_settings($settings);

        $this->assertEquals(
            get_option(AAM_Framework_Service_Settings::DB_OPTION),
            $settings
        );
    }

    /**
     * Test set settings only for a specific access level
     *
     * @return void
     *
     * @access public
     * @version 6.9.34
     */
    public function testSetAccessLevelSettings()
    {
        $service  = AAM_Framework_Manager::settings([
            'access_level' => 'role',
            'subject_id'   => 'subscriber'
        ]);

        $settings = [
            'menu' => [
                'test' => true
            ],
            'toolbar' => [
                'test' => true
            ]
        ];

        $service->set_settings($settings);

        $this->assertEquals(
            get_option(AAM_Framework_Service_Settings::DB_OPTION),
            [
                'role' => [
                    'subscriber' => $settings
                ]
            ]
        );
    }

    /**
     * Test set settings only for a specific access level and no override
     *
     * @return void
     *
     * @access public
     * @version 6.9.34
     */
    public function testSetAccessLevelSettingsNoOverride()
    {
        $service  = AAM_Framework_Manager::settings();

        $settings = [
            'role' => [
                'subscriber' => [
                    'post' => [
                        '2' => [
                            'hidden' => true
                        ]
                    ]
                ]
            ]
        ];

        $service->set_settings($settings);

        $service->set_settings([
            'menu' => [
                'test' => true
            ]
        ], [
            'access_level' => 'user',
            'subject_id'   => 2
        ]);

        $this->assertEquals(
            get_option(AAM_Framework_Service_Settings::DB_OPTION),
            [
                'role' => [
                    'subscriber' => [
                        'post' => [
                            '2' => [
                                'hidden' => true
                            ]
                        ]
                    ]
                ],
                'user' => [
                    '2' => [
                        'menu' => [
                            'test' => true
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * Test set setting for specific access level
     *
     * @return void
     *
     * @access public
     * @version 6.9.34
     */
    public function testSetAccessLevelSetting()
    {
        $service  = AAM_Framework_Manager::settings();

        $service->set_setting('post.2', [ 'hidden' => true ], [
            'access_level' => 'role',
            'subject_id'   => 'subscriber'
        ]);

        $this->assertEquals(
            get_option(AAM_Framework_Service_Settings::DB_OPTION),
            [
                'role' => [
                    'subscriber' => [
                        'post' => [
                            '2' => [
                                'hidden' => true
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

}