<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Extension Repository
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Extension_Repository {
    
    /**
     * Extension status: installed
     * 
     * Extension has been installed and is up to date
     */
    const STATUS_INSTALLED = 'installed';
    
    /**
     * License violation
     * 
     * Extension is inactive
     */
    const STATUS_INACTIVE = 'inactive';
    
    /**
     * Extension status: download
     * 
     * Extension is not installed and either needs to be purchased or 
     * downloaded for free.
     */
    const STATUS_DOWNLOAD = 'download';
    
    /**
     * Extension status: update
     * 
     * New version of the extension has been detected.
     */
    const STATUS_UPDATE = 'update';

    /**
     * Single instance of itself
     * 
     * @var AAM_Extension_Repository
     * 
     * @access private
     * @static 
     */
    private static $_instance = null;
    
    /**
     * List of detected extensions during the boot
     * 
     * @var array
     * 
     * @access protected 
     */
    protected $detectedExtensions = array();
    
    /**
     * Extension list
     * 
     * @var array
     * 
     * @access protected 
     */
    protected $list = array();

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     */
    protected function __construct() {}

    /**
     * Load active extensions
     *
     * @return void
     *
     * @access public
     */
    public function load($dir = null) {
        $basedir = (is_null($dir) ? $this->getBasedir() : $dir);
        
        //since release 3.4 some extensions get integrated into core
        AAM_Core_Compatibility::initExtensions();

        if (file_exists($basedir)) {
            //iterate through each active extension and load it
            foreach (scandir($basedir) as $extension) {
                if (!in_array($extension, array('.', '..'), true)) {
                    $this->bootstrapExtension($basedir . '/' . $extension);
                }
            }
            // TODO: Rethink this hook
            //Very important hook for cases when there is extensions dependency.
            //For example AAM Plus Package depends on AAM Utilities properties
            do_action('aam-post-extensions-load');
        }
    }
    
    /**
     * Bootstrap the Extension
     *
     * In case of any errors, the output can be found in console
     *
     * @param string $path
     *
     * @return void
     * @access protected
     */
    protected function bootstrapExtension($path) {
        static $cache = null;

        if (is_null($cache)) {
            $cache = AAM_Core_Compatibility::getLicenseList();
        }
        
        $load      = false;
        $config    = "{$path}/config.php";
        $bootstrap = "{$path}/bootstrap.php";
        
        if (file_exists($config)) {
            $conf = require $config;
            
            $this->detectedExtensions[$conf['id']] = $conf['version'];
            
            // determine if extension needs to be loaded based on the status
            $status = empty($cache[$conf['id']]['status']) || ($cache[$conf['id']]['status'] !== self::STATUS_INACTIVE);
            
            // determine if extension meets minimum required AAM version
            $list    = AAM_Extension_List::get();
            $issue   = !empty($conf['requires']['aam']) && (version_compare(AAM_Core_API::version(), $conf['requires']['aam']) === -1);
            $load    = $status && !$issue;
            
            if ($issue) {
                if (!empty($list[$conf['id']]['title'])) { // Any custom extensions
                    AAM_Core_Console::add(AAM_Backend_View_Helper::preparePhrase(
                        sprintf(
                            __('[%s] was not loaded. Update AAM plugin to the latest version.', AAM_KEY),
                            $list[$conf['id']]['title']
                        ),
                        'b'
                    ));
                }
            }
        } else { // TODO - Remove May 2019
            AAM_Core_Console::add(AAM_Backend_View_Helper::preparePhrase(
                sprintf(
                    __('The [%s] does not appear to be a valid AAM extension. %sRead more.%s', AAM_KEY),
                    str_replace(AAM_EXTENSION_BASE . '/', '', $config),
                   '<a href="https://aamplugin.com/article/how-to-fix-the-config-php-file-is-missing-notification" target="_blank">',
                   '</a>'
                ),
                'b'    
            ));
        }

        if ($load && file_exists($bootstrap)) { //bootstrap the extension
            require($bootstrap);
        }
    }

    /**
     * Store the license key
     * 
     * This is important to have just for the update extension purposes
     * 
     * @param stdClass $package
     * @param string   $license
     * 
     * @return void
     * 
     * @access public
     */
    public function storeLicense($package, $license) {
        //retrieve the installed list of extensions
        $list = AAM_Core_Compatibility::getLicenseList();
        
        $list[$package->id] = array(
            'license' => $license, 'expire' => $package->expire
        );
        
        //update the extension list
        AAM_Core_API::updateOption('aam-extensions', $list);
    }
    
    /**
     * 
     * @return type
     */
    public function getCommercialLicenses($details = true) {
        $response   = array();
        $licenses   = AAM_Core_API::getOption('aam-extensions', array());
        $extensions = AAM_Extension_List::get();
        
        foreach((array) $licenses as $key => $data) {
            if (isset($extensions[$key]) 
                    && !empty($data['license']) 
                    && $extensions[$key]['type'] === 'commercial') {
                
                if ($details) {
                    $response[] = array(
                        'license'   => $data['license'],
                        'extension' => $extensions[$key]['title'],
                        'expires'   => (!empty($data['expires']) ? $data['expires'] : null)
                    );
                } else {
                    $response[] = $data['license'];
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Add new extension
     * 
     * @param blob $zip
     * 
     * @return boolean
     * @access public
     */
    public function add($zip) {
        $filepath = $this->getBasedir() . '/' . uniqid('aam_');
        $result   = false;
        
        if (file_put_contents($filepath, $zip)) { //unzip the archive
            WP_Filesystem(false, false, true); //init filesystem
            $result = unzip_file($filepath, $this->getBasedir());
            if (!is_wp_error($result)) {
                $result = true;
            }
            @unlink($filepath); //remove the working archive
        }

        return $result;
    }
    
    /**
     * Update extension status
     * 
     * @param string $id
     * @param string $status
     */
    public function updateStatus($id, $status) {
        //retrieve the installed list of extensions
        $list = AAM_Core_Compatibility::getLicenseList();
     
        $list[$id]['status'] = $status;
        
        //update the extension list
        AAM_Core_API::updateOption('aam-extensions', $list);
    }
    
    /**
     * Get extension version
     * 
     * @param string $id
     * 
     * @return string|null
     * 
     * @access public
     */
    public function getVersion($id) {
        return (isset($this->detectedExtensions[$id]) ? $this->detectedExtensions[$id] : null);
    }
    
    /**
     * Get extension list
     * 
     * @return array
     * 
     * @access public
     */
    public function getList() {
        if (empty($this->list)) {
            $list  = AAM_Extension_List::get();
            $index = AAM_Core_Compatibility::getLicenseList();
            $check = AAM_Core_API::getOption('aam-check', array(), 'site');
            
            foreach ($list as $id => &$item) {
                //get premium license from the stored license index
                if (empty($item['license'])) {
                    if (!empty($index[$id]['license'])) {
                        $item['license'] = $index[$id]['license'];
                        $item['expire']  = (!empty($index[$id]['expire']) ? date('Y-m-d', strtotime($index[$id]['expire'])) : null);
                    } else {
                        $item['license'] = '';
                    }
                }

                if (!empty($item['plugin'])) {
                    $item['pluginStatus'] = $this->getPluginStatus($item['plugin']);
                }
                
                //update extension status
                $item['status'] = $this->checkStatus($item, $check, $index);
            }
            
            $this->list = $list;
        }
        
        return $this->list;
    }

    protected function getPluginStatus($plugin) {
        if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $filename = WP_PLUGIN_DIR . '/' . $plugin;
        $status   = null;
        
        if (function_exists('get_plugin_data') && file_exists($filename)) {
            $status = is_plugin_active($plugin);
        }
        
        return $status;
    }
    
    /**
     * 
     * @param type $item
     * @param type $index
     * @return type
     */
    protected function checkStatus($item, $retrieved, $stored) {
        $id     = $item['id'];
        $status = !empty($stored[$id]['status']) ? $stored[$id]['status'] : null;
        
        if (is_null($status)) {
            $status = AAM_Extension_Repository::STATUS_DOWNLOAD;
            
            if (isset($this->detectedExtensions[$id])) {
                $status = AAM_Extension_Repository::STATUS_INSTALLED;
                
                if ($this->isOutdatedVersion($item, $retrieved, $this->detectedExtensions[$id])) {
                    $status = AAM_Extension_Repository::STATUS_UPDATE;
                    AAM_Core_Console::add(
                        AAM_Backend_View_Helper::preparePhrase(sprintf(
                            'The [%s] extension has new update available for download;',
                            $item['title']
                        ), 'b')
                    );
                }
            }
        } elseif ($status === AAM_Extension_Repository::STATUS_INSTALLED) {
            if (!isset($this->detectedExtensions[$id])) {
                $status = AAM_Extension_Repository::STATUS_DOWNLOAD;
            } elseif ($this->isOutdatedVersion($item, $retrieved, $this->detectedExtensions[$id])) {
                $status = AAM_Extension_Repository::STATUS_UPDATE;
            }
        }
        
        return $status;
    }
    
    /**
     * 
     * @param type $item
     * @param type $retrieved
     * @param type $version
     * @return type
     */
    protected function isOutdatedVersion($item, $retrieved, $version) {
        $id = $item['id'];
        
        // first check the retrieved version from the server
        if (isset($retrieved->$id)) {
            $outdated = version_compare($version, $retrieved->$id->version) === -1;
        } else {
            $outdated = version_compare($version, $item['latest']) === -1;
        }

        return $outdated;
    }
    
    /**
     * Check extension directory
     * 
     * @return boolean|string
     * 
     * @access public
     * 
     * @global type $wp_filesystem
     */
    public function checkDirectory() {
        $error   = false;
        $basedir = $this->getBasedir();
        
        if (!file_exists($basedir)) {
            if (!@mkdir($basedir, fileperms(ABSPATH) & 0777 | 0755, true)) {
                $error = sprintf(__('Failed to create %s', AAM_KEY), $basedir);
            }
        } elseif (!is_writable($basedir)) {
            $error = sprintf(
                    __('Directory %s is not writable', AAM_KEY), $basedir
            );
        }

        return $error;
    }
    
    /**
     * Get base directory
     * 
     * @return string
     * 
     * @access public
     */
    public function getBasedir($relative = false) {
        $dir = AAM_Core_Config::get('core.extension.directory', AAM_EXTENSION_BASE);
        
        return ($relative ? str_replace(ABSPATH, '', $dir) : $dir);
    }
    
    /**
     * 
     * @return type
     */
    public function isWriteableDirectory() {
        $directory = $this->getBasedir();
        
        return file_exists($directory) && is_writeable($directory);
    }
    
    /**
     * Check if there are any updates
     * 
     * @return type
     */
    public function hasUpdates() {
        $updates = 0;
        
        foreach($this->getList() as $item) {
            $updates += ($item['status'] === self::STATUS_UPDATE);
        }
        
        return $updates ? true : false;
    }
    
    /**
     * Get single instance of itself
     * 
     * @param AAM $parent
     * 
     * @return AAM_Extension_Repository
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}