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
 * URI service
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Main_Uri
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the feature
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_uri';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Uri::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/uri.php';

    /**
     * Get list of all rules
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function getTable()
    {
        $subject = AAM_Backend_Subject::getInstance();
        $rules   = $subject->getObject(self::OBJECT_TYPE)->getOption();

        $response = array(
            'recordsTotal'    => count($rules),
            'recordsFiltered' => count($rules),
            'draw'            => $this->getFromRequest('draw'),
            'data'            => array(),
        );

        foreach ($rules as $uri => $rule) {
            $response['data'][] = array(
                $uri,
                $rule['type'],
                $rule['action'],
                isset($rule['code']) ? $rule['code'] : 307,
                'edit,delete'
            );
        }

        return wp_json_encode($response);
    }

    /**
     * Save URI access rule
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $uri   = str_replace(site_url(), '', $this->getFromPost('uri'));
        $type  = $this->getFromPost('type');
        $value = $this->getFromPost('value');
        $code  = $this->getFromPost('code');

        $object = AAM_Backend_Subject::getInstance()->getObject(self::OBJECT_TYPE);

        $result = $object->updateOptionItem($uri, array(
            'type'   => $type,
            'action' => $value,
            'code'   => $code
        ))->save();

        return wp_json_encode(array('status' => ($result ? 'success' : 'failure')));
    }

    /**
     * Delete URI access rule
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function delete()
    {
        $uri     = filter_input(INPUT_POST, 'uri');
        $object = AAM_Backend_Subject::getInstance()->getObject(self::OBJECT_TYPE);

        return wp_json_encode(
            array('status' => ($object->delete($uri) ? 'success' : 'failure'))
        );
    }

    /**
     * Register service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'uri',
            'position'   => 55,
            'title'      => __('URI Access', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}