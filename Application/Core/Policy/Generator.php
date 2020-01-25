<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core policy generator
 *
 * @since 6.3.0 Refactored post statement generation to cover the bug
 *              https://github.com/aamplugin/advanced-access-manager/issues/22
 * @since 6.2.2 Fixed bug with incompatibility with PHP lower than 7.0.0
 * @since 6.2.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.3.0
 */
class AAM_Core_Policy_Generator
{
    /**
     * Current subject
     *
     * Who should we use to get access settings from?
     *
     * @var AAM_Core_Subject
     *
     * @access protected
     * @version 6.2.0
     */
    protected $subject;

    /**
     * Access settings
     *
     * @var array
     *
     * @access protected
     * @version 6.2.0
     */
    protected $settings;

    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     *
     * @access public
     * @version 6.2.0
     */
    public function __construct(AAM_Core_Subject $subject)
    {
        $this->subject = $subject;

        // Read all direct access settings for provided subject
        $xpath  = $subject::UID;
        $xpath .= ($subject->getId() ? '.' . $subject->getId() : '');

        $this->settings = AAM_Core_AccessSettings::getInstance()->get($xpath);

        // Share post access settings conversion with add-ons and other third-party
        // solutions
        add_filter('aam_post_policy_generator_filter', function($list, $res, $opts) {
            return array_merge(
                $list, $this->_convertToPostStatements($res, $opts)
            );
        }, 10, 3);
    }

    /**
     * Generate Access Policy and return it as JSON string
     *
     * @return string
     *
     * @access public
     * @version 6.2.0
     */
    public function generate()
    {
        $generated = array(
            'Statement' => array(),
            'Param'     => array()
        );

        foreach($this->settings as $res_type => $data) {
            switch($res_type) {
                case AAM_Core_Object_Menu::OBJECT_TYPE:
                    $generated['Statement'] = array_merge(
                        $generated['Statement'],
                        $this->generateBackendMenuStatements($data)
                    );
                    break;

                case AAM_Core_Object_Toolbar::OBJECT_TYPE:
                    $generated['Statement'] = array_merge(
                        $generated['Statement'],
                        $this->generateToolbarStatements($data)
                    );
                    break;

                case AAM_Core_Object_Metabox::OBJECT_TYPE:
                    $generated['Statement'] = array_merge(
                        $generated['Statement'],
                        $this->generateMetaboxStatements($data)
                    );
                    break;

                case AAM_Core_Object_Post::OBJECT_TYPE:
                    $generated['Statement'] = array_merge(
                        $generated['Statement'],
                        $this->generatePostStatements($data)
                    );
                    break;

                case AAM_Core_Object_Uri::OBJECT_TYPE:
                    $generated['Statement'] = array_merge(
                        $generated['Statement'],
                        $this->generateUriStatements($data)
                    );
                    break;

                case AAM_Core_Object_Route::OBJECT_TYPE:
                    $generated['Statement'] = array_merge(
                        $generated['Statement'],
                        $this->generateRouteStatements($data)
                    );
                    break;

                default:
                    $generated = apply_filters(
                        'aam_generated_policy_filter',
                        $generated,
                        $res_type,
                        $data,
                        $this->subject
                    );
                    break;
            }
        }

        // If subject is User, then also include combined list of capabilities that
        // are assigned to him
        if (is_a($this->subject, 'AAM_Core_Subject_User')) {
            $allowed = $denied = array();

            foreach($this->subject->allcaps as $cap => $effect) {
                if (!empty($effect)) {
                    $allowed[] = 'Capability:' . $cap;
                } else {
                    $denied[] = 'Capability:' . $cap;
                }
            }

            if (!empty($allowed)) {
                $generated['Statement'][] = array(
                    'Effect'   => 'allow',
                    'Resource' => $allowed
                );
            }

            if (!empty($denied)) {
                $generated['Statement'][] = array(
                    'Effect'   => 'deny',
                    'Enforce'  => true,
                    'Resource' => $denied
                );
            }
        }

        $policy = json_decode(
            AAM_Backend_Feature_Main_Policy::getDefaultPolicy(), true
        );

        return wp_json_encode(array_merge($policy, $generated));
    }

