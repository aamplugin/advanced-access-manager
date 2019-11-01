<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * AAM core policy manager factory
 *
 * @package AAM
 * @version 6.0.0
 */
final class AAM_Core_Policy_Factory
{

    /**
     * Collection of policy manage instances
     *
     * @var array
     *
     * @access private
     * @version 6.0.0
     */
    private static $_instances = array();

    /**
     * Get single instance of access manager
     *
     * @param AAM_Core_Subject $subject
     *
     * @return AAM_Core_Policy_Manager
     *
     * @access public
     * @version 6.0.0
     */
    public static function get(AAM_Core_Subject $subject = null)
    {
        if (is_null($subject)) {
            $subject = AAM::getUser();
        }

        $id  = $subject->getId();
        $sid = $subject::UID . (empty($id) ? '' : '_' . $id);

        if (!isset(self::$_instances[$sid])) {
            self::$_instances[$sid] = new AAM_Core_Policy_Manager($subject);
            // Parse all attached to the user policies
            self::$_instances[$sid]->initialize();
        }

        return self::$_instances[$sid];
    }

    /**
     * Reset internal cache
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function reset()
    {
        self::$_instances = array();
    }

}