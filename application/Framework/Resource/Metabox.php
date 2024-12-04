<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Metaboxes resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Metabox implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::METABOX;

    /**
     * Check whether the metabox is hidden or not
     *
     * @param string $slug [Optional]
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($slug = null)
    {
        $result = null;
        $slug   = empty($slug) ? $this->_internal_id : $slug;

        if (empty($slug)) {
            throw new InvalidArgumentException(
                'Non-empty metabox slug has to be provided'
            );
        }

        if (array_key_exists($slug, $this->_permissions)) {
            $result = $this->_permissions[$slug]['effect'] !== 'allow';
        }

        return $result;
    }

}