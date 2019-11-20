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
 * Backend subject
 *
 * Currently managed subject. Based on the HTTP request data, define what subject
 * is currently managed with AAM UI.
 *
 * @package AAM
 * @version 6.0.0
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
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        $subject = $this->getFromPost('subject');

        if ($subject) {
            $this->initRequestedSubject($subject, $this->getFromPost('subjectId'));
        } else {
            $this->initDefaultSubject();
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
     * @access protected
     * @version 6.0.0
     */
    protected function initRequestedSubject($type, $id)
    {
        if ($type === AAM_Core_Subject_User::UID) {
            $subject = AAM::api()->getUser(intval($id));
        } elseif ($type === AAM_Core_Subject_Default::UID) {
            $subject = AAM_Core_Subject_Default::getInstance();
        } else {
            $class_name = 'AAM_Core_Subject_' . ucfirst($type);
            $subject   = new $class_name(stripslashes($id));
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
     * @access protected
     * @version 6.0.0
     */
    protected function initDefaultSubject()
    {
        if (current_user_can('aam_manage_roles')) {
            $roles = array_keys(get_editable_roles());
            $this->initRequestedSubject(
                AAM_Core_Subject_Role::UID, array_shift($roles)
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
            wp_die(__('You are not allowed to manage any AAM subject', AAM_KEY));
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

}