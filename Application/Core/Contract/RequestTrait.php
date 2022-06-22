<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Work with HTTP requests
 *
 * @since 6.7.9 https://github.com/aamplugin/advanced-access-manager/issues/192
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.7.9
 */
trait AAM_Core_Contract_RequestTrait
{

    /**
     * Get data from the POST payload
     *
     * @param string $param
     * @param int    $filter
     * @param int    $options
     *
     * @return mixed
     *
     * @since 6.8.3 https://github.com/aamplugin/advanced-access-manager/issues/208
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.8.3
     */
    public function getFromPost($param, $filter = FILTER_DEFAULT, $options = 0)
    {
        $post = filter_input(INPUT_POST, $param, $filter, $options);

        if (is_null($post)) {
            $post = filter_var($this->readFromArray($_POST, $param), $filter, $options);
        }

        return $post;
    }

    /**
     * Get sanitized value from post
     *
     * @param string $param
     * @param int    $filter
     * @param int    $options
     *
     * @return mixed
     *
     * @since 6.8.3 https://github.com/aamplugin/advanced-access-manager/issues/208
     * @since 6.7.9 Initial implementation of the method
     *
     * @access public
     * @version 6.8.3
     */
    public function getSafeFromPost($param, $filter = FILTER_DEFAULT, $options = 0)
    {
        $value = $this->getFromPost($param, $filter, $options);

        return current_user_can('unfiltered_html') ? $value : wp_kses_post($value);
    }

    /**
     * Get data from the GET/Query
     *
     * @param string $param
     * @param int    $filter
     * @param int    $options
     *
     * @return mixed
     *
     * @since 6.8.3 https://github.com/aamplugin/advanced-access-manager/issues/208
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.8.3
     */
    public function getFromQuery($param, $filter = FILTER_DEFAULT, $options = 0)
    {
        $get = filter_input(INPUT_GET, $param, $filter, $options);

        if (is_null($get)) {
            $get = filter_var($this->readFromArray($_GET, $param), $filter, $options);
        }

        return $get;
    }

    /**
     * Get data from the super-global $_REQUEST
     *
     * @param string $param
     * @param int    $filter
     * @param int    $options
     *
     * @return mixed
     *
     * @since 6.8.3 https://github.com/aamplugin/advanced-access-manager/issues/208
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.8.3
     */
    public function getFromRequest($param, $filter = FILTER_DEFAULT, $options = 0)
    {
        return filter_var($this->readFromArray($_REQUEST, $param), $filter, $options);
    }

    /**
     * Get data from Cookie
     *
     * @param string $param
     * @param int    $filter
     * @param int    $options
     *
     * @return mixed
     *
     * @since 6.8.3 https://github.com/aamplugin/advanced-access-manager/issues/208
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.8.3
     */
    public function getFromCookie($param, $filter = FILTER_DEFAULT, $options = 0)
    {
        $cookie = filter_input(INPUT_COOKIE, $param, $filter, $options);

        if (is_null($cookie)) {
            $cookie = filter_var($this->readFromArray(
                $_COOKIE, $param), $filter, $options
            );
        }

        return $cookie;
    }

    /**
     * Get data from the super-global $_SERVER
     *
     * @param string $param
     * @param int    $filter
     * @param int    $options
     *
     * @return mixed
     *
     * @since 6.8.3 https://github.com/aamplugin/advanced-access-manager/issues/208
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.8.3
     */
    public function getFromServer($param, $filter = FILTER_DEFAULT, $options = 0)
    {
        $var = filter_input(INPUT_SERVER, $param, $filter, $options);

        // Cover the unexpected server issues (e.g. FastCGI may cause unexpected null)
        if (empty($var)) {
            $var = filter_var(
                $this->readFromArray($_SERVER, $param), $filter, $options
            );
        }

        return $var;
    }

    /**
     * Check array for specified parameter and return the it's value or
     * default one
     *
     * @param array  $array   Global array _GET, _POST etc
     * @param string $param   Array Parameter
     * @param mixed  $default Default value
     *
     * @return mixed
     *
     * @access protected
     * @version 6.0.0
     */
    protected function readFromArray($array, $param, $default = null)
    {
        $value = $default;

        if (is_null($param)) {
            $value = $array;
        } else {
            $chunks = explode('.', $param);
            $value = $array;
            foreach ($chunks as $chunk) {
                if (isset($value[$chunk])) {
                    $value = $value[$chunk];
                } else {
                    $value = $default;
                    break;
                }
            }
        }

        return $value;
    }

}