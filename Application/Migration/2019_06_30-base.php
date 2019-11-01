<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

namespace AAM\Migration;

use WP_Error,
    AAM_Core_API,
    AAM_Core_Config,
    AAM_Core_Migration,
    AAM_Core_ConfigPress,
    AAM_Addon_Repository,
    AAM_Core_AccessSettings,
    AAM_Backend_Feature_Settings_Core,
    AAM_Core_Contract_MigrationInterface,
    AAM_Backend_Feature_Settings_Content,
    AAM_Backend_Feature_Settings_Security;

/**
 * This migration class converts all AAM legacy access settings
 *
 * The main purpose for this class is to eliminate AAM_Core_Compatibility
 *
 * @package AAM
 * @version 6.0.0
 */
class Migration600 implements AAM_Core_Contract_MigrationInterface
{
    /**
     * Migration script version
     *
     * @version 6.0.0
     */
    const VERSION = '6.0.0';

    /**
     * Migration callbacks
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected $migrationCallbacks = array();

    /**
     * Constructor
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function __construct()
    {
        $this->migrationCallbacks = array(
            'menu'           => array($this, '_convertMenuOptions'),
            'metabox'        => array($this, '_convertMetaboxOptions'),
            'toolbar'        => array($this, '_convertFlatOptions'),
            'route'          => array($this, '_convertFlatOptions'),
            'uri'            => array($this, '_convertUriOptions'),
            'redirect'       => array($this, '_convertAsIs'),
            'loginredirect'  => array($this, '_convertAsIs'),
            'logoutredirect' => array($this, '_convertAsIs'),
            'policy'         => array($this, '_convertAsIs'),
            // Plus Package related object
            'term'           => array($this, '_convertTermOptions'),
            'type'           => array($this, '_convertTypeOptions'),
            'taxonomy'       => array($this, '_convertTaxonomyOptions'),
            // IP Check related object
            'ipCheck'        => array($this, '_convertIPCheckOptions')
        );
    }

    /**
     * @inheritdoc
     *
     * @version 6.0.0
     */
    public function run()
    {
        // Fetch the list of all the access settings that are going to be converted
        // Prior to AAM v6, access settings were distributed between following db
        // tables: wp_options, wp_usermeta, wp_postmeta
        $settings = $this->fetchAccessSettings();

        // Iterate over each group of settings and convert them to AAM v6 format
        $results = array(
            'errors' => array(),
            'dump'   => $settings
        );

        foreach($settings as $group => $collection) {
            if ($group === 'options') {
                $results['errors'] = array_merge(
                    $results['errors'], $this->processOptions($collection)
                );
            } elseif ($group === 'usermeta') {
                $results['errors'] = array_merge(
                    $results['errors'], $this->processUsermeta($collection)
                );
            } elseif ($group === 'postmeta') {
                $results['errors'] = array_merge(
                    $results['errors'], $this->processPostmeta($collection)
                );
            }
        }

        // Save access settings
        AAM_Core_AccessSettings::getInstance()->save();

        // Clear Scheduled legacy AAM task
        wp_clear_scheduled_hook('aam-cron');

        // Finally store this script as completed
        AAM_Core_Migration::storeCompletedScript(basename(__FILE__));

        return $results;
    }

