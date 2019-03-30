<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Metabox object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Metabox extends AAM_Core_Object {

    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     *
     * @return void
     *
     * @access public
     */
    public function __construct(AAM_Core_Subject $subject) {
        parent::__construct($subject);
        
        $option = AAM_Core_Compatibility::convertMetaboxes(
                $this->getSubject()->readOption('metabox')
        );
        
        if (!empty($option)) {
            $this->setOverwritten(true);
        }
        
        // Load settings from Access & Security Policy
        if (empty($option)) {
            $stms = AAM_Core_Policy_Factory::get($subject)->find("/^(Metabox|Widget):/i");
            
            foreach($stms as $key => $stm) {
                $chunks = explode(':', $key);
                $option[$chunks[1]] = ($stm['Effect'] === 'deny' ? 1 : 0);
            }
        }
        
        if (empty($option)) {
            $option = $this->getSubject()->inheritFromParent('metabox');
        }

        $this->setOption($option);
    }

    /**
     *
     * @global type $wp_registered_widgets
     * @param type $sidebar_widgets
     * @return type
     */
    public function filterFrontend($sidebar_widgets) {
        global $wp_registered_widgets;

        if (is_array($wp_registered_widgets)) {
            foreach ($wp_registered_widgets as $id => $widget) {
                $callback = $this->getWidgetCallback($widget);
                if ($this->has('widgets', $callback)) {
                    unregister_widget($callback);
                    //remove it from registered widget global var!!
                    //INFORM: Why Unregister Widget does not clear global var?
                    unset($wp_registered_widgets[$id]);
                }
            }
        }

        return $sidebar_widgets;
    }

    /**
     * 
     * @param type $widget
     * @return type
     */
    protected function getWidgetCallback($widget) {
        if (is_object($widget['callback'][0])) {
            $callback = get_class($widget['callback'][0]);
        } elseif (is_string($widget['callback'][0])) {
            $callback = $widget['callback'][0];
        } else {
            $callback = isset($widget['classname']) ? $widget['classname'] : null;
        }

        return $callback;
    }

    /**
     *
     * @global type $wp_meta_boxes
     * @param type $screen
     */
    public function filterBackend($screen) {
        global $wp_meta_boxes;

        if (is_array($wp_meta_boxes)) {
            foreach ($wp_meta_boxes as $screen_id => $zones) {
                if ($screen === $screen_id) {
                    $this->filterZones($zones, $screen_id);
                }
            }
        }
    }
    
    /**
     * 
     * @global type $wp_registered_widgets
     */
    public function filterAppearanceWidgets() {
        global $wp_registered_widgets;
        
        foreach($wp_registered_widgets as $id => $widget) {
            $callback = $this->getWidgetCallback($widget);
            if ($this->has('widgets', $callback)) {
                unregister_widget($callback);
                unset($wp_registered_widgets[$id]);
            }
        }
    }

    /**
     * 
     * @param type $zones
     * @param type $screen_id
     */
    protected function filterZones($zones, $screen_id) {
        foreach ($zones as $zone => $priorities) {
            foreach ($priorities as $metaboxes) {
                $this->filterMetaboxes($zone, $metaboxes, $screen_id);
            }
        }
    }

    /**
     * 
     * @param type $zone
     * @param type $metaboxes
     * @param type $screen_id
     */
    protected function filterMetaboxes($zone, $metaboxes, $screen_id) {
        foreach ($metaboxes as $id => $metabox) {
            if ($this->has($screen_id, $id, $metabox['title'])) {
                remove_meta_box($id, $screen_id, $zone);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function save($metabox, $granted) {
        $option = $this->getOption();

        $option[$metabox]        = $granted;
        $option[crc32($metabox)] = $granted;

        return $this->getSubject()->updateOption($option, 'metabox');
    }
    
    /**
     * 
     */
    public function reset() {
        return $this->getSubject()->deleteOption('metabox');
    }

    /**
     *
     * @param type $screen
     * @param type $metabox
     * @return type
     */
    public function has($screen, $metaboxId, $metaboxTitle = null) {
        $options = $this->getOption();
        $mid     = "{$screen}|{$metaboxId}";

        if(function_exists('mb_strtolower')) {
            $mtl = mb_strtolower("{$screen}|{$metaboxTitle}");
        } else {
            $mtl = strtolower("{$screen}|{$metaboxTitle}");
        }

        // Also remove any HTML tags
        $mtl = wp_strip_all_tags($mtl);

        return !empty($options[$mid]) || !empty($options[crc32($mid)]) || !empty($options[$mtl]);
    }
    
    /**
     * Allow access to a specific metabox
     * 
     * @param string $screen
     * @param string $metabox
     * 
     * @return boolean
     * 
     * @access public
     */
    public function allow($screen, $metabox) {
        $this->save("{$screen}|{$metabox}", 0);
    }
    
    /**
     * Deny access to a specific metabox
     * 
     * @param string $screen
     * @param string $metabox
     * 
     * @return boolean
     * 
     * @access public
     */
    public function deny($screen, $metabox) {
        return $this->save("{$screen}|{$metabox}", 1);
    }
    
    /**
     * 
     * @param type $external
     * @return type
     */
    public function mergeOption($external) {
        return AAM::api()->mergeSettings($external, $this->getOption(), 'metabox');
    }

}