    /**
     * Generate Backend Menu statements
     *
     * @param array $menus
     *
     * @return array
     *
     * @access protected
     * @version 6.2.0
     */
    protected function generateBackendMenuStatements($menus)
    {
        return $this->_generateBasicStatements($menus, 'BackendMenu');
    }

    /**
     * Generate Toolbar statements
     *
     * @param array $toolbar
     *
     * @return array
     *
     * @access protected
     * @version 6.2.0
     */
    protected function generateToolbarStatements($toolbar)
    {
        return $this->_generateBasicStatements($toolbar, 'Toolbar');
    }

    /**
     * Generate URI statements
     *
     * @param array $uris
     *
     * @return array
     *
     * @access protected
     * @version 6.2.0
     */
    protected function generateUriStatements($uris)
    {
        return $this->_generateBasicStatements($uris, 'URI');
    }

    /**
     * Generate API Route statements
     *
     * @param array $routes
     *
     * @return array
     *
     * @access protected
     * @version 6.2.0
     */
    protected function generateRouteStatements($routes)
    {
        $normalized = array();

        foreach($routes as $id => $effect) {
            $normalized[str_replace('|', ':', $id)] = $effect;
        }

        return $this->_generateBasicStatements($normalized, 'Route');
    }

    /**
     * Generate Metabox & Widget statements
     *
     * @param array $list
     *
     * @return array
     *
     * @access protected
     * @version 6.2.0
     */
    protected function generateMetaboxStatements($list)
    {
        $metaboxes = $widgets = array();

        foreach($list as $id => $effect) {
            $parts = explode('|', $id);

            if (in_array($parts[0], array('dashboard', 'widget'), true)) {
                $widgets[$id] = $effect;
            } else {
                $metaboxes[$id] = $effect;
            }
        }

        return array_merge(
            $this->_generateBasicStatements($widgets, 'Widget'),
            $this->_generateBasicStatements($metaboxes, 'Metabox')
        );
    }

    /**
     * Generate Post statements
     *
     * @param array $posts
     *
     * @return array
     *
     * @access protected
     * @version 6.2.0
     */
    protected function generatePostStatements($posts)
    {
        $statements = array();

        foreach($posts as $id => $options) {
            $parts    = explode('|', $id);
            $resource = "Post:{$parts[1]}:{$parts[0]}";

            $statements = array_merge(
                $statements, $this->_convertToPostStatements($resource, $options)
            );
        }

        return $statements;
    }

