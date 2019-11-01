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
 * Abstract class for each backend UI feature
 *
 * @package AAM
 * @version 6.0.0
 */
abstract class AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manager';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = null;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = null;

    /**
     * Save access settings for the specific object
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $param = $this->getFromPost('param');
        $value = $this->getFromPost('value');

        $object = $this->getSubject()->getObject(static::OBJECT_TYPE, null, true);

        $object->updateOptionItem($param, $value)->save();

        return wp_json_encode(array('status' => 'success'));
    }

    /**
     * Rest access settings for the specific object
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function reset()
    {
        $result = $this->getSubject()->getObject(static::OBJECT_TYPE)->reset();

        return wp_json_encode(array('status' => ($result ? 'success' : 'failure')));
    }

    /**
     * Check inheritance status
     *
     * Check if access settings are overwritten
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function isOverwritten()
    {
        $object = $this->getSubject()->getObject(static::OBJECT_TYPE);

        return $object->isOverwritten();
    }

    /**
     * Get HTML content
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function getContent()
    {
        ob_start();
        require_once(dirname(__DIR__) . '/tmpl/' . static::TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Get currently managed subject
     *
     * @return AAM_Backend_Subject
     *
     * @access public
     * @version 6.0.0
     */
    public function getSubject()
    {
        return AAM_Backend_Subject::getInstance();
    }

    /**
     * Register feature
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register() {}

}