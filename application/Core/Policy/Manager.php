<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM policy manager for a specific subject
 *
 * @since 6.9.25 https://github.com/aamplugin/advanced-access-manager/issues/353
 *               https://github.com/aamplugin/advanced-access-manager/issues/355
 * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
 * @since 5.5.4  https://github.com/aamplugin/advanced-access-manager/issues/128
 * @since 6.5.3  https://github.com/aamplugin/advanced-access-manager/issues/122
 *               https://github.com/aamplugin/advanced-access-manager/issues/124
 * @since 6.4.0  Supporting Param's "Value" to be an array
 * @since 6.3.1  Fixed bug where draft policies get applied to assignees
 * @since 6.2.1  Added support for the POLICY_META token
 * @since 6.2.0  Fetched the way access policies are fetched
 * @since 6.1.0  Implemented `=>` operator. Improved inheritance mechanism
 * @since 6.0.4  Potential bug fix with improperly merged Param option:* values
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.25
 */
class AAM_Core_Policy_Manager
{

    /**
     * Policy core object
     *
     * @var AAM_Core_Object_Policy
     *
     * @access protected
     * @version 6.0.0
     */
    protected $object;

    /**
     * Parsed policy tree
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected $tree = array(
        'Statement' => array(),
        'Param'     => array()
    );

    /**
     * Effect stemming map
     *
     * @var array
     *
     * @access private
     * @version 6.9.24
     */
    private $_effects = array();

    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     * @param boolean          $skip_inheritance
     *
     * @access protected
     *
     * @since 6.1.0 Added new `$skip_inheritance` mandatory argument
     * @since 6.0.0 Initial implementation of the method
     *
     * @return void
     * @version 6.1.0
     */
    public function __construct(AAM_Core_Subject $subject, $skip_inheritance)
    {
        $this->object  = $subject->getObject(
            AAM_Core_Object_Policy::OBJECT_TYPE, null, $skip_inheritance
        );

        $this->_effects = apply_filters('aam_access_policy_effects_filter', array(
            'allowed' => 'allow',
            'denied'  => 'deny',
        ));
    }

    /**
     * Parse all attached policies into the tree
     *
     * @return void
     *
     * @since 6.5.3 https://github.com/aamplugin/advanced-access-manager/issues/124
     * @since 6.4.1 Changed the way updatePolicyTree is invoked
     * @since 6.3.1 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/49
     * @since 6.2.0 Changed the way access policies are fetched
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.5.3
     */
    public function initialize()
    {
        // Get the list of all policies that are attached to the subject
        $ids = array_filter($this->object->getOption(), function ($attached) {
            return !empty($attached);
        });

        // If there is at least one policy attached and it is published, then
        // parse into the tree
        if (count($ids)) {
            $policies = $this->fetchPolicies(array(
                'post_status' => array('publish'),
                'include'     => array_keys($ids)
            ));

            foreach ($policies as $policy) {
                $this->updatePolicyTree($this->parsePolicy($policy));
            }

            $this->_cleanupTree($this->tree['Statement']);
        }
    }

    /**
     * Fetch public policies by IDs
     *
     * @param array $ids
     *
     * @return array
     *
     * @since 6.2.0 Changed the way access policies are fetched to support multisite
     *              network setup
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.2.0
     */
    public function fetchPolicies($args = array())
    {
        do_action('aam_pre_policy_fetch_action');

        $posts = get_posts(wp_parse_args($args, array(
            'post_status'      => array('publish', 'draft', 'pending'),
            'suppress_filters' => true,
            'post_type'        => AAM_Service_AccessPolicy::POLICY_CPT,
            'nopaging'         => true
        )));

        do_action('aam_post_policy_fetch_action');

        return $posts;
    }

