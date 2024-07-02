<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Trait with methods to work with DB
 *
 * @package AAM
 * @version 6.9.34
 */
trait AAM_Framework_Service_DbTrait
{

    /**
     * Read option from DB
     *
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access private
     * @version 6.9.34
     */
    private function _read_option($option, $default = null)
    {
        if (is_multisite()) {
            $result = get_blog_option(get_current_blog_id(), $option, $default);
        } else {
            $result = get_option($option, $default);
        }

        return $result;
    }

    /**
     * Save configuration option to DB
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return boolean
     *
     * @access private
     * @version 6.9.34
     */
    private function _save_option($option, $value)
    {
        $old_value = $this->_read_option($option);

        if (maybe_serialize($old_value) !== maybe_serialize($value)) {
            if (is_multisite()) {
                $result = update_blog_option(get_current_blog_id(), $option, $value);
            } else {
                $result = update_option($option, $value, true);
            }
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * Delete option from DB
     *
     * @param string $option
     *
     * @return boolean
     *
     * @access private
     * @version 6.9.34
     */
    private function _delete_option($option)
    {
        if (is_multisite()) {
            $result = delete_blog_option(get_current_blog_id(), $option);
        } else {
            $result = delete_option($option);
        }

        return $result;
    }

}