    /**
     * Fetch all access settings from the DB
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function fetchAccessSettings()
    {
        global $wpdb;

        $response = array();

        // Fetch access settings from the wp_options table
        $opt_query  = "SELECT * FROM {$wpdb->options} WHERE (`option_name` LIKE %s) ";
        $opt_query .= "OR (`option_name` = %s)";
        $wpdb->query($wpdb->prepare($opt_query, array('aam_%', 'aam-%')));

        $response['options'] = $wpdb->last_result;

        // Fetch access settings from the wp_usermeta table
        $query  = "SELECT * FROM {$wpdb->usermeta} WHERE (`meta_key` LIKE %s) ";
        $query .= "OR (`meta_key` LIKE %s)";
        $wpdb->query($wpdb->prepare($query, array("{$wpdb->prefix}aam_%", 'aam-%')));

        $response['usermeta'] = $wpdb->last_result;

        // Fetch access settings from the wp_postmeta table
        $query = "SELECT * FROM {$wpdb->postmeta} WHERE (`meta_key` LIKE %s)";
        $wpdb->query($wpdb->prepare($query, array('aam-post-access-%')));

        $response['postmeta'] = $wpdb->last_result;

        return $response;
    }

    /**
     * Process settings fetched from the _options DB table
     *
     * @param array $options
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function processOptions($options)
    {
        $results = array();

        foreach($options as $option) {
            switch($option->option_name) {
                case 'aam-configpress':
                    $result = $this->_convertConfigPress($option);
                    break;

                case 'aam-extensions':
                    $result = $this->_convertExtensionRegistry($option);
                    break;

                case 'aam-utilities':
                    $result = $this->_convertSettings($option);
                    break;

                case 'aam_metabox_cache':
                case 'aam_menu_cache':
                case 'aam_toolbar_cache':
                case 'aam-check':
                case 'aam-uid':
                    // Skip this one and just delete
                    AAM_Core_API::deleteOption($option->option_name);
                    break;

                default:
                    $result = $this->_parseObjectOption($option);
                    break;
            }

            if ($result !== true) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Convert postmeta options
     *
     * @param object $options
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function processPostmeta($options)
    {
        $results = array();

        foreach($options as $option) {
            $name  = str_replace('aam-post-access-', '', $option->meta_key);
            $value = $this->_convertPostObject(maybe_unserialize($option->meta_value));

            if (strpos($name, 'user') === 0) {
                $xpath = 'user.' . substr($name, 4) . '.post.' . $option->post_id;
            } elseif (strpos($name, 'role') === 0) {
                $xpath = 'role.' . substr($name, 4) . '.post.' . $option->post_id;
            } elseif (in_array($name, array('visitor', 'default'), true)) {
                $xpath = $name . '.post.' . $option->post_id;
            } else {
                $xpath = null;
            }

            if (!is_null($xpath)) {
                AAM_Core_AccessSettings::getInstance()->set($xpath, $value);

                // Delete legacy option
                delete_post_meta($option->post_id, $option->meta_key);
            } else {
                $results[] = new WP_Error(
                    'migration_error',
                    sprintf('Failed to convert post "%d" options', $option->post_id),
                    $option
                );
            }
        }

        return $results;
    }

    /**
     * Convert usermeta options
     *
     * @param object $options
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function processUsermeta($options)
    {
        global $wpdb;

        $results = array();

        foreach($options as $option) {
            // e.g. "wp_aam_type_post", "wp_aam_term_1|category"
            $regex = '/^' . $wpdb->prefix . 'aam_([a-z]+)_?([a-z0-9_\-\|]*)$/i';

            // Let's parse the option name and determine object & subject
            if (preg_match($regex, $option->meta_key, $match)) {
                // (
                //   [0] => wp_aam_term_1|category
                //   [1] => term
                //   [2] => 1|category
                // )
                if (isset($this->migrationCallbacks[$match[1]])) {
                    // Convert options
                    $options = call_user_func(
                        $this->migrationCallbacks[$match[1]],
                        maybe_unserialize($option->meta_value),
                        $match[1]
                    );

                    $xpath  = 'user.' . $option->user_id;

                    if ($match[1] === 'taxonomy') {
                        $xpath .= '.system.defaultTerm.';
                        $xpath .= str_replace('|', '.', $match[2]);
                    } else {
                        $xpath .= ".{$match[1]}";
                        $xpath .= (empty($match[2]) ? '' : ".{$match[2]}");
                    }

                    AAM_Core_AccessSettings::getInstance()->set($xpath, $options);

                    // Delete legacy meta
                    delete_user_meta($option->user_id, $option->meta_key);
                } else {
                    $results[] = new WP_Error(
                        'migration_error',
                        sprintf('Unrecognized object type "%s"', $match[1]),
                        $option
                    );
                }
            }elseif ($option->meta_key === 'aam-jwt') {
                // Just delete it. AAM v5 JWT tokens are no longer valid due to the
                // new way to calculate exp property
                delete_user_meta($option->user_id, $option->meta_key);
            } else {
                $results[] = new WP_Error(
                    'migration_error',
                    sprintf('Failed to parse access option %s', $option->meta_key),
                    $option
                );
            }
        }

        return $results;
    }

    /**
     * Convert ConfigPress options
     *
     * @param object $option
     *
     * @return array|WP_Error
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertConfigPress($option)
    {
        $result = AAM_Core_ConfigPress::getInstance()->save($option->option_value);

        if ($result === true) {
            // Delete legacy option
            AAM_Core_API::deleteOption($option->option_name);
        } else {
            $response = new WP_Error(
                'migration_error', 'Failed to convert ConfigPress settings', $option
            );
        }

        return (!empty($response) ? $response : true);
    }

    /**
     * Convert AAM extensions option
     *
     * @param object $option
     *
     * @return array|WP_Error
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertExtensionRegistry($option)
    {
        $result = AAM_Core_API::updateOption(
            AAM_Addon_Repository::DB_OPTION, $option->option_value, 'site'
        );

        if ($result === true) {
            // Delete legacy option
            AAM_Core_API::deleteOption($option->option_name);
        } else {
            $response = new WP_Error(
                'migration_error', 'Failed to convert Addon settings', $option
            );
        }

        return (!empty($response) ? $response : true);
    }

    /**
     * Convert AAM Settings option
     *
     * @param object $option
     *
     * @return array|WP_Error
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertSettings($option)
    {
        $settings     = maybe_unserialize($option->option_value);
        $settings_map = array(
            'manage-capability'     => 'core.settings.editCapabilities',
            'render-access-metabox' => 'ui.settings.renderAccessMetabox',
            'core.xmlrpc'           => 'core.settings.xmlrpc',
            'core.restful'          => 'core.settings.restful',
            'page-category'         => 'core.settings.pageCategory',
            'media-category'        => 'core.settings.mediaCategory',
            'single-session'        => 'core.settings.singleSession',
            'brute-force-lockout'   => 'core.settings.bruteForceLockout'
        );

        $whitelist = array_merge(
            AAM_Backend_Feature_Settings_Content::getList(),
            AAM_Backend_Feature_Settings_Core::getList(),
            AAM_Backend_Feature_Settings_Security::getList(),
            array(
                'frontend.404redirect.type'     => true,
                'frontend.404redirect.callback' => true,
            )
        );

        $result = true;

        if (is_array($settings)) {
            $converted = array();

            foreach ($settings as $key => $value) {
                if (array_key_exists($key, $settings_map)) {
                    $converted[$settings_map[$key]] = filter_var(
                        $value, FILTER_VALIDATE_BOOLEAN
                    );
                } elseif (array_key_exists($key, $whitelist)) {
                    $converted[$key] = filter_var(
                        $value, FILTER_VALIDATE_BOOLEAN
                    );
                }
            }

            $result = AAM_Core_API::updateOption(
                AAM_Core_Config::DB_OPTION, $converted, 'site'
            );
        }

        if ($result === true) {
            // Delete legacy option
            AAM_Core_API::deleteOption($option->option_name);
        } else {
            $response = new WP_Error(
                'migration_error', 'Failed to convert core settings', $option
            );
        }

        return (!empty($response) ? $response : true);
    }

    /**
     * Convert IP Check options
     *
     * @param object $option
     *
     * @return array|WP_Error
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertIPCheckOptions($options)
    {
        $converted = array();

        foreach($options as $option) {
            $id = $option['type'] . '|' . $option['rule'];
            $converted[$id] = filter_var($option['mode'], FILTER_VALIDATE_BOOLEAN);
        }

        return $converted;
    }

    /**
     * Parse object specific DB option and delegate conversion
     *
     * @param object $option
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _parseObjectOption($option)
    {
        // e.g. "aam_visitor_ipCheck", "aam_visitor_term_1|category"
        if (strpos($option->option_name, 'aam_visitor') === 0) {
            $regex = '/^aam_visitor_([a-z]+)_?([a-z0-9_\-\|]*)$/i';
        } else {
            // e.g. "aam_route_role_administrator", "aam_type_post_role_editor"
            $regex = '/^aam_([a-z]+)_([a-z0-9_\-\|]+)?_?(role|default)_?([a-z0-9_\-]*)$/i';
        }

        // Let's parse the option name and determine object & subject
        if (preg_match($regex, $option->option_name, $match)) {
            // Role or Default subjects:
            // (
            //   [0] => aam_term_1|category_role_administrator_v2
            //   [1] => term
            //   [2] => 1|category
            //   [3] => role
            //   [4] => administrator_v2
            // )
            //
            // Visitor subject:
            // (
            //   [0] => aam_visitor_term_1|category
            //   [1] => term
            //   [2] => 1|category
            // )
            if (isset($this->migrationCallbacks[$match[1]])) {
                // Convert options
                $options = call_user_func(
                    $this->migrationCallbacks[$match[1]],
                    maybe_unserialize($option->option_value),
                    $match[1]
                );

                // Quick normalization. There are side effects with RegEx for terms
                // (e.g. term_1|category_) as well as IP Check object is ipCheck
                $object_id = strtolower(trim($match[2], '_'));

                if (count($match) === 3) { // This is Visitor
                    $xpath  = 'visitor.' . strtolower($match[1]);
                    $xpath .= (empty($object_id) ? '' : ".{$object_id}");
                } else { // This is either Role or Default
                    $xpath = $match[3] . (empty($match[4]) ? '' : ".{$match[4]}");

                    if ($match[1] === 'taxonomy') {
                        $xpath .= '.system.defaultTerm.';
                        $xpath .= str_replace('|', '.', $object_id);
                    } else {
                        $xpath .= ".{$match[1]}";
                        $xpath .= (empty($object_id) ? '' : ".{$object_id}");
                    }
                }

                AAM_Core_AccessSettings::getInstance()->set($xpath, $options);

                // Delete legacy option
                AAM_Core_API::deleteOption($option->option_name);
            } else {
                $error = new WP_Error(
                    'migration_error',
                    sprintf('Skipped unrecognized object type "%s"', $match[1]),
                    $option
                );
            }
        } else {
            $error = new WP_Error(
                'migration_error',
                sprintf('Skipped unrecognized option "%s"', $option->option_name),
                $option
            );
        }

        return (!empty($error) ? $error : true);
    }

    /**
     * Convert "flat" array of options
     *
     * It expects to have simple associated array of string => boolean values
     *
     * @param array  $options
     * @param string $object
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertFlatOptions($options, $object)
    {
        $converted = array();

        if (is_array($options)) {
            $converted = array_map(function($effect) {
                return filter_var($effect, FILTER_VALIDATE_BOOLEAN);
            }, $options);
        }

        return $converted;
    }

    /**
     * Convert metabox array of options
     *
     * @param array  $options
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertMetaboxOptions($options)
    {
        $converted = array();

        if (is_array($options)) {
            foreach($options as $key => $value) {
                if (!is_numeric($key)) {
                    $converted[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        return $converted;
    }

    /**
     * Convert menu array of options
     *
     * @param array  $options
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertMenuOptions($options)
    {
        return $this->_convertMetaboxOptions($options);
    }

    /**
     * Convert As-Is
     *
     * @param array  $options
     * @param string $object
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertAsIs($options, $object)
    {
        return $options;
    }

    /**
     * Convert URI options
     *
     * @param array $options
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertUriOptions($options)
    {
        $converted = array();

        if (is_array($options)) {
            foreach($options as $option) {
                $code = !empty($option['code']) ? intval($option['code']) : null;

                $converted[$option['uri']] = array(
                    'type'   => $option['type'],
                    'action' => $option['action'],
                    'code'   => $code
                );
            }
        }

        return $converted;
    }

    /**
     * Convert Term related options
     *
     * @param array $options
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertTermOptions($options)
    {
        $term_options = $post_options = array();

        foreach($options as $key => $value) {
            $parts = explode('|', $key);

            if ($parts[0] === 'post') {
                $post_options[$parts[1]] = $value;
            } elseif ($parts[0] === 'term') {
                $term_options[$parts[1]] = $value;
            }
        }

        return array_merge(
            $this->_convertTermObject($term_options),
            $this->_convertPostObject($post_options, 'post/')
        );
    }

    /**
     * Convert Type related options
     *
     * @param array $options
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertTypeOptions($options)
    {
        return $this->_convertTermOptions($options);
    }

    /**
     * Convert Taxonomy related options
     *
     * @param array $options
     *
     * @return int|null
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertTaxonomyOptions($options)
    {
        return (isset($options['default']) ? intval($options['default']) : null);
    }

    /**
     * Convert post object options
     *
     * @param array  $options
     * @param string $ns
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertPostObject($options, $ns = '')
    {
        $converted  = array();
        $normalized = $this->_normalizeContentOptions($options);

        foreach($normalized as $key => $val) {
            switch($key) {
                case 'list':
                    $converted[$ns . 'hidden'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'list_others':
                    $converted[$ns . 'hidden_others'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'read':
                    $converted[$ns . 'restricted'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'read_others':
                    $converted[$ns . 'restricted_others'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'limit':
                    $msg = (!empty($normalized['teaser']) ? $normalized['teaser'] : '');
                    $converted[$ns . 'teaser'] = array(
                        'enabled' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
                        'message' => $msg
                    );
                    break;

                case 'access_counter':
                    $l = (!empty($normalized['access_counter_limit']) ? $normalized['access_counter_limit'] : 0);
                    $converted[$ns . 'limited'] = array(
                        'enabled'   => filter_var($val, FILTER_VALIDATE_BOOLEAN),
                        'threshold' => $l
                    );
                    break;

                case 'comment':
                case 'edit':
                case 'delete':
                case 'publish':
                case 'edit_others':
                case 'delete_others':
                case 'publish_others':
                    $converted[$ns . $key] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'add':
                    $converted[$ns . 'create'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'redirect':
                    $chunks = explode('|', $normalized['location']);

                    $converted[$ns . 'redirected'] = array(
                        'enabled'     => filter_var($val, FILTER_VALIDATE_BOOLEAN),
                        'type'        => $chunks[0],
                        'destination' => $chunks[1],
                        'httpCode'    => (isset($chunks[2]) ? intval($chunks[2]) : 307)
                    );
                    break;

                case 'protected':
                    $converted[$ns . 'protected'] = array(
                        'enabled'  => filter_var($val, FILTER_VALIDATE_BOOLEAN),
                        'password' => $normalized['password']
                    );
                    break;

                case 'expire':
                    $converted[$ns . 'ceased'] = array(
                        'enabled' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
                        'after'   => $normalized['expire_datetime']
                    );
                    break;

                case 'access_counter_limit':
                case 'teaser':
                case 'location':
                case 'password':
                case 'expire_datetime':
                    // Skip those
                    break;

                default:
                    break;
            }
        }

        return $converted;
    }

    /**
     * Normalize content options
     *
     * Because we are removing the segmentation of access settings between website
     * levels (frontend, backend and api), this method with merge access settings
     * based on preferred priority where API has the highest and Backend the lowest
     *
     * @param array $options
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _normalizeContentOptions($options)
    {
        $normalized = array(
            'backend'  => array(),
            'frontend' => array(),
            'api'      => array(),
        );

        // Normalized it first
        foreach($options as $key => $value) {
            if (preg_match('/^(frontend|backend|api)\.(.*)$/i', $key, $match)) {
                $normalized[$match[1]][$match[2]] = $value;
            }
        }

        return array_merge(
            $normalized['backend'],  // Lowest priority
            $normalized['frontend'], // Higher priority
            $normalized['api']       // Highest priority
        );
    }

    /**
     * Convert term object options
     *
     * @param array $options
     *
     * @return array
     *
     * @access private
     * @version 6.0.0
     */
    private function _convertTermObject($options)
    {
        $converted  = array();
        $normalized = $this->_normalizeContentOptions($options);

        foreach($normalized as $key => $val) {
            switch($key) {
                case 'browse':
                    $converted['term/restricted'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'list':
                    $converted['term/hidden'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'edit':
                case 'delete':
                    $converted["term/{$key}"] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    break;

                default:
                    break;
            }
        }

        return $converted;
    }

}

if (defined('AAM_KEY')) {
    return (new Migration600())->run();
}