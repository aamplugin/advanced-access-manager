<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

 use Vectorface\Whip\Whip;

/**
 * AAM shortcode handler for content visibility
 *
 * @since 6.9.19 https://github.com/aamplugin/advanced-access-manager/issues/333
 * @since 6.9.16 https://github.com/aamplugin/advanced-access-manager/issues/316
 *               https://github.com/aamplugin/advanced-access-manager/issues/317
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/96
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.19
 */
class AAM_Service_Shortcode_Handler_Content
    implements AAM_Core_Contract_ShortcodeInterface
{

    /**
     * Shortcode arguments
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected $args;

    /**
     * Wrapped by shortcode content
     *
     * @var string
     *
     * @access protected
     * @version 6.0.0
     */
    protected $content;

    /**
     * Initialize shortcode decorator
     *
     * Expecting attributes in $args are:
     *   "hide"     => comma-separated list of role, caps, user IDs to hide content
     *   "show"     => comma-separated list of role, caps, user IDs to show content
     *   "limit"    => comma-separated list of role, caps, user IDs to limit content
     *   "message"  => message to show if "limit" is defined
     *
     * @param array  $args
     * @param string $content
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function __construct($args, $content)
    {
        $this->args    = $args;
        $this->content = do_shortcode($content);
    }

    /**
     * Process shortcode
     *
     * @return string
     *
     * @since 6.9.16 https://github.com/aamplugin/advanced-access-manager/issues/316
     * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/96
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.16
     */
    public function run()
    {
        // Prepare list of subjects
        if (get_current_user_id()) {
            $roles = array_merge(AAM::getUser()->roles);

            // Build the list of assigned capabilities
            $caps = array();
            foreach(AAM::getUser()->allcaps as $key => $effect) {
                if (!empty($effect)) {
                    $caps[] = $key;
                }
            }

            if (AAM::api()->configs()->get_config('core.settings.multiSubject')) {
                $parts = array_merge(array((string) AAM::getUser()->ID), $roles);
            } else {
                $parts = array((string) AAM::getUser()->ID, array_shift($roles));
            }

            $parts = array_merge($parts, $caps);
        } else {
            $parts = array('visitor');
        }

        $show  = $this->getAccess('show');
        $limit = $this->getAccess('limit');
        $hide  = $this->getAccess('hide');
        $msg   = $this->getMessage();

        $content = $this->content;

        // #1. Check if content is restricted for current user
        if (in_array('all', $hide, true) || $this->check($parts, $hide)) {
            $content = '';
        }

        // #2. Check if content is limited for current user
        if (in_array('all', $limit, true) || $this->check($parts, $limit)) {
            $content = do_shortcode($msg);
        }

        // #3. Check if content is allowed for current user
        if ($this->check($parts, $show)) {
            $content = $this->content;
        }

        return $content;
    }

    /**
     * Check if visibility condition is matched
     *
     * @param mixed $subject
     * @param array $conditions
     *
     * @return boolean
     *
     * @since 6.9.16 https://github.com/aamplugin/advanced-access-manager/issues/317
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.16
     */
    protected function check($subject, $conditions)
    {
        $match = false;
        $auth  = get_current_user_id();
        $whip = new Whip();

        foreach ($conditions as $condition) {
            if (($condition === 'authenticated') && $auth) {
                $match = true;
            } else if (preg_match('/^[\d*-]+\.[\d*-]+[\d\.*-]*[\d\.*-]*$/', $condition)) {
                $match = $this->checkIP(
                    $condition, $whip->getValidIpAddress()
                );
            } else {
                $match = in_array($condition, $subject, true);
            }

            if ($match) {
                break;
            }
        }

        return $match;
    }

    /**
     * Check user IP for match
     *
     * @param string $ip
     * @param string $userIp
     *
     * @return boolean
     *
     * @since 6.3.0 https://github.com/aamplugin/advanced-access-manager/issues/38
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.3.0
     */
    protected function checkIP($ip, $userIp)
    {
        $match = true;

        $ipSplit  = preg_split('/[\.:]/', $ip);
        $uipSplit = preg_split('/[\.:]/', $userIp);

        foreach ($ipSplit as $i => $group) {
            if (strpos($group, '-') !== false) { //range
                $parts = explode('-', $group);

                if ($uipSplit[$i] < $parts[0] || $uipSplit[$i] > $parts[1]) {
                    $match = false;
                    break;
                }
            } elseif ($group !== '*') {
                if ($group !== $uipSplit[$i]) {
                    $match = false;
                    break;
                }
            }
        }

        return $match;
    }

    /**
     * Get access preference by type
     *
     * @return array
     *
     * @since 6.9.19 https://github.com/aamplugin/advanced-access-manager/issues/333
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.19
     */
    public function getAccess($type)
    {
        $access = (isset($this->args[$type]) ? $this->args[$type] : null);

        return is_string($access) ? array_map('trim', explode(',', $access)) : array();
    }

    /**
     * Get replacement message
     *
     * @return string|null
     *
     * @since 6.9.16 https://github.com/aamplugin/advanced-access-manager/issues/316
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.16
     */
    public function getMessage()
    {
        return isset($this->args['message']) ? esc_js($this->args['message']) : null;
    }

}