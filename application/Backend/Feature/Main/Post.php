<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend posts & terms service UI
 *
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/384
 * @since 6.9.29 https://github.com/aamplugin/advanced-access-manager/issues/375
 * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/363
 * @since 6.7.9  https://github.com/aamplugin/advanced-access-manager/issues/192
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/89
 *               https://github.com/aamplugin/advanced-access-manager/issues/108
 * @since 6.3.1  Fixed bug with incorrectly escaped passwords and teaser messages
 * @since 6.3.0  Fixed bug with PHP noticed that was triggered if password was not
 *               defined
 * @since 6.2.0  Added more granular control over the HIDDEN access option
 * @since 6.0.3  Allowed to manage access to ALL registered post types
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.31
 */
class AAM_Backend_Feature_Main_Post extends AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_ServiceTrait,
        AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_content';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Post::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/post.php';

    /**
     * Constructor
     *
     * @return void
     *
     * @access public
     * @since 6.2.0
     */
    public function __construct()
    {
        add_filter('aam_sanitize_post_value_filter', function($value, $option) {
            if ($option === 'hidden') {
                $value['frontend'] = isset($value['frontend']) && filter_var(
                    $value['frontend'], FILTER_VALIDATE_BOOLEAN
                );

                $value['backend'] = isset($value['backend']) && filter_var(
                    $value['backend'], FILTER_VALIDATE_BOOLEAN
                );

                $value['api'] = isset($value['api']) && filter_var(
                    $value['api'], FILTER_VALIDATE_BOOLEAN
                );
            }

            return $value;
        }, 10, 2);
    }

    /**
     * Get access form with pre-populated data
     *
     * @param mixed  $id
     * @param string $type
     *
     * @return string
     *
     * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/89
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.5.0
     */
    public function getAccessForm($id, $type)
    {
        $object = $this->getSubject()->getObject($type, $id);
        $view   = AAM_Backend_View::getInstance();
        $args   = array(
            'object'    => $object,
            'type'      => $type,
            'id'        => $id,
            'subject'   => $this->getSubject(),
            'httpCodes' => $this->getRedirectHttpCodes(),
            'previews'  => $this->preparePreviewValues(
                apply_filters(
                    'aam_post_preview_options_filter',
                    ($object ? $object->getOption() : array()),
                    $object
                )
            )
        );

        // Prepare HTML response
        switch ($type) {
            case 'term':
                $chunks = explode('|', $id);
                $args['term']     = get_term($chunks[0], $chunks[1]);
                $args['postType'] = (isset($chunks[2]) ? $chunks[2] : null);

                $response = apply_filters(
                    'aam_term_access_form_filter',
                    $view->loadPartial('term-access-form', $args),
                    (object) $args
                );
                break;

            case 'taxonomy':
                $args['taxonomy'] = get_taxonomy($id);

                $response = apply_filters(
                    'aam_taxonomy_access_form_filter',
                    $view->loadPartial('taxonomy-access-form', $args),
                    (object) $args
                );
                break;

            case 'type':
                $args['postType'] = get_post_type_object($id);

                $response = apply_filters(
                    'aam_type_access_form_filter',
                    $view->loadPartial('type-access-form', $args),
                    (object) $args
                );
                break;

            case 'post':
                $args['postType'] = get_post_type_object($object->post_type);
                $args['options']  = $this->getAccessOptionList($object->post_type);

                $response = $view->loadPartial('post-access-form', $args);
                break;

            default:
                $response = null;
                break;
        }

        return $response;
    }

    /**
     * Decorate additional view elements for access settings
     *
     * This method is necessary to prepare some preview information for access
     * options like LIMIT or REDIRECT.
     *
     * @param array $options
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function preparePreviewValues($options)
    {
        $previews = array();

        foreach ($options as $option => $value) {
            $previews[$option] = $this->getPreviewValue($option, $value);
        }

        return $previews;
    }

    /**
     * Get post object access options
     *
     * @param string $post_type
     *
     * @return array
     *
     * @since 6.5.0 Added new param $post_type to filter out options by post type
     * @since 6.2.0 Added additional argument "current subject" to the
     *              `aam_post_access_options_filter` filter
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.5.0
     */
    protected function getAccessOptionList($post_type)
    {
        $response    = array();
        $excluded    = array($post_type, $this->getSubject()->getSubjectType());
        $option_list = apply_filters(
            'aam_post_access_options_filter',
            AAM_Backend_View_PostOptionList::get(),
            $this->getSubject()
        );

        foreach($option_list as $key => $data) {
            if (empty($data['exclude'])
                || (count(array_intersect($data['exclude'], $excluded)) === 0)
            ) {
                $response[$key] = $data;
            }
        }

        return $response;
    }

    /**
     * Get list of HTTP redirect types
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getRedirectHttpCodes()
    {
        return apply_filters('aam_content_redirect_http_codes', array(
            '307' => __('307 - Temporary Redirect (Default)', AAM_KEY),
            '301' => __('301 - Moved Permanently', AAM_KEY),
            '303' => __('303 - See Other', AAM_KEY)
        ));
    }

    /**
     * Prepare readable preview value
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return string
     *
     * @since 6.3.0 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/31
     * @since 6.2.0 Added HIDDEN preview value
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.3.0
     */
    protected function getPreviewValue($option, $value)
    {
        switch ($option) {
            case 'hidden':
                $preview = $this->prepareHiddenPreview($value);
                break;

            case 'teaser':
                $preview = $this->prepareTeaserPreview($value);
                break;

            case 'limited':
                $preview = $this->prepareLimitedPreview($value);
                break;

            case 'redirected':
                $preview = $this->prepareRedirectPreview($value);
                break;

            case 'protected':
                $preview = (!empty($value['password']) ? $value['password'] : '');
                break;

            case 'ceased':
                $preview = $this->prepareCeasePreview($value);
                break;

            default:
                $preview = apply_filters(
                    'aam_post_option_preview_filter',
                    '',
                    $value,
                    $option
                );
                break;
        }

        return $preview;
    }

    /**
     * Prepare preview value for the HIDDEN option
     *
     * @param array|boolean $hidden
     *
     * @return string
     *
     * @access protected
     * @version 6.2.0
     */
    protected function prepareHiddenPreview($hidden)
    {
        $preview = null;

        if (is_array($hidden)) {
            if ($hidden['enabled'] === true) {
                $areas = array();
                if (!empty($hidden['frontend'])) {
                    $areas[] = __('Frontend', AAM_KEY);
                }
                if (!empty($hidden['backend'])) {
                    $areas[] = __('Backend', AAM_KEY);
                }
                if (!empty($hidden['api'])) {
                    $areas[] = __('RESTful API', AAM_KEY);
                }

                $preview = implode(', ', $areas);
            }
        } elseif (!empty($hidden)) {
            $preview = __('All Areas', AAM_KEY);
        }

        return $preview;
    }

    /**
     * Prepare teaser message preview
     *
     * @param array $teaser
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareTeaserPreview($teaser)
    {
        $preview = null;

        if (!empty($teaser['message'])) {
            // Remove all HTML tags first
            $str = wp_strip_all_tags($teaser['message']);

            // Take in consideration UTF-8 encoding
            if (function_exists('mb_strlen')) {
                $preview = (mb_strlen($str) > 25 ? mb_substr($str, 0, 22) . '...' : $str);
            } else {
                $preview = (strlen($str) > 25 ? substr($str, 0, 22) . '...' : $str);
            }
        }

        return $preview;
    }

    /**
     * Prepare limited option preview
     *
     * @param array $limited
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareLimitedPreview($limited)
    {
        $preview = null;

        if (!empty($limited['threshold'])) {
            $preview = sprintf(__('%d times', AAM_KEY), $limited['threshold']);
        }

        return $preview;
    }

    /**
     * Prepare redirect option preview
     *
     * @param array $redirect
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareRedirectPreview($redirect)
    {
        switch (isset($redirect['type']) ? $redirect['type'] : null) {
            case 'page':
                $page    = get_post($redirect['destination']);
                $preview = sprintf(
                    __('"%s" page', AAM_KEY),
                    (is_a($page, 'WP_Post') ? $page->post_title : '')
                );
                break;

            case 'url':
                $preview = sprintf(__('%s URL', AAM_KEY), $redirect['destination']);
                break;

            case 'login':
                $preview = __('Login page', AAM_KEY);
                break;

            case 'callback':
                $preview = $redirect['destination'];
                break;

            default:
                $preview = null;
                break;
        }

        return $preview;
    }

    /**
     * Prepare ceased option preview
     *
     * @param array $cease
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareCeasePreview($cease)
    {
        return (!empty($cease['after']) ? date('m/d/Y H:i O', $cease['after']) : null);
    }

    /**
     * Save Posts & Terms access properties
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $type  = $this->getFromPost('object');
        $id    = $this->getFromPost('objectId');
        $param = $this->getFromPost('param');
        $value = $this->sanitizeOption($param, AAM_Core_Request::post('value'));

        $object = $this->getSubject()->getObject($type, $id, true);
        $result = $object->updateOptionItem($param, $value)->save();

        return wp_json_encode(array(
            'status'  => ($result ? 'success' : 'failure')
        ));
    }

    /**
     * Reset view counter
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function resetCounter()
    {
        $type  = $this->getFromPost('object');
        $id    = $this->getFromPost('objectId');

        if ($type === 'post') {
            $result = delete_user_option(
                $this->getSubject()->getId(),
                sprintf(AAM_Service_Content::POST_COUNTER_DB_OPTION, $id)
            );
        } else {
            $result = apply_filters(
                'aam_ajax_filter', false, $this->getSubject(), 'Main_Post.resetCounter'
            );
        }

        return wp_json_encode(array(
            'status'  => ($result ? 'success' : 'failure')
        ));
    }

    /**
     * Sanitize and normalize the access settings
     *
     * Depending on the type of access, normalize and sanitize the incoming data
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return mixed
     *
     * @since 6.7.9 https://github.com/aamplugin/advanced-access-manager/issues/192
     * @since 6.3.1 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/42
     * @since 6.2.0 Added support for the new filter `aam_sanitize_post_value_filter`
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.7.9
     */
    protected function sanitizeOption($option, $value)
    {
        if (is_array($value)) {
            foreach($value as $k => $v) {
                if ($k === 'enabled') {
                    $value[$k] = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                } elseif (is_numeric($v))  {
                    $value[$k] = intval($v);
                } elseif (current_user_can('unfiltered_html')) {
                    $value[$k] = stripslashes($v);
                } else {
                    $value[$k] = wp_kses_post(stripslashes($v));
                }
            }
        } else { // Any scalar value has to be boolean
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return apply_filters('aam_sanitize_post_value_filter', $value, $option);
    }

    /**
     * Check if post can be managed for current subject
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function isAllowedToManageCurrentSubject()
    {
        return apply_filters(
            'aam_posts_terms_manage_subject_filter',
            !$this->getSubject()->isDefault(),
            $this->getSubject()->getSubject()
        );
    }

    /**
     * Register Posts & Pages service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'post',
            'position'   => 20,
            'title'      => __('Posts & Terms', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }

}