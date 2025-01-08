<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM framework utilities
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_Utility_Users implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Query list of users & return aggregated result
     *
     * @param array $args
     *
     * @return Generator
     * @access public
     *
     * @version 7.0.0
     */
    public function get_users(array $args = [])
    {
        $query  = $this->_prepare_user_query($args);
        $result = function () use ($query) {
            foreach ($query->get_results() as $user_id) {
                yield $this->get_user($user_id);
            }
        };

        return $result();
    }

    /**
     * Get user proxy object
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_Proxy_User|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_user($identifier)
    {
        if (is_numeric($identifier)) { // Get user by ID
            $user = get_user_by('id', $identifier);
        } elseif (is_string($identifier)) {
            if (strpos($identifier, '@') > 0) { // Email?
                $user = get_user_by('email', $identifier);
            } else {
                $user = get_user_by('login', $identifier);
            }
        } elseif (is_a($identifier, 'WP_User')) {
            $user = $identifier;
        } elseif (is_a($identifier, AAM_Framework_Proxy_User::class)) {
            $user = $identifier;
        } else {
            throw new InvalidArgumentException('Invalid user identifier');
        }

        $result = null;

        if (is_a($user, AAM_Framework_Proxy_User::class)) {
            $result = $user;
        } elseif (is_a($user, WP_User::class)) {
            $result = new AAM_Framework_Proxy_User($user);
        }

        return $result;
    }

    /**
     * Get total number of users
     *
     * @param array $args
     *
     * @return int
     * @access public
     *
     * @version 7.0.0
     */
    public function get_user_count(array $args = [])
    {
        if (empty($args)) {
            $result = count_users()['total_users'];
        } else {
            $result = $this->_prepare_user_query($args)->get_total();
        }

        return intval($result);
    }

    /**
     * Prepare user query args
     *
     * @param array $args
     *
     * @return WP_User_Query
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_user_query(array $args)
    {
        $args = array_merge([
            'blog_id'        => get_current_blog_id(),
            'fields'         => 'ID',
            'number'         => 10,
            'offset'         => 0,
            'search'         => '',
            'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
            'orderby'        => 'display_name'
        ], $args);

        $result = AAM_Framework_Manager::_()->object_cache->get($args);

        if (empty($result)) {
            $result = new WP_User_Query($args);

            AAM_Framework_Manager::_()->object_cache->set($args, $result);
        }

        return $result;
    }

}