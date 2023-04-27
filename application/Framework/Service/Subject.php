<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service to manager subjects
 *
 * @package AAM
 * @version 6.9.9
 */
class AAM_Framework_Service_Subject
{

    /**
     * Single instance of itself
     *
     * @var AAM_Framework_Service_Subject
     *
     * @access private
     * @static
     * @version 6.9.9
     */
    private static $_instance = null;

    /**
     * Instantiate the service
     *
     * @return void
     *
     * @access protected
     * @version 6.9.9
     */
    protected function __construct() {}

    /**
     * Determine subject based on access level and ID
     *
     * @param string $access_level
     * @param mixed  $id
     *
     * @return AAM_Core_Subject
     *
     * @access public
     * @version 6.9.9
     */
    public function get($access_level, $id = null)
    {
        if ($access_level === AAM_Core_Subject_Role::UID) {
            $subject = $this->get_role($id);
        } elseif ($access_level === AAM_Core_Subject_User::UID) {
            $subject = $this->get_user(intval($id));
        } elseif ($access_level === AAM_Core_Subject_Visitor::UID) {
            $subject = $this->get_visitor();
        } elseif ($access_level === AAM_Core_Subject_Default::UID) {
            $subject = $this->get_default();
        } else {
            throw new InvalidArgumentException('Unsupported access_level');
        }

        return $subject;
    }

    /**
     * Get role subject
     *
     * @param string $id Role ID (aka slug)
     *
     * @return AAM_Core_Subject_Role
     *
     * @access public
     * @version 6.9.9
     */
    public function get_role($id)
    {
        return new AAM_Core_Subject_Role($id);
    }

    /**
     * Get user subject
     *
     * @param int $id User ID
     *
     * @return AAM_Core_Subject_User
     *
     * @access public
     * @version 6.9.9
     */
    public function get_user($id)
    {
        $user = new AAM_Core_Subject_User($id);
        $user->initialize();

        return $user;
    }

    /**
     * Get visitor subject
     *
     * @return AAM_Core_Subject_Visitor
     *
     * @access public
     * @version 6.9.9
     */
    public function get_visitor()
    {
        return new AAM_Core_Subject_Visitor();
    }

    /**
     * Get default subject
     *
     * @return AAM_Core_Subject_Default
     *
     * @access public
     * @version 6.9.9
     */
    public function get_default()
    {
        return AAM_Core_Subject_Default::getInstance();
    }

    /**
     * Bootstrap the role service
     *
     * @return AAM_Framework_Service_Subject
     *
     * @access public
     * @static
     * @version 6.9.9
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}