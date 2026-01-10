<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Database utility
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Utility_Db implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Internal cache
     *
     * @var array
     * @access private
     *
     * @version 7.0.11
     */
    private $_cache = [];

    /**
     * Read option from DB
     *
     * @param string $option
     * @param mixed  $default [Optional]
     * @param int    $blog_id [Optional]
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    public function read($option, $default = null, $blog_id = null)
    {
        if (is_multisite()) {
            $result = get_blog_option(
                empty($blog_id) ? get_current_blog_id() : $blog_id,
                $option,
                $default
            );
        } else {
            $result = get_option($option, $default);
        }

        return $result;
    }

    /**
     * Save option to DB
     *
     * @param string $option
     * @param mixed  $value
     * @param bool   $autoload [Optional]
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function write($option, $value, $autoload = true)
    {
        $old_value = $this->read($option);

        if ($old_value === null) { // Option does not exist, add it
            if (is_multisite()) {
                $result = add_blog_option(get_current_blog_id(), $option, $value);
            } else {
                $result = add_option($option, $value, '', $autoload);
            }
        } elseif (maybe_serialize($old_value) !== maybe_serialize($value)) {
            if (is_multisite()) {
                $result = update_blog_option(get_current_blog_id(), $option, $value);
            } else {
                $result = update_option($option, $value, $autoload);
            }
        } else{
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
     * @access public
     *
     * @version 7.0.0
     */
    public function delete($option)
    {
        if (is_multisite()) {
            $result = delete_blog_option(get_current_blog_id(), $option);
        } else {
            $result = delete_option($option);
        }

        return $result;
    }

    /**
     * Query DB
     *
     * @param string $query
     *
     * @return array|null
     * @access public
     *
     * @version 7.0.11
     */
    public function get_results($query)
    {
        global $wpdb;

        // Checking if we have results already cached
        $cache_key = md5($query);

        if (array_key_exists($cache_key, $this->_cache)) {
            $result = $this->_cache[$cache_key];
        } else {
            $result = $wpdb->get_results($query, ARRAY_A);

            if ($result !== null) {
                $this->_cache[$cache_key] = $result;
            }
        }

        return $result;
    }

}