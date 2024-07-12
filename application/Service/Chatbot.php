<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM chatbot
 *
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/402
 * @since 6.9.27 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
 */
class AAM_Service_Chatbot
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.9.27
     */
    const FEATURE_FLAG = 'core.service.chatbot.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/402
     * @since 6.9.27 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.35
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = false;
            }

            return $result;
        }, 10, 2);

        if (is_admin()) {
            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Aarmie Chatbot', AAM_KEY),
                    'description' => __('Helpful AI assistant that is pre-trained on large amount of information we accumulated over the past 10 years.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 40);
        }

        if (AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize the service hooks
     *
     * @return void
     *
     * @access protected
     * @version 6.9.27
     */
    protected function initializeHooks()
    {
        if (AAM::isAAM()) {
            add_action('init', function() {
                wp_enqueue_script(
                    'ai-chatbot',
                    AAM_MEDIA . '/js/chatbot.js?v=' . AAM_VERSION,
                    [],
                    '1.0.0',
                    [
                        'strategy'  => 'async',
                        'in_footer' => true
                    ]
                );

                wp_add_inline_script(
                    'ai-chatbot', $this->_getChatbotConfig(), 'before'
                );
            });
        }

        AAM_Restful_ChatbotService::bootstrap();
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    private function _getChatbotConfig()
    {
        $script     = file_get_contents(AAM_BASEDIR . '/media/js/chatbot.config.js');
        $launch_img = 'data:image/svg+xml;base64,' . base64_encode(
            file_get_contents(AAM_BASEDIR . '/media/chatbot-launcher.svg')
        );
        $aarmie_img = 'data:image/svg+xml;base64,' . base64_encode(
            file_get_contents(AAM_BASEDIR . '/media/aarmie.svg')
        );

        if (!defined('AAM_COMPLETE_PACKAGE_LICENSE')) {
            $free_note = '<br/><br/>' . sprintf(__(
                'Our free tier limits conversations to around 1000 words and 3 per day. Keep questions brief. Consider our %spremium add-on%s, risk-free with a 60-day full refund guarantee.',
                AAM_KEY
            ), '<a href="https://aamportal.com/premium" target="_blank">', '</a>');
        } else {
            $free_note = '';
        }

        return str_replace(
            array('%greeting', '%launcher', '%aarmie', '%rest_nonce', '%rest_base'),
            array(
                sprintf(
                    esc_attr(__("Howdy, %s. I'm Aarmie, your virtual assistant. Trained on ~1500 Q&As and counting. Though I may not know everything and make mistakes, feel free to ask. If you're unsatisfied, leave your email for follow-up. %s", AAM_KEY)),
                    wp_get_current_user()->first_name,
                    $free_note
                ),
                $launch_img,
                $aarmie_img,
                wp_create_nonce('wp_rest'),
                esc_url_raw(rest_url())
            ),
            $script
        );
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Chatbot::bootstrap();
}