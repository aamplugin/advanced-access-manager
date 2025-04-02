<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * URL resource
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Url implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::URL;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        foreach($this->policies()->statements('Url:*') as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';
            $parsed = explode(':', $stm['Resource'], 2);

            if (!empty($parsed[1])) {
                $url = $this->misc->sanitize_url($parsed[1]);

                // Covert redirect
                if (!empty($stm['Redirect']) && is_array($stm['Redirect'])) {
                    $redirect = $this->policy->convert_statement_redirect(
                        $stm['Redirect']
                    );
                } else {
                    $redirect = [ 'type' => 'default' ];
                }

                $result = array_replace([
                    $url => [
                        'access' => [
                            'effect'   => $effect !== 'allow' ? 'deny' : 'allow',
                            'redirect' => $redirect
                        ]
                    ]
                ], $result);
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}