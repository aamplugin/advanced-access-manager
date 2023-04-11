<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Service context
 *
 * @package AAM
 * @since 6.9.9
 */
class AAM_Framework_Model_ServiceContext implements ArrayAccess
{

    /**
     * Context container
     *
     * @var array
     *
     * @access private
     * @since 6.9.9
     */
    private $_container = array();

    /**
     * Constructor
     *
     * @param array|null $context
     *
     * @access public
     * @since 6.9.9
     */
    public function __construct(array $context = null)
    {
        $this->_container = $context;
    }

    /**
     * Check if offset exists
     *
     * @param string $offset
     *
     * @return bool
     *
     * @access public
     * @since 6.9.9
     */
    public function offsetExists($offset) {
        return isset($this->_container[$offset]);
    }

    /**
     * Get value by offset
     *
     * @param string $offset
     *
     * @return mixed
     *
     * @access public
     * @since 6.9.9
     */
    public function offsetGet($offset) {
        return $this->_container[$offset];
    }

    /**
     * Set value
     *
     * @param string $offset
     * @param mixed  $value
     *
     * @return void
     *
     * @access public
     * @since 6.9.9
     */
    public function offsetSet($offset, $value) {
        if (!is_string($offset)) {
            throw new InvalidArgumentException('The offset has to be a string');
        }

        $this->_container[$offset] = $value;
    }

    /**
     * Delete offset
     *
     * @param string $offset
     *
     * @return void
     *
     * @access public
     * @since 6.9.9
     */
    public function offsetUnset($offset) {
        if ($this->offsetExists($offset)) {
            unset($this->_container[$offset]);
        }
    }

}