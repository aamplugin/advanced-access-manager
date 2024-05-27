window['TLDR_Chatbot_Config'] = {
    app: {
        captureFeedback: false
    },
    i18n: {
        en: {
            name: 'Aarmie',
            conversation: {
                greeting: '%greeting'
            }
        }
    },
    theme: {
        skin: {
            launcher: {
                bgColor: '#704abf',
                icons: {
                    openImg: '%launcher'
                }
            },
            processing: {
                bgColor: '#704abf'
            },
            header: {
                bgColor: '#704abf',
                txtColor: '#DFDCFF',
                icons: {
                    logoImg: '%aarmie'
                }
            },
            input: {
                bgColor: '#292533',
                txtColor: '#FFFFFF'
            }
        }
    },
    api: {
        post: (data, cb) => {
            jQuery.ajax(`%rest_baseaam/v2/service/chatbot`, {
                type: 'POST',
                headers: {
                    'X-WP-Nonce': '%rest_nonce'
                },
                data,
                dataType: 'json',
                success: function (response) {
                    cb(response);
                },
                error: function (response) {
                    let answer = 'Sorry, something went wrong';

                    if (response.responseJSON && response.responseJSON.error) {
                        answer = response.responseJSON.error;
                    }

                    cb({ answer });
                }
            });
        }
    }
}