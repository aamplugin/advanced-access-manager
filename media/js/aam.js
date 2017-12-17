/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */


/**
 * 
 * @param {type} $
 * @returns {undefined}
 */
(function ($) {
    
    /**
     * 
     * @returns {undefined}
     */
    function UI() {
        
        /**
         * Role List Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            /**
             * 
             * @param {type} id
             * @returns {Boolean}
             */
            function isCurrent(id) {
                var subject = aam.getSubject();

                return (subject.type === 'role' && subject.id === id);
            }

            /**
             * 
             * @returns {undefined}
             */
            function fetchRoleList(exclude) {
                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_Role.getList',
                        _ajax_nonce: aamLocal.nonce,
                        exclude: exclude
                    },
                    beforeSend: function () {
                        $('.inherit-role-list').html(
                            '<option value="">' + aam.__('Loading...') + '</option>'
                        );
                    },
                    success: function (response) {
                        $('.inherit-role-list').html(
                            '<option value="">' + aam.__('No Role') + '</option>'
                        );
                        for (var i in response) {
                            $('.inherit-role-list').append(
                                '<option value="' + i + '">' + response[i].name + '</option>'
                            );
                        }
                        if ($.aamEditRole) {
                            $('#inherit-role').val($.aamEditRole[0]);
                        }
                        aam.triggerHook('post-get-role-list', {
                           list : response
                       });
                       //TODO - Rerwite JavaScript to support $.aam 
                       $.aamEditRole = null;
                    }
                });
            }
            
            /**
             * 
             * @param {type} container
             * @returns {undefined}
             */
            function resetForm(container) {
                $('input,select', container).each(function() {
                    if ($(this).attr('type') === 'checkbox') {
                        $(this).prop('checked', false);
                    } else {
                        $(this).val('');
                    }
                });
            }

            //initialize the role list table
            $('#role-list').DataTable({
                autoWidth: false,
                ordering: false,
                dom: 'ftrip',
                pagingType: 'simple',
                processing: true,
                serverSide: false,
                ajax: {
                    url: aamLocal.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_Role.getTable',
                        _ajax_nonce: aamLocal.nonce
                    }
                },
                columnDefs: [
                    {visible: false, targets: [0, 1, 4, 5]}
                ],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: aam.__('Search Role'),
                    info: aam.__('_TOTAL_ role(s)'),
                    infoFiltered: ''
                },
                initComplete: function () {
                    if (!aam.isUI() && aamLocal.caps.create_roles) {
                        var create = $('<a/>', {
                            'href': '#',
                            'class': 'btn btn-primary'
                        }).html('<i class="icon-plus"></i> ' + aam.__('Create'))
                        .bind('click', function () {
                            resetForm('#add-role-modal .modal-body');
                            $('#add-role-modal').modal('show');
                        });

                        $('.dataTables_filter', '#role-list_wrapper').append(create);
                    }
                },
                createdRow: function (row, data) {
                    if (isCurrent(data[0])) {
                        $('td:eq(0)', row).html('<strong class="aam-highlight">' + data[2] + '</strong>');
                    } else {
                        $('td:eq(0)', row).html('<span>' + data[2] + '</span>');
                    }

                    $(row).attr('data-id', data[0]);

                    //add subtitle
                    var expire = (data[5] ? '; <i class="icon-clock"></i>' : '');
                    $('td:eq(0)', row).append(
                        $('<i/>', {'class': 'aam-row-subtitle'}).html(
                            aam.applyFilters(
                                'role-subtitle', 
                                aam.__('Users') + ': <b>' + parseInt(data[1]) + '</b>; ID: <b>' + data[0] + '</b>' + expire,
                                data
                            )
                        )
                    );

                    var actions = data[3].split(',');

                    var container = $('<div/>', {'class': 'aam-row-actions'});
                    $.each(actions, function (i, action) {
                        switch (action) {
                            case 'manage':
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action icon-cog ' + (isCurrent(data[0]) ? 'text-muted': 'text-info')
                                }).bind('click', function () {
                                    if (!$(this).prop('disabled')) {
                                        $(this).prop('disabled', true);
                                        var title = $('td:eq(0) span', row).html();
                                        aam.setSubject('role', data[0], title, data[4]);
                                        $('td:eq(0) span', row).replaceWith(
                                            '<strong class="aam-highlight">' + title + '</strong>'
                                        );
                                        $('i.icon-cog', container).attr(
                                            'class', 'aam-row-action icon-cog text-muted'
                                        );
                                        if (!aam.isUI()) {
                                            $('i.icon-cog', container).attr(
                                                'class', 'aam-row-action icon-spin4 animate-spin'
                                            );
                                            aam.fetchContent('main');
                                            $('i.icon-spin4', container).attr(
                                                'class', 'aam-row-action icon-cog text-muted'
                                            );
                                        } else {
                                            $.aam.loadAccessForm($('#load-post-object-type').val(), $('#load-post-object').val(), $(this));
                                        }
                                    }
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': aam.__('Manage Role')
                                }).prop('disabled', (isCurrent(data[0]) ? true: false)));
                                break;

                            case 'edit':
                                if (!aam.isUI()) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-pencil text-warning'
                                    }).bind('click', function () {
                                        resetForm('#edit-role-modal .modal-body');
                                        $('#edit-role-btn').data('role', data[0]);
                                        $('#edit-role-name').val(data[2]);
                                        $('#edit-role-expiration').val(data[5]);
                                        $('#edit-role-modal').modal('show');
                                        fetchRoleList(data[0]);
                                        
                                        //TODO - Rerwite JavaScript to support $.aam 
                                        $.aamEditRole = data;
                                        
                                        aam.triggerHook('edit-role-modal', data);
                                    }).attr({
                                        'data-toggle': "tooltip",
                                        'title': aam.__('Edit Role')
                                    }));
                                }
                                break;

                            case 'clone':
                                if (!aam.isUI()) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-clone text-success'
                                    }).bind('click', function () {
                                        //TODO - Rerwite JavaScript to support $.aam 
                                        $.aamEditRole = data;
                                        $('#clone-role').prop('checked', true);
                                        $('#add-role-modal').modal('show');
                                    }).attr({
                                        'data-toggle': "tooltip",
                                        'title': aam.__('Clone Role')
                                    }));
                                }
                                break;

                            case 'delete':
                                if (!aam.isUI()) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-trash-empty text-danger'
                                    }).bind('click', {role: data}, function (event) {
                                        $('#delete-role-btn').data('role', data[0]);
                                        var message = $('#delete-role-modal .aam-confirm-message').data('message');
                                        $('#delete-role-modal .aam-confirm-message').html(
                                            message.replace(
                                                '%s', '<strong>' + event.data.role[2] + '</strong>'
                                            )
                                        );

                                        $('#delete-role-modal').modal('show');
                                    }).attr({
                                        'data-toggle': "tooltip",
                                        'title': aam.__('Delete Role')
                                    }));
                                }
                                break;

                            default:
                                if (!aam.isUI()) {
                                    aam.triggerHook('role-action', {
                                        container: container,
                                        action   : action,
                                        data     : data
                                    });
                                }
                                break;
                        }
                    });
                    $('td:eq(1)', row).html(container);

                    aam.triggerHook('decorate-role-row', {
                        row : row,
                        data: data
                    });
                }
            });

            $('#role-list').on( 'draw.dt', function () {
                $('tr', '#role-list tbody').each(function() {
                    if (!isCurrent($(this).data('id'))) {
                        $('td:eq(0) strong', this).replaceWith(
                            '<span>' + $('td:eq(0) strong', this).text() + '</span>'
                        );
                        $('.text-muted', this).attr('disabled', false);
                        $('.text-muted', this).toggleClass('text-muted text-info');
                    }
                });
            } );

            $('#add-role-modal').on('shown.bs.modal', function (e) {
                fetchRoleList();
                //clear add role form first
                $('input[name="name"]', '#add-role-modal').val('').focus();
            });

            $('#edit-role-modal').on('shown.bs.modal', function (e) {
                $('input[name="name"]', '#edit-role-modal').focus();
            });

            //add role button
            $('#add-role-btn').bind('click', function () {
                var _this = this;

                $('input[name="name"]', '#add-role-modal').parent().removeClass('has-error');

                var data = {
                    action: 'aam',
                    sub_action: 'Subject_Role.add',
                    _ajax_nonce: aamLocal.nonce
                };

                $('input,select', '#add-role-modal .modal-body').each(function() {
                    if ($(this).attr('name')) {
                        if ($(this).attr('type') === 'checkbox') {
                            data[$(this).attr('name')] = $(this).prop('checked') ? 1 : 0;
                        } else {
                            data[$(this).attr('name')] = $.trim($(this).val());
                        }
                    }
                });

                if (data.name) {
                    $.ajax(aamLocal.ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: data,
                        beforeSend: function () {
                            $(_this).text(aam.__('Saving...')).attr('disabled', true);
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                $('#role-list').DataTable().ajax.reload();
                                aam.setSubject(
                                    'role', 
                                    response.role.id, 
                                    response.role.name, 
                                    response.role.level
                                );
                                aam.fetchContent('main');
                                $('#add-role-modal').modal('hide');
                            } else {
                                aam.notification(
                                        'danger', aam.__('Failed to add new role')
                                );
                            }
                        },
                        error: function () {
                            aam.notification('danger', aam.__('Application error'));
                        },
                        complete: function () {
                            $(_this).text(aam.__('Add Role')).attr('disabled', false);
                        }
                    });
                } else {
                    $('input[name="name"]', '#add-role-modal').focus().parent().addClass('has-error');
                }
            });

            //edit role button
            $('#edit-role-btn').bind('click', function () {
                var _this = this;

                $('#edit-role-name').parent().removeClass('has-error');

                var data = {
                    action: 'aam',
                    sub_action: 'Subject_Role.edit',
                    _ajax_nonce: aamLocal.nonce,
                    subject: 'role',
                    subjectId: $(_this).data('role')
                };

                $('input,select', '#edit-role-modal .modal-body').each(function() {
                    if ($(this).attr('name')) {
                        if ($(this).attr('type') === 'checkbox') {
                            data[$(this).attr('name')] = $(this).prop('checked') ? 1 : 0;
                        } else {
                            data[$(this).attr('name')] = $.trim($(this).val());
                        }
                    }
                });

                if (data.name) {
                    $.ajax(aamLocal.ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: data,
                        beforeSend: function () {
                            $(_this).text(aam.__('Saving...')).attr('disabled', true);
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                $('#role-list').DataTable().ajax.reload();
                            } else {
                                aam.notification(
                                    'danger', aam.__('Failed to update role')
                                );
                            }
                        },
                        error: function () {
                            aam.notification('danger', aam.__('Application error'));
                        },
                        complete: function () {
                            $('#edit-role-modal').modal('hide');
                            $(_this).text(aam.__('Update')).attr('disabled', false);
                        }
                    });
                } else {
                    $('#edit-role-name').focus().parent().addClass('has-error');
                }
            });

            //edit role button
            $('#delete-role-btn').bind('click', function () {
                var _this = this;

                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_Role.delete',
                        _ajax_nonce: aamLocal.nonce,
                        subject: 'role',
                        subjectId: $(_this).data('role')
                    },
                    beforeSend: function () {
                        $(_this).text(aam.__('Deleting...')).attr('disabled', true);
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#role-list').DataTable().ajax.reload();
                        } else {
                            aam.notification('danger', aam.__('Failed to delete role'));
                        }
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application error'));
                    },
                    complete: function () {
                        $('#delete-role-modal').modal('hide');
                        $(_this).text(aam.__('Delete Role')).attr('disabled', false);
                    }
                });
            });

            //add setSubject hook
            aam.addHook('setSubject', function () {
                //clear highlight
                $('tbody tr', '#role-list').each(function () {
                    if ($('strong', $(this)).length) {
                        var highlight = $('strong', $(this));
                        $('.icon-cog', $(this)).toggleClass('text-muted text-info');
                        $('.icon-cog', $(this)).prop('disabled', false);
                        highlight.replaceWith($('<span/>').text(highlight.text()));
                    }
                });
                //show post & pages access control groups that belong to backend
                $('.aam-backend-post-access').show();
            });

            //in case interface needed to be reloaded
            aam.addHook('refresh', function () {
                $('#role-list').DataTable().ajax.url(aamLocal.ajaxurl).load();
            });

        })(jQuery);


        /**
         * User List Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            /**
             * 
             * @param {type} id
             * @returns {Boolean}
             */
            function isCurrent(id) {
                var subject = aam.getSubject();

                return (subject.type === 'user' && parseInt(subject.id) === id);
            }

            /**
             * 
             * @param {type} id
             * @param {type} btn
             * @returns {undefined}
             */
            function blockUser(id, btn) {
                var state = ($(btn).hasClass('icon-lock') ? 0 : 1);

                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_User.block',
                        _ajax_nonce: aamLocal.nonce,
                        subject: 'user',
                        subjectId: id
                    },
                    beforeSend: function () {
                        $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            if (state === 1) {
                                $(btn).attr({
                                    'class': 'aam-row-action icon-lock text-danger',
                                    'title': aam.__('Unlock User'),
                                    'data-original-title': aam.__('Unlock User')
                                });
                            } else {
                                $(btn).attr({
                                    'class': 'aam-row-action icon-lock-open-alt text-warning',
                                    'title': aam.__('Lock User'),
                                    'data-original-title': aam.__('Lock User')
                                });
                            }
                        } else {
                            aam.notification('danger', aam.__('Failed to block user'));
                        }
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application error'));
                    }
                });
            }

            /**
             * 
             * @param {type} id
             * @param {type} btn
             * @returns {undefined}
             */
            function switchToUser(id, btn) {
                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'switchToUser',
                        _ajax_nonce: aamLocal.nonce,
                        user: id
                    },
                    beforeSend: function () {
                        $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            location.href = response.redirect;
                        } else {
                            aam.notification('danger', response.reason);
                        }
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application error'));
                    },
                    complete: function () {
                        $(btn).attr('class', 'aam-row-action icon-exchange text-success');
                    }
                });
            }

            //initialize the user list table
            $('#user-list').DataTable({
                autoWidth: false,
                ordering: false,
                dom: 'ftrip',
                pagingType: 'simple',
                serverSide: true,
                processing: true,
                ajax: {
                    url: aamLocal.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_User.getTable',
                        _ajax_nonce: aamLocal.nonce
                    }
                },
                columnDefs: [
                    {visible: false, targets: [0, 1, 4]}
                ],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: aam.__('Search User'),
                    info: aam.__('_TOTAL_ user(s)'),
                    infoFiltered: ''
                },
                initComplete: function () {
                    if (!aam.isUI() && aamLocal.caps.create_users) {
                        var create = $('<a/>', {
                            'href': '#',
                            'class': 'btn btn-primary'
                        }).html('<i class="icon-plus"></i> ' + aam.__('Create')).bind('click', function () {
                            window.open(aamLocal.url.addUser, '_blank');
                        });

                        $('.dataTables_filter', '#user-list_wrapper').append(create);
                    }
                },
                createdRow: function (row, data) {
                    if (isCurrent(data[0])) {
                        $('td:eq(0)', row).html('<strong class="aam-highlight">' + data[2] + '</strong>');
                    } else {
                        $('td:eq(0)', row).html('<span>' + data[2] + '</span>');
                    }

                    //add subtitle
                    $('td:eq(0)', row).append(
                        $('<i/>', {'class': 'aam-row-subtitle'}).html(
                            aam.__('Role') + ': ' + data[1] + '; ID: <b>' + data[0] + '</b>'
                        )
                    );

                    var actions = data[3].split(',');
                    var container = $('<div/>', {'class': 'aam-row-actions'});

                    if ($.trim(data[3])) { 
                        $.each(actions, function (i, action) {
                        switch (action) {
                            case 'manage':
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action icon-cog ' + (isCurrent(data[0]) ? 'text-muted': 'text-info')
                                }).bind('click', function () {
                                    if (!$(this).prop('disabled')) {
                                        $(this).prop('disabled', true);
                                        aam.setSubject('user', data[0], data[2], data[4]);
                                        $('td:eq(0) span', row).replaceWith(
                                            '<strong class="aam-highlight">' + data[2] + '</strong>'
                                        );
                                        $('i.icon-cog', container).attr('class', 'aam-row-action icon-cog text-muted');

                                        if (!aam.isUI()) {
                                            $('i.icon-cog', container).attr('class', 'aam-row-action icon-spin4 animate-spin');
                                            aam.fetchContent('main');
                                            $('i.icon-spin4', container).attr('class', 'aam-row-action icon-cog text-muted');
                                        } else {
                                            $.aam.loadAccessForm($('#load-post-object-type').val(), $('#load-post-object').val(), $(this));
                                        }
                                    }
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': aam.__('Manage User')
                                })).prop('disabled', (isCurrent(data[0]) ? true: false));
                                break;

                            case 'edit':
                                if (!aam.isUI()) {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action icon-pencil text-info'
                                }).bind('click', function () {
                                    window.open(
                                            aamLocal.url.editUser + '?user_id=' + data[0], '_blank'
                                            );
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': aam.__('Edit User')
                                }));
                            }
                                break;

                            case 'lock':
                                if (!aam.isUI()) {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action icon-lock-open-alt text-warning'
                                }).bind('click', function () {
                                    blockUser(data[0], $(this));
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': aam.__('Lock User')
                                }));
                            }
                                break;

                            case 'unlock':
                                if (!aam.isUI()) {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action icon-lock text-danger'
                                }).bind('click', function () {
                                    blockUser(data[0], $(this));
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': aam.__('Unlock User')
                                }));
                            }
                                break;

                            case 'switch':
                                if (!aam.isUI()) {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action icon-exchange text-success'
                                }).bind('click', function () {
                                    switchToUser(data[0], $(this));
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': aam.__('Switch To User')
                                }));
                            }
                                break;

                            default:
                                break;
                        }
                    });
                    } else {
                        $(container).append($('<i/>', {
                            'class': 'aam-row-action text-muted'
                        }).text('---'));
                    }
                    $('td:eq(1)', row).html(container);
                }
            });

            //add setSubject hook
            aam.addHook('setSubject', function () {
                //clear highlight
                $('tbody tr', '#user-list').each(function () {
                    if ($('strong', $(this)).length) {
                        var highlight = $('strong', $(this));
                        $('.icon-cog', $(this)).toggleClass('text-muted text-info');
                        $('.icon-cog', $(this)).prop('disabled', false);
                        highlight.replaceWith('<span>' + highlight.text() + '</span>');
                    }
                });
                //show post & pages access control groups that belong to backend
                $('.aam-backend-post-access').show();
            });

            //in case interface needed to be reloaded
            aam.addHook('refresh', function () {
                $('#user-list').DataTable().ajax.url(aamLocal.ajaxurl).load();
            });

        })(jQuery);


        /**
         * Visitor Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            $('document').ready(function() {
                 $('#manage-visitor').bind('click', function () {
                    var _this = this;

                    aam.setSubject('visitor', null, aam.__('Anonymous'), 0);
                    $('i.icon-cog', $(this)).attr('class', 'icon-spin4 animate-spin');

                    if (!aam.isUI()) {
                        aam.fetchContent('main');
                        $('i.icon-spin4', $(this)).attr('class', 'icon-cog');
                    } else {
                        $.aam.loadAccessForm($('#load-post-object-type').val(), $('#load-post-object').val(), null, function () {
                            $('i.icon-spin4', $(_this)).attr('class', 'icon-cog');
                        });
                    }
                    //hide post & pages access control groups that belong to backend
                    $('.aam-backend-post-access').hide();
                });
            });

        })(jQuery);

        /**
         * Default Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            $('document').ready(function() {
                $('#manage-default').bind('click', function () {
                    var _this = this;

                    aam.setSubject('default', null, aam.__('All Users, Roles and Visitor'), 0);
                    $('i.icon-cog', $(this)).attr('class', 'icon-spin4 animate-spin');
                    if (!aam.isUI()) {
                        aam.fetchContent('main');
                        $('i.icon-spin4', $(this)).attr('class', 'icon-cog');
                    } else {
                        $.aam.loadAccessForm($('#load-post-object-type').val(), $('#load-post-object').val(), null, function () {
                            $('i.icon-spin4', $(_this)).attr('class', 'icon-cog');
                        });
                    }
                });
            });

        })(jQuery);


        /**
         * Admin Menu Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            /**
             * 
             * @param {type} param
             * @param {type} value
             * @returns {undefined}
             */
            function save(items, status, successCallback) {
                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Menu.save',
                        subject: aam.getSubject().type,
                        subjectId: aam.getSubject().id,
                        _ajax_nonce: aamLocal.nonce,
                        items: items,
                        status: status
                    },
                    success: function(response) {
                        successCallback(response);
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application Error'));
                    }
                });
            }

            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                if ($('#admin_menu-content').length) {
                    $('.aam-restrict-menu').each(function () {
                        $(this).bind('click', function () {
                            var _this  = $(this);
                            var status = ($('i', $(this)).hasClass('icon-eye-off') ? 1 : 0);
                            var target = _this.data('target');

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');

                            var items = new Array(_this.data('menu-id'));

                            $('input', target).each(function () {
                                $(this).attr('checked', status ? true : false);
                                items.push($(this).data('menu-id'));
                            });

                            save(items, status, function(result) {
                                if (result.status === 'success') {
                                    $('#aam-menu-overwrite').show();

                                    if (status) { //locked the menu
                                        $('.aam-bordered', target).append(
                                                $('<div/>', {'class': 'aam-lock'})
                                        );
                                        _this.removeClass('btn-danger').addClass('btn-primary');
                                        _this.html('<i class="icon-eye"></i>' + aam.__('Show Menu'));
                                        //add menu restricted indicator
                                        var ind = $('<i/>', {
                                            'class': 'aam-panel-title-icon icon-eye-off text-danger'
                                        });
                                        $('.panel-title', target + '-heading').append(ind);
                                    } else {
                                        $('.aam-lock', target).remove();
                                        _this.removeClass('btn-primary').addClass('btn-danger');
                                        _this.html(
                                                '<i class="icon-eye-off"></i>' + aam.__('Restrict Menu')
                                        );
                                        $('.panel-title .icon-eye-off', target + '-heading').remove();
                                    }
                                } else {
                                    _this.attr('checked', !status);
                                }
                            });
                        });
                    });

                    $('input[type="checkbox"]', '#admin-menu').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);
                            
                            aam.save(
                                _this.data('menu-id'),
                                _this.attr('checked') ? 1 : 0,
                                'menu',
                                null,
                                function(result) {
                                    if (result.status === 'success') {
                                        $('#aam-menu-overwrite').show();
                                        if (_this.attr('checked')) {
                                            _this.next().attr('data-original-title', aam.__('Uncheck to allow'));
                                        } else {
                                            _this.next().attr('data-original-title', aam.__('Check to restrict'));
                                        }
                                    }
                                }
                            );
                        });
                    });

                    //reset button
                    $('#menu-reset').bind('click', function () {
                        aam.reset('menu');
                    });
                }
            }

            aam.addHook('init', initialize);

        })(jQuery);


        /**
         * Metaboxes & Widgets Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            /**
             * 
             * @returns {undefined}
             */
            function getContent() {
                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'html',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Metabox.getContent',
                        _ajax_nonce: aamLocal.nonce,
                        subject: aam.getSubject().type,
                        subjectId: aam.getSubject().id
                    },
                    success: function (response) {
                        $('#metabox-content').replaceWith(response);
                        $('#metabox-content').addClass('active');
                        initialize();
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application error'));
                    }
                });
            }

            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                if ($('#metabox-content').length) {
                    //init refresh list button
                    $('#refresh-metabox-list').bind('click', function () {
                        $.ajax(aamLocal.ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Metabox.refreshList',
                                _ajax_nonce: aamLocal.nonce
                            },
                            beforeSend: function () {
                                $('i', '#refresh-metabox-list').attr(
                                    'class', 'icon-spin4 animate-spin'
                                );
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    getContent();
                                } else {
                                    aam.notification(
                                        'danger', aam.__('Failed to retrieve mataboxes')
                                    );
                                }
                            },
                            error: function () {
                                aam.notification('danger', aam.__('Application error'));
                            },
                            complete: function () {
                                $('i', '#refresh-metabox-list').attr(
                                    'class', 'icon-arrows-cw'
                                );
                            }
                        });
                    });

                    $('#init-url-btn').bind('click', function () {
                        $.ajax(aamLocal.ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Metabox.initURL',
                                _ajax_nonce: aamLocal.nonce,
                                url: $('#init-url').val()
                            },
                            beforeSend: function () {
                                $('#init-url-btn').text(aam.__('Processing'));
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    $('#init-url-modal').modal('hide');
                                    getContent();
                                } else {
                                    aam.notification(
                                        'danger', aam.__('Failed to initialize URL')
                                    );
                                }
                            },
                            error: function () {
                                aam.notification('danger', aam.__('Application error'));
                            },
                            complete: function () {
                                $('#init-url-btn').text(aam.__('Initialize'));
                                $('#init-url-modal').modal('hide');
                            }
                        });
                    });

                    //reset button
                    $('#metabox-reset').bind('click', function () {
                        aam.reset('metabox');
                    });

                    $('input[type="checkbox"]', '#metabox-list').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);
                            aam.save(
                                $(this).data('metabox'),
                                $(this).attr('checked') ? 1 : 0,
                                'metabox',
                                null,
                                function(result) {
                                    if (result.status === 'success') {
                                        $('#aam-metabox-overwrite').show();
                                        
                                        if (_this.attr('checked')) {
                                            _this.next().attr('data-original-title', aam.__('Uncheck to show'));
                                        } else {
                                            _this.next().attr('data-original-title', aam.__('Check to hide'));
                                        }
                                    }
                                }
                            );
                        });
                    });
                }
            }

            aam.addHook('init', initialize);

        })(jQuery);


        /**
         * Capabilities Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            /**
             * 
             * @param {type} capability
             * @param {type} btn
             * @returns {undefined}
             */
            function save(capability, btn) {
                var granted = $(btn).hasClass('icon-check-empty') ? 1 : 0;

                //show indicator
                $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');

                aam.save(capability, granted, 'capability', null, function(result) {
                    if (result.status === 'success') {
                        if (granted) {
                            $(btn).attr('class', 'aam-row-action text-success icon-check');
                        } else {
                            $(btn).attr('class', 'aam-row-action text-muted icon-check-empty');
                        }
                    } else {
                        if (granted) {
                            aam.notification(
                                'danger', aam.__('WordPress core does not allow to grant this capability')
                            );
                            $(btn).attr('class', 'aam-row-action text-muted icon-check-empty');
                        } else {
                            $(btn).attr('class', 'aam-row-action text-success icon-check');
                        }
                    }
                });
            }
            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                if ($('#capability-content').length) {
                    //initialize the role list table
                    $('#capability-list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        pagingType: 'simple',
                        serverSide: false,
                        ajax: {
                            url: aamLocal.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Capability.getTable',
                                _ajax_nonce: aamLocal.nonce,
                                subject: aam.getSubject().type,
                                subjectId: aam.getSubject().id
                            }
                        },
                        columnDefs: [
                            {visible: false, targets: [0]}
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: aam.__('Search Capability'),
                            info: aam.__('_TOTAL_ capability(s)'),
                            infoFiltered: '',
                            infoEmpty: aam.__('Nothing to show'),
                            lengthMenu: '_MENU_'
                        },
                        createdRow: function (row, data) {
                            var actions = data[3].split(',');

                            var container = $('<div/>', {'class': 'aam-row-actions'});
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'unchecked':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-check-empty'
                                        }).bind('click', function () {
                                            save(data[0], this);
                                        }));
                                        break;

                                    case 'checked':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-success icon-check'
                                        }).bind('click', function () {
                                            save(data[0], this);
                                        }));
                                        break;

                                    case 'edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-warning'
                                        }).bind('click', function () {
                                            $('#capability-id').val(data[0]);
                                            $('#update-capability-btn').attr('data-cap', data[0]);
                                            $('#edit-capability-modal').modal('show');
                                        }));
                                        break;

                                    case 'delete':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-trash-empty text-danger'
                                        }).bind('click', function () {
                                            var message = $('.aam-confirm-message', '#delete-capability-modal');
                                            $(message).html(message.data('message').replace(
                                                    '%s', '<b>' + data[0] + '</b>')
                                            );
                                            $('#capability-id').val(data[0]);
                                            $('#delete-capability-btn').attr('data-cap', data[0]);
                                            $('#delete-capability-modal').modal('show');
                                        }));
                                        break;

                                    default:
                                        aam.triggerHook('decorate-capability-row', {
                                            action: action,
                                            container: container,
                                            data: data
                                        });
                                        break;
                                }
                            });
                            $('td:eq(2)', row).html(container);
                        }
                    });

                    $('a', '#capability-groups').each(function () {
                        $(this).bind('click', function () {
                            var table = $('#capability-list').DataTable();
                            if ($(this).data('clear') !== true) {
                                table.column(1).search($(this).text()).draw();
                            } else {
                                table.column(1).search('').draw();
                            }
                        });
                    });

                    $('#add-capability-modal').on('shown.bs.modal', function (e) {
                        $('#new-capability-name').val('');
                    });

                    $('#add-capability').bind('click', function () {
                        $('#add-capability-modal').modal('show');
                    });

                    $('#add-capability-btn').bind('click', function () {
                        var _this = this;

                        var capability = $.trim($('#new-capability-name').val());
                        $('#new-capability-name').parent().removeClass('has-error');

                        if (capability) {
                            $.ajax(aamLocal.ajaxurl, {
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'aam',
                                    sub_action: 'Main_Capability.add',
                                    _ajax_nonce: aamLocal.nonce,
                                    capability: capability,
                                    subject: aam.getSubject().type,
                                    subjectId: aam.getSubject().id
                                },
                                beforeSend: function () {
                                    $(_this).text(aam.__('Saving...')).attr('disabled', true);
                                },
                                success: function (response) {
                                    if (response.status === 'success') {
                                        $('#add-capability-modal').modal('hide');
                                        $('#capability-list').DataTable().ajax.reload();
                                    } else {
                                        aam.notification(
                                                'danger', aam.__('Failed to add new capability')
                                        );
                                    }
                                },
                                error: function () {
                                    aam.notification('danger', aam.__('Application error'));
                                },
                                complete: function () {
                                    $(_this).text(aam.__('Add Capability')).attr('disabled', false);
                                }
                            });
                        } else {
                            $('#new-capability-name').parent().addClass('has-error');
                        }
                    });

                    $('#add-capability-modal').on('shown.bs.modal', function (e) {
                        $('#new-capability-name').focus();
                    });

                    $('#update-capability-btn').bind('click', function () {
                        var btn = this;
                        var cap = $.trim($('#capability-id').val());

                        if (cap) {
                            $.ajax(aamLocal.ajaxurl, {
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'aam',
                                    sub_action: 'Main_Capability.update',
                                    _ajax_nonce: aamLocal.nonce,
                                    capability: $(this).attr('data-cap'),
                                    updated: cap
                                },
                                beforeSend: function () {
                                    $(btn).text(aam.__('Saving...')).attr('disabled', true);
                                },
                                success: function (response) {
                                    if (response.status === 'success') {
                                        $('#edit-capability-modal').modal('hide');
                                        $('#capability-list').DataTable().ajax.reload();
                                    } else {
                                        aam.notification(
                                            'danger', aam.__('Failed to update capability')
                                        );
                                    }
                                },
                                error: function () {
                                    aam.notification('danger', aam.__('Application error'));
                                },
                                complete: function () {
                                    $(btn).text(aam.__('Update Capability')).attr(
                                            'disabled', false
                                    );
                                }
                            });
                        }
                    });

                    $('#delete-capability-btn').bind('click', function () {
                        var btn = this;

                        $.ajax(aamLocal.ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Capability.delete',
                                _ajax_nonce: aamLocal.nonce,
                                subject: aam.getSubject().type,
                                subjectId: aam.getSubject().id,
                                capability: $(this).attr('data-cap')
                            },
                            beforeSend: function () {
                                $(btn).text(aam.__('Deleting...')).attr('disabled', true);
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    $('#delete-capability-modal').modal('hide');
                                    $('#capability-list').DataTable().ajax.reload();
                                } else {
                                    aam.notification(
                                        'danger', aam.__('Failed to delete capability')
                                    );
                                }
                            },
                            error: function () {
                                aam.notification('danger', aam.__('Application error'));
                            },
                            complete: function () {
                                $(btn).text(aam.__('Delete Capability')).attr(
                                        'disabled', false
                                );
                            }
                        });
                    });

                    //reset button
                    $('#capability-reset').bind('click', function () {
                        aam.reset('capability');
                    });
                }
            }

            aam.addHook('init', initialize);

        })(jQuery);


        /**
         * Posts & Pages Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            /**
             * Table extra filter
             * 
             * @type Object
             */
            var filter = {
                type: null
            };
            
            /**
             * 
             * @type type
             */
            var objectAccess = {};

            /**
             * 
             * @param {*} param 
             * @param {*} value 
             * @param {*} object 
             * @param {*} object_id 
             * @param {*} successCallback 
             */
            function save(param, value, object, object_id, successCallback) {
                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Post.save',
                        _ajax_nonce: aamLocal.nonce,
                        subject: aam.getSubject().type,
                        subjectId: aam.getSubject().id,
                        param: param,
                        value: value,
                        object: object,
                        objectId: object_id
                    },
                    success: function (response) {
                        if (response.status === 'failure') {
                            aam.notification('danger', response.error);
                        } else {
                            $('#post-overwritten').removeClass('hidden');
                            //add some specific attributes to reset button
                            $('#post-reset').attr({
                                'data-type': object,
                                'data-id': object_id
                            });
                        }
                        successCallback(response);
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application error'));
                    }
                });
            }

            /**
             * 
             * @param {type} type
             * @param {type} id
             * @param {type} title
             * @returns {undefined}
             */
            function addBreadcrumbLevel(type, id, title) {
                var level = $((type === 'type' ? '<a/>' : '<span/>')).attr({
                    'href': '#',
                    'data-level': type,
                    'data-id': id
                }).html('<i class="icon-angle-double-right"></i>' + title);
                $('.aam-post-breadcrumb').append(level);
            }

            /**
             * 
             * @param {type} object
             * @param {type} id
             * @param {type} btn
             * @param {type} callback
             * @returns {undefined}
             */
            $.aam.loadAccessForm = function(object, id, btn, callback) {
                //reset the form first
                var container = $('.aam-access-form[data-type="' + object + '"]');
                $('#post-overwritten').addClass('hidden');

                //show overlay if present
                $('.aam-overlay', container).show();

                //reset data preview elements
                $('.option-preview', container).text('');

                $('.aam-row-action', container).each(function () {
                    $(this).attr({
                        'class': 'aam-row-action text-muted icon-check-empty',
                        'data-type': object,
                        'data-id': id
                    });

                    //initialize each access property
                    $(this).unbind('click').bind('click', function () {
                        var _this   = $(this);
                        var checked = !_this.hasClass('icon-check');

                        _this.attr('class', 'aam-row-action icon-spin4 animate-spin');
                        save(
                            _this.data('property'),
                            (checked ? 1 : 0),
                            object,
                            id,
                            function(response) {
                                if (response.status === 'success') {
                                    if (checked) {
                                        _this.attr(
                                            'class', 'aam-row-action text-danger icon-check'
                                        );
                                    } else {
                                        _this.attr(
                                            'class', 'aam-row-action text-muted icon-check-empty'
                                        );
                                    }
                                }
                            }
                        );
                    });
                });

                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Post.getAccess',
                        _ajax_nonce: aamLocal.nonce,
                        type: object,
                        id: id,
                        subject: aam.getSubject().type,
                        subjectId: aam.getSubject().id
                    },
                    beforeSend: function () {
                        $(btn).attr('data-class', $(btn).attr('class'));
                        $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
                    },
                    success: function (response) {
                        objectAccess = response;
                        
                        //iterate through each property
                        for (var property in response.access) {
                            var checkbox = $('[data-property="' + property + '"]', container);
                            
                            if (checkbox.length) {
                                var cname = (parseInt(response.access[property]) ? 'text-danger icon-check' : 'text-muted icon-check-empty');
                                checkbox.attr({
                                    'class': 'aam-row-action ' + cname
                                });
                            } else {
                                $('.option-preview[data-ref="' + property + '"]').html(
                                        response.preview[property]
                                );
                            }
                        }

                        //check metadata and show message if necessary
                        if (response.meta.overwritten === true) {
                            $('#post-overwritten').removeClass('hidden');
                            //add some specific attributes to reset button
                            $('#post-reset').attr({
                                'data-type': object,
                                'data-id': id
                            });
                        }

                        $('.extended-post-access-btn').attr({
                            'data-type': object,
                            'data-id': id
                        });

                        $('#post-list_wrapper').addClass('aam-hidden');
                        container.addClass('active');

                        if (typeof callback === 'function') {
                            callback.call();
                        }

                        //update dynamic labels
                        if ($('#load-post-object-title').length) {
                            var marker = $('#load-post-object-title').val();
                        } else {
                            marker = $('.aam-post-breadcrumb span').text();
                        }
                        $('[data-dynamic-post-label]').each(function() {
                            $(this).html(
                                $(this).attr('data-dynamic-post-label').replace(/%s/g, '<b>' + marker + '</b>')
                            );
                        });
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application error'));
                    },
                    complete: function () {
                        $(btn).attr('class', $(btn).attr('data-class')).removeAttr('data-class');
                        //show overlay if present
                        $('.aam-overlay', container).hide();
                    }
                });
            };

            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                if ($('#post-content').length) {
                    //reset filter to default list of post types
                    filter.type = null;

                    //initialize the role list table
                    $('#post-list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        pagingType: 'simple',
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: aamLocal.ajaxurl,
                            type: 'POST',
                            data: function (data) {
                                data.action = 'aam';
                                data.sub_action = 'Main_Post.getTable';
                                data._ajax_nonce = aamLocal.nonce;
                                data.subject = aam.getSubject().type;
                                data.subjectId = aam.getSubject().id;
                                data.type = filter.type;
                            }
                        },
                        columnDefs: [
                            {visible: false, targets: [0, 1]}
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: aam.__('Search'),
                            info: aam.__('_TOTAL_ object(s)'),
                            infoFiltered: '',
                            lengthMenu: '_MENU_'
                        },
                        initComplete: function () {
                            $('#post-list_filter .form-control').bind('change', function() {
                                if ($(this).val()) {
                                    $(this).addClass('highlight');
                                } else {
                                    $(this).removeClass('highlight');
                                }
                            });
                        },
                        rowCallback: function (row, data) {
                            //object type icon
                            switch (data[2]) {
                                case 'type':
                                    $('td:eq(0)', row).html('<i class="icon-box"></i>');
                                    break;

                                case 'term':
                                    $('td:eq(0)', row).html('<i class="icon-folder"></i>');
                                    break;

                                default:
                                    $('td:eq(0)', row).html('<i class="icon-doc-text-inv"></i>');
                                    break;
                            }

                            //update the title to a link
                            if (data[2] === 'type') {
                                var link = $('<a/>', {
                                    href: '#'
                                }).bind('click', function () {
                                    //visual feedback - show loading icon
                                    $('td:eq(0)', row).html(
                                            '<i class="icon-spin4 animate-spin"></i>'
                                    );
                                    //set filter
                                    filter[data[2]] = data[0];

                                    //finally reload the data
                                    $('#post-list').DataTable().ajax.reload();

                                    //update the breadcrumb
                                    addBreadcrumbLevel('type', data[0], data[3]);

                                }).html(data[3]);
                                $('td:eq(1)', row).html(link);
                            } else { //reset the post/term title
                                $('td:eq(1)', row).html(data[3]);
                            }

                            //update the actions
                            var actions = data[4].split(',');

                            var container = $('<div/>', {'class': 'aam-row-actions'});
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'drilldown':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-success icon-level-down'
                                        }).bind('click', function () {
                                            if (!$(this).prop('disabled')) {
                                                $(this).prop('disabled', true);
                                                //set filter
                                                filter[data[2]] = data[0];
                                                //finally reload the data
                                                $('#post-list').DataTable().ajax.reload();
                                                //update the breadcrumb
                                                addBreadcrumbLevel('type', data[0], data[3]);
                                            }
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': aam.__('Drill-Down')
                                        }));
                                        $('.tooltip').remove();
                                        break;

                                    case 'manage':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-info icon-cog'
                                        }).bind('click', function () {
                                            $.aam.loadAccessForm(data[2], data[0], $(this), function () {
                                                addBreadcrumbLevel('edit', data[2], data[3]);
                                            });
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': aam.__('Manage Access')
                                        }));
                                        $('.tooltip').remove();
                                        break;

                                    case 'edit' :
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-warning icon-pencil'
                                        }).bind('click', function () {
                                            window.open(data[1], '_blank');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': aam.__('Edit')
                                        }));
                                        break;

                                    default:
                                        aam.triggerHook('post-action', {
                                            container: container,
                                            action   : action,
                                            data     : data
                                        });
                                        break;
                                }
                            });
                            $('td:eq(2)', row).html(container);
                        }
                    });

                    //initialize the breadcrumb
                    $('.aam-post-breadcrumb').delegate('a', 'click', function () {
                        filter.type = $(this).data('id');
                        $('#post-list').DataTable().ajax.reload();
                        $(this).nextAll().remove();
                        $('.aam-slide-form').removeClass('active');
                        $('#post-list_wrapper').removeClass('aam-hidden');
                        $('#post-overwritten').addClass('hidden');
                    });

                    //reset button
                    $('#post-reset').bind('click', function () {
                        var type = $(this).attr('data-type');
                        var id   = $(this).attr('data-id');

                        $.ajax(aamLocal.ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Post.reset',
                                _ajax_nonce: aamLocal.nonce,
                                type: type,
                                id: id,
                                subject: aam.getSubject().type,
                                subjectId: aam.getSubject().id
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    $('#post-overwritten').addClass('hidden');
                                    $.aam.loadAccessForm(type, id);
                                }
                            }
                        });
                    });

                    //go back button
                    $('.aam-slide-form').delegate('.post-back', 'click', function () {
                        var type = $(this).parent().data('type');

                        $('.aam-slide-form[data-type="' + type + '"]').removeClass('active');
                        $('#post-list_wrapper').removeClass('aam-hidden');
                        $('.aam-post-breadcrumb span:last').remove();
                        $('#post-overwritten').addClass('hidden');
                    });

                    //load referenced post
                    if ($('#load-post-object').val()) {
                        $.aam.loadAccessForm(
                            $('#load-post-object-type').val(), 
                            $('#load-post-object').val()
                        );
                    }
                    
                    $('.advanced-post-option').each(function() {
                       $(this).bind('click', function() {
                           var container = $(this).attr('href');
                           var option = objectAccess.access[$(this).data('ref')];
                           var field  = $($('.extended-post-access-btn', container).data('field'));
                           
                           //add attributes to the .extended-post-access-btn
                           $('.extended-post-access-btn', container).attr({
                               'data-ref': $(this).data('ref'),
                               'data-preview': $(this).data('preview')
                           });
                           
                           //set field value
                           field.val(option);
                       });
                    });
                    
                    $('.extended-post-access-btn').each(function() {
                        $(this).bind('click', function() {
                           var _this = $(this);
                           var label = _this.text();
                           var value = $(_this.data('field')).val();
                           
                           _this.text(aam.__('Saving...'));
                           
                           save(
                                _this.attr('data-ref'),
                                value,
                                _this.attr('data-type'),
                                _this.attr('data-id'),
                                function(response) {
                                    if (response.status === 'success') {
                                        objectAccess.access[_this.attr('data-ref')] = value;
                                        $(_this.attr('data-preview')).html(response.preview);
                                        var tr = $(_this.attr('data-preview')).parent().parent().parent();
                                        if ($('.aam-row-action', tr).hasClass('icon-check-empty')) {
                                            $('.aam-row-action', tr).trigger('click');
                                        }
                                    }
                                    $(_this.data('modal')).modal('hide');

                                    _this.text(label);
                                }
                            );
                        });
                    });
                }
            }

            aam.addHook('init', initialize);

        })(jQuery);

        /**
         * Redirect Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                var container = '#redirect-content';

                if ($(container).length) {
                    $('input[type="radio"]', container).each(function () {
                        $(this).bind('click', function () {
                            //hide group
                            $('.' + $(this).data('group')).hide();

                            //show the specific one
                            $($(this).data('action')).show();

                            //save redirect type
                            aam.save(
                                $(this).attr('name'), 
                                $(this).val(), 
                                'redirect', 
                                null,
                                function(result) {
                                    if (result.status === 'success') {
                                        $('#aam-redirect-overwrite').show();
                                    }
                                }
                            );
                        });
                    });

                    $('input[type="text"],select,textarea', container).each(function () {
                        $(this).bind('change', function () {
                            //save redirect type
                            aam.save(
                                $(this).attr('name'), 
                                $(this).val(), 
                                'redirect', 
                                null,
                                function(result) {
                                    if (result.status === 'success') { 
                                        $('#aam-redirect-overwrite').show();
                                    }
                                }
                            );
                        });
                    });

                    $('#redirect-reset').bind('click', function () {
                        aam.reset('redirect');
                    });
                }
            }

            aam.addHook('init', initialize);

        })(jQuery);

        /**
         * Login Redirect Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                var container = '#login_redirect-content';

                if ($(container).length) {
                    $('input[type="radio"]', container).each(function () {
                        $(this).bind('click', function () {
                            //hide all fields
                            $('.login-redirect-action').hide();

                            //show the specific one
                            $($(this).data('action')).show();

                            //save redirect type
                            aam.save(
                                $(this).attr('name'), 
                                $(this).val(), 
                                'loginRedirect', 
                                null,
                                function(result) {
                                    if (result.status === 'success') {
                                        $('#aam-login-redirect-overwrite').show();
                                    }
                                }
                            );
                        });
                    });

                    $('input[type="text"],select,textarea', container).each(function () {
                        $(this).bind('change', function () {
                            if ($(this).is('input[type="checkbox"]')) {
                                var val = $(this).prop('checked') ? $(this).val() : 0;
                            } else {
                                val = $.trim($(this).val());
                            }

                            //save redirect type
                            aam.save(
                                $(this).attr('name'), 
                                val, 
                                'loginRedirect',
                                null,
                                function(result) {
                                    if (result.status === 'success') {
                                        $('#aam-login-redirect-overwrite').show();
                                    }
                                }
                            );
                        });
                    });
                    
                    $('#login-redirect-reset').bind('click', function () {
                        aam.reset('loginRedirect');
                    });
                }
            }

            aam.addHook('init', initialize);

        })(jQuery);

        /**
         * Logout Redirect Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                var container = '#logout_redirect-content';

                if ($(container).length) {
                    $('input[type="radio"]', container).each(function () {
                        $(this).bind('click', function () {
                            //hide all fields
                            $('.logout-redirect-action').hide();

                            //show the specific one
                            $($(this).data('action')).show();

                            //save redirect type
                            aam.save(
                                $(this).attr('name'), 
                                $(this).val(), 
                                'logoutRedirect', 
                                null,
                                function(result) {
                                    if (result.status === 'success') {
                                        $('#aam-logout-redirect-overwrite').show();
                                    }
                                }
                            );
                        });
                    });

                    $('input[type="text"],select,textarea', container).each(function () {
                        $(this).bind('change', function () {
                            //save redirect type
                            aam.save(
                                $(this).attr('name'), 
                                $(this).val(), 
                                'logoutRedirect', 
                                null,
                                function(result) {
                                    if (result.status === 'success') {
                                        $('#aam-logout-redirect-overwrite').show();
                                    }
                                }
                            );
                        });
                    });
                    
                    $('#logout-redirect-reset').bind('click', function () {
                        aam.reset('logoutRedirect');
                    });
                }
            }

            aam.addHook('init', initialize);

        })(jQuery);

        /**
         * 404 Redirect Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            /**
             * 
             * @param {type} param
             * @param {type} value
             * @returns {undefined}
             */
            function save(param, value) {
                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_404Redirect.save',
                        _ajax_nonce: aamLocal.nonce,
                        subject: aam.getSubject().type,
                        subjectId: aam.getSubject().id,
                        param: param,
                        value: value
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application error'));
                    }
                });
            }

            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                var container = '#404redirect-content';

                if ($(container).length) {
                    $('input[type="radio"]', container).each(function () {
                        $(this).bind('click', function () {
                            //hide group
                            $('.aam-404redirect-action').hide();

                            //show the specific one
                            $($(this).data('action')).show();

                            //save redirect type
                            save($(this).attr('name'), $(this).val());
                        });
                    });

                    $('input[type="text"],select,textarea', container).each(function () {
                        $(this).bind('change', function () {
                            //save redirect type
                            save($(this).attr('name'), $(this).val());
                        });
                    });
                }
            }

            aam.addHook('init', initialize);

        })(jQuery);

        /**
         * Extensions Interface
         * 
         * @param {jQuery} $
         * 
         * @returns {void}
         */
        (function ($) {

            var dump = null;

            /**
             * 
             * @param {type} data
             * @param {type} cb
             * @returns {undefined}
             */
            function downloadExtension(data, cb) {
                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: data,
                    success: function (response) {
                        if (response.status === 'success') {
                            setTimeout(function () {
                                aam.fetchContent('extensions');
                            }, 500);
                        } else {
                            aam.notification('danger', aam.__(response.error));
                            if (typeof response.content !== 'undefined') {
                                dump = response;
                                $('#installation-error').html(response.error);
                                $('#extension-notification-modal').modal('show');
                            }
                        }
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application error'));
                    },
                    complete: function() {
                        cb();
                    }
                });
            }
            
            /**
             * 
             * @param {type} data
             * @returns {undefined}
             */
            function updateStatus(data) {
                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: data,
                    success: function (response) {
                        if (response.status === 'success') {
                            aam.notification(
                                'success', 
                                aam.__('Extension status was updated successfully')
                            );
                        } else {
                            aam.notification('danger', aam.__(response.error));
                        }
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application error'));
                    },
                    complete: function () {
                        aam.fetchContent('extensions');
                    }
                });
            }

            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                if ($('#extension-content').length) {
                    //check for updates
                    $('#aam-update-check').bind('click', function() {
                        $.ajax(aamLocal.ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Extension_Manager.check',
                                _ajax_nonce: aamLocal.nonce,
                            },
                            beforeSend: function () {
                                $('#aam-update-check i').attr('class', 'icon-spin4 animate-spin');
                            },
                            complete: function () {
                                aam.fetchContent('extensions');
                            }
                        });
                    });

                    //init refresh list button
                    $('#install-extension').bind('click', function () {
                        $('#extension-key').parent().removeClass('error');

                        var _this = $(this);
                        var license = $.trim($('#extension-key').val());

                        if (!license) {
                            $('#extension-key').parent().addClass('error');
                            $('#extension-key').focus();
                            return;
                        }

                        $('i', _this).attr('class', 'icon-spin4 animate-spin');
                        downloadExtension({
                            action: 'aam',
                            sub_action: 'Extension_Manager.install',
                            _ajax_nonce: aamLocal.nonce,
                            license: $('#extension-key').val()
                        }, function() {
                            $('i', _this).attr('class', 'icon-download-cloud');
                        });
                    });

                    //update extension
                    $('.aam-update-extension').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');
                            downloadExtension({
                                action: 'aam',
                                sub_action: 'Extension_Manager.update',
                                _ajax_nonce: aamLocal.nonce,
                                extension: _this.data('product')
                            }, function() {
                                $('i', _this).attr('class', 'icon-arrows-cw');
                            });
                        });
                    });
                    
                    //deactivate extension
                    $('.aam-deactivate-extension').each(function() {
                        $(this).bind('click', function () {
                            var _this = $(this);

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');
                            updateStatus({
                                action: 'aam',
                                sub_action: 'Extension_Manager.deactivate',
                                _ajax_nonce: aamLocal.nonce,
                                extension: _this.data('product')
                            });
                        });
                    });
                    
                    //activet extension
                    $('.aam-activate-extension').each(function() {
                        $(this).bind('click', function () {
                            var _this = $(this);

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');
                            updateStatus({
                                action: 'aam',
                                sub_action: 'Extension_Manager.activate',
                                _ajax_nonce: aamLocal.nonce,
                                extension: _this.data('product')
                            });
                        });
                    });

                    //download extension
                    $('.aam-download-extension').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');
                            downloadExtension({
                                action: 'aam',
                                sub_action: 'Extension_Manager.install',
                                _ajax_nonce: aamLocal.nonce,
                                license: _this.data('license')
                            }, function() {
                                $('i', _this).attr('class', 'icon-download-cloud');
                            });
                        });
                    });

                    //bind the download handler
                    $('#download-extension').bind('click', function () {
                        download(
                                'data:application/zip;base64,' + dump.content,
                                dump.title + '.zip',
                                'application/zip'
                        );
                        $('#extension-notification-modal').modal('hide');
                    });

                    if(/(Version)\/(\d+)\.(\d+)(?:\.(\d+))?.*Safari\//.test(navigator.userAgent)) {
                        $('#safari-download-notification').removeClass('hidden');
                    }
                }
            }

            aam.addHook('init', initialize);

        })(jQuery);


        /**
         * Utilities Interface
         * 
         * @param {type} $
         * 
         * @returns {undefined}
         */
        (function ($) {

            /**
             * 
             * @param {type} param
             * @param {type} value
             * @param {type} reload
             * @returns {undefined}
             */
            function save(param, value) {
                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Settings_Manager.save',
                        _ajax_nonce: aamLocal.nonce,
                        param: param,
                        value: value
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application Error'));
                    }
                });
            }

            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                if ($('.aam-feature.settings').length) {
                    $('[data-toggle="toggle"]').bootstrapToggle();

                    $('input[type="checkbox"]', '.aam-feature.settings').bind('change', function () {
                        save(
                            $(this).attr('name'), 
                            ($(this).prop('checked') ? 1 : 0)
                        );
                    });

                    $('input[type="text"]', '.aam-feature.settings').bind('change', function() {
                        save($(this).attr('name'), $(this).val());
                    });

                    $('#clear-settings').bind('click', function () {
                        $.ajax(aamLocal.ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Settings_Tools.clear',
                                _ajax_nonce: aamLocal.nonce
                            },
                            beforeSend: function() {
                                $('#clear-settings').prop('disabled', true);
                                $('#clear-settings').text(aam.__('Wait...'));
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    aam.notification('success', aam.__('All settings were cleared successfully'));
                                } else {
                                    aam.notification('danger', response.reason);
                                }
                            },
                            error: function () {
                                aam.notification('danger', aam.__('Application Error'));
                            },
                            complete: function() {
                                $('#clear-settings').prop('disabled', false);
                                $('#clear-settings').text(aam.__('Clear'));
                                $('#clear-settings-modal').modal('hide');
                            }
                        });
                    });

                    $('#clear-cache').bind('click', function () {
                        $.ajax(aamLocal.ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Settings_Tools.clearCache',
                                _ajax_nonce: aamLocal.nonce
                            },
                            beforeSend: function() {
                                $('#clear-cache').prop('disabled', true);
                                $('#clear-cache').text(aam.__('Wait...'));
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    aam.notification('success', aam.__('The cache was cleared successfully'));
                                } else {
                                    aam.notification('danger', response.reason);
                                }
                            },
                            error: function () {
                                aam.notification('danger', aam.__('Application Error'));
                            },
                            complete: function() {
                                $('#clear-cache').prop('disabled', false);
                                $('#clear-cache').text(aam.__('Clear'));
                            }
                        });
                    });

                    $('#export-aam').bind('click', function () {
                        $.ajax(aamLocal.ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Settings_Tools.export',
                                _ajax_nonce: aamLocal.nonce
                            },
                            beforeSend: function () {
                                $('#export-aam').prop('disabled', true);
                                $('#export-aam').attr('data-lable', $('#export-aam').text());
                                $('#export-aam').text(aam.__('Wait...'));
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    download(
                                        'data:text/plain;base64,' + response.content,
                                        'aam-export.json',
                                        'text/plain'
                                    );
                                }
                            },
                            error: function () {
                                aam.notification('danger', aam.__('Application Error'));
                            },
                            complete: function () {
                                $('#export-aam').prop('disabled', false);
                                $('#export-aam').text($('#export-aam').attr('data-lable'));
                            }
                        });
                    });

                    $('#import-aam').bind('click', function () {
                        if (typeof FileReader !== 'undefined') { 
                            $('#aam-import-file').trigger('click');
                        } else {
                            aam.notification('danger', 'Your browser does not support FileReader functionality');
                        }
                    });

                    $('#aam-import-file').bind('change', function () {
                        var file = $(this)[0].files[0];
                        var json = null;

                        var reader = new FileReader();
                        reader.onload = function(e) {
                            json = reader.result;

                            try {
                                //validate the content
                                var loaded = JSON.parse(json);
                                if (loaded.plugin && loaded.plugin == 'advanced-access-manager') {
                                    $.ajax(aamLocal.ajaxurl, {
                                        type: 'POST',
                                        dataType: 'json',
                                        data: {
                                            action: 'aam',
                                            sub_action: 'Settings_Tools.import',
                                            _ajax_nonce: aamLocal.nonce,
                                            json: json
                                        },
                                        beforeSend: function () {
                                            $('#import-aam').prop('disabled', true);
                                            $('#import-aam').attr('data-lable', $('#import-aam').text());
                                            $('#import-aam').text(aam.__('Wait...'));
                                        },
                                        success: function(response) {
                                            if (response.status === 'success') {
                                                aam.notification('success', 'All settings were imported successfully');
                                            // location.reload();
                                            } else {
                                                aam.notification('danger', aam.__('Invalid data format'));
                                            }
                                        },
                                        error: function () {
                                            aam.notification('danger', aam.__('Application Error'));
                                        },
                                        complete: function () {
                                            $('#import-aam').prop('disabled', false);
                                            $('#import-aam').html($('#import-aam').attr('data-lable'));
                                        }
                                    });
                                } else {
                                    throw 'Invalid format'; 
                                }
                            } catch (e) {
                                aam.notification('danger', 'Invalid file format');
                            }
                        };
                        reader.readAsText(file);

                    });
                }
            }

            aam.addHook('init', initialize);

        })(jQuery);
        
        aam.fetchContent('main'); //fetch default AAM content
    }

    /**
     * Main AAM class
     * 
     * @returns void
     */
    function AAM() {
        /**
         * Current Subject
         */
        this.subject = {};

        /**
         * Different UI hooks
         */
        this.hooks = {};
        
        /**
         * Content filters
         */
        this.filters = {};
    }
    
    /**
     * 
     * @returns {undefined}
     */
    AAM.prototype.initializeMenu = function() {
        var _this = this;
        
        //initialize the menu switch
        $('li', '#feature-list').each(function () {
            $(this).bind('click', function () {
                $('.aam-feature').removeClass('active');
                //highlight active feature
                $('li', '#feature-list').removeClass('active');
                $(this).addClass('active');
                //show feature content
                $('#' + $(this).data('feature') + '-content').addClass('active');
                location.hash = $(this).data('feature');
                //trigger hook
                _this.triggerHook('menu-feature-click', $(this).data('feature'));
            });
        });
    };
    
    /**
     * 
     * @returns {undefined}
     */
    AAM.prototype.fetchContent = function (uiType) {
        var _this = this;
        
        //referred object ID like post, page or any custom post type
        var object   = window.location.search.match(/&oid\=([^&]*)/);
        var type     = window.location.search.match(/&otype\=([^&]*)/);

        $.ajax(aamLocal.url.site, {
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'aamc',
                _ajax_nonce: aamLocal.nonce,
                uiType: uiType,
                subject: this.getSubject().type,
                subjectId: this.getSubject().id,
                oid: object ? object[1] : null,
                otype: type ? type[1] : null
            },
            beforeSend: function () {
                if ($('#aam-initial-load').length === 0) {
                    var loader = $('<div/>', {'class': 'aam-loading'}).html(
                            '<i class="icon-spin4 animate-spin"></i>'
                    );
                    $('#aam-content').html(loader);
                }
            },
            success: function (response) {
                $('#aam-content').html(response);
                //init menu
                _this.initializeMenu();
                //trigger initialization hook
                _this.triggerHook('init');
                //activate one of the menu items
                var item = $('li:eq(0)', '#feature-list');

                if (location.hash !== '') {
                    var hash = location.hash.substr(1);
                    if ($('li[data-feature="' + hash + '"]', '#feature-list').length) {
                        item = $('li[data-feature="' + hash + '"]', '#feature-list');
                    }
                }

                item.trigger('click');
                
                $('.aam-sidebar .metabox-holder').hide();
                $('.aam-sidebar .shared-metabox').show();
                $('.aam-sidebar .' + uiType + '-metabox').show();
                
                if (uiType !== 'main') { //hide subject and user/role manager
                    $('#aam-subject-banner').hide();
                } else {
                    $('#aam-subject-banner').show();
                }
            }
        });
    };

    /**
     * Add UI hook
     * 
     * @param {String}   name
     * @param {Function} callback
     * 
     * @returns {void}
     */
    AAM.prototype.addHook = function (name, callback) {
        if (typeof this.hooks[name] === 'undefined') {
            this.hooks[name] = new Array();
        }

        this.hooks[name].push(callback);
    };

    /**
     * Trigger UI hook
     * 
     * @param {String} name
     * @param {Object} params
     * 
     * @returns {void}
     */
    AAM.prototype.triggerHook = function (name, params) {
        if (typeof this.hooks[name] !== 'undefined') {
            for (var i in this.hooks[name]) {
                this.hooks[name][i].call(this, params);
            }
        }
    };
    
    /**
     * Add UI filter
     * 
     * @param {String}   name
     * @param {Function} callback
     * 
     * @returns {void}
     */
    AAM.prototype.addFilter = function (name, callback) {
        if (typeof this.filters[name] === 'undefined') {
            this.filters[name] = new Array();
        }

        this.filters[name].push(callback);
    };

    /**
     * Apply UI filters
     * 
     * @param {String} name
     * @param {Object} params
     * 
     * @returns {void}
     */
    AAM.prototype.applyFilters = function (name, result, params) {
        if (typeof this.filters[name] !== 'undefined') {
            for (var i in this.filters[name]) {
                result = this.filters[name][i].call(this, result, params);
            }
        }
        
        return result;
    };
    
    /**
     * Initialize the AAM
     * 
     * @returns {undefined}
     */
    AAM.prototype.initialize = function () {
        //read default subject and set it for AAM object
        this.setSubject(
                aamLocal.subject.type, 
                aamLocal.subject.id,
                aamLocal.subject.name,
                aamLocal.subject.level
        );
        
        //load the UI javascript support
        UI();

        //initialize help context
        $('.aam-help-menu').each(function() {
            var target = $(this).data('target');
            
            if (target) {
                $(this).bind('click', function() {
                    if ($(this).hasClass('active')) {
                        $('.aam-help-context', target).removeClass('active');
                        $('.aam-postbox-inside', target).show();
                        $(this).removeClass('active');
                    } else {
                        $('.aam-postbox-inside', target).hide();
                        $('.aam-help-context', target).addClass('active');
                        $(this).addClass('active');
                    }
                });
            }
        });
        
        //help tooltips
        $('body').delegate('[data-toggle="tooltip"]', 'hover', function (event) {
            event.preventDefault();
            $(this).tooltip({
                'placement' : 'top',
                'container' : 'body'
            });
            $(this).tooltip('show');
        });
        
        $('.aam-area').each(function() {
           $(this).bind('click', function() {
               $('.aam-area').removeClass('text-danger');
               $(this).addClass('text-danger');
               aam.fetchContent($(this).data('type')); 
           });
        });

        // preventDefault for all links with # href
        $('#aam-container').delegate('a[href="#"]', 'click', function(event) {
            event.preventDefault();
        });
    };

    /**
     * 
     * @param {type} label
     * @returns {unresolved}
     */
    AAM.prototype.__ = function (label) {
        return (aamLocal.translation[label] ? aamLocal.translation[label] : label);
    };