    /**
     * Evaluate unknown method
     *
     * Tries to process methods like isAllowed or isDeniedTo. This method recognizes
     * and executes the following methods /^(is)([a-z]+)(To)?$/
     *
     * @param string $name
     * @param array  $args
     *
     * @return boolean|null
     *
     * @since 6.9.25 https://github.com/aamplugin/advanced-access-manager/issues/353
     * @since 6.9.24 Initial implementation of the method
     *
     * @access public
     * @version 6.9.25
     */
    public function __call($name, $args)
    {
        $result = null;

        // We are calling method like isAllowed, isAttached or isDeniedTo
        if (strpos($name, 'is') === 0) {
            $resource = array_shift($args);

            if (strpos($name, 'To') === (strlen($name) - 2)) {
                $effect = substr($name, 2, -2);
                $action = array_shift($args);
            } else {
                $effect = substr($name, 2);
                $action = null;
            }

            $context_args = array_shift($args);

            // Method overload. If the next argument is boolean, then this indicates
            // the $default response. Otherwise, the $default is null and whatever is
            // in the $context_args is considered to be actual inline args
            if (is_bool($context_args)) {
                $default      = $context_args;
                $context_args = array_shift($args);
            } else {
                $default = null;
            }

            $result = $this->is(
                $resource,
                $this->_stemEffect($effect),
                $action,
                $default,
                is_array($context_args) ? $context_args : array()
            );
        }

        return $result;
    }

    /**
     * Get policy parameter
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     *
     * @since 6.4.1 https://github.com/aamplugin/advanced-access-manager/issues/84
     * @since 6.4.0 Supporting "Value" to be an array
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.4.1
     */
    public function getParam($id, $args = array())
    {
        $value = null;

        if (isset($this->tree['Param'][$id])) {
            $param = $this->getBestCandidate($this->tree['Param'][$id], $args);
            $value = is_null($param) ? null : $param['Value'];
        }

        return $value;
    }

    /**
     * Find all params that match provided search criteria
     *
     * @param string|array $s
     * @param array        $args
     *
     * @return array
     *
     * @since 6.4.1 https://github.com/aamplugin/advanced-access-manager/issues/84
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.4.1
     */
    public function getParams($s, $args = array())
    {
        if (is_array($s)) {
            $regex = '/^(' . implode('|', $s) . ')$/i';
        } else {
            $regex = "/^{$s}$/i";
        }

        $params = array();

        foreach (array_keys($this->tree['Param']) as $id) {
            if (preg_match($regex, $id)) {
                $params[$id] = $this->getBestCandidate(
                    $this->tree['Param'][$id], $args
                );
            }
        }

        return $params;
    }

    /**
     * Find all statements that match provided resource of list of resources
     *
     * @param string|array $s
     * @param array        $args
     *
     * @return array
     *
     * @since 6.5.3 https://github.com/aamplugin/advanced-access-manager/issues/124
     * @since 6.4.1 https://github.com/aamplugin/advanced-access-manager/issues/84
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.5.3
     */
    public function getResources($s, $args = array())
    {
        if (is_array($s)) {
            $regex = '/^(' . implode('|', $s) . '):/i';
        } else {
            $regex = "/^{$s}:/i";
        }

        $statements = array();

        foreach ($this->tree['Statement'] as $key => $stms) {
            if (preg_match($regex, $key)) {
                $stm = $this->getBestCandidate($stms, $args);

                if (!is_null($stm)) {
                    // Remove the resource type to keep it clean
                    $statements[preg_replace($regex, '', $key)] = $stm;
                }
            }
        }

        return $statements;
    }

    /**
     * Hook into WP core function to override WP options
     *
     * @param mixed  $res
     * @param string $option
     *
     * @return mixed
     *
     * @since 6.0.4 Fixed the potential bug with improperly merged options when Value
     *              is defined as multi-dimensional array
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.0.4
     */
    public function getOption($res, $option)
    {
        if (isset($this->tree['Param']["option:{$option}"])) {
            $param = $this->getBestCandidate(
                $this->tree['Param']["option:{$option}"]
            );
        }

        if (is_null($param)) {
            $res = null;
        } elseif (is_array($res) && is_array($param['Value'])) {
            $res = array_replace_recursive($res, $param['Value']);
        } else {
            $res = $param['Value'];
        }

        return $res;
    }

    /**
     * Get parsed policy tree
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Check if resource and/or action is allowed
     *
     * @param mixed     $resource Resource name or resource object
     * @param string    $effect   Constraint effect (e.g. allow, deny)
     * @param string    $action   Any specific action upon provided resource
     * @param bool|null $default  Default response
     * @param array     $args     Inline arguments that are added to the context
     *
     * @return boolean|null The `null` is returned if there is no applicable statements
     *                      that explicitly define effect
     *
     * @access protected
     * @version 6.9.24
     */
    protected function is($resource, $effect, $action, $default, $args)
    {
        $result = $default;
        $id     = strtolower($resource . (!empty($action) ? ':' . $action : ''));

        if (isset($this->tree['Statement'][$id])) {
            $stm = $this->getBestCandidate(
                $this->tree['Statement'][$id], $args
            );

            if (!is_null($stm)) {
                $result = (strtolower($stm['Effect']) === $effect);
            }
        }

        return $result;
    }

