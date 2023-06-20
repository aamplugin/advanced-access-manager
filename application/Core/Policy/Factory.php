<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core policy manager factory
 *
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/287
 * @since 6.1.0  Fixed bug with incorrectly managed internal cache
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.12
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
     * @param boolean          $skipInheritance
     *
     * @return AAM_Core_Policy_Manager
     *
     * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/287
     * @since 6.1.0  Fixed bug with incorrectly managed internal caching
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.12
     */
    public static function get(AAM_Core_Subject $subject = null, $skipInheritance)
    {
        if (is_null($subject)) {
            $subject = AAM::getUser();
        }

        $id   = $subject->getId();
        $sid  = $subject::UID . (empty($id) ? '' : '_' . $id);
        $sid .= ($skipInheritance ? '_direct' : '_complete');

        if (!array_key_exists($sid, self::$_instances)) {
            self::$_instances[$sid] = new AAM_Core_Policy_Manager(
                $subject, $skipInheritance
            );

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