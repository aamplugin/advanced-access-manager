<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

use Composer\Semver\Semver;

/**
 * AAM access policy validator
 *
 * @since 6.9.9 https://github.com/aamplugin/advanced-access-manager/issues/267
 * @since 6.2.2 Bug fixing
 * @since 6.2.0 Allowing to define token in the dependencies array as well as
 *              enhanced with additional attributes
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.9
 */
class AAM_Core_Policy_Validator
{

    /**
     * Policy dependency exists however version is not satisfied
     *
     * @version 6.2.0
     */
    const INVALID_DEPENDENCY_VERSION = 10;

    /**
     * Policy dependency does not exist
     *
     * @version 6.2.0
     */
    const MISSING_DEPENDENCY = 20;

    /**
     * Raw policy text
     *
     * @var string
     *
     * @access protected
     * @version 6.0.0
     */
    protected $policy;

    /**
     * Parsed JSON document
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected $json;

    /**
     * Collection of errors
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected $errors = array();

    /**
     * Constructor
     *
     * @param string $policy
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function __construct($policy)
    {
        $this->policy = trim($policy);
        $this->json   = json_decode($policy, true);
    }

    /**
     * Validate the policy by invoking several validation steps
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function validate()
    {
        $steps = array(
            'isJSON',            // #1. Check if policy is valid JSON
            'isNotEmpty',        // #2. Check if policy is not empty
            'isValidDependency', // #3. Check if all dependencies are defined properly
        );

        foreach ($steps as $step) {
            if (call_user_func(array($this, $step)) === false) {
                break;
            }
        }

        return $this->errors;
    }

    /**
     * Check if policy is valid JSON
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function isJSON()
    {
        $result = is_array($this->json);

        if ($result === false) {
            $this->errors[] = __('The policy is not valid JSON object', AAM_KEY);
        }

        return $result;
    }

    /**
     * Check if policy is empty
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function isNotEmpty()
    {
        $result = !empty($this->policy) && !empty($this->json);

        if ($result === false) {
            $this->errors[] = __('The policy document is empty', AAM_KEY);
        }

        return $result;
    }

    /**
     * Check for the policy dependencies
     *
     * Make sure that depending plugins are installed and have proper versions
     *
     * @return void
     *
     * @since 6.2.2 Fixed bug with validation when plugin is not installed
     * @since 6.2.0 Enhanced dependency with more attributes
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.2.2
     */
    protected function isValidDependency()
    {
        if (!empty($this->json['Dependency'])) {
            foreach ($this->json['Dependency'] as $slug => $info) {
                try {
                    $v     = (is_array($info) ? $info['Version'] : $info);
                    $app_v = $this->getAppVersion($slug);
                    $valid = !empty($app_v) && Semver::satisfies($app_v, $v);

                    if ($valid === false) {
                        throw new Exception('', self::INVALID_DEPENDENCY_VERSION);
                    }
                } catch (Exception $e) {
                    // Build the error message
                    if (is_array($info)) {
                        $name = (isset($info['Name']) ? $info['Name'] : $slug);
                        $url  = (isset($info['URL']) ? $info['URL'] : null);
                    } else {
                        $name = $slug;
                        $url  = null;
                    }

                    // Prepare $app marker
                    if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                        $app = sprintf(
                            '<a href="%s" target="_blank">' . $name . '</a>', $url
                        );
                    } else {
                        $app = $name;
                    }

                    if ($e->getCode() === self::INVALID_DEPENDENCY_VERSION) {
                        $message = __('The {$app} is not active or does not satisfy minimum required version', AAM_KEY);
                    } elseif ($e->getCode() === self::MISSING_DEPENDENCY) {
                        $message = __('The {$app} is required', AAM_KEY);
                    } else {
                        $message = $e->getMessage();
                    }

                    $this->errors[] = str_replace('{$app}', $app, $message);
                }
            }
        }
    }

    /**
     * Get dependency's version
     *
     * @param string $app
     *
     * @return void
     *
     * @since 6.2.0 Allowing token to be a slug
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.2.0
     */
    protected function getAppVersion($app)
    {
        global $wp_version;

        $slug = strtolower($app);

        if ($slug === 'wordpress') {
            $version = $wp_version;
        } elseif (strpos($slug, '${') === 0) {
            $version = AAM_Core_Policy_Token::getTokenValue($app);
        } else {
            $version = $this->getPluginVersion($slug);
        }

        return $version;
    }

    /**
     * Get plugin's version
     *
     * @param string $slug
     *
     * @return string
     *
     * @since 6.9.9 https://github.com/aamplugin/advanced-access-manager/issues/267
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @throws Exception
     * @version 6.9.9
     */
    protected function getPluginVersion($slug)
    {
        static $plugins = null;

        if (is_null($plugins)) {
            if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            // Also load MU plugins
            $plugins = array_merge(get_plugins(), $this->_getMUPlugins());
        }

        $version = null;

        foreach ($plugins as $plugin => $data) {
            if (stripos($plugin, $slug . '/') === 0) {
                $version = $data['Version'];
            }
        }

        if (is_null($version)) {
            throw new Exception('', self::MISSING_DEPENDENCY);
        }

        return $version;
    }

    /**
     * Get list of must-use plugins
     *
     * @return array
     *
     * @access private
     * @since 6.9.9
     */
    private function _getMUPlugins()
    {
        $mu_plugins = array();

        if (is_dir(WPMU_PLUGIN_DIR)) {
            foreach (new DirectoryIterator(WPMU_PLUGIN_DIR) as $plugin) {
                if ($plugin->isDir() && !$plugin->isDot()) {
                    $files = glob($plugin->getPathname() . '/*.php');

                    if ( $files ) {
                        foreach ($files as $file) {
                            $info = get_plugin_data($file, false, false);

                            if (!empty( $info['Name'] ) ) {
                                $slug = $plugin->getBasename() . '/' . basename($file);
                                $mu_plugins[$slug] = $info;
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $mu_plugins;
    }

}