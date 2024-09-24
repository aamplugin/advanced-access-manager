<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend subject
 *
 * Currently managed subject. Based on the HTTP request data, define what subject
 * is currently managed with AAM UI.
 *
 * @since 6.9.39 https://github.com/aamplugin/advanced-access-manager/issues/424
 * @since 6.2.0  Enhanced security & improved general functionality
 * @since 6.1.1  Improved safety by using a last role as default
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.39
 */
class AAM_Backend_Subject
{

    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_SingletonTrait;

    /**
     * Subject information
     *
     * @var AAM_Core_Subject
     *
     * @access protected
     * @version 6.0.0
     */
    protected $subject = null;

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.39 https://github.com/aamplugin/advanced-access-manager/issues/424
     * @since 6.2.0  Enhanced security. Making sure that subject type is normalized
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.39
     */
    protected function __construct()
    {
        $access_level = strtolower($this->getFromPost('subject'));
        $id           = $this->getFromPost('subjectId');

        if ($access_level) {
            AAM_Core_Cache::set(
                'managed_access_level_by_' . get_current_user_id(),
                [
                    'type' => $access_level,
                    'id'   => $id
                ]
            );

            $this->initRequestedSubject($access_level, $id);
        } else {
            // Read the last known access level that was managed
            $last_managed_al = $this->_get_last_managed_access_level();

            if (is_null($last_managed_al)) {
                $this->initDefaultSubject();
            } else {
                $this->initRequestedSubject(
                    $last_managed_al['type'],
                    (!empty($last_managed_al['id']) ? $last_managed_al['id'] : null)
                );
            }
        }
    }

    /**
     * Check if current subject is role
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isRole()
    {
        return $this->getSubjectType() === AAM_Core_Subject_Role::UID;
    }

    /**
     * Check if current subject is user
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isUser()
    {
        return $this->getSubjectType() === AAM_Core_Subject_User::UID;
    }

    /**
     * Check if current subject is visitor
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isVisitor()
    {
        return $this->getSubjectType() === AAM_Core_Subject_Visitor::UID;
    }

    /**
     * Check if current subject is default
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isDefault()
    {
        return $this->getSubjectType() === AAM_Core_Subject_Default::UID;
    }

    /**
     * Get current subject type
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function getSubjectType()
    {
        $subject = $this->getSubject();

        return $subject::UID;
    }

    /**
     * Initialize requested subject
     *
     * @param string $type
     * @param mixed  $id
     *
     * @return AAM_Core_Subject
     *
     * @since 6.9.39 https://github.com/aamplugin/advanced-access-manager/issues/424
     * @since 6.2.0  Refactored to use AAM API to retrieve subject
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.39
     */
    protected function initRequestedSubject($type, $id = null)
    {
        if ($type === AAM_Core_Subject_User::UID) {
            $subject = AAM::api()->getUser(intval($id));
        } elseif ($type === AAM_Core_Subject_Default::UID) {
            $subject = AAM::api()->getDefault();
        } elseif ($type === AAM_Core_Subject_Visitor::UID) {
            $subject = AAM::api()->getVisitor();
        } else {
            // Covering scenario when changing between sites and they have mismatched
            // list of roles
            if (wp_roles()->is_role($id)) {
                $subject = AAM::api()->getRole($id);
            } else {
                $roles   = array_keys(get_editable_roles());
                $subject = AAM::api()->getRole(array_pop($roles));
            }
        }

        $this->setSubject($subject);

        return $subject;
    }

    /**
     * Initialize default subject
     *
     * Based on user permissions, pick the first available subject that current user
     * can manage with AAM UI
     *
     * @return void
     *
     * @since 6.1.1 For safety reasons, using the last role as the default
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.1.1
     */
    protected function initDefaultSubject()
    {
        if (current_user_can('aam_manage_roles')) {
            $roles = array_keys(get_editable_roles());
            $this->initRequestedSubject(
                AAM_Core_Subject_Role::UID, array_pop($roles)
            );
        } elseif (current_user_can('aam_manage_users')) {
            $this->initRequestedSubject(
                AAM_Core_Subject_User::UID, get_current_user_id()
            );
        } elseif (current_user_can('aam_manage_visitors')) {
            $this->initRequestedSubject(AAM_Core_Subject_Visitor::UID, null);
        } elseif (current_user_can('aam_manage_default')) {
            $this->initRequestedSubject(AAM_Core_Subject_Default::UID, null);
        } else {
            wp_die(__('You are not allowed to manage any users or roles', AAM_KEY));
        }
    }

    /**
     * Set AAM core subject
     *
     * @param AAM_Core_Subject $subject
     *
     * @access protected
     * @version 6.0.0
     */
    protected function setSubject(AAM_Core_Subject $subject)
    {
        $this->subject = $subject;
    }

    /**
     * Get subject property
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function __get($name)
    {
        return $this->subject->$name;
    }

    /**
     * Call subject's method
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function __call($name, $args)
    {
        $response = null;
        //make sure that method is callable
        if (method_exists($this->subject, $name)) {
            $response = call_user_func_array(array($this->subject, $name), $args);
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'Backend Subject does not have method defined',
                AAM_VERSION
            );
        }

        return $response;
    }

    /**
     * Get AAM core subject
     *
     * @return AAM_Core_Subject
     *
     * @access public
     * @version 6.0.0
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get last managed access level
     *
     * @return array|null
     *
     * @access private
     * @version 6.9.39
     */
    private function _get_last_managed_access_level()
    {
        $level = AAM_Core_Cache::get(
            'managed_access_level_by_' . get_current_user_id()
        );

        if (!is_null($level)) {
            // Verifying that access level exists and is accessible
            if ($level['type'] === 'role') {
                if (!AAM_Framework_Manager::roles()->is_editable_role($level['id'])) {
                    $level = null;
                }
            } elseif ($level['type'] === 'user') {
                $user = apply_filters('aam_get_user', get_user_by('id', $level['id']));

                if ($user === false
                    || is_wp_error($user)
                    || !current_user_can('edit_user', $user->ID)
                ) {
                    $level = null;
                }
            }
        }

        return $level;
    }

}