    /**
     * Convert post settings to policy format
     *
     * @param string $resource
     * @param array  $options
     *
     * @return array
     *
     * @since 6.3.0 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/22
     * @since 6.2.2 Fixed bug that caused fatal error for PHP lower than 7.0.0
     * @since 6.2.0 Initial implementation of the method
     *
     * @access private
     * @version 6.3.0
     */
    private function _convertToPostStatements($resource, $options)
    {
        $tree = (object) array(
            'allowed'    => array(),
            'denied'     => array(),
            'statements' => array()
        );

        foreach($options as $option => $settings) {
            // Compute Effect property
            if (is_bool($settings)) {
                $effect = ($settings === true ? 'denied' : 'allowed');
            } else {
                $effect = (!empty($settings['enabled']) ? 'denied' : 'allowed');
            }

            $action = null;

            switch($option) {
                case 'restricted':
                    $action = 'Read';
                    break;

                case 'comment':
                case 'edit':
                case 'delete':
                case 'publish':
                case 'create':
                    $action = ucfirst($option);
                    break;

                case 'hidden':
                    $item = array(
                        'Effect'  => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'List',
                        'Resource' => $resource
                    );

                    $conditions = array();

                    if (is_array($settings)) {
                        if (!empty($settings['frontend'])) {
                            $conditions['(*boolean)${CALLBACK.is_admin}'] = false;
                        }
                        if (!empty($settings['backend'])) {
                            $conditions['(*boolean)${CALLBACK.is_admin}'] = true;
                        }
                        if (!empty($settings['api'])) {
                            $conditions['(*boolean)${CONST.REST_REQUEST}'] = true;
                        }
                    }

                    if (!empty($conditions)) {
                        $item['Condition']['Equals'] = $conditions;
                    }

                    $tree->statements[] = $item;
                    break;

                case 'teaser':
                    $tree->statements[] = array(
                        'Effect'  => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'Read',
                        'Resource' => $resource,
                        'Metadata' => array(
                            'Teaser' => array(
                                'Value' => $settings['message']
                            )
                        )
                    );
                    break;

                case 'limited':
                    $tree->statements[] = array(
                        'Effect'   => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'Read',
                        'Resource' => $resource,
                        'Metadata' => array(
                            'Limited' => array(
                                'Threshold' => intval($settings['threshold'])
                            )
                        )
                    );
                    break;

                case 'redirected':
                    $metadata = array(
                        'Type' => $settings['type'],
                        'Code' => intval(isset($settings['httpCode']) ? $settings['httpCode'] : 307)
                    );

                    if ($settings['type'] === 'page') {
                        $metadata['Id'] = intval($settings['destination']);
                    } elseif ($settings['type']  === 'url') {
                        $metadata['URL'] = trim($settings['destination']);
                    } elseif ($settings['type'] === 'callback') {
                        $metadata['Callback'] = trim($settings['destination']);
                    }

                    $tree->statements[] = array(
                        'Effect'   => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'Read',
                        'Resource' => $resource,
                        'Metadata' => array(
                            'Redirect' => $metadata
                        )
                    );
                    break;

                case 'protected':
                    $tree->statements[] = array(
                        'Effect'   => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'Read',
                        'Resource' => $resource,
                        'Metadata' => array(
                            'Password' => array(
                                'Value' => $settings['password']
                            )
                        )
                    );
                    break;

                case 'ceased':
                    $tree->statements[] = array(
                        'Effect'   => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'Read',
                        'Resource' => $resource,
                        'Condition' => array(
                            'Greater' => array(
                                '(*int)${DATETIME.U}' => intval($settings['after'])
                            )
                        )
                    );
                    break;

                default:
                    do_action(
                        'aam_post_option_to_policy_action',
                        $resource,
                        $option,
                        $effect,
                        $settings,
                        $tree
                    );
                    break;
            }

            if ($action !== null) {
                if ($effect === 'allowed') {
                    $tree->allowed[] = $resource . ':' . $action;
                } else {
                    $tree->denied[] = $resource . ':' . $action;
                }
            }
        }

        // Finally prepare the consolidated statements
        if (!empty($tree->denied)) {
            $tree->statements[] = array(
                'Effect'   => 'deny',
                'Resource' => $tree->denied
            );
        }

        if (!empty($tree->allowed)) {
            $tree->statements[] = array(
                'Effect'   => 'allow',
                'Resource' => $tree->allowed
            );
        }

        return $tree->statements;
    }

    /**
     * Generate basic access policy statement
     *
     * @param array  $options
     * @param string $resource
     *
     * @return array
     *
     * @access private
     * @version 6.2.0
     */
    private function _generateBasicStatements($options, $resource)
    {
        $denied = $allowed =  $statements = array();

        foreach($options as $id => $effect) {
            if ($effect === true) {
                $denied[] = "{$resource}:{$id}";
            } else {
                $allowed[] = "{$resource}:{$id}";
            }
        }

        if (!empty($denied)) {
            $statements[] = array(
                'Effect'   => 'deny',
                'Resource' => $denied
            );
        }

        if (!empty($allowed)) {
            $statements[] = array(
                'Effect'   => 'allow',
                'Resource' => $allowed
            );
        }

        return $statements;
    }

}