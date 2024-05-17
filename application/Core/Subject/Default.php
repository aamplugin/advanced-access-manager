<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Default subject
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Core_Subject_Default extends AAM_Core_Subject
{

    /**
     * Subject UID: DEFAULT
     *
     * @version 6.0.0
     */
    const UID = 'default';

    /**
     * Single instance of itself
     *
     * @var AAM_Core_Subject_Default
     *
     * @access private
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    { }

    /**
     * @inheritDoc
     * @version 6.0.0
     */
    public function getName()
    {
        return __('All Users, Roles and Visitor', AAM_KEY);
    }

    /**
     * @inheritDoc
     * @version 6.0.0
     */
    public function getParent()
    {
        return null; // Default subject is the highest subject that can even be
    }

    /**
     * Get max subject user level
     *
     * @return int
     *
     * @access public
     * @version 6.0.0
     */
    public function getMaxLevel()
    {
        return AAM_Core_API::maxLevel(AAM_Core_API::getAllCapabilities());
    }

     /**
     * Bootstrap the object
     *
     * @return AAM_Core_Subject_Default
     *
     * @access public
     * @version 6.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}