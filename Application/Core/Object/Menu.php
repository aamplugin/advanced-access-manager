<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Menu object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Menu extends AAM_Core_Object {

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
        
        $option = $this->getSubject()->readOption('menu');
        
        if (empty($option)) {
            $option = $this->getSubject()->inheritFromParent('menu');
        } else {
            $this->setOverwritten(true);
        }
        
        $this->setOption($option);
    }

    /**
     * Filter Menu List
     * 
     * Keep in mind that this funciton only filter the menu items but do not
     * restrict access to them. You have to explore roles and capabilities to
     * control the full access to menus.
     *
     * @global array $menu
     * @global array $submenu
     *
     * @return void
     *
     * @access public
     */
    public function filter() {
        global $menu, $submenu;

        foreach ($menu as $id => $item) {
            if (!empty($submenu[$item[2]])) {
                $subs = $this->filterSubmenu($item[2]);
            } else {
                $subs = array();
            }

            // cover scenario like with Visual Composer where landing page
            // is defined dynamically
            if ($this->has('menu-' . $item[2])) {
                unset($menu[$id]);
            } elseif ($this->has($item[2])) {
                if (count($subs)) {
                    $menu[$id][2] = $subs[0][2];
                    $submenu[$menu[$id][2]] = $subs;
                } else {
                    unset($menu[$id]);
                }
            }
        }

        // remove duplicated separators
        $count = 0;
        foreach ($menu as $id => $item) {
            if (preg_match('/^separator/', $item[2])) {
                if ($count === 0) {
                    $count++;
                } else {
                    unset($menu[$id]);
                }
            } else {
                $count = 0;
            }
        }
    }
    
    /**
     * 
     * @param array $menu
     * @return array
     */
    protected function normalizeItem($menu) {
        if (strpos($menu, 'customize.php') === 0) {
            $menu = 'customize.php';
        }
        
        return $menu;
    }

    /**
     * Filter submenu
     * 
     * @param string $parent
     * 
     * @return void
     * 
     * @access protected
     * @global array $menu
     * @global array $submenu
     */
    protected function filterSubmenu($parent) {
        global $submenu;

        $filtered = array();

        foreach ($submenu[$parent] as $id => $item) {
            if ($this->has($this->normalizeItem($item[2]))) {
                unset($submenu[$parent][$id]);
            } else {
                $filtered[] = $submenu[$parent][$id];
            }
        }

        return $filtered;
    }

    /**
     * Check is menu defined
     * 
     * Check if menu defined in options based on the id
     * 
     * @param string $menu
     * 
     * @return boolean
     * 
     * @access public
     */
    public function has($menu, $both = false) {
        //decode URL in case of any special characters like &amp;
        $decoded = htmlspecialchars_decode($menu);
        $options = $this->getOption();
        
        return !empty($options[$decoded]) || ($both && !empty($options['menu-' . $decoded]));
    }

    /**
     * @inheritdoc
     */
    public function save($menu, $granted) {
        $option = $this->getOption();
        $option[$menu] = $granted;
        $this->setOption($option);

        return $this->getSubject()->updateOption($option, 'menu');
    }
    
    /**
     * 
     */
    public function reset() {
        return $this->getSubject()->deleteOption('menu');
    }

}