<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * ConfigPress section evaluator
 *
 * Parse configuration section and evaluate an expression. At this point it
 * does not take in consideration the operator's precedence but you can force
 * the order with parenthesises. 
 *
 * @package ConfigPress
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @copyright Copyright Vasyl Martyniuk
 */
class AAM_Core_ConfigPress_Evaluator {

    /**
     * Accepted operators
     * 
     * @var array
     * 
     * @access private 
     */
    private $_operators = array(
        array('*', '/'), //the highest priority
        array('+', '-'),
        array('==', '!=', '===', '!==', '<', '>', '>=', '<=', '<>'),
        array('&&', '||'),
        array('as') //the lowest priority
    );

    /**
     * Expression to parse
     * 
     * @var string
     * 
     * @access protected 
     */
    protected $expression;

    /**
     * Parsing expression alias
     * 
     * @var string
     * 
     * @access protected 
     */
    protected $alias;

    /**
     * Current expression part index
     * 
     * @var array
     * 
     * @access protected
     */
    protected $index = array(0);

    /**
     * Prepare expression evaluation
     * 
     * @param string $expression
     * 
     * @return void
     */
    public function __construct($expression) {
        $this->alias = $expression;

        $regexp = '/(===|!==|==|>=|<=|<>|<|>|\+|\-|\*|\/|&&|\|\||\(|\)|\sas\s)/';
        $this->expression = preg_split(
                $regexp, $expression, -1, PREG_SPLIT_DELIM_CAPTURE
        );
    }

    /**
     * Evaluate the expression
     * 
     * @return mixed
     * 
     * @access public
     */
    public function evaluate() {
        $queue = array();

        $index = &$this->index[count($this->index) - 1];

        for ($index; $index < count($this->expression); $index++) {
            $chunk = trim($this->expression[$index]);

            if (empty($chunk)) {
                continue; //skip empty part
            } elseif ($chunk === '(') {
                $this->index[] = ++$index;
                $queue[] = $this->evaluate();
            } elseif ($chunk === ')') {
                array_pop($this->index);
                $this->index[count($this->index) - 1] = ++$index;
                break;
            } else { //evaluate operand or operator
                $queue[] = $this->evaluateOperand($chunk);
            }
        }

        //compute the queue
        return $this->computeQueue($queue);
    }

    /**
     * Evaluate an operand
     * 
     * @param string $operand
     * 
     * @return mixed
     * 
     * @access protected
     */
    protected function evaluateOperand($operand) {
        if (strpos($operand, '$') === 0) { //variable
            $operand = $this->parseVariable(substr($operand, 1));
        } elseif (strpos($operand, '@') === 0) { //callback function
            $operand = $this->parseCallback(substr($operand, 1));
        }

        return $operand;
    }

    /**
     * Evaluate variable
     * 
     * @param string $variable
     * 
     * @return mixed
     * 
     * @access protected
     */
    protected function parseVariable($variable) {
        $value = null;

        $xpath = explode('.', $variable);
        $root = array_shift($xpath);

        if (isset($GLOBALS[$root])) {
            $value = $GLOBALS[$root];
            foreach ($xpath as $level) {
                if (is_array($value) && isset($value[$level])) {
                    $value = $value[$level];
                } elseif (is_object($value) && property_exists($value, $level)) {
                    $value = $value->{$level};
                } else {
                    break;
                }
            }
        }

        return $value;
    }

    /**
     * Evaluate callback function
     * 
     * @param string $callback
     * 
     * @return mixed
     */
    protected function parseCallback($callback) {
        $value = null;

        if (is_callable($callback)) {
            $value = call_user_func($callback);
        }

        return $value;
    }

    /**
     * Compute parsed expression
     * 
     * @param array $queue
     * 
     * @return mixed
     * 
     * @access protected
     */
    protected function computeQueue($queue) {
        $value = $queue[0]; //default value
        
        foreach ($this->_operators as $operators) {
            $i = 0;
            while ($i < count($queue)) {
                if (!is_bool($queue[$i]) && in_array($queue[$i], $operators, true)) {
                    $value = $this->processOperation(
                            $queue[$i], $queue[$i - 1], $queue[$i + 1]
                    );
                    //replace just calculated value
                    array_splice($queue, --$i, 3, $value);
                } else {
                    $i++;
                }
            }
        }

        return $value;
    }

    /**
     * Process the calculation
     * 
     * @param string $operation
     * @param mixed $operandA
     * @param mixed $operandB
     * 
     * @return mixed
     * 
     * @access protected
     */
    protected function processOperation($operation, $operandA, $operandB) {
        switch ($operation) {
            case '+':
                $operandA += $operandB;
                break;

            case '-':
                $operandA -= $operandB;
                break;

            case '*':
                $operandA *= $operandB;
                break;

            case '/';
                $operandA /= $operandB;
                break;

            case '==':
                $operandA = ($operandA == $operandB);
                break;

            case '===':
                $operandA = ($operandA === $operandB);
                break;

            case '!=':
            case '<>':
                $operandA = ($operandA != $operandB);
                break;

            case '!==':
                $operandA = ($operandA !== $operandB);
                break;

            case '<':
                $operandA = ($operandA < $operandB);
                break;

            case '>':
                $operandA = ($operandA > $operandB);
                break;

            case '<=':
                $operandA = ($operandA <= $operandB);
                break;

            case '>=':
                $operandA = ($operandA >= $operandB);
                break;

            case '&&':
                $operandA = ($operandA && $operandB);
                break;

            case '||':
                $operandA = ($operandA || $operandB);
                break;

            case 'as':
                $this->alias = $operandB;
                break;

            default:
                $operandA = false;
                break;
        }

        return $operandA;
    }

    /**
     * Get section alias
     * 
     * @return string
     * 
     * @access public
     */
    public function getAlias() {
        return $this->alias;
    }

}