<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service to manager access levels
 *
 * @package AAM
 * @version 6.9.9
 */
class AAM_Framework_Service_AccessLevels
{

    /**
     * Single instance of itself
     *
     * @var AAM_Framework_Service_AccessLevels
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
     * @param mixed  $identifier
     *
     * @return AAM_Core_Subject
     *
     * @access public
     * @version 6.9.9
     */
    public function get($access_level, $identifier = null)
    {
        if ($access_level === AAM_Framework_Type_AccessLevel::ROLE) {
            $subject = $this->get_role($identifier);
        } elseif ($access_level === AAM_Framework_Type_AccessLevel::USER) {
            $subject = $this->get_user($identifier);
        } elseif ($access_level === AAM_Framework_Type_AccessLevel::VISITOR) {
            $subject = $this->get_visitor();
        } elseif ($access_level === AAM_Framework_Type_AccessLevel::DEFAULT) {
            $subject = $this->get_default();
        } else {
            throw new InvalidArgumentException('Unsupported access_level');
        }

        return $subject;
    }

    /**
     * Get role subject
     *
     * @param string|WP_Role $slug Role slug or instance
     *
     * @return AAM_Core_Subject_Role
     *
     * @access public
     * @version 6.9.9
     */
    public function get_role($identifier)
    {
        return AAM_Framework_Manager::roles()->get_role($identifier);
    }

    /**
     * Get user subject
     *
     * @param int|string|WP_User|null $id User identifier or null for current user
     *
     * @return AAM_Core_Subject_User
     *
     * @access public
     * @version 6.9.9
     */
    public function get_user($identifier = null)
    {
        if (is_null($identifier)) { // Get current user
            $user = AAM::getUser();
        } elseif (is_numeric($identifier)) { // Get user by ID
            $user = get_user_by('id', $identifier);
        } elseif (is_string($identifier)) {
            if (strpos($identifier, '@') > 0) { // Email?
                $user = get_user_by('email', $identifier);
            } else {
                $user = get_user_by('login', $identifier);
            }
        } elseif (is_a($identifier, 'WP_User')) {
            $user = $identifier;
        }

        // Convert the WP_User into user subject
        if (is_a($user, 'WP_User')) {
            $user = (new AAM_Core_Subject_User($user->ID))->initialize();
        } elseif ($user === false) { // User not found
            throw new InvalidArgumentException(
                sprintf('Cannot find user by identifier %s', $identifier)
            );
        }

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
        return AAM_Core_Subject_Default::bootstrap();
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