/**
 * 
 * @param {type} type
 * @param {type} id
 * @param {type} name
 * @param {type} level
 * @returns {undefined}
 */
    AAM.prototype.setSubject = function (type, id, name, level) {
        this.subject = {
            type: type,
            id: id,
            name: name,
            level: level
        };
        
        //update the header
        var subject = type.charAt(0).toUpperCase() + type.slice(1);
        $('.aam-current-subject').html(
                aam.__(subject) + ': <strong>' + name + '</strong>'
        );

        //highlight screen if the same level
        if (parseInt(level) >= aamLocal.level || type === 'default') {
            $('.aam-current-subject').addClass('danger');
            $('#wpcontent').css('background-color', '#FAEBEA');
        } else {
            $('.aam-current-subject').removeClass('danger');
            $('#wpcontent').css('background-color', '#FFFFFF');
        }

        this.triggerHook('setSubject');
    };

    /**
     * 
     * @returns {aam_L1.AAM.subject}
     */
    AAM.prototype.getSubject = function () {
        return this.subject;
    };

    /**
     * 
     * @param {type} status
     * @param {type} message
     * @returns {undefined}
     */
    AAM.prototype.notification = function (status, message) {
        var notification = $('<div/>', {'class': 'aam-sticky-note ' + status});
        
        notification.append($('<span/>').text(message));
        $('.wrap').append(notification);
        
        setTimeout(function () {
            $('.aam-sticky-note').remove();
        }, 9000);
    };
    
    /**
     * 
     * @param {type} param
     * @param {type} value
     * @param {type} object
     * @param {type} object_id
     * @param {type} successCallback
     * @returns {undefined}
     */
    AAM.prototype.save = function(param, value, object, object_id, successCallback) {
        $.ajax(aamLocal.ajaxurl, {
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'aam',
                sub_action: 'save',
                _ajax_nonce: aamLocal.nonce,
                subject: this.getSubject().type,
                subjectId: this.getSubject().id,
                param: param,
                value: value,
                object: object,
                objectId: object_id
            },
            success: function (response) {
                if (typeof successCallback === 'function') {
                    successCallback(response);
                }
            },
            error: function () {
                aam.notification('danger', aam.__('Application error'));
            }
        });
    };

    /**
     * 
     * @param {type} object
     * @returns {undefined}
     */
    AAM.prototype.reset = function(object) {
        $.ajax(aamLocal.ajaxurl, {
            type: 'POST',
            data: {
                action: 'aam',
                sub_action: 'reset',
                _ajax_nonce: aamLocal.nonce,
                subject: this.getSubject().type,
                subjectId: this.getSubject().id,
                object: object
            },
            success: function () {
                aam.fetchContent('main');
            },
            error: function () {
                aam.notification('danger', aam.__('Application error'));
            }
        });
    };
    
    /**
     * 
     * @param {type} el
     * @returns {undefined}
     */
    AAM.prototype.readMore = function(el) {
        $(el).append($('<a/>').attr({
            'href'  : '#',
            'class' : 'aam-readmore' 
        }).text('Read More').bind('click', function(event){
            event.preventDefault();
            $(this).hide();
            $(el).append('<span>' + $(el).data('readmore') + '</span>');
        }));
    };
    
    /**
     * 
     * @returns {Boolean}
     */
    AAM.prototype.isUI = function() {
        return (typeof aamLocal.ui !== 'undefined');
    };

    /**
     * Initialize UI
     */
    $(document).ready(function () {
        aam = new AAM();
        $.aam = aam;
        aam.initialize();
    });

})(jQuery);