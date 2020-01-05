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
 * @since 6.2.0 Added support for the (*date) type casting
 * @since 6.1.0 Improved type casting functionality
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.2.0
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
     * @access public
     * @version 6.0.0
     */
    public function evaluate($conditions, $args = array())
    {
        $res = true;

        foreach ($conditions as $type => $condition) {
            $type = strtolower($type);

            if (isset($this->map[$type])) {
                $callback = array($this, $this->map[$type]);

                // Since v5.9.2 - if specific condition type is array, then combine
                // them with AND operation
                if (isset($condition[0]) && is_array($condition[0])) {
                    foreach ($condition as $set) {
                        $res = $res && call_user_func($callback, $set, $args);
                    }
                } else {
                    $res = $res && call_user_func($callback, $condition, $args);
                }
            } else {
                $res = false;
            }
        }

        return $res;
    }

    /**
     * Evaluate group of BETWEEN conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateBetweenConditions($conditions, $args)
    {
        $result = false;

        foreach ($this->prepareConditions($conditions, $args) as $cnd) {
            // Convert the right condition into the array of array to cover more
            // complex between conditions like [[0,8],[13,15]]
            if (is_array($cnd['right'][0])) {
                $right = $cnd['right'];
            } else {
                $right = array($cnd['right']);
            }
            foreach ($right as $subset) {
                $min = (is_array($subset) ? array_shift($subset) : $subset);
                $max = (is_array($subset) ? end($subset) : $subset);

                $result = $result || ($cnd['left'] >= $min && $cnd['left'] <= $max);
            }
        }

        return $result;
    }

    /**
     * Evaluate group of EQUALS conditions
     *
     * The values have to be identical
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateEqualsConditions($conditions, $args)
    {
        $result = false;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            $result = $result || ($condition['left'] === $condition['right']);
        }

        return $result;
    }

    /**
     * Evaluate group of NOT EQUALs conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateNotEqualsConditions($conditions, $args)
    {
        return !$this->evaluateEqualsConditions($conditions, $args);
    }

    /**
     * Evaluate group of GREATER THEN conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateGreaterConditions($conditions, $args)
    {
        $result = false;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            $result = $result || ($condition['left'] > $condition['right']);
        }

        return $result;
    }

    /**
     * Evaluate group of LESS THEN conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateLessConditions($conditions, $args)
    {
        $result = false;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            $result = $result || ($condition['left'] < $condition['right']);
        }

        return $result;
    }

    /**
     * Evaluate group of GREATER OR EQUALS THEN conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateGreaterOrEqualsConditions($conditions, $args)
    {
        $result = false;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            $result = $result || ($condition['left'] >= $condition['right']);
        }

        return $result;
    }

    /**
     * Evaluate group of LESS OR EQUALS THEN conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateLessOrEqualsConditions($conditions, $args)
    {
        $result = false;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            $result = $result || ($condition['left'] <= $condition['right']);
        }

        return $result;
    }

    /**
     * Evaluate group of IN conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateInConditions($conditions, $args)
    {
        $result = false;

        foreach ($this->prepareConditions($conditions, $args) as $cnd) {
            $result = $result || in_array($cnd['left'], (array) $cnd['right'], true);
        }

        return $result;
    }

    /**
     * Evaluate group of NOT IN conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateNotInConditions($conditions, $args)
    {
        return !$this->evaluateInConditions($conditions, $args);
    }

    /**
     * Evaluate group of LIKE conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateLikeConditions($conditions, $args)
    {
        $result = false;

        foreach ($this->prepareConditions($conditions, $args) as $cnd) {
            foreach ((array) $cnd['right'] as $el) {
                $sub = str_replace(
                    array('\*', '@'), array('.*', '\\@'), preg_quote($el)
                );
                $result = $result || preg_match('@^' . $sub . '$@', $cnd['left']);
            }
        }

        return $result;
    }

    /**
     * Evaluate group of NOT LIKE conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateNotLikeConditions($conditions, $args)
    {
        return !$this->evaluateLikeConditions($conditions, $args);
    }

    /**
     * Evaluate group of REGEX conditions
     *
     * @param array $conditions
     * @param array $args
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function evaluateRegexConditions($conditions, $args)
    {
        $result = false;

        foreach ($this->prepareConditions($conditions, $args) as $condition) {
            $result = $result || preg_match($condition['right'], $condition['left']);
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
     * @access protected
     * @version 6.0.0
     */
    protected function prepareConditions($conditions, $args)
    {
        $result = array();

        if (is_array($conditions)) {
            foreach ($conditions as $left => $right) {
                $result[] = array(
                    'left'  => $this->parseExpression($left, $args),
                    'right' => $this->parseExpression($right, $args)
                );
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

}