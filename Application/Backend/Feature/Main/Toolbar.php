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
 * Toolbar manager
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Main_Toolbar
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_toolbar';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Toolbar::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/toolbar.php';

    /**
     * Save toolbar settings
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $status  = $this->getFromPost('status');
        $items   = $this->getFromPost('items', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        $subject = AAM_Backend_Subject::getInstance();
        $object  = $subject->getObject(self::OBJECT_TYPE, null, true);

        foreach ($items as $item) {
            $object->updateOptionItem($item, !empty($status));
        }

        return wp_json_encode(
            array('status' => ($object->save() ? 'success' : 'failure'))
        );
    }

    /**
     * Get toolbar
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getToolbar()
    {
        return AAM_Service_Toolbar::getInstance()->getToolbarCache();
    }

    /**
     * Get list of child items
     *
     * @param object $branch
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getAllChildren($branch)
    {
        $children = array();
        $types    = array('container', 'group');

        foreach ($branch->children as $child) {
            if (empty($child->type) || !in_array($child->type, $types, true)) {
                $children[] = $child;
            }
            if (!empty($child->children)) {
                $children = array_merge($children, $this->getAllChildren($child));
            }
        }

        return $children;
    }

    /**
     * Normalize the item title
     *
     * @param object $node
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function normalizeTitle($node)
    {
        $title = wp_strip_all_tags(!empty($node->title) ? $node->title : $node->id);

        return ucwords(trim(preg_replace('/[\d]/', '', $title)));
    }

    /**
     * Register Menu feature
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'toolbar',
            'position'   => 6,
            'title'      => __('Toolbar', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}