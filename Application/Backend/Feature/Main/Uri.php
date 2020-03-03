<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * URI service
 *
 * @since 6.4.0 Improved UI functionality with better rules handling
 * @since 6.3.0 Fixed bug with incorrectly handled record editing
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.4.0
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
     * @since 6.4.0 Do not allow to edit/delete inherited rules
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.0.0
     */
    public function getTable()
    {
        $object = AAM_Backend_Subject::getInstance()->getObject(self::OBJECT_TYPE);
        $rules  = $object->getOption();

        $response = array(
            'recordsTotal'    => count($rules),
            'recordsFiltered' => count($rules),
            'draw'            => $this->getFromRequest('draw'),
            'data'            => array(),
        );

        foreach ($rules as $uri => $rule) {
            $prefix = ($object->has($uri) ? '' : 'no-');
            $response['data'][] = array(
                $uri,
                $rule['type'],
                $rule['action'],
                isset($rule['code']) ? $rule['code'] : 307,
                "{$prefix}edit,{$prefix}delete"
            );
        }

        return wp_json_encode($response);
    }

    /**
     * Save URI access rule
     *
     * @return string
     *
     * @since 6.4.0 Fixed https://github.com/aamplugin/advanced-access-manager/issues/77
     * @since 6.3.0 Fixed https://github.com/aamplugin/advanced-access-manager/issues/35
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.4.0
     */
    public function save()
    {
        $uri    = str_replace(site_url(), '', $this->getFromPost('uri'));
        $edited = $this->getFromPost('edited_uri');

        // Compile rule
        $rule = array(
            'type'   => $this->getFromPost('type'),
            'action' => $this->getFromPost('value'),
            'code'   => $this->getFromPost('code')
        );

        $object = AAM_Backend_Subject::getInstance()->getObject(self::OBJECT_TYPE);

        // If $edited is not empty, then we actually editing existing record. In this
        // case let's delete it and insert new record after that
        if (!empty($edited) && $object->has($edited)) {
            $modified = array();

            foreach($object->getExplicitOption() as $stored_uri => $data) {
                if ($stored_uri === $edited) {
                    $modified[$uri] = $rule; // Replace & preserve the order
                } else {
                    $modified[$stored_uri] = $data;
                }
            }

            $object->setExplicitOption($modified);
        } else { // Adding new rule
            $object->updateOptionItem($uri, $rule);
        }

        $result = $object->save();

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
        $object = AAM_Backend_Subject::getInstance()->getObject(self::OBJECT_TYPE);
        $result = $object->delete($this->getFromPost('uri'));

        return wp_json_encode(
            array('status' => ($result ? 'success' : 'failure'))
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