    /**
     * Stem the effect
     *
     * Basically try to stem the effect from something like "Allowed" to "allow", or
     * "Denied" to "deny".
     *
     * @param string $effect
     *
     * @return string
     *
     * @access private
     * @version 6.9.24
     */
    private function _stemEffect($effect)
    {
        $n = strtolower($effect);

        return (isset($this->_effects[$n]) ? $this->_effects[$n] : $n);
    }

    /**
     * Based on multiple competing statements or params, get the best candidate
     *
     * @param array $candidates
     * @param array $args
     *
     * @return array|null
     *
     * @since 6.9.25 https://github.com/aamplugin/advanced-access-manager/issues/355
     * @since 5.5.4  https://github.com/aamplugin/advanced-access-manager/issues/128
     * @since 6.5.3  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.25
     */
    protected function getBestCandidate($candidates, $args = array())
    {
        $candidate = null;

        if (is_array($candidates) && isset($candidates[0])) {
            // Take in consideration ONLY currently applicable candidates and select
            // either the last one or the one that is enforced
            $enforced = false;

            foreach($candidates as $c) {
                if ($this->isApplicable($c, $args)) {
                    if (!empty($c['Enforce'])) {
                        $candidate = $c;
                        $enforced  = true;
                    } elseif ($enforced === false) {
                        $candidate = $c;
                    }
                }
            }
        } else if ($this->isApplicable($candidates, $args)) {
            $candidate = $candidates;
        }

        return $candidate;
    }

    /**
     * Parse JSON policy and extract statements and params
     *
     * @param WP_Post $policy
     *
     * @return array
     *
     * @since 6.2.1 Added support for the POLICY_META token
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.2.1
     */
    protected function parsePolicy($policy)
    {
        // Any ${POLICY_META. replace with ${POLICY_META.123
        $json = str_replace(
            '${POLICY_META.',
            '${POLICY_META.' . $policy->ID . '.',
            $policy->post_content
        );
        $val  = json_decode($json, true);

        // Do not load the policy if any errors
        if (json_last_error() === JSON_ERROR_NONE) {
            $tree = array(
                'Statement' => $this->_getArrayOfArrays($val, 'Statement'),
                'Param'     => $this->_getArrayOfArrays($val, 'Param'),
            );
        } else {
            $tree = array('Statement' => array(), 'Param' => array());

            // Make sure that this is noticed
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                sprintf(
                    'Access policy %d error %s', $policy->ID, json_last_error_msg()
                ),
                AAM_VERSION
            );
        }

