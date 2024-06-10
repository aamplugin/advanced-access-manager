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
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
 * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/371
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/105
 * @since 6.3.0  Fixed bug where home page could not be protected
 * @since 6.1.0  Fixed bug with incorrectly halted inheritance mechanism
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.31
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
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
     * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/371
     * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/105
     * @since 6.1.0  Fixed bug with incorrectly halted inheritance mechanism
     * @since 6.0.0  Initial implementation of the method
     *
     * @version 6.9.31
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption(self::OBJECT_TYPE);

        // Making sure that all URL are lowercase
        $normalized = array();
        foreach($option as $key => $val) {
            $normalized[$this->_toLower($key)] = $val;
        }

        $this->setExplicitOption($option);

        // Trigger custom functionality that may populate the menu options. For
        // example, this hooks is used by Access Policy service
        $normalized = apply_filters(
            'aam_uri_object_option_filter', $normalized, $this
        );

        $this->setOption(is_array($normalized) ? $normalized : array());
    }

    /**
     * Find the match in the set of rules
     *
     * @param string $s
     * @param array  $params
     *
     * @return null|array
     *
     * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/371
     * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/77
     * @since 6.3.0  https://github.com/aamplugin/advanced-access-manager/issues/17
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.28
     */
    public function findMatch($s, $params = array())
    {
        $match = null;

        // Normalize the search URI
        $s = $this->_toLower(rtrim($s,  '/'));

        foreach ($this->normalizeOrder() as $uri => $rule) {
            $meta = wp_parse_url($uri);
            $out  = array();

            if (!empty($meta['query'])) {
                parse_str($meta['query'], $out);
            }

            // Normalize the target URI
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

        return apply_filters('aam_uri_match_result_filter', $match);
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
     * @param array $target
     *
     * @return array
     *
     * @since 6.9.20 https://github.com/aamplugin/advanced-access-manager/issues/336
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.20
     */
    public function mergeOption($target)
    {
        $merged  = array();
        $current = $this->getOption();

        // Converting the two sets into true/false representation where false is
        // when type === "allow" and everything else is true
        $set1 = array_map(function($v) { return $v['type'] !== 'allow'; }, $target);
        $set2 = array_map(function($v) { return $v['type'] !== 'allow'; }, $current);

        $result = AAM::api()->mergeSettings(
            $set1, $set2, self::OBJECT_TYPE
        );

        // Covert back to the actual properties
        foreach($result as $uri => $effect) {
            if ($effect === false) { // Which means we are allowing
                $merged[$uri] = array(
                    'type' => 'allow'
                );
            } else { // Otherwise we are restricting access, however...
                $merged[$uri] = isset($current[$uri]) ? $current[$uri] : $target[$uri];
            }
        }

        return $merged;
    }

    /**
     * Sort rules in proper order
     *
     * Place all "allowed" rules in the end of the list to allow the ability to
     * define whitelisted set of conditions
     *
     * @return array
     *
     * @access protected
     * @version 6.4.0
     */
    protected function normalizeOrder()
    {
        $denied = $allowed = array();

        foreach ($this->getOption() as $uri => $rule) {
            if ($rule['type'] === 'allow') {
                $allowed[$uri] = $rule;
            } else {
                $denied[$uri] = $rule;
            }
        }

        return array_merge($denied, $allowed);
    }

    /**
     * Convert string to its lowercase
     *
     * @param string $str
     *
     * @return string
     *
     * @access private
     * @version 6.9.28
     */
    private function _toLower($str)
    {
        return call_user_func(
            function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower',
            $str
        );
    }

}