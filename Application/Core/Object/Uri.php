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
 * @since 6.1.0 Fixed bug with incorrectly halted inheritance mechanism
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.1.0
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
     * @access public
     * @version 6.0.0
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
            $s            = rtrim($s,  '/');
            $meta['path'] = rtrim(isset($meta['path']) ? $meta['path'] : '', '/');
            $regex        = '@^' . preg_quote($meta['path']) . '$@';

            // Perform the initial match for the base URI
            $uri_matched = apply_filters(
                'aam_uri_match_filter', preg_match($regex, $s), $uri, $s
            );

            // Perform the initial match for the query params if defined
            $query_matched = empty($out) || (count(array_intersect_assoc($params, $out)) === count($out));

            if ($uri_matched && $query_matched) {
                $match = $rule;
            }
        }

        return $match;
    }

    /**
     * Delete specified URI rule
     *
     * @param string $uri
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function delete($uri)
    {
        $option = $this->getOption();

        if (isset($option[$uri])) {
            unset($option[$uri]);

            $this->setOption($option);

            $result = $this->getSubject()->updateOption(
                $this->getOption(), self::OBJECT_TYPE
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