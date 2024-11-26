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
     *
     * @access public
     * @version 7.0.0
     */
    public function get_all()
    {
        try {
            $result       = [];
            $access_level = $this->_get_access_level();

            if ($access_level::TYPE === AAM_Framework_Type_AccessLevel::USER) {
                $result = $access_level->allcaps;
            } elseif ($access_level::TYPE === AAM_Framework_Type_AccessLevel::ROLE) {
                $result = $access_level->capabilities;
            }

            // Normalizing the list of capabilities to ensure that they all have
            // boolean value
            foreach($result as $key => $value) {
                $result[$key] = (bool)$value;
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
     *
     * @access public
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
     * @param bool   $is_granted    [optional]
     * @param bool   $ignore_format [optional]
     *
     * @return bool|WP_Error
     *
     * @access public
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

            $result       = true;
            $access_level = $this->_get_access_level();
            $valid_levels = [
                AAM_Framework_Type_AccessLevel::USER,
                AAM_Framework_Type_AccessLevel::ROLE
            ];

            if (in_array($access_level::TYPE, $valid_levels, true)) {
                // Neither WP_Role nor WP_User return result, so do nothing here and
                // assume that capability was added
                $access_level->add_cap($capability, $is_granted);
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
     * @return bool|null|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function remove($capability)
    {
        try {
            $access_level = $this->_get_access_level();
            $result       = null;

            if ($access_level::TYPE === AAM_Framework_Type_AccessLevel::USER) {
                // Additionally check if capability is assigned to user explicitly
                if (array_key_exists($capability, $access_level->caps)) {
                    // The remove_cap does not return any values
                    $access_level->remove_cap($capability);

                    $result = true;
                }
            } elseif ($access_level::TYPE === AAM_Framework_Type_AccessLevel::ROLE) {
                if (array_key_exists($capability, $access_level->capabilities)) {
                    // The remove_cap does not return any values
                    $access_level->remove_cap($capability);

                    $result = true;
                }
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
     * @param bool   $ignore_format [optional]
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function deprive($capability, $ignore_format = false)
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
     *
     * @access public
     * @version 7.0.0
     */
    public function grant($capability, $ignore_format = false)
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
     *
     * @access public
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
            if ($this->exists($old_slug)) {
                // Step #2. Determine if old capability is granted to current access
                // level
                $is_granted = $this->is_granted($old_slug);

                // Step #3. Remove old capability
                $this->remove($old_slug);

                // Step #4. Add new capability
                $result = $this->add($new_slug, $is_granted, $ignore_format);
            } else {
                $result = false;
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if capability exists
     *
     * Note! This method only checks if capability is added to the access level and
     * returns true if it is present in the list of capabilities.
     *
     * @param string $capability
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function exists($capability)
    {
        try {
            $caps         = [];
            $access_level = $this->_get_access_level();

            if ($access_level::TYPE === AAM_Framework_Type_AccessLevel::USER) {
                $caps = $access_level->caps;
            } elseif ($access_level::TYPE === AAM_Framework_Type_AccessLevel::ROLE) {
                $caps = $access_level->capabilities;
            }

            // To prevent from any kind of corrupted data
            if (is_array($caps)) {
                $result = array_key_exists($capability, $caps);
            } else {
                $result = false;
            }
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
     *
     * @access public
     * @version 7.0.0
     */
    public function is_granted($capability)
    {
        try {
            $access_level = $this->_get_access_level();
            $valid_levels = [
                AAM_Framework_Type_AccessLevel::USER,
                AAM_Framework_Type_AccessLevel::ROLE
            ];

            if (in_array($access_level::TYPE, $valid_levels, true)) {
                $result = $access_level->has_cap($capability);
            } else {
                $result = false;
            }
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
     *
     * @access public
     * @version 7.0.0
     */
    public function is_deprived($capability)
    {
        $result = $this->is_granted($capability);

        return is_bool($result) ? !$result : $result;
    }

}