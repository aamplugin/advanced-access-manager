/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

if (typeof jQuery !== 'undefined') {
    (function ($) {
        $('document').ready(function () {
            $('.login-input').bind('keyup', function(event) {
                if (event.which === 13) {
                    $('.aam-login-submit').trigger('click');
                }
            });

            $('.aam-login-submit').each(function () {
                $(this).bind('click', function () {
                    var button = $(this);
                    var prefix = $(this).data('prefix');
                    
                    var log = $.trim($('#' + prefix + 'log').val());
                    var pwd = $('#' + prefix + 'pwd').val();
                    
                    if (log && pwd) {
                        $('#' + prefix + 'error').css('display', 'none');
                        
                        $.ajax(aamLocal.ajaxurl, {
                            data: {
                                log: log,
                                pwd: pwd,
                                action: 'aamlogin',
                                redirect:  $('#' + prefix + 'redirect').val(),
                                rememberme:  ($('#' + prefix + 'rememberme').prop('checked') ? 1 : 0)
                            },
                            dataType: 'json',
                            type: 'POST',
                            beforeSend: function() {
                                button.attr({
                                    disabled: 'disabled',
                                    'data-original-label': button.val()
                                }).val('Wait...');
                            },
                            success: function(response) {
                                if (response.status === "success") {
                                    if (response.redirect) {
                                        location.href = response.redirect;
                                    }
                                } else {
                                    $('#' + prefix + 'error').html(
                                        response.reason
                                    ).css('display', 'block');
                                }
                            },
                            error: function() {
                                $('#' + prefix + 'error').html(
                                    '<strong>ERROR:</strong> Unexpected error.'
                                ).css('display', 'block');
                            },
                            complete: function() {
                                button.attr({
                                    disabled: null
                                }).val(button.attr('data-original-label'));
                            }
                        });
                    } else {
                        $('#' + prefix + 'error').html(
                            '<strong>ERROR:</strong> Username and password are required.'
                        ).css('display', 'block');
                    }
                });
            });
        });
    })(jQuery);
} else {
    console.log(
            'AAM requires jQuery library in order for login widget to work'
    );
}