        return $tree;
    }

    /**
     * Get array of array for Statement and Param policy props
     *
     * @param array  $input
     * @param string $prop
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _getArrayOfArrays($input, $prop)
    {
        $response = array();

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
     * Extend tree with additional statements and params
     *
     * @param array $addition
     *
     * @return array
     *
     * @since 6.9.25 https://github.com/aamplugin/advanced-access-manager/issues/355
     * @since 6.5.3  https://github.com/aamplugin/advanced-access-manager/issues/122
     *               https://github.com/aamplugin/advanced-access-manager/issues/124
     * @since 6.4.1  Simplified by removing &$tree first param
     * @since 6.4.0  Supporting Param's Value to be more than just a scalar value
     * @since 6.2.1  Typecasting param's value
     * @since 6.1.0  Added support for the `=>` (map to) operator
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.25
     */
    protected function updatePolicyTree($addition)
    {
        $stmts  = &$this->tree['Statement'];
        $params = &$this->tree['Param'];

        $callback = array($this, 'getOption'); // Callback that hooks into get_option

        // Step #1. If there are any params, let's index them and insert into the list
        foreach ($addition['Param'] as $param) {
            if (!empty($param['Key'])) {
                $param['Value'] = $this->replaceTokens($param['Value'], true);

                foreach($this->evaluatePolicyKey($param['Key']) as $key) {
                    if (!isset($params[$key]) || empty($params[$key]['Enforce'])) {
                        if (!isset($params[$key])) {
                            $params[$key] = array();
                        }

                        array_push($params[$key], $param);

                        // If "option:" - hooks to the WP core for override
                        if (strpos($key, 'option:') === 0) {
                            $name = substr($key, 7);

                            // Hook into the core
                            add_filter('pre_option_' . $name, $callback, 1, 2);
                            add_filter('pre_site_option_' . $name, $callback, 1, 2);
                        }
                    }
                }
            }
        }

        // Step #2. If there are any statements, let's index them by resource:action
        // and insert into the list of statements
        foreach ($addition['Statement'] as $stm) {
            $resources = (isset($stm['Resource']) ? (array) $stm['Resource'] : array());
            $actions   = (isset($stm['Action']) ? (array) $stm['Action'] : array(''));

            foreach ($resources as $res) {
                foreach($this->evaluatePolicyKey($res) as $resource) {
                    foreach ($actions as $act) {
                        $id = strtolower($resource . (!empty($act) ? ":{$act}" : ''));

                        if (!isset($stmts[$id])) {
                            $stmts[$id] = array();
                        }

                        array_push($stmts[$id], $stm);
                    }
                }
            }
        }
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
     *
     * @access protected
     * @version 6.4.1
     */
    protected function evaluatePolicyKey($key)
    {
        $response = array();

        // Allow to build resource name or param key dynamically.
        if (preg_match('/^(.*)[\s]+(map to|=>)[\s]+(.*)$/i', $key, $match)) {

            // e.g. "Term:category:%s:posts => ${USER_META.regions}"
            // e.g. "%s:default:category => ${HTTP_POST.post_types}"
            $values = (array) AAM_Core_Policy_Token::getTokenValue($match[3]);

            // Create the map of resources/params and replace
            foreach($values as $value) {
                $response[] = sprintf($match[1], $value);
            }
        } elseif (preg_match_all('/(\$\{[^}]+\})/', $key, $match)) {
            // e.g. "Term:category:${USER_META.region}:posts"
            $response = array(AAM_Core_Policy_Token::evaluate($key, $match[1]));
        } else {
            $response = array($key);
        }

        return $response;
    }

    /**
     * Replace all the dynamic tokens recursively
     *
     * @param array   $data
     * @param boolean $type_cast
     *
     * @return array
     *
     * @since 6.4.1 Added type casting param
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.4.1
     */
    protected function replaceTokens($data, $type_cast = false)
    {
        $replaced = array();

        if (is_scalar($data)) {
            $replaced = $this->_replaceTokensInString($data, $type_cast);
        } else {
            foreach($data as $key => $value) {
                // Evaluate array's key and replace tokens
                $key = $this->_replaceTokensInString($key);

                // Evaluate array's value and replace tokens
                if (is_array($value)) {
                    $replaced[$key] = $this->replaceTokens($value, $type_cast);
                } else {
                    $replaced[$key] = $this->_replaceTokensInString(
                        $value, $type_cast
                    );
                }
            }
        }

        return $replaced;
    }

    /**
     * Replace tokens is provided scalar string
     *
     * @param string  $token
     * @param boolean $type_cast
     *
     * @return mixed
     *
     * @access private
     * @version 6.4.1
     */
    private function _replaceTokensInString($token, $type_cast = false)
    {
        if (preg_match_all('/(\$\{[^}]+\})/', $token, $match)) {
            $value = AAM_Core_Policy_Token::evaluate($token, $match[1]);

            if ($type_cast === true) {
                $replaced = AAM_Core_Policy_Typecast::execute($value);
            } else {
                $replaced = $value;
            }
        } else {
            $replaced = $token;
        }

        return  $replaced;
    }

    /**
     * Perform some internal clean-up
     *
     * @param array &$statements
     *
     * @return void
     *
     * @since 6.5.3 https://github.com/aamplugin/advanced-access-manager/issues/124
     * @since 6.0.0 Initial implementation of the method
     *
     * @access private
     * @version 6.5.3
     */
    private function _cleanupTree(&$statements)
    {
        foreach($statements as $id => &$stm) {
            if (is_array($stm) && isset($stm[0])) {
                $this->_cleanupTree($stm);
            } else {
                if (isset($stm['Resource'])) {
                    unset($statements[$id]['Resource']);
                }
                if (isset($stm['Action'])) {
                    unset($statements[$id]['Action']);
                }
            }
        }
    }

    /**
     * Check if policy block is applicable
     *
     * @param array $block
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function isApplicable($block, $args = array())
    {
        $result = true;

        if (!empty($block['Condition']) && is_array($block['Condition'])) {
            $result = AAM_Core_Policy_Condition::getInstance()->evaluate(
                $block['Condition'], $args
            );
        }

        return $result;
    }

}