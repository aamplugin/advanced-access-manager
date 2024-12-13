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
     * @param array  $args
     * @param string $result_type [Optional] Can be "list", "summary" or "full"
     *
     * @return array|Generator
     * @access public
     *
     * @version 7.0.0
     */
    public function get_list(array $args = [], $result_type = 'list')
    {
        $args = array_merge([
            'blog_id'        => get_current_blog_id(),
            'fields'         => 'all',
            'number'         => 10,
            'offset'         => 0,
            'search'         => '',
            'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
            'orderby'        => 'display_name'
        ], $args);

        $query = new WP_User_Query($args);
        $data  = [];

        if ($result_type !== 'summary') {
            // Prepare the generator
            $generator = function () use ($query) {
                foreach ($query->get_results() as $user) {
                    yield $this->get_user($user);
                }
            };

            $data['list'] = $generator();
        }

        if (in_array($result_type, [ 'full', 'summary' ], true)) {
            $data['summary'] = [
                'total_count'    => count_users()['total_users'],
                'filtered_count' => $query->get_total()
            ];
        }

        if ($result_type === 'list') {
            $result = $data['list'];
        } elseif ($result_type === 'summary') {
            $result = $data['summary'];
        } else {
            $result = $data;
        }

        return $result;
    }

    /**
     * Alias for the get_list method
     *
     * @param array  $args
     * @param string $result_type [Optional] Can be "list", "summary" or "full"
     *
     * @return array|Generator
     * @access public
     *
     * @version 7.0.0
     */
    public function list(array $args = [], $result_type = 'list')
    {
        return $this->get_list($args, $result_type);
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
     * Alias for the get_user method
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_Proxy_User
     * @access public
     *
     * @version 7.0.0
     */
    public function user($identifier)
    {
        return $this->get_user($identifier);
    }

}