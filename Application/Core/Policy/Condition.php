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
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @since AAM v5.8.2
 */
final class AAM_Core_Policy_Condition {
    
    /**
     * Single instance of itself
     * 
     * @var AAM_Core_Policy_Condition
     * 
     * @access protected
     * @static 
     */
    protected static $instance = null;
    
    /**
     * Map between condition type and method that evaluates the
     * group of conditions
     * 
     * @var array
     * 
     * @access protected
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
     * Constructor
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {}
    
    /**
     * Evaluate the group of conditions based on type
     * 
     * @param array $conditions List of conditions
     * @param array $args       Since 5.9 - Inline args for evaluation
     * 
     * @return boolean
     * 
     * @access public
     */
    public function evaluate($conditions, $args = array()) {
        $result = true;

        foreach($conditions as $type => $conditions) {
            $type = strtolower($type);
            
            if (isset($this->map[$type])) {
                $callback = array($this, $this->map[$type]);
                
                // Since v5.9.2 - if specific condition type is array, then combine
                // them with AND operation
                if (isset($conditions[0]) && is_array($conditions[0])) {
                    foreach($conditions as $set) {
                        $result = $result && call_user_func($callback, $set, $args);
                    }
                } else {
                    $result = $result && call_user_func($callback, $conditions, $args);
                }
            } else {
                $result = false;
            }
        }

        return $result;
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
     */
    protected function evaluateBetweenConditions($conditions, $args) {
        $result = false;
        
        foreach($this->prepareConditions($conditions, $args) as $condition) {
            // Convert the right condition into the array of array to cover more
            // complex between conditions like [[0,8],[13,15]]
            if (is_array($condition['right'][0])) {
                $right = $condition['right'];
            } else {
                $right = array($condition['right']);
            }
            foreach($right as $subset) {
                $min = (is_array($subset) ? array_shift($subset) : $subset);
                $max = (is_array($subset) ? end($subset) : $subset);
                
                $result = $result || ($condition['left'] >= $min && $condition['left'] <= $max);
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
     */
    protected function evaluateEqualsConditions($conditions, $args) {
        $result = false;
        
        foreach($this->prepareConditions($conditions, $args) as $condition) {
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
     */
    protected function evaluateNotEqualsConditions($conditions, $args) {
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
     */
    protected function evaluateGreaterConditions($conditions, $args) {
        $result = false;
        
        foreach($this->prepareConditions($conditions, $args) as $condition) {
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
     */
    protected function evaluateLessConditions($conditions, $args) {
        $result = false;
        
        foreach($this->prepareConditions($conditions, $args) as $condition) {
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
     */
    protected function evaluateGreaterOrEqualsConditions($conditions, $args) {
        $result = false;
        
        foreach($this->prepareConditions($conditions, $args) as $condition) {
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
     */
    protected function evaluateLessOrEqualsConditions($conditions, $args) {
        $result = false;
        
        foreach($this->prepareConditions($conditions, $args) as $condition) {
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
     */
    protected function evaluateInConditions($conditions, $args) {
        $result = false;

        foreach($this->prepareConditions($conditions, $args) as $condition) {
            $result = $result || in_array($condition['left'], (array)$condition['right'], true);
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
     */
    protected function evaluateNotInConditions($conditions, $args) {
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
     */
    protected function evaluateLikeConditions($conditions, $args) {
        $result = false;
        
        foreach($this->prepareConditions($conditions, $args) as $condition) {
            foreach((array)$condition['right'] as $el) {
                $sub    = str_replace('\*', '.*', preg_quote($el));
                $result = $result || preg_match('@^' . $sub . '$@', $condition['left']);
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
     */
    protected function evaluateNotLikeConditions($conditions, $args) {
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
     */
    protected function evaluateRegexConditions($conditions, $args) {
        $result = false;
        
        foreach($this->prepareConditions($conditions, $args) as $condition) {
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
     */
    protected function prepareConditions($conditions, $args) {
        $result = array();
        
        if (is_array($conditions)) {
            foreach($conditions as $left => $right) {
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
     * @access protected
     */
    protected function parseExpression($exp, $args) {
        if (is_scalar($exp)) {
            if (preg_match_all('/(\$\{[^}]+\})/', $exp, $match)) {
                $exp = AAM_Core_Policy_Token::evaluate($exp, $match[1], $args);
            }
            // If there is type scaling, perform it too
            if (preg_match('/^\(\*(string|ip|int|boolean|bool|array)\)(.*)/i', $exp, $scale)) {
                $exp = $this->scaleValue($scale[2], $scale[1]);
            }
        } elseif (is_array($exp) || is_object($exp)) {
            foreach($exp as &$value) {
                $value = $this->parseExpression($value, $args);
            }
        } else {
            $exp = false;
        }
        
        return $exp;
    }
    
    /**
     * Scale value to specific type
     * 
     * @param mixed  $value
     * @param string $type
     * 
     * @return mixed
     * 
     * @access protected
     */
    protected function scaleValue($value, $type) {
        switch(strtolower($type)) {
            case 'string':
                $value = (string)$value;
                break;
            
            case 'ip':
                $value = inet_pton($value);
                break;
            
            case 'int':
                $value = (int)$value;
                break;
            
            case 'boolean':
            case 'bool':
                $value = (bool)$value;
                break;

            case 'array':
                $value = json_decode($value, true);
                break;
        }
        
        return $value;
    }
    
    /**
     * Get single instance of itself
     * 
     * @return AAM_Core_Policy_Condition
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
}