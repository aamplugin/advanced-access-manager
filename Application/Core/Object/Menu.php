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
     * Keep in mind that this function only filter the menu items but do not
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
                // Cover the scenario when there are some dynamic submenus
                $subs = $this->filterSubmenu($item, ($this->has('menu-' . $item[2])));
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
     * @param array &$parent
     * @param bool  $deny_all
     * 
     * @return void
     * 
     * @access protected
     * 
     * @global array $menu
     * @global array $submenu
     */
    protected function filterSubmenu(&$parent, $deny_all = false) {
        global $submenu;

        $filtered = array();

        foreach ($submenu[$parent[2]] as $id => $item) {
            if ($deny_all || $this->has($this->normalizeItem($item[2]))) {
                unset($submenu[$parent[2]][$id]);
            } else {
                $filtered[] = $submenu[$parent[2]][$id];
            }
        }
        
        if (count($filtered)) { //make sure that the parent points to the first sub
            $values    = array_values($filtered);
            $parent[2] = $values[0][2];
        }

        return $filtered;
    }
    
    /**
     * Get parent menu
     * 
     * @param string $search
     * 
     * @return string|bool
     * 
     * @access protected
     * @global array $submenu
     */
    protected function getParentMenu($search) {
        global $submenu;
        
        $result = null;
        
        //if (is_array($submenu)) {
            foreach($submenu as $parent => $subs) {
                foreach($subs as $sub) {
                    if ($sub[2] == $search) {
                        $result = $parent;
                        break;
                    }
                }

                if ($result !== null) {
                    break;
                }
            }
        //}
        
        return $result;
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
        $parent  = $this->getParentMenu($decoded);
        
        // Step #1. Check if menu is directly restricted
        $direct = !empty($options[$decoded]);
        
        // Step #2. Check if whole branch is restricted
        $branch = ($both && !empty($options['menu-' . $decoded]));
        
        // Step #3. Check if dynamic submenu is restricted because of whole branch
        $indirect = ($parent && !empty($options['menu-' . $parent]));
        
        return $direct || $branch || $indirect;
    }

    /**
     * Save menu option
     * 
     * @return bool
     * 
     * @access public
     */
    public function save($item = null, $value = null) {
        if (!is_null($item)) { // keep it compatible with main Manager.save
            $this->updateOptionItem($item, $value);
        }
        
        return $this->getSubject()->updateOption($this->getOption(), 'menu');
    }
    
    /**
     * Reset default settings
     * 
     * @return bool
     * 
     * @access public
     */
    public function reset() {
        return $this->getSubject()->deleteOption('menu');
    }

}