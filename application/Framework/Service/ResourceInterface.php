<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Interface for services that work with resources
 *
 * @package AAM
 * @version 7.0.0
 */
interface AAM_Framework_Service_ResourceInterface
{

  /**
   * Get resource by its type and internal ID
   *
   * @param string|int $resource_id
   * @param boolean    $reload
   * @param array      $inline_context
   *
   * @return AAM_Framework_Resource_Interface|null
   *
   * @access public
   * @version 7.0.0
   */
  public function get_resource(
    $resource_id = null, $reload = false, $inline_context = null
  );

}
