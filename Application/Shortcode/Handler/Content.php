<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * AAM shortcode handler for content visibility
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Shortcode_Handler_Content
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
     *   "hide"     => comma-separated list of role and user IDs to hide content
     *   "show"     => comma-separated list of role and user IDs to show content
     *   "limit"    => comma-separated list of role and user IDs to limit content
     *   "message"  => message to show if "limit" is defined
     *   "callback" => callback function that returns message if "limit" is defined
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
     * @access public
     * @version 6.0.0
     */
    public function run()
    {
        // Prepare list of subjects
        if (get_current_user_id()) {
            $roles = array_merge(AAM::getUser()->roles);

            if (AAM::api()->getConfig('core.settings.multiSubject', false)) {
                $parts = array_merge(array((string) AAM::getUser()->ID), $roles);
            } else {
                $parts = array((string) AAM::getUser()->ID, array_shift($roles));
            }
        } else {
            $parts = array('visitor');
        }

        $show  = $this->getAccess('show');
        $limit = $this->getAccess('limit');
        $hide  = $this->getAccess('hide');
        $msg   = $this->getMessage();

        if (!empty($this->args['callback'])) {
            $content = call_user_func($this->args['callback'], $this);
        } else {
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
     * @access protected
     * @version 6.0.0
     */
    protected function check($subject, $conditions)
    {
        $match = false;
        $auth  = get_current_user_id();

        foreach ($conditions as $condition) {
            if (($condition === 'authenticated') && $auth) {
                $match = true;
            } else if (preg_match('/^[\d*-]+\.[\d*-]+[\d\.*-]*[\d\.*-]*$/', $condition)) {
                $match = $this->checkIP(
                    $condition, AAM_Core_Request::server('REMOTE_ADDR')
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
     * @since 6.3.0 Fixed potential bug https://github.com/aamplugin/advanced-access-manager/issues/38
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
     * @access public
     * @version 6.0.0
     */
    public function getAccess($type)
    {
        $access = (isset($this->args[$type]) ? $this->args[$type] : null);

        return array_map('trim', explode(',', $access));
    }

    /**
     * Get replacement message
     *
     * @return string|null
     *
     * @access public
     * @version 6.0.0
     */
    public function getMessage()
    {
        return isset($this->args['message']) ? $this->args['message'] : null;
    }

}