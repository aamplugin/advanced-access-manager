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
 * @since 6.3.1 Fixed bug where draft policies get applied to assignees
 * @since 6.2.1 Added support for the POLICY_META token
 * @since 6.2.0 Fetched the way access policies are fetched
 * @since 6.1.0 Implemented `=>` operator. Improved inheritance mechanism
 * @since 6.0.4 Potential bug fix with improperly merged Param option:* values
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.3.1
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
     * Current subject
     *
     * @var AAM_Core_Subject
     *
     * @access protected
     * @version 6.0.0
     */
    protected $subject;

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
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     * @param boolean          $skipInheritance
     *
     * @access protected
     *
     * @since 6.1.0 Added new `$skipInheritance` mandatory argument
     * @since 6.0.0 Initial implementation of the method
     *
     * @return void
     * @version 6.1.0
     */
    public function __construct(AAM_Core_Subject $subject, $skipInheritance)
    {
        $this->object  = $subject->getObject(
            AAM_Core_Object_Policy::OBJECT_TYPE, null, $skipInheritance
        );

        $this->subject = $subject;
    }

    /**
     * Get policy parameter
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function getParam($id, $args = array())
    {
        $value = null;

        if (isset($this->tree['Param'][$id])) {
            $param = $this->tree['Param'][$id];

            if ($this->isApplicable($param, $args)) {
                if (preg_match_all('/(\$\{[^}]+\})/', $param['Value'], $match)) {
                    $value = AAM_Core_Policy_Token::evaluate(
                        $param['Value'], $match[1]
                    );
                } else {
                    $value = $param['Value'];
                }
            }
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
     * @access public
     * @version 6.0.0
     */
    public function getParams($s, $args = array())
    {
        if (is_array($s)) {
            $regex = '/^(' . implode('|', $s) . ')$/i';
        } else {
            $regex = "/^{$s}$/i";
        }

        $params = array();

        foreach ($this->tree['Param'] as $key => $param) {
            if (preg_match($regex, $key) && $this->isApplicable($param, $args)) {
                $params[$key] = $param;
            }
        }

        return $this->replaceTokens($params);
    }

    /**
     * Find all statements that match provided resource of list of resources
     *
     * @param string|array $s
     * @param array        $args
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getResources($s, $args = array())
    {
        if (is_array($s)) {
            $regex = '/^(' . implode('|', $s) . '):/i';
        } else {
            $regex = "/^{$s}:/i";
        }

        $statements = array();

        foreach ($this->tree['Statement'] as $key => $stm) {
            if (preg_match($regex, $key) && $this->isApplicable($stm, $args)) {
                // Remove the resource type to keep it clean
                $statements[preg_replace($regex, '', $key)] = $stm;
            }
        }

        return $this->replaceTokens($statements);
    }

    /**
     * Replace all the dynamic tokens recursively
     *
     * @param array $data
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function replaceTokens($data)
    {
        $replaced = array();

        foreach($data as $key => $value) {
            if (preg_match_all('/(\$\{[^}]+\})/', $key, $match)) {
                $key = AAM_Core_Policy_Token::evaluate($key, $match[1]);
            }

            if (is_array($value)) {
                $replaced[$key] = $this->replaceTokens($value);
            } elseif (preg_match_all('/(\$\{[^}]+\})/', $value, $match)) {
                $replaced[$key] = AAM_Core_Policy_Token::evaluate($value, $match[1]);
            } else {
                $replaced[$key] = $value;
            }
        }

        return $replaced;
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
     * @see AAM_Core_Policy_Manager::updatePolicyTree
     * @version 6.0.0
     */
    public function getOption($res, $option)
    {
        $param = $this->tree['Param']["option:{$option}"];

        if ($this->isApplicable($param)) {
            if (is_array($res) && is_array($param['Value'])) {
                $res = array_replace_recursive($res, $param['Value']);
            } else {
                $res = $param['Value'];
            }
        }

        return $res;
    }

    /**
     * Check if specified action is allowed for resource
     *
     * This method is working with "Statement" array.
     *
     * @param string $resource Resource name
     * @param array  $args     Args that will be injected during condition evaluation
     *
     * @return boolean|null
     *
     * @access public
     * @version 6.0.0
     */
    public function isAllowed($resource, $args = array())
    {
        $allowed = null;
        $id      = strtolower($resource);

        if (isset($this->tree['Statement'][$id])) {
            $stm = $this->tree['Statement'][$id];

            if ($this->isApplicable($stm, $args)) {
                $allowed = (strtolower($stm['Effect']) === 'allow');
            }
        }

        return $allowed;
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
     * Parse all attached policies into the tree
     *
     * @return void
     *
     * @since 6.3.1 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/49
     * @since 6.2.0 Changed the way access policies are fetched
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.1
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
                $this->updatePolicyTree($this->tree, $this->parsePolicy($policy));
            }

            $this->_cleanupTree();
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
     * @param array &$tree
     * @param array $addition
     *
     * @return array
     *
     * @since 6.2.1 Typecasting param's value
     * @since 6.1.0 Added support for the `=>` (map to) operator
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.2.1
     */
    protected function updatePolicyTree(&$tree, $addition)
    {
        $stmts  = &$tree['Statement'];
        $params = &$tree['Param'];

        // Step #1. If there are any statements, let's index them by resource:action
        // and insert into the list of statements
        foreach ($addition['Statement'] as $stm) {
            $resources = (isset($stm['Resource']) ? (array) $stm['Resource'] : array());
            $actions   = (isset($stm['Action']) ? (array) $stm['Action'] : array(''));

            foreach ($resources as $res) {
                $map = array(); // Reset map

                // Allow to build resource name dynamically.
                if (preg_match('/^(.*)[\s]+(map to|=>)[\s]+(.*)$/i', $res, $match)) {
                    // e.g. "Term:category:%s:posts => ${USER_META.regions}"
                    $values = (array) AAM_Core_Policy_Token::getTokenValue($match[3]);

                    // Create the map of resources and replace
                    foreach($values as $value) {
                        $map[] = sprintf($match[1], $value);
                    }
                } elseif (preg_match_all('/(\$\{[^}]+\})/', $res, $match)) {
                    // e.g. "Term:category:${USER_META.region}:posts"
                    $map = array(AAM_Core_Policy_Token::evaluate($res, $match[1]));
                } else {
                    $map = array($res);
                }

                foreach($map as $resource) {
                    foreach ($actions as $act) {
                        $id = strtolower($resource . (!empty($act) ? ":{$act}" : ''));

                        if (!isset($stmts[$id]) || empty($stmts[$id]['Enforce'])) {
                            $stmts[$id] = $stm;
                        }
                    }
                }
            }
        }

        $callback = array($this, 'getOption'); // Callback that hooks into get_option

        // Step #2. If there are any params, let's index them and insert into the list
        foreach ($addition['Param'] as $param) {
            if (!empty($param['Key'])) {
                // Allow to build param name dynamically.
                // e.g. "${USER_META.region}_posts"
                if (preg_match_all('/(\$\{[^}]+\})/', $param['Key'], $match)) {
                    $id = AAM_Core_Policy_Token::evaluate($param['Key'], $match[1]);
                } else {
                    $id = $param['Key'];
                }

                // If necessary typecast the params value
                $param['Value'] = AAM_Core_Policy_Typecast::execute($param['Value']);

                if (!isset($params[$id]) || empty($params[$id]['Enforce'])) {
                    $params[$id] = $param;

                    if (strpos($id, 'option:') === 0) {
                        $name = substr($id, 7);

                        // Hook into the core
                        add_filter('pre_option_' . $name, $callback, 1, 2);
                        add_filter('pre_site_option_' . $name, $callback, 1, 2);
                    }
                }
            }
        }
    }

    /**
     * Perform some internal clean-up
     *
     * @return void
     *
     * @access private
     * @version 6.0.0
     */
    private function _cleanupTree()
    {
        foreach($this->tree['Statement'] as $id => $stm) {
            if (isset($stm['Resource'])) {
                unset($this->tree['Statement'][$id]['Resource']);
            }
            if (isset($stm['Action'])) {
                unset($this->tree['Statement'][$id]['Action']);
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