<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for Capabilities
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Capabilities
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get list of capabilities assigned to the access level
     *
     * Note, this method is intended to work only with Role & User access levels. For
     * the Default or Visitor access levels, this method will return an empty array.
     *
     * @return array|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function get_all()
    {
        try {
            $result = [];
            $access_level = $this->_get_access_level();

            if ($access_level::TYPE === AAM_Framework_Type_AccessLevel::USER) {
                $caps = $access_level->allcaps;
            } elseif ($access_level::TYPE === AAM_Framework_Type_AccessLevel::ROLE) {
                $caps = $access_level->capabilities;
            } else {
                $caps = [];
            }

            foreach(array_keys($caps) as $cap) {
                $result[$cap] = $this->_is_allowed($cap);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_all
     *
     * @return array|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function list()
    {
        return $this->get_all();
    }

    /**
     * Add capability to the access level
     *
     * The method will trigger RuntimeException exception is current access level is
     * not either Role or User.
     *
     * @param string $capability
     * @param bool   $is_granted    [Optional]
     * @param bool   $ignore_format [Optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function add($capability, $is_granted = true, $ignore_format = false)
    {
        try {
            if (!$ignore_format && !preg_match('/^[a-z\d\-_]+/', $capability)) {
                throw new InvalidArgumentException(
                    'Valid capability slug is required'
                );
            }

            // Neither WP_Role nor WP_User return result, so do nothing here and
            // assume that capability was added
            $result       = true;
            $access_level = $this->_get_access_level();

            if ($this->_is_acceptable_access_level()) {
                $this->_get_access_level()->add_cap($capability, $is_granted);
            } else {
                throw new RuntimeException(sprintf(
                    'The access level %s cannot have capabilities',
                    $access_level::TYPE
                ));
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Remove capability from the access level
     *
     * The method will trigger RuntimeException exception is current access level is
     * not either Role or User. The `null` value will be returned if current access
     * level does not have the capability. Otherwise boolean value is returned when
     * true indicates that capability was removed successfully.
     *
     * @param string $capability
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function remove($capability)
    {
        try {
            $access_level = $this->_get_access_level();

            if ($this->_is_acceptable_access_level()) {
                $result = $this->_get_resource()->remove_permission($capability);
            } else {
                throw new RuntimeException(sprintf(
                    'The access level %s cannot have capabilities',
                    $access_level::TYPE
                ));
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Deprive access level from given capability
     *
     * This method DOES NOT remove capability from the list of access level
     * capabilities but rather set it's flag to false
     *
     * @param string $capability
     * @param bool   $ignore_format [Optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($capability, $ignore_format = false)
    {
        return $this->add($capability, false, $ignore_format);
    }

    /**
     * Grant capability to the access level
     *
     * @param string $capability
     * @param bool   $ignore_format [optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($capability, $ignore_format = false)
    {
        return $this->add($capability, true, $ignore_format);
    }

    /**
     * Replace a capability with new slug
     *
     * @param string $old_slug
     * @param string $new_slug
     * @param bool   $ignore_format [optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function replace($old_slug, $new_slug, $ignore_format = false)
    {
        try {
            // Step #1. Validate new slug before we do anything funky
            if (!$ignore_format && !preg_match('/^[a-z\d\-_]+/', $new_slug)) {
                throw new InvalidArgumentException(
                    'Valid new capability slug is required'
                );
            }

            // Replace only if capability actually assigned to the access level
            if ($this->_is_acceptable_access_level() && $this->_exists($old_slug)) {
                $resource = $this->_get_resource();
                $explicit = $resource->get_permissions(true);

                // Step #2. Determine if old capability is granted to current access
                // level
                $is_granted = $explicit[$old_slug]['effect'] === 'allow';

                // Step #3. Remove old capability
                $resource->remove_permission($old_slug);

                // Step #4. Add new capability
                $this->_get_access_level()->add_cap($new_slug, $is_granted);

                // Neither WP_Role nor WP_User return result, so do nothing here and
                // assume that capability was added
                $result = true;
            } else {
                $result = false;
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if capability is explicitly assigned
     *
     * Capability is considered as explicitly assigned when it is explicitly added
     * to the access level. It does not matter if it is granted or deprived.
     *
     * @param string $capability
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function exists($capability)
    {
        try {
            $result = $this->_exists($capability);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if capability is granted to the access level
     *
     * @param string $capability
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed($capability)
    {
        try {
            $result = $this->_is_allowed($capability);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if capability is deprived from the access level
     *
     * @param string $capability
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied($capability)
    {
        $result = $this->is_allowed($capability);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Check if capability is explicitly assigned
     *
     * @param string $capability
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _exists($capability)
    {
        $access_level = $this->_get_access_level();

        if ($access_level::TYPE === AAM_Framework_Type_AccessLevel::USER) {
            $caps = $access_level->caps;
        } elseif ($access_level::TYPE === AAM_Framework_Type_AccessLevel::ROLE) {
            $caps = $access_level->capabilities;
        } else {
            $caps = [];
        }

        return array_key_exists($capability, $caps);
    }

    /**
     * Determine if capability is allowed
     *
     * @param string $capability
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _is_allowed($capability)
    {
        $resource    = $this->_get_resource();
        $permissions = $resource->get_permissions();

        // Determine if capability is explicitly granted with AAM
        if (array_key_exists($capability, $permissions)) {
            $result = $permissions[$capability]['effect'] === 'allow';
        } else {
            $result = apply_filters(
                'aam_capability_is_allowed_filter',
                null,
                $capability,
                $resource
            );
        }

        // Otherwise - default to WP core
        if (is_null($result) && $this->_is_acceptable_access_level()) {
            $result = $this->_get_access_level()->has_cap($capability);
        }

        return is_bool($result) ? $result : false;
    }

    /**
     * Making sure the access level is acceptable to work with capabilities
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _is_acceptable_access_level()
    {
        $access_level = $this->_get_access_level();

        return in_array($access_level::TYPE, [
            AAM_Framework_Type_AccessLevel::USER,
            AAM_Framework_Type_AccessLevel::ROLE
        ], true);
    }

    /**
     * Get capability resource
     *
     * @return AAM_Framework_Resource_Capability
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::CAPABILITY
        );
    }

}