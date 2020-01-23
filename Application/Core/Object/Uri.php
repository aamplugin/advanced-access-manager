<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * URI object
 *
 * @since 6.3.0 Fixed bug where home page could not be protected
 * @since 6.1.0 Fixed bug with incorrectly halted inheritance mechanism
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.3.0
 */
class AAM_Core_Object_Uri extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'uri';

    /**
     * @inheritdoc
     *
     * @since 6.1.0 Fixed bug with incorrectly halted inheritance mechanism
     * @since 6.0.0 Initial implementation of the method
     *
     * @version 6.1.0
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption(self::OBJECT_TYPE);

        $this->determineOverwritten($option);

        // Trigger custom functionality that may populate the menu options. For
        // example, this hooks is used by Access Policy service
        $option = apply_filters('aam_uri_object_option_filter', $option, $this);

        $this->setOption(is_array($option) ? $option : array());
    }

    /**
     * Find the match in the set of rules
     *
     * @param string $s
     * @param array  $params
     *
     * @return null|array
     *
     * @since 6.3.0 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/17
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.0
     */
    public function findMatch($s, $params = array())
    {
        $match = null;

        foreach ($this->getOption() as $uri => $rule) {
            $meta = wp_parse_url($uri);
            $out  = array();

            if (!empty($meta['query'])) {
                parse_str($meta['query'], $out);
            }

            // Normalize the search and target URIs
            $s    = rtrim($s,  '/');
            $path = rtrim(isset($meta['path']) ? $meta['path'] : '', '/');

            // Check if two URIs are equal
            $uri_matched = ($s === $path);

            // If match already found, no need to do additional checks
            if ($uri_matched === false) {
                $uri_matched = apply_filters(
                    'aam_uri_match_filter', false, $uri, $s
                );
            }

            // Perform the initial match for the query params if defined
            $same          = array_intersect_assoc($params, $out);
            $query_matched = empty($out) || (count($same) === count($out));

            if ($uri_matched && $query_matched) {
                $match = $rule;
            }
        }

        return $match;
    }

    /**
     * Check if exact URI is defined
     *
     * @param string  $uri
     * @param boolean $explicit
     *
     * @return boolean
     *
     * @access public
     * @version 6.3.0
     */
    public function has($uri, $explicit = true)
    {
        if ($explicit) {
            $option = $this->getExplicitOption();
        } else {
            $option = $this->getOption();
        }

        return isset($option[$uri]);
    }

    /**
     * Delete specified URI rule
     *
     * @param string $uri
     *
     * @return boolean
     *
     * @since 6.3.0 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/35
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.0
     */
    public function delete($uri)
    {
        $option = $this->getExplicitOption();

        if (isset($option[$uri])) {
            unset($option[$uri]);

            $this->setExplicitOption($option);

            $result = $this->getSubject()->updateOption(
                $this->getExplicitOption(), self::OBJECT_TYPE
            );
        }

        return !empty($result);
    }

    /**
     * Merge URI access settings
     *
     * @param array $options
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function mergeOption($options)
    {
        $merged = array();
        $pref   = AAM::api()->getConfig('core.settings.uri.merge.preference', 'deny');

        foreach (array_merge($options, $this->getOption()) as $uri => $options) {
            // If merging preference is "deny" and at least one of the access
            // settings is checked, then final merged array will have it set
            // to checked
            if (!isset($merged[$uri])) {
                $merged[$uri] = $options;
            } else {
                if (($pref === 'deny') && ($options['type'] !== 'allow')) {
                    $merged[$uri] = $options;
                    break;
                } elseif ($pref === 'allow' && ($options['type'] === 'allow')) {
                    $merged[$uri] = $options;
                    break;
                }
            }
        }

        return $merged;
    }

}