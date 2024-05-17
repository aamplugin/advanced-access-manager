<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract layer for the "subject"
 *
 * @package AAM
 *
 * @version 7.0.0
 */
abstract class AAM_Framework_Level_Abstract
{

    /**
     * Type of the subject
     *
     * Has to be overwritten by the class
     *
     * @param string
     * @version 6.0.0
     */
    const TYPE = 'abstract';

    /**
     * Core native instance of a subject
     *
     * This property holds an instance of a core object without abstracted AAM layer.
     * This way you have the ability to access methods and properties of the native
     * instance (e.g. WP_User or WP_Role)
     *
     * @var object
     *
     * @access private
     * @version 7.0.0
     */
    private $_core_instance;

    /**
     * Constructor
     *
     * @param object $core_instance
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __construct(object $core_instance = null)
    {
        $this->_core_instance = $core_instance;

        // Extend access level with more methods
        $closures = apply_filters(
            'aam_extend_level_filter', array(), static::TYPE, $core_instance
        );

        if (is_array($closures)) {
            foreach($closures as $closure) {
                $closure->bindTo($this);
            }
        }
    }

    /**
     * Check if current subject has specified capability
     *
     * @param string $capability
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function can($capability)
    {

    }

    /**
     * Alias for the `can` method
     *
     * @see AAM_Framework_Level_Abstract::can
     * @version 7.0.0
     */
    public function has_cap($capability)
    {
        return $this->can($capability);
    }

    /**
     * Alias for the `can` method
     *
     * @see AAM_Framework_Level_Abstract::can
     * @version 7.0.0
     */
    public function has_capability($capability)
    {
        return $this->can($capability);
    }


    public function add_cap($capability)
    {

    }

    public function add_capability($capability)
    {
        return $this->add_cap($capability);
    }

    public function remove_cap($capability)
    {

    }

    public function remove_capability($capability)
    {
        return $this->remove_cap($capability);
    }

    public function is_allowed($object)
    {

    }

    public function is_denied($object)
    {
        return !$this->allowed($object);
    }

    public function is_allowed_to($action, $object)
    {

    }

    public function is_denied_to($action, $object)
    {
        return !$this->is_allowed_to($action, $object);
    }

    public function deny($object)
    {

    }

    public function allow($object)
    {

    }

    public function deny_to($action, $object, $metadata = null)
    {

    }

    public function allow_to($action, $object, $metadata = null)
    {

    }

    public function reset($object = null, $action = null)
    {

    }

    public function policy($policy_id)
    {

    }

    // public function policies()
    // {

    // }

    // public function admin_menu($menu_id = null)
    // {

    // }

    // public function toolbar($toolbar_id = null)
    // {

    // }

    // public function metabox($metabox_id)
    // {

    // }

    // public function metaboxes()
    // {

    // }

    public function widget($widget_id)
    {

    }

    public function widgets()
    {

    }

    // public function capabilities()
    // {

    // }

    public function access_denied_redirect($area)
    {

    }

    public function access_denied_redirects()
    {

    }

    // public function login_redirect()
    // {

    // }

    // public function logout_redirect()
    // {

    // }

    public function not_found_redirect()
    {

    }

    public function api_routes()
    {

    }

    public function api_route($route_id)
    {

    }

    public function urls()
    {

    }

    public function url($url_id)
    {

    }

    /**
     * Get post object
     *
     * @param int|WP_Post $post_identifier
     *
     * @return AAM_Framework_Object_Post
     */
    public function post($post_identifier)
    {

    }

    /**
     * Get posts service
     *
     * @return AAM_Framework_Service_Posts
     */
    public function posts()
    {

    }

    /**
     * Get terms service
     *
     * @return AAM_Framework_Service_Terms
     */
    public function terms()
    {

    }

    /**
     * Get taxonomies service
     *
     * @return AAM_Framework_Service_Taxonomies
     */
    public function taxonomies()
    {

    }

    /**
     * Get post types service
     *
     * @return AAM_Framework_Service_PostTypes
     */
    public function post_types()
    {

    }

    /**
     * Proxy method to the legacy AAM subject
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @access public
     * @since 7.0.0
     */
    public function __call($name, $arguments)
    {
        $response = null;

        if (method_exists($this->_core_instance, $name)) {
            $response = call_user_func_array(
                array($this->_core_instance, $name), $arguments
            );
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'Method does not exist or is not accessible',
                AAM_VERSION
            );
        }

        return $response;
    }

    /**
     * Get a property of a core instance
     *
     * @param string $name
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function __get($name)
    {
        $response = null;

        if (is_object($this->_core_instance)) {
            $response = $this->_core_instance->{$name};
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'Property does not exist',
                AAM_VERSION
            );
        }

        return $response;
    }

}