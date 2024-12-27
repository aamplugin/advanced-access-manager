<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * JSON access policies service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Policies
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * JSON Access policy CPT
     *
     * @version 7.0.0
     */
    const CPT = 'aam_policy';

    /**
     * List of all registered policies for given access level
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_registered_policies = null;

     /**
     * Policy tree cache
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_policy_tree_cache = null;

    /**
     * Get list of access policies
     *
     * @param array $args [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_policies($args = [])
    {
        try {
            $policies = $this->_get_registered_policies($args);

            // Prepare the output result
            $result    = [];
            $container = $this->_get_container();

            foreach($policies as $post) {
                if (array_key_exists($post->ID, $container)) {
                    $is_attached = $container[$post->ID]['effect'] !== 'detach';
                } else {
                    $is_attached = false;
                }

                array_push($result, [
                    'id'            => $post->ID,
                    'status'        => $post->post_status,
                    'raw_policy'    => $post->post_content,
                    'parsed_policy' => json_decode($post->post_content, true),
                    'is_attached'   => $is_attached
                ]);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_policies method
     *
     * @param array $args
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function policies($args = [])
    {
        return $this->get_policies($args);
    }

    /**
     * Attach a policy to the access level
     *
     * @param int $policy_id
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function attach($policy_id)
    {
        try {
            // Attach new policy to the list
            $result = $this->_update($policy_id, 'attach');
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Detach a policy from the access level
     *
     * @param int $policy_id
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function detach($policy_id)
    {
        try {
            // Detach new policy to the list
            $result = $this->_update($policy_id, 'detach');
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Create new policy and attach it to current access level if specified
     *
     * @param mixed  $policy
     * @param string $status [Optional]
     * @param bool   $attach [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function create($policy, $status = 'publish', $attach = true)
    {
        try {
            // Let's validate the incoming policy first
            if (is_string($policy)) {
                $policy = json_decode(
                    htmlspecialchars_decode(stripslashes($policy)), true
                );

                if (json_last_error() === JSON_ERROR_NONE) {
                    throw new InvalidArgumentException(
                        'The provided policy is not a valid JSON'
                    );
                }
            }

            if (is_multisite()) {
                switch_to_blog($this->_get_main_site_id());
            }

            // Insert new policy
            $policy_id = wp_insert_post([
                'post_type'    => self::CPT,
                'status'       => $status,
                'post_content' => wp_json_encode($policy, JSON_PRETTY_PRINT)
            ]);

            if (is_multisite()) {
                restore_current_blog();
            }

            if (is_wp_error($policy_id)) {
                throw new RuntimeException($policy_id->get_error_message());
            } elseif ($attach) {
                $result = $this->_update($policy_id, 'attach');

                // TODO: Update policy tree
            } else {
                $result = true;
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get list of applicable statements for given resource key
     *
     * The resource_key can be a full resource name or part of it. RegEx is used
     * to find a match
     *
     * @param string|array $resource_key [Optional]
     * @param array        $args         [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_statements($resource_key = null, $args = [])
    {
        try {
            $result = [];

            foreach($this->_search('statement', $resource_key, $args) as $stm) {
                $result[$stm['Resource']] = array_filter($stm, function($k) {
                    return !in_array($k, [ 'Resource', 'Condition' ], true);
                }, ARRAY_FILTER_USE_KEY);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get list of applicable params for given param key
     *
     * The param_key can be a full param name or part of it. RegEx is used
     * to find a match
     *
     * @param string|array $param_key [Optional]
     * @param array        $args      [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_params($param_key = null, $args = [])
    {
        try {
            $result = [];

            foreach($this->_search('param', $param_key, $args) as $param) {
                $result[$param['Key']] = $param['Value'];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_statements method
     *
     * @param string|array $resource_key [Optional]
     * @param array        $args         [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function statements($resource_key = null, $args = [])
    {
        return $this->get_statements($resource_key, $args);
    }

    /**
     * Alias for the get_params method
     *
     * @param string|array $param_key [Optional]
     * @param array        $args      [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function params($param_key = null, $args = [])
    {
        return $this->get_params($param_key, $args);
    }

    /**
     * Reset attached policies for current access level
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function reset()
    {
        try {
            $result = $this->settings($this->_get_access_level())->delete_setting(
                'policy'
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get list of all registered access policies on the site
     *
     * @param array $args [Optional]
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_registered_policies($args = [])
    {
        if (is_null($this->_registered_policies)) {
            if (is_multisite()) {
                switch_to_blog($this->_get_main_site_id());
            }

            // Initializing the registered policies cache
            $this->_registered_policies = [];

            // Fetching the list of registered policies from DB
            $policies = get_posts(array_merge(
                [
                    'post_status' => [ 'publish', 'draft', 'pending' ],
                    'nopaging'    => true
                ],
                $args,
                [
                    'suppress_filters' => true,
                    'post_type'        => self::CPT
                ]
            ));

            foreach($policies as $policy) {
                $this->_registered_policies[$policy->ID] = [
                    'id'            => $policy->ID,
                    'status'        => $policy->post_status,
                    'raw_policy'    => $policy->post_content,
                    'parsed_policy' => $this->_parse_policy($policy)
                ];
            }

            if (is_multisite()) {
                restore_current_blog();
            }
        }

        return $this->_registered_policies;
    }

    /**
     * Access policy tree for given access level
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_access_level_policy_tree()
    {
        if (is_null($this->_policy_tree_cache)) {
            $this->_policy_tree_cache = [
                'Statement' => [],
                'Param'     => []
            ];

            // Prepare the list of all activated policies first
            $activated = [];

            // Getting the list of all registered policies
            $registered = $this->_get_registered_policies();

            // Get list of policies attached to current access level
            foreach($this->_get_container() as $policy_id => $data) {
                if ($data['effect'] !== 'detach'
                    && array_key_exists($policy_id, $registered)
                ) {
                    array_push($activated, $policy_id);
                }
            }

            // Iterated over the list of all activated policies and prepare the policy
            // tree
            foreach($activated as $policy_id) {
                $parsed = $registered[$policy_id]['parsed_policy'];

                $this->_policy_tree_cache = [
                    'Statement' => array_merge(
                        $this->_policy_tree_cache['Statement'],
                        $parsed['Statement']
                    ),
                    'Param'=> array_merge(
                        $this->_policy_tree_cache['Param'],
                        $parsed['Param']
                    )
                ];
            }
        }

        return $this->_policy_tree_cache;
    }

    /**
     * Get access policy container
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_container()
    {
        return $this->settings($this->_get_access_level())->get_setting(
            'policy', []
        );
    }

    /**
     * Parse access policy content and convert it to a normalized tree
     *
     * @param WP_Post $policy
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _parse_policy($policy)
    {
        // Parsing policy raw content
        // Any ${POLICY_META. replace with ${POLICY_META.123
        $raw = json_decode(str_replace(
            '${POLICY_META.',
            '${POLICY_META.' . $policy->ID . '.',
            $policy->post_content
        ), true);

        // Do not load the policy if any errors
        if (json_last_error() === JSON_ERROR_NONE) {
            $result = [
                'Statement' => $this->_get_array_of_arrays($raw, 'Statement'),
                'Param'     => $this->_get_array_of_arrays($raw, 'Param'),
            ];
        } else {
            $result = [ 'Statement' => [], 'Param' => [] ];

            // Make sure that this is noticed
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                sprintf(
                    'Access policy %d error %s', $policy->ID, json_last_error_msg()
                ),
                AAM_VERSION
            );
        }

        return $result;
    }

     /**
     * Get array of array for Statement and Param policy props
     *
     * @param array  $input
     * @param string $prop
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_array_of_arrays($input, $prop)
    {
        $response = [];

        // Parse Statements and determine if it is multidimensional
        if (array_key_exists($prop, $input)) {
            if (!isset($input[$prop][0]) || !is_array($input[$prop][0])) {
                $response = array($input[$prop]);
            } else {
                $response = $input[$prop];
            }
        }

        return $response;
    }

    /**
     * Search statement of param by given key
     *
     * @param string       $type
     * @param string|array $search_key
     * @param array        $args
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _search($type, $search_key, $args)
    {
        $result = [];

        if (is_array($search_key)) {
            $regex = '/^(' . implode('|', $search_key) . '):/i';
        } elseif (is_string($search_key)) {
            $regex = "/^{$search_key}/i";
        } else {
            $regex = null;
        }

        if ($type === 'statement') {
            $list = $this->_get_processed_statements($args);
        } else {
            $list = $this->_get_processed_params($args);
        }

        foreach ($list as $key => $candidates) {
            if (is_null($regex) || preg_match($regex, $key)) {
                $candidate = $this->_get_best_candidate($candidates, $args);

                if (!is_null($candidate)) {
                    array_push($result, $candidate);
                }
            }
        }

        return $result;
    }

    /**
     * Get all statements associated with current access level
     *
     * This method dynamically replaces all the markers
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_processed_statements($args)
    {
        $tree = $this->_replace_dynamic_markers(
            $this->_get_access_level_policy_tree(),
            $args
        );

        // Dynamically replacing all the markers
        return $tree['Statement'];
    }

    /**
     * Get all params associated with current access level
     *
     * This method dynamically replaces all the markers
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_processed_params($args)
    {
        $tree = $this->_replace_dynamic_markers(
            $this->_get_access_level_policy_tree(),
            $args
        );

        // Dynamically replacing all the markers
        return $tree['Param'];
    }

    /**
     * Recursively iterate over the tree and replace dynamic markers
     *
     * @param array $tree
     * @param array $args
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _replace_dynamic_markers($tree, $args)
    {
        // Step #1. We are building the tree of params & statements. Params go first
        //          because they can be used as markers for statements
        $params = [];

        foreach ($tree['Param'] as $param) {
            if (!empty($param['Key']) && !empty($param['Value'])) {
                foreach($this->_evaluate_policy_key($param['Key']) as $key) {
                    if (!array_key_exists($key, $params)) {
                        $params[$key] = [];
                    }

                    // Remove the Key and let's dynamically replace all other markers
                    array_push($params[$key], $this->_replace_markers_recursively(
                        $param, $args, [ 'Key', 'Condition' ]
                    ));
                }
            }
        }

        // Step #2. If there are any statements, let's index them by resource:action
        //          and insert into the list of statements
        $statements = [];

        foreach ($tree['Statement'] as $stm) {
            $resources = (isset($stm['Resource']) ? (array) $stm['Resource'] : []);
            $actions   = (isset($stm['Action']) ? (array) $stm['Action'] : [ '' ]);

            foreach ($resources as $res) {
                foreach($this->_evaluate_policy_key($res) as $resource) {
                    foreach ($actions as $action) {
                        $key = strtolower(
                            $resource . (!empty($action) ? ":{$action}" : '')
                        );

                        if (!array_key_exists($key, $statements)) {
                            $statements[$key] = [];
                        }

                        // Process the statement
                        $statement = $this->_replace_markers_recursively(
                            $stm, $args, [ 'Key', 'Action', 'Condition' ]
                        );

                        // If there is a specific action applied to the statement's
                        // resource, include it in the statement
                        if (!empty($action)){
                            $statement['Action'] = $action;
                        }

                        array_push($statements[$key], $statement);
                    }
                }
            }
        }

        return [
            'Statement' => $statements,
            'Param'     => $params
        ];
    }

    /**
     * Replace dynamic markers recursively
     *
     * @param array $array
     * @param array $args
     * @param array $skip_keys [Optional]
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _replace_markers_recursively(
        $array, $args, $skip_keys = []
    ) {
        $result = [];

        foreach($array as $key => $value) {
            if (in_array($key, $skip_keys, true)) {
                $result[$key] = $value; // Leave the entire branch as-is
            } else {
                // Note! We do not type-cast key because it has to be a scalar
                // value for all this thing to work. Type-casting for a key can
                // be done only inside Condition block as it it handled
                // differently
                $key = $this->_replace_markers($key, false, $args);

                if (is_array($value)) {
                    $result[$key] = $this->_replace_markers_recursively(
                        $value, $args
                    );
                } else {
                    $result[$key] = $this->_replace_markers($value, true, $args);
                }
            }
        }

        return $result;
    }

    /**
     * Evaluate resource name or param key
     *
     * The resource or param key may have tokens that build dynamic keys. This method
     * covers 3 possible scenario:
     * - Map To "=>" - the token should return array of values that are mapped to the
     *                 key;
     * - Token       - returns scalar value;
     * - Raw Value   - returns as-is
     *
     * @param string $key
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _evaluate_policy_key($key)
    {
        $result = [];

        // Allow to build resource name or param key dynamically.
        if (preg_match('/^(.*)[\s]+(map to|=>)[\s]+(.*)$/i', $key, $match)) {
            // e.g. "Term:category:%s:posts => ${USER_META.regions}"
            // e.g. "%s:default:category => ${HTTP_POST.post_types}"
            $values = (array) AAM_Framework_Policy_Marker::get_marker_value(
                $match[3]
            );

            // Create the map of resources/params and replace
            foreach($values as $value) {
                $result[] = sprintf($match[1], $value);
            }
        } else {
            $result[] = AAM_Framework_Policy_Marker::execute($key, [], false);
        }

        return $result;
    }

    /**
     * Replace all the dynamic markers recursively
     *
     * @param array $data
     * @param bool  $type_cast [Optional]
     * @param array $args      [Optional]
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _replace_markers($data, $type_cast = false, $args = [])
    {
        $replaced = array();

        if (is_scalar($data)) {
            $replaced = AAM_Framework_Policy_Marker::execute(
                $data, $args, $type_cast
            );
        } else {
            foreach($data as $key => $value) {
                // Evaluate array's key and replace markers
                $key = AAM_Framework_Policy_Marker::execute($key, $args, false);

                // Evaluate array's value and replace markers
                if (is_array($value)) {
                    $replaced[$key] = $this->_replace_markers(
                        $value, $type_cast, $args
                    );
                } else {
                    $replaced[$key] = AAM_Framework_Policy_Marker::execute(
                        $value, $args, $type_cast
                    );
                }
            }
        }

        return $replaced;
    }

    /**
     * Based on multiple competing statements or params, get the best candidate
     *
     * @param array $candidates
     * @param array $args
     *
     * @return array|null
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_best_candidate($candidates, $args)
    {
        $result = null;

        // Take in consideration ONLY currently applicable candidates and select
        // either the last one or the one that is enforced
        $enforced = false;

        foreach($candidates as $candidate) {
            if ($this->_is_applicable($candidate, $args)) {
                if (!empty($candidate['Enforce'])) {
                    $result   = $candidate;
                    $enforced = true;
                } elseif ($enforced === false) {
                    $result = $candidate;
                }
            }
        }

        return $result;
    }

    /**
     * Check if policy block is applicable
     *
     * @param array $block
     * @param array $args
     *
     * @return boolean
     * @access private
     *
     * @version 7.0.0
     */
    private function _is_applicable($block, $args)
    {
        $result = true;

        if (!empty($block['Condition']) && is_array($block['Condition'])) {
            $result = AAM_Framework_Policy_Condition::get_instance()->execute(
                $block['Condition'], $args
            );
        }

        return $result;
    }

    /**
     * Update policy container with new policy effect
     *
     * @param int    $policy_id
     * @param string $effect
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _update($policy_id, $effect)
    {
        $settings = $this->settings($this->_get_access_level());

        // Update policy permissions
        return $settings->set_setting('policy', array_replace(
            $settings->get_setting('policy', []),
            [ $policy_id => [ 'effect' => $effect ] ]
        ), true);
    }

    /**
     * Get main site ID in multi-site setup
     *
     * @return int
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_main_site_id()
    {
        if (function_exists('get_main_site_id')) {
            $result = get_main_site_id();
        } elseif (is_multisite()) {
            $network = get_network();
            $result  = ($network ? $network->site_id : 0);
        } else {
            $result = get_current_blog_id();
        }

        return $result;
    }

}