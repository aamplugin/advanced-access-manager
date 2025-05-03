<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM access policy condition evaluator
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Policy_Condition
{

    /**
     * Map between condition type and method that evaluates the
     * group of conditions
     *
     * @var array
     * @access protected
     *
     * @version 7.0.0
     */
    protected $map = array(
        'between'         => 'evaluate_between',
        'equals'          => 'evaluate_equals',
        'notequals'       => 'evaluate_not_equals',
        'greater'         => 'evaluate_greater',
        'less'            => 'evaluate_less',
        'greaterorequals' => 'evaluate_greater_or_equals',
        'lessorequals'    => 'evaluate_less_or_equals',
        'in'              => 'evaluate_in',
        'notin'           => 'evaluate_not_in',
        'like'            => 'evaluate_like',
        'notlike'         => 'evaluate_not_like',
        'regex'           => 'evaluate_regex'
    );

    /**
     * Single instance of itself
     *
     * @var AAM_Framework_Policy_Condition
     * @access private
     *
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct() { }

    /**
     * Evaluate the group of conditions based on type
     *
     * @param array $conditions List of conditions
     * @param array $args       Since 5.9 - Inline args for evaluation
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function execute($conditions, $args = [])
    {
        $res = true;

        // Determine if we have a logical operator defined
        $operator = isset($conditions['Operator']) ? $conditions['Operator'] : 'AND';

        foreach ($conditions as $type => $group) {
            $type = strtolower($type);

            if ($type === 'operator') {
                continue;
            } elseif (isset($this->map[$type])) {
                $callback       = array($this, $this->map[$type]);
                $group          = $this->_prepare_condition_group($group);
                $group_operator = isset($group['Operator']) ? $group['Operator'] : 'OR';

                $res = $this->_compute(
                    $res,
                    call_user_func($callback, $group, $args, $group_operator),
                    $operator
                );
            } else {
                $res = apply_filters(
                    'aam_policy_condition_result_filter',
                    false,
                    $type,
                    $this->_prepare_conditions(
                        $this->_prepare_condition_group($group), $args
                    ),
                    $args
                );
            }
        }

        return $res;
    }

    /**
     * Evaluate group of BETWEEN conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_between($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->_prepare_conditions($conditions, $args) as $condition) {
            if (isset($condition['right'][0]) && is_array($condition['right'][0])) {
                $right = $condition['right'];
            } else {
                $right = array($condition['right']);
            }

            foreach ($right as $subset) {
                if (is_a($subset, 'Closure')) {
                    $result = $this->_compute(
                        $result, $subset($condition['left']), $operator
                    );
                } else {
                    list($min, $max) = $subset;

                    $result = $this->_compute(
                        $result,
                        ($condition['left'] >= $min && $condition['left'] <= $max),
                        $operator
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Evaluate group of EQUALS conditions
     *
     * The values have to be identical
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_equals($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->_prepare_conditions($conditions, $args) as $condition) {
            foreach ($this->_prepare_right_operand($condition['right']) as $right) {
                $result = $this->_compute(
                    $result,
                    ($condition['left'] === $right),
                    $operator
                );
            }
        }

        return $result;
    }

    /**
     * Evaluate group of NOT EQUALs conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_not_equals($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->_prepare_conditions($conditions, $args) as $condition) {
            foreach ($this->_prepare_right_operand($condition['right']) as $right) {
                $result = $this->_compute(
                    $result,
                    ($condition['left'] !== $right),
                    $operator
                );
            }
        }

        return $result;
    }

    /**
     * Evaluate group of GREATER THEN conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_greater($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->_prepare_conditions($conditions, $args) as $condition) {
            foreach ($this->_prepare_right_operand($condition['right']) as $right) {
                $result = $this->_compute(
                    $result,
                    ($condition['left'] > $right),
                    $operator
                );
            }
        }

        return $result;
    }

    /**
     * Evaluate group of LESS THEN conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_less($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->_prepare_conditions($conditions, $args) as $condition) {
            foreach ($this->_prepare_right_operand($condition['right']) as $right) {
                $result = $this->_compute(
                    $result,
                    ($condition['left'] < $right),
                    $operator
                );
            }
        }

        return $result;
    }

    /**
     * Evaluate group of GREATER OR EQUALS THEN conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_greater_or_equals($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->_prepare_conditions($conditions, $args) as $condition) {
            foreach ($this->_prepare_right_operand($condition['right']) as $right) {
                $result = $this->_compute(
                    $result,
                    ($condition['left'] >= $right),
                    $operator
                );
            }
        }

        return $result;
    }

    /**
     * Evaluate group of LESS OR EQUALS THEN conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_less_or_equals($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->_prepare_conditions($conditions, $args) as $condition) {
            foreach ($this->_prepare_right_operand($condition['right']) as $right) {
                $result = $this->_compute(
                    $result,
                    ($condition['left'] <= $right),
                    $operator
                );
            }
        }

        return $result;
    }

    /**
     * Evaluate group of IN conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_in($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->_prepare_conditions($conditions, $args) as $condition) {
            // Transform the left and right operands, if necessary
            if (is_a($condition['left'], 'Closure')) {
                $left = $condition['left'](); // Left operand does not require any values to cb
            } else {
                $left = $condition['left'];
            }

            if (isset($condition['right'][0]) && is_array($condition['right'][0])) {
                $normalized_right = $condition['right'];
            } else {
                $normalized_right = array($condition['right']);
            }

            foreach ($normalized_right as $right) {
                if (is_a($right, 'Closure')) {
                    $right = $right($left); // Right operand accepts left operand
                }

                // If both operands are arrays, then this condition verifies that the
                // left set of values is present in the right set of values
                if (is_array($left) && is_array($right)) {
                    $ci     = count(array_intersect($left, $right));
                    $result = $this->_compute(
                        $result, count($left) === $ci, $operator
                    );
                } elseif (is_scalar($left) && is_array($right)) {
                    $set_result = null;

                    foreach($right as $value) {
                        if (is_scalar($value)) {
                            $set_result = $set_result || ($left === $value);
                        } elseif (is_a($value, 'Closure')) {
                            $set_result = $set_result || $value($left);
                        }
                    }

                    $result = $this->_compute($result, $set_result, $operator);
                }
            }
        }

        return $result;
    }

    /**
     * Evaluate group of NOT IN conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_not_in($conditions, $args, $operator)
    {
        return !$this->evaluate_in($conditions, $args, $operator);
    }

    /**
     * Evaluate group of LIKE conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_like($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->_prepare_conditions($conditions, $args) as $condition) {
            foreach ($this->_prepare_right_operand($condition['right']) as $right) {
                $sub = str_replace(
                    array('\*', '@'), array('.*', '\\@'), preg_quote($right)
                );

                $result = $this->_compute(
                    $result,
                    preg_match('@^' . $sub . '$@', $condition['left']),
                    $operator
                );
            }
        }

        return $result;
    }

    /**
     * Evaluate group of NOT LIKE conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_not_like($conditions, $args, $operator)
    {
        return !$this->evaluate_like($conditions, $args, $operator);
    }

    /**
     * Evaluate group of REGEX conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function evaluate_regex($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->_prepare_conditions($conditions, $args) as $condition) {
            foreach ($this->_prepare_right_operand($condition['right']) as $right) {
                $result = $this->_compute(
                    $result,
                    preg_match($right, $condition['left']),
                    $operator
                );
            }
        }

        return $result;
    }

    /**
     * Prepare conditions by replacing all defined tokens
     *
     * @param array $conditions
     * @param array $args
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_conditions($conditions, $args)
    {
        $result = [];

        if (is_array($conditions)) {
            foreach ($conditions as $left => $right) {
                if ($left !== 'Operator') {
                    $result[] = array(
                        'left'  => $this->_parse_expression($left, $args),
                        'right' => $this->_parse_expression($right, $args)
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Parse condition and try to replace all defined tokens
     *
     * @param mixed $exp  Part of the condition (either left or right)
     * @param array $args Inline arguments
     *
     * @return mixed Prepared part of the condition or false on failure
     * @access private
     *
     * @version 7.0.0
     */
    private function _parse_expression($exp, $args)
    {
        if (is_scalar($exp)) {
            $exp = AAM_Framework_Policy_Marker::execute($exp, $args);
        } elseif (is_array($exp) || is_object($exp)) {
            foreach ($exp as &$value) {
                $value = $this->_parse_expression($value, $args);
            }
        } elseif (is_null($exp) === false) {
            $exp = false;
        }

        return $exp;
    }

     /**
     * Compute the logical expression
     *
     * @param boolean $left
     * @param boolean $right
     * @param string  $operator
     *
     * @return boolean|null
     * @access private
     *
     * @version 7.0.0
     */
    private function _compute($left, $right, $operator)
    {
        $upper  = strtoupper($operator);
        $result = null;

        // The first condition that checks for null is super important to ensure that
        // we cover the first condition in chain
        if ($left === null) {
            $result = $right;
        } elseif ($upper === 'AND') {
            $result = $left && $right;
        } elseif ($upper === 'OR') {
            $result = $left || $right;
        }

        return $result;
    }

    /**
     * Prepare the right operand
     *
     * @param mixed $right
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_right_operand($right)
    {
        if (is_array($right)) { // Naming collision?
            $response = $right;
        } else {
            $response = array($right);
        }

        return $response;
    }

    /**
     * Prepare condition group
     *
     * @param array $group
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_condition_group($group)
    {
        $response = [];

        // Taking into consideration legacy format like
        // {
        //      "Equals": [
        //           { "${USER.email}": "a" },
        //           { "${USER.email}": "b" }
        //      ]
        // }
        if (isset($group[0]) && is_array($group[0])) { // Array of arrays?
            foreach($group as $subset) {
                foreach($subset as $key => $value) {
                    if (isset($response[$key])) {
                        if (is_scalar($response[$key])) {
                            $response[$key] = array($response[$key]);
                        }
                        array_push($response[$key], $value);
                    } else {
                        $response[$key] = $value;
                    }
                }
            }
        } else { // Otherwise assume a normal conditional group
            $response = $group;
        }

        return $response;
    }

    /**
     * Bootstrap the object
     *
     * @return AAM_Framework_Policy_Condition
     * @access public
     *
     * @version 7.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Get single instance of itself
     *
     * @return AAM_Framework_Policy_Condition
     * @access public
     *
     * @version 7.0.0
     */
    public static function get_instance()
    {
        return self::bootstrap();
    }

}