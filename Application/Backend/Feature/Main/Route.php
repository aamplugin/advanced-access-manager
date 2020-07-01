<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * WordPress API manager
 *
 * @since 6.6.0 Fixed https://github.com/aamplugin/advanced-access-manager/issues/131
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.6.0
 */
class AAM_Backend_Feature_Main_Route
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_api_routes';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Route::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/route.php';

    /**
     * Get list of API routes
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function getTable()
    {
        $list   = array();
        $object = AAM_Backend_Subject::getInstance()->getObject(self::OBJECT_TYPE);

        // Build all RESTful routes
        if (AAM::api()->getConfig('core.settings.restful', true)) {
            foreach (rest_get_server()->get_routes() as $route => $handlers) {
                $methods = array();
                foreach ($handlers as $handler) {
                    $methods = array_merge($methods, array_keys($handler['methods']));
                }

                foreach (array_unique($methods) as $method) {
                    $isRestricted = $object->isRestricted('restful', $route, $method);
                    $list[] = array(
                        $route,
                        'restful',
                        $method,
                        htmlspecialchars($route),
                        $isRestricted ? 'checked' : 'unchecked'
                    );
                }
            }
        }

        return wp_json_encode(array('data' => $list));
    }

    /**
     * Save route access settings
     *
     * @return string
     *
     * @since 6.6.0 Fixed https://github.com/aamplugin/advanced-access-manager/issues/131
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.0
     */
    public function save()
    {
        $type   = $this->getFromPost('type');
        $route  = $this->getFromPost('route');
        $method = $this->getFromPost('method');
        $value  = $this->getFromPost('value', FILTER_VALIDATE_BOOLEAN);

        $object = AAM_Backend_Subject::getInstance()->getObject(self::OBJECT_TYPE);
        $id     = strtolower("{$type}|{$route}|{$method}");

        $result = $object->updateOptionItem($id, $value)->save();

        return wp_json_encode(array('status' => ($result ? 'success' : 'failure')));
    }

    /**
     * Register API Routes service
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'route',
            'position'   => 50,
            'title'      => __('API Routes', AAM_KEY),
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