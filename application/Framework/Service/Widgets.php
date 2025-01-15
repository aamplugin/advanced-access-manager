<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for widgets
 *
 * Widgets are functional block that are rendered on the admin dashboard and frontend
 * areas
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Widgets
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Restrict/hide widget
     *
     * @param mixed $widget
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($widget)
    {
        try {
            $result = $this->_update_item_permission($widget, 'deny');
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow widget
     *
     * @param mixed $widget
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($widget)
    {
        try {
            $result = $this->_update_item_permission($widget, 'allow');
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset permissions
     *
     * Resets all permissions if no $widget is provided. Otherwise, try to reset
     * permissions for a given widget or trigger a filter that invokes third-party
     * implementation.
     *
     * @param mixed $widget [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($widget = null)
    {
        try {
            $resource = $this->_get_resource();

            if (empty($widget)) {
                $result = $resource->reset();
            } else {
                $result = $resource->reset(
                    $this->_normalize_resource_identifier($widget)
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if metabox is restricted/hidden
     *
     * @param mixed $widget
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied($widget)
    {
        try {
            $result = $this->_is_denied($widget);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if metabox is allowed
     *
     * @param mixed $widget
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed($widget)
    {
        $result = $this->is_denied($widget);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Get widget resource
     *
     * @return AAM_Framework_Resource_Widget
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::WIDGET
        );
    }

     /**
     * Check if metabox is restricted
     *
     * @param string $metabox
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _is_denied($widget)
    {
        $result     = null;
        $resource   = $this->_get_resource();
        $identifier = $this->_normalize_resource_identifier($widget);
        $permission = $resource->get_permission($identifier, 'list');

        if (!empty($permission)) {
            $result = $permission['effect'] !== 'allow';
        }

        // Allow third-party implementations to integrate with the
        // decision making process
        $result = apply_filters(
            'aam_widget_is_denied_filter',
            $result,
            $identifier,
            $resource,
            is_array($widget) && isset($widget['area']) ? $widget['area'] : null
        );

        // Prepare the final answer
        return is_bool($result) ? $result : false;
    }

    /**
     * Update existing widget permission
     *
     * @param mixed $widget
     * @param bool  $is_denied
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _update_item_permission($widget, $effect)
    {
        try {
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($widget);

            // Prepare array of new permissions and save them
            $result = $resource->set_permission($identifier, 'list', $effect);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    private function _normalize_resource_identifier($widget)
    {
       // Determining metabox slug
       if (is_array($widget) && isset($widget['callback'])) {
            $result = $this->misc->callable_to_slug($widget['callback']);
        } elseif (is_a($widget, WP_Widget::class)) {
            $result = $this->misc->callable_to_slug($widget);
        } elseif (is_string($widget)) {
            $result = $this->misc->sanitize_slug($widget);
        } elseif (is_array($widget) && isset($widget['slug'])) {
            $result = $widget['slug'];
        } else {
            throw new InvalidArgumentException('Invalid widget provided');
        }

        return $result;
    }

}