<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core policy condition evaluator
 *
 * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/349
 *               https://github.com/aamplugin/advanced-access-manager/issues/351
 * @since 6.5.3  https://github.com/aamplugin/advanced-access-manager/issues/123
 * @since 6.2.0  Added support for the (*date) type casting
 * @since 6.1.0  Improved type casting functionality
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.24
 */
class AAM_Core_Policy_Condition
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * Map between condition type and method that evaluates the
     * group of conditions
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected $map = array(
        'between'         => 'evaluateBetweenConditions',
        'equals'          => 'evaluateEqualsConditions',
        'notequals'       => 'evaluateNotEqualsConditions',
        'greater'         => 'evaluateGreaterConditions',
        'less'            => 'evaluateLessConditions',
        'greaterorequals' => 'evaluateGreaterOrEqualsConditions',
        'lessorequals'    => 'evaluateLessOrEqualsConditions',
        'in'              => 'evaluateInConditions',
        'notin'           => 'evaluateNotInConditions',
        'like'            => 'evaluateLikeConditions',
        'notlike'         => 'evaluateNotLikeConditions',
        'regex'           => 'evaluateRegexConditions'
    );

    /**
     * Evaluate the group of conditions based on type
     *
     * @param array $conditions List of conditions
     * @param array $args       Since 5.9 - Inline args for evaluation
     *
     * @return boolean
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.24
     */
    public function evaluate($conditions, $args = array())
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
                $group          = $this->prepareConditionGroup($group);
                $group_operator = isset($group['Operator']) ? $group['Operator'] : 'OR';

                $res = $this->compute(
                    $res,
                    call_user_func($callback, $group, $args, $group_operator),
                    $operator
                );
            } else {
                $res = apply_filters(
                    'aam_policy_condition_result_filter',
                    false,
                    $type,
                    $this->prepareConditions($this->prepareConditionGroup($group), $args),
                    $args
                );
            }
        }

        return $res;
    }

    /**
     * Prepare condition group
     *
     * @param array $group
     *
     * @return array
     *
     * @version 6.9.24
     */
    protected function prepareConditionGroup($group)
    {
        $response = array();

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
     * Evaluate group of BETWEEN conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     *               https://github.com/aamplugin/advanced-access-manager/issues/349
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateBetweenConditions($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            if (isset($condition['right'][0]) && is_array($condition['right'][0])) {
                $right = $condition['right'];
            } else {
                $right = array($condition['right']);
            }

            foreach ($right as $subset) {
                if (is_a($subset, 'Closure')) {
                    $result = $this->compute(
                        $result, $subset($condition['left']), $operator
                    );
                } else {
                    list($min, $max) = $subset;

                    $result = $this->compute(
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateEqualsConditions($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            foreach ($this->prepareRightOperand($condition['right']) as $right) {
                $result = $this->compute(
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateNotEqualsConditions($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            foreach ($this->prepareRightOperand($condition['right']) as $right) {
                $result = $this->compute(
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateGreaterConditions($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            foreach ($this->prepareRightOperand($condition['right']) as $right) {
                $result = $this->compute(
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateLessConditions($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            foreach ($this->prepareRightOperand($condition['right']) as $right) {
                $result = $this->compute(
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateGreaterOrEqualsConditions($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            foreach ($this->prepareRightOperand($condition['right']) as $right) {
                $result = $this->compute(
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateLessOrEqualsConditions($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            foreach ($this->prepareRightOperand($condition['right']) as $right) {
                $result = $this->compute(
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     *               https://github.com/aamplugin/advanced-access-manager/issues/349
     * @since 6.5.3  https://github.com/aamplugin/advanced-access-manager/issues/123
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateInConditions($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
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
                    $result = $this->compute($result, count($left) === $ci, $operator);
                } elseif (is_scalar($left) && is_array($right)) {
                    $set_result = null;

                    foreach($right as $value) {
                        if (is_scalar($value)) {
                            $set_result = $set_result || ($left === $value);
                        } elseif (is_a($value, 'Closure')) {
                            $set_result = $set_result || $value($left);
                        }
                    }

                    $result = $this->compute($result, $set_result, $operator);
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateNotInConditions($conditions, $args, $operator)
    {
        return !$this->evaluateInConditions($conditions, $args, $operator);
    }

    /**
     * Evaluate group of LIKE conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateLikeConditions($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            foreach ($this->prepareRightOperand($condition['right']) as $right) {
                $sub = str_replace(
                    array('\*', '@'), array('.*', '\\@'), preg_quote($right)
                );

                $result = $this->compute(
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateNotLikeConditions($conditions, $args, $operator)
    {
        return !$this->evaluateLikeConditions($conditions, $args, $operator);
    }

    /**
     * Evaluate group of REGEX conditions
     *
     * @param array  $conditions
     * @param array  $args
     * @param string $operator
     *
     * @return boolean
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function evaluateRegexConditions($conditions, $args, $operator)
    {
        $result = null;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            foreach ($this->prepareRightOperand($condition['right']) as $right) {
                $result = $this->compute(
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/351
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
     */
    protected function prepareConditions($conditions, $args)
    {
        $result = array();

        if (is_array($conditions)) {
            foreach ($conditions as $left => $right) {
                if ($left !== 'Operator') {
                    $result[] = array(
                        'left'  => $this->parseExpression($left, $args),
                        'right' => $this->parseExpression($right, $args)
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
     *
     * @since 6.2.1 Moved type casting to the separate class
     * @since 6.2.0 Added support for new `date` type
     * @since 6.1.0 Improved type casing functionality
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.2.1
     */
    public function parseExpression($exp, $args)
    {
        if (is_scalar($exp)) {
            if (preg_match_all('/(\$\{[^}]+\})/', $exp, $match)) {
                $exp = AAM_Core_Policy_Token::evaluate($exp, $match[1], $args);
            }

            // Perform type casting if necessary
            $exp = AAM_Core_Policy_Typecast::execute($exp);
        } elseif (is_array($exp) || is_object($exp)) {
            foreach ($exp as &$value) {
                $value = $this->parseExpression($value, $args);
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
     *
     * @access public
     *
     * @version 6.9.24
     */
    protected function compute($left, $right, $operator)
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
     *
     * @version 6.9.24
     */
    protected function prepareRightOperand($right)
    {
        if (is_array($right)) { // Naming collision?
            $response = $right;
        } else {
            $response = array($right);
        }

        return $response;
    }

}