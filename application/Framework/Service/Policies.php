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
     * Cache policy tree
     *
     * To improve performance, caching parsed policy tree
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_cached_policy_tree = null;

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
            $result = $this->_get_registered_policies($args);
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
     * Get a single registered policy
     *
     * @param int $policy_id
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_policy($policy_id)
    {
        try {
            $match = array_filter(
                $this->_get_registered_policies(), function($p) use ($policy_id) {
                    return $p === $policy_id;
                }, ARRAY_FILTER_USE_KEY
            );

            if (!empty($match)) {
                $result = array_shift($match);
            } else {
                throw new OutOfRangeException(
                    sprintf('Policy with ID %d does not exist', $policy_id)
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_policy method
     *
     * @param int $policy_id
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function policy($policy_id)
    {
        return $this->get_policy($policy_id);
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

            if ($result) {
                $this->_registered_policies[$policy_id] = $this->_prepare_policy_item(
                    get_post($policy_id)
                );

                // Reset internal cache
                $this->_cached_policy_tree = null;
            }
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

            if ($result) {
                $this->_registered_policies[$policy_id] = $this->_prepare_policy_item(
                    get_post($policy_id)
                );

                // Reset internal cache
                $this->_cached_policy_tree = null;
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Create new policy and attach it to current access level if specified
     *
     * @param string|array $policy
     * @param string       $status [Optional]
     * @param bool         $attach [Optional]
     *
     * @return int|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function create($policy, $status = 'publish', $attach = true)
    {
        try {
            $post_data = [];

            // Let's validate the incoming policy first
            if (is_string($policy)) {
                $post_content = $policy;
            } elseif (is_array($policy) && isset($policy['json']) ) {
                $post_content = $policy['json'];
                $post_data    = [
                    'post_title'   => isset($policy['title']) ? $policy['title']: '',
                    'post_excerpt' => isset($policy['excerpt']) ? $policy['excerpt']: '',
                ];
            } else {
                throw new InvalidArgumentException('Invalid policy provided');
            }

            $decoded = json_decode(
                htmlspecialchars_decode(stripslashes($post_content)), true
            );

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException(
                    'The provided policy is not a valid JSON'
                );
            }

            if (is_multisite()) {
                switch_to_blog($this->_get_main_site_id());
            }

            // Insert new policy
            $result = wp_insert_post(array_merge(
                [ 'post_status'  => $status ],
                $post_data,
                [
                    'post_type'    => self::CPT,
                    'post_content' => wp_json_encode($decoded, JSON_PRETTY_PRINT)
                ]
            ));

            if (is_multisite()) {
                restore_current_blog();
            }

            if (is_wp_error($result)) {
                throw new RuntimeException($result->get_error_message());
            } elseif ($attach) {
                if (!$this->_update($result, 'attach')) {
                    throw new RuntimeException('Failed to attach created policy');
                }

                // Insert policy into the list of registered policies
                $this->_registered_policies[$result] = $this->_prepare_policy_item(
                    get_post($result)
                );

                // Reset internal cache
                $this->_cached_policy_tree = null;
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
     * @param string $resource_pattern [Optional]
     * @param array  $args             [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_statements($resource_pattern = null, $args = [])
    {
        try {
            $result  = [];
            $matches = $this->_search('statement', $resource_pattern, $args);

            foreach($matches as $key => $stm) {
                $result[$key] = array_filter($stm, function($k) {
                    return !in_array($k, [ 'Condition' ], true);
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
     * @param string $key_pattern [Optional]
     * @param array  $args        [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_params($key_pattern = null, $args = [])
    {
        try {
            $result  = [];
            $matches = $this->_search('param', $key_pattern, $args);

            foreach($matches as $key => $param) {
                $result[$key] = $param['Value'];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get a single param
     *
     * The $key has to be an exact match for the parameter, otherwise the WP_Error is
     * returned.
     *
     * @param string $key
     * @param array  $args [Optional]
     *
     * @return mixed|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function get_param($key, $args = [])
    {
        $result = null;

        try {
            foreach ($this->_get_processed_params($args) as $param_key => $params) {
                if ($param_key === $key) {
                    $candidate = $this->_get_best_candidate($params, $args);

                    if (!is_null($candidate)) {
                        $result = $candidate['Value'];

                        break; // No need to search any further
                    }
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_statements method
     *
     * @param string $resource_pattern [Optional]
     * @param array  $args             [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function statements($resource_pattern = null, $args = [])
    {
        return $this->get_statements($resource_pattern, $args);
    }

    /**
     * Alias for the get_params method
     *
     * @param string $key_pattern [Optional]
     * @param array  $args        [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function params($key_pattern = null, $args = [])
    {
        return $this->get_params($key_pattern, $args);
    }

    /**
     * Alias for the get_param method
     *
     * The $key has to be an exact match for the parameter, otherwise the WP_Error is
     * returned.
     *
     * @param string $key
     * @param array  $args [Optional]
     *
     * @return mixed|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function param($key, $args = [])
    {
        return $this->get_param($key, $args);
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
            $result = $this->_get_resource()->reset();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Are permissions customized for current access level
     *
     * Determine if permissions for the resource are customized for the current
     * access level. Permissions are considered customized if there is at least one
     * permission explicitly allowed or denied.
     *
     * @return boolean
     * @version 7.0.0
     */
    public function is_customized()
    {
        try {
            $result = $this->_get_resource()->is_customized();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if policy is attached to current access level
     *
     * @param int $policy_id
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_attached($policy_id)
    {
        try {
            $result = $this->_is_attached($policy_id);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if policy is detached from current access level
     *
     * @param int $policy_id
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_detached($policy_id)
    {
        try {
            $result = !$this->_is_attached($policy_id);
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
            // Initializing the registered policies cache
            $this->_registered_policies = [];

            // Fetching the list of registered policies from DB
            $policies = get_posts(array_merge(
                [
                    'post_status' => [ 'publish', 'draft', 'pending', 'private' ],
                    'nopaging'    => true,
                    'order'       => 'ASC',
                    'orderby'     => 'post_parent, menu_order'
                ],
                $args,
                [
                    'suppress_filters' => true,
                    'post_type'        => self::CPT
                ]
            ));

            foreach($policies as $policy) {
                $this->_registered_policies[$policy->ID] = $this->_prepare_policy_item(
                    $policy
                );
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
        if (is_null($this->_cached_policy_tree)) {
            // Reset policy tree each time we get it
            $result = [
                'Statement' => [],
                'Param'     => []
            ];

            // Important! Retain the exact order of fetched policies
            $permissions = $this->_get_resource()->get_permissions();

            foreach($this->_get_registered_policies() as $id => $policy) {
                if (array_key_exists($id, $permissions)
                    && $permissions[$id]['attach']['effect'] !== 'detach'
                ) {
                    $this->_insert_policy_into_tree($policy, $result);
                }
            }

            // Cache the tree
            $this->_cached_policy_tree = $result;
        } else {
            $result = $this->_cached_policy_tree;
        }

        return $result;
    }

    /**
     * Get access policy resource
     *
     * @return AAM_Framework_Resource_Policy
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::POLICY
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
     * @version 7.0.5
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
        if (json_last_error() === JSON_ERROR_NONE && is_array($raw)) {
            $result = [
                'Statement' => $this->_get_array_of_arrays($raw, 'Statement'),
                'Param'     => $this->_get_array_of_arrays($raw, 'Param'),
            ];
        } else {
            $result = [ 'Statement' => [], 'Param' => [] ];

            // Make sure that this is noticed
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                sprintf('Invalid access policy (ID: %d)', $policy->ID),
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
     * @param string $type
     * @param string $pattern
     * @param array  $args
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _search($type, $pattern, $args)
    {
        $result = [];

        if (is_string($pattern)) {
            $regex = '/^' . str_replace('\*', '(.*)', preg_quote($pattern)) .  '/i';
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
                    $result[$key] = $candidate;
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
                            $stm, $args, [ 'Resource', 'Action', 'Condition' ]
                        );

                        // Making sure we have a single representation of the
                        // Resource attribute
                        $statement['Resource'] = $resource;

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
        return $this->_get_resource()->set_permission(
            $this->_normalize_resource_identifier($policy_id),
            'attach',
            $effect
        );
    }

    /**
     * Prepare policy item
     *
     * @param WP_Post $policy
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_policy_item($policy)
    {
        return [
            'id'          => $policy->ID,
            'status'      => $policy->post_status,
            'json'        => $policy->post_content,
            'parsed'      => $this->_parse_policy($policy),
            'is_attached' => $this->_is_attached($policy->ID),
            'ref'         => $policy
        ];
    }

    /**
     * Determine if policy is attached to current access level
     *
     * @param int $policy_id
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _is_attached($policy_id)
    {
        $permission = $this->_get_resource()->get_permission(
            $this->_normalize_resource_identifier($policy_id),
            'attach'
        );

        if (!empty($permission)) {
            $result = $permission['effect'] !== 'detach';
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Insert a policy into a policy tree
     *
     * The policy tree represents a collection of statements and params from
     * published policies that are attached to current access level
     *
     * @param array $policy
     * @param array &$policy_tree
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _insert_policy_into_tree($policy, &$policy_tree)
    {
        if ($policy['status'] === 'publish') {
            $policy_tree = [
                'Statement' => array_merge(
                    $policy_tree['Statement'],
                    $policy['parsed']['Statement']
                ),
                'Param'=> array_merge(
                    $policy_tree['Param'],
                    $policy['parsed']['Param']
                )
            ];
        }
    }

    /**
     * @inheritDoc
     *
     * @return WP_Post
     */
    private function _normalize_resource_identifier($resource_identifier)
    {
        $result = null;

        if (is_a($resource_identifier, WP_Post::class)) {
            $result = $resource_identifier;
        } elseif (is_numeric($resource_identifier)) {
            $result = get_post($resource_identifier);
        } elseif (is_array($resource_identifier)) {
            if (isset($resource_identifier['id'])) {
                $result = get_post($resource_identifier['id']);
            } else {
                // Let's get post_name
                if (isset($resource_identifier['slug'])) {
                    $post_name = $resource_identifier['slug'];
                } elseif (isset($resource_identifier['post_name'])) {
                    $post_name = $resource_identifier['post_name'];
                }

                if (!empty($post_name)) {
                    $result = get_page_by_path(
                        $post_name,
                        OBJECT,
                        self::CPT
                    );
                }
            }

            // Do some additional validation if id & post_type are provided in the
            // array
            if (is_a($result, WP_Post::class)
                && self::CPT !== $result->post_type
            ) {
                throw new OutOfRangeException('Invalid policy instance');
            }
        }

        if (!is_a($result, WP_Post::class)) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        return $result;
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