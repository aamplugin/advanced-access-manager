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
     * @return array
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
            'result_type'    => 'full',
            'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
            'orderby'        => 'display_name'
        ], $args);

        $query = new WP_User_Query($args);
        $data  = [];

        if ($result_type !== 'summary') {
            $data['list'] = [];

            foreach($query->get_results() as $user) {
                array_push($data['list'], AAM::api()->user($user));
            }
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

}