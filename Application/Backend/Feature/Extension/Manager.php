<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend extension manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Extension_Manager extends AAM_Backend_Feature_Abstract {
    
    /**
     *
     * @var type 
     */
    protected static $instance = null;
    
    /**
     * 
     */
    public function render() {
        require_once(dirname(__FILE__) . '/../../phtml/extensions.phtml');
    }
    
    /**
     * Undocumented function
     *
     * @return void
     */
    public function check() {
        AAM::cron();

        return json_encode(array('status' => 'success'));
    }
    
    /**
     * Install an extension
     * 
     * @param string $storedLicense
     * 
     * @return string
     * 
     * @access public
     */
    public function install($storedLicense = null) {
        $repo    = AAM_Extension_Repository::getInstance();
        $license = AAM_Core_Request::post('license', $storedLicense);
        
        //download the extension from the server first
        $package = AAM_Core_Server::download($license);
        
        if (is_wp_error($package)) {
            $manually = sprintf(
                __('To install extension manually, check %sthis article%s.', AAM_KEY), 
                '<a href="https://aamplugin.com/help/how-to-install-aam-extension-manually">', 
                '</a>'
            );
            $response = array(
                'status' => 'failure', 
                'error'  => strip_tags($package->get_error_message()) . ' ' . $manually
            );
        }elseif ($error = $repo->checkDirectory()) {
            $response = $this->installFailureResponse($error, $package);
            $repo->storeLicense($package, $license);
        } elseif (empty($package->content)) { //any unpredictable scenario
            $response = array(
                'status' => 'failure', 
                'error'  => 'Download failure. Please try again or contact us.'
            );
        } else { //otherwise install the extension
            $result = $repo->add(base64_decode($package->content));
            if (is_wp_error($result)) {
                $response = $this->installFailureResponse(
                        $result->get_error_message(), $package
                );
            } else {
                $response = array('status' => 'success');
            }
            $repo->storeLicense($package, $license);
        }
        
        return json_encode($response);
    }
    
    /**
     * Update the extension
     * 
     * @return string
     * 
     * @access public
     */
    public function update() {
        $id       = AAM_Core_Request::post('extension');
        $licenses = AAM_Core_Compatibility::getLicenseList();
        
        if (!empty($licenses[$id]['license'])) {
            $response = $this->install($licenses[$id]['license']);
        } else {
            //fallback compatibility
            $list = AAM_Extension_Repository::getInstance()->getList();
            if (!empty($list[$id]['license'])) {
                $response = $this->install($list[$id]['license']);
            } else {
                $response = json_encode(array(
                    'status' => 'failure', 
                    'error'  => __('Enter license key to update extension.', AAM_KEY)
                ));
            }
        }
        
        return $response;
    }
    
    /**
     * 
     * @return type
     */
    public function deactivate() {
        AAM_Extension_Repository::getInstance()->updateStatus(
                AAM_Core_Request::post('extension'), 
                AAM_Extension_Repository::STATUS_INACTIVE
        );
        
        return json_encode(array('status' => 'success'));
    }
    
    /**
     * 
     * @return type
     */
    public function activate() {
        AAM_Extension_Repository::getInstance()->updateStatus(
                AAM_Core_Request::post('extension'), 
                AAM_Extension_Repository::STATUS_INSTALLED
        );
        
        return json_encode(array('status' => 'success'));
    }
    
    /**
     * 
     * @param type $type
     * @return type
     */
    public function getList($type) {
        $response = array();
        
        foreach(AAM_Extension_Repository::getInstance()->getList() as $item) {
            if ($item['type'] == $type) {
                $response[] = $item;
            }
        }
        
        return $response;
    }
    
    /**
     * 
     * @return type
     */
    public function canShowLicense() {
        $result = true;
        
        if (AAM_Core_API::capabilityExists('aam_display_license')) {
            $result = !empty(AAM::getUser()->allcaps['aam_display_license']);
        }
        
        return $result;
    }
    
    /**
     * Install extension failure response
     * 
     * In case the file system fails, AAM allows to download the extension for
     * manual installation
     * 
     * @param string   $error
     * @param stdClass $package
     * 
     * @return array
     * 
     * @access protected
     */
    protected function installFailureResponse($error, $package) {
        return array(
            'status'  => 'failure',
            'error'   => $error,
            'title'   => $package->title,
            'content' => $package->content
        );
    }
    
    /**
     * 
     * @return AAM_Backend_Feature_Extension_Manager
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
   
}