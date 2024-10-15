<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Identity Governance resource
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Identity
implements
    AAM_Framework_Resource_Interface,
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::IDENTITY;

    /**
     * Determine if specific permission is allowed to given identity
     *
     * @param string     $identity_type
     * @param string|int $identity
     * @param string     $permission
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed_to($identity_type, $identity, $permission)
    {
        // Find the permission
        $result = null;

        foreach($this->get_permissions() as $perm) {
            if ($perm['identity_type'] === $identity_type
                && $perm['identity'] == $identity
                && $perm['permission'] === $permission
            ) {
                $result = $perm['effect'] === 'allow';
            }
        }

        return apply_filters(
            'aam_identity_is_allowed_to_filter',
            $result,
            $identity_type,
            $identity,
            $permission,
            $this
        );
    }

    /**
     * Determine if specific permission is denied to given identity
     *
     * @param string     $identity_type
     * @param string|int $identity
     * @param string     $permission
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_denied_to($identity_type, $identity, $permission)
    {
        $result = $this->is_allowed_to($identity_type, $identity, $permission);

        return is_bool($result) ? !$result : null;
    }

}