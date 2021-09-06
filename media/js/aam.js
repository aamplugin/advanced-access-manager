/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

 /**
  *
  */
(function ($) {

    /**
     * Local representation of AAM object
     */
    let aam;

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
                var subject = getAAM().getSubject();

                return (!getAAM().isUI('principal') && subject.type === 'role' && subject.id === id);
            }

            /**
             *
             * @param {type} exclude
             */
            function fetchRoleList(exclude) {
                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_Role.getList',
                        _ajax_nonce: getLocal().nonce,
                        exclude: exclude
                    },
                    beforeSend: function () {
                        $('.inherit-role-list').html(
                            '<option value="">' + getAAM().__('Loading...') + '</option>'
                        );
                    },
                    success: function (response) {
                        $('.inherit-role-list').html(
                            '<option value="">' + getAAM().__('No role') + '</option>'
                        );
                        for (var i in response) {
                            $('.inherit-role-list').append(
                                '<option value="' + i + '">' + response[i].name + '</option>'
                            );
                        }
                        if ($.aamEditRole) {
                            $('.inherit-role-list').val($.aamEditRole[0]);
                        }
                        getAAM().triggerHook('post-get-role-list', {
                            list: response
                        });
                        //TODO - Rewrite JavaScript to support $.aam
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
                $('input,select', container).each(function () {
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
                ordering: true,
                dom: 'ftrip',
                pagingType: 'simple',
                processing: true,
                stateSave: true,
                serverSide: false,
                ajax: {
                    url: getLocal().ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_Role.getTable',
                        _ajax_nonce: getLocal().nonce,
                        subject: getAAM().getSubject().type,
                        subjectId: getAAM().getSubject().id,
                        ui: getLocal().ui,
                        policyId: $('#aam-policy-id').val()
                    }
                },
                columnDefs: [
                    { visible: false, targets: [0, 1, 4] },
                    { orderable: false, targets: [0, 1, 3, 4] }
                ],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: getAAM().__('Search role'),
                    info: getAAM().__('_TOTAL_ role(s)'),
                    infoFiltered: ''
                },
                initComplete: function () {
                    if (getAAM().isUI('main') && getLocal().caps.create_roles) {
                        var create = $('<a/>', {
                            'href': '#',
                            'class': 'btn btn-primary'
                        })
                            .html('<i class="icon-plus"></i>')
                            .bind('click', function () {
                                resetForm('#add-role-modal .modal-body');
                                $('#add-role-modal').modal('show');
                            })
                            .attr({
                                'data-toggle': "tooltip",
                                'title': getAAM().__('Create New Role')
                            });

                        $('.dataTables_filter', '#role-list_wrapper').append(create);
                    }
                },
                createdRow: function (row, data) {
                    if (isCurrent(data[0])) {
                        $('td:eq(0)', row).html(
                            '<strong class="aam-highlight">' + data[2] + '</strong>'
                        );
                    } else {
                        $('td:eq(0)', row).html('<span>' + data[2] + '</span>');
                    }

                    $(row).attr('data-id', data[0]);

                    //add subtitle
                    $('td:eq(0)', row).append(
                        $('<i/>', { 'class': 'aam-row-subtitle' }).html(
                            getAAM().applyFilters(
                                'role-subtitle',
                                getAAM().__('Users') + ': <b>' + parseInt(data[1]) + '</b>; ID: <b>' + data[0] + '</b>',
                                data
                            )
                        )
                    );

                    var actions = data[3].split(',');

                    var container = $('<div/>', { 'class': 'aam-row-actions' });
                    $.each(actions, function (i, action) {
                        switch (action) {
                            case 'manage':
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action icon-cog ' + (isCurrent(data[0]) ? 'text-muted' : 'text-info')
                                }).bind('click', function () {
                                    if (!$(this).prop('disabled')) {
                                        $(this).prop('disabled', true);
                                        var title = $('td:eq(0) span', row).html();
                                        getAAM().setSubject('role', data[0], title, data[4]);
                                        $('td:eq(0) span', row).replaceWith(
                                            '<strong class="aam-highlight">' + title + '</strong>'
                                        );

                                        $('i.icon-cog', container).attr(
                                            'class', 'aam-row-action icon-spin4 animate-spin'
                                        );

                                        if (getAAM().isUI('main')) {
                                            $('i.icon-cog', container).attr(
                                                'class', 'aam-row-action icon-spin4 animate-spin'
                                            );
                                            getAAM().fetchContent('main');
                                            $('i.icon-spin4', container).attr(
                                                'class', 'aam-row-action icon-cog text-muted'
                                            );
                                        } else if (getAAM().isUI('post')) {
                                            getAAM().fetchPartial('post-access-form', function (content) {
                                                $('#metabox-post-access-form').html(content);
                                                getAAM().triggerHook('load-access-form', [
                                                    $('#content-object-type').val(),
                                                    $('#content-object-id').val(),
                                                    $(this)
                                                ]);
                                                $('i.icon-spin4', container).attr(
                                                    'class', 'aam-row-action icon-cog text-muted'
                                                );
                                            });
                                        }
                                    }
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': getAAM().__('Manage role')
                                }).prop('disabled', (isCurrent(data[0]) ? true : false)));
                                break;

                            case 'edit':
                                if (getAAM().isUI('main')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-pencil text-warning'
                                    }).bind('click', function () {
                                        resetForm('#edit-role-modal .modal-body');
                                        $('#edit-role-btn').data('role', data[0]);
                                        $('#edit-role-name').val(data[2]);
                                        $('#edit-role-slug').val(data[0]);
                                        $('#edit-role-modal').modal('show');
                                        fetchRoleList(data[0]);

                                        if (data[1] > 0) {
                                            $('#edit-role-slug').prop('disabled', true);
                                        } else {
                                            $('#edit-role-slug').prop('disabled', false);
                                        }

                                        //TODO - Rewrite JavaScript to support $.aam
                                        $.aamEditRole = data;

                                        getAAM().triggerHook('edit-role-modal', data);
                                    }).attr({
                                        'data-toggle': "tooltip",
                                        'title': getAAM().__('Edit role')
                                    }));
                                }
                                break;

                            case 'no-edit':
                                if (getAAM().isUI('main')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-pencil text-muted'
                                    }));
                                }
                                break;

                            case 'clone':
                                if (getAAM().isUI('main')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-clone text-success'
                                    }).bind('click', function () {
                                        //TODO - Rewrite JavaScript to support $.aam
                                        $.aamEditRole = data;
                                        $('#clone-role').prop('checked', true);
                                        $('#add-role-modal').modal('show');
                                    }).attr({
                                        'data-toggle': "tooltip",
                                        'title': getAAM().__('Clone role')
                                    }));
                                }
                                break;

                            case 'no-clone':
                                if (getAAM().isUI('main')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-clone text-muted'
                                    }));
                                }
                                break;

                            case 'delete':
                                if (getAAM().isUI('main')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-trash-empty text-danger'
                                    }).bind('click', { role: data }, function (event) {
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
                                        'title': getAAM().__('Delete role')
                                    }));
                                }
                                break;

                            case 'no-delete':
                                if (getAAM().isUI('main')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-trash-empty text-muted'
                                    }));
                                }
                                break;

                            case 'attach':
                                if (getAAM().isUI('principal')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-check-empty'
                                    }).bind('click', function () {
                                        getAAM().applyPolicy(
                                            {
                                                type: 'role',
                                                id: data[0]
                                            },
                                            $('#aam-policy-id').val(),
                                            ($(this).hasClass('icon-check-empty') ? 1 : 0),
                                            this
                                        );
                                    }));
                                }
                                break;

                            case 'detach':
                                if (getAAM().isUI('principal')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-check text-success'
                                    }).bind('click', function () {
                                        getAAM().applyPolicy(
                                            {
                                                type: 'role',
                                                id: data[0]
                                            },
                                            $('#aam-policy-id').val(),
                                            ($(this).hasClass('icon-check') ? 0 : 1),
                                            this
                                        );
                                    }));
                                }
                                break;

                            default:
                                if (getAAM().isUI('main')) {
                                    getAAM().triggerHook('role-action', {
                                        container: container,
                                        action: action,
                                        data: data
                                    });
                                }
                                break;
                        }
                    });
                    $('td:eq(1)', row).html(container);

                    getAAM().triggerHook('decorate-role-row', {
                        row: row,
                        data: data
                    });
                }
            });

            $('#role-list').on('draw.dt', function () {
                $('tr', '#role-list tbody').each(function () {
                    if (!isCurrent($(this).data('id'))) {
                        $('td:eq(0) strong', this).replaceWith(
                            '<span>' + $('td:eq(0) strong', this).text() + '</span>'
                        );
                        $('.icon-cog.text-muted', this).attr('disabled', false);
                        $('.icon-cog.text-muted', this).toggleClass('text-muted text-info');
                    }
                });
            });

            $('#add-role-modal').on('shown.bs.modal', function (e) {
                fetchRoleList();
                //clear add role form first
                $('input', '#add-role-modal').val('').focus();
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
                    sub_action: 'Subject_Role.create',
                    _ajax_nonce: getLocal().nonce
                };

                $('input,select', '#add-role-modal .modal-body').each(function () {
                    if ($(this).attr('name')) {
                        if ($(this).attr('type') === 'checkbox') {
                            data[$(this).attr('name')] = $(this).is(':checked') ? true : false;
                        } else {
                            data[$(this).attr('name')] = $.trim($(this).val());
                        }
                    }
                });

                if (data.name) {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: data,
                        beforeSend: function () {
                            $(_this).text(getAAM().__('Saving...')).attr('disabled', true);
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                $('#role-list').DataTable().ajax.reload();
                                getAAM().setSubject(
                                    'role',
                                    response.role.id,
                                    response.role.name,
                                    response.role.level
                                );
                                getAAM().fetchContent('main');
                            } else {
                                getAAM().notification(
                                    'danger', response.reason
                                );
                            }
                        },
                        error: function () {
                            getAAM().notification('danger');
                        },
                        complete: function () {
                            $('#add-role-modal').modal('hide');
                            $(_this).text(getAAM().__('Add role')).attr('disabled', false);
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
                $('#edit-role-slug').parent().removeClass('has-error');

                var data = {
                    action: 'aam',
                    sub_action: 'Subject_Role.edit',
                    _ajax_nonce: getLocal().nonce,
                    subject: 'role',
                    subjectId: $(_this).data('role')
                };

                $('input,select', '#edit-role-modal .modal-body').each(function () {
                    if ($(this).attr('name')) {
                        if ($(this).attr('type') === 'checkbox') {
                            data[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
                        } else {
                            data[$(this).attr('name')] = $.trim($(this).val());
                        }
                    }
                });

                if (data.name) {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: data,
                        beforeSend: function () {
                            $(_this).text(getAAM().__('Saving...')).attr('disabled', true);
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                location.reload();
                            } else {
                                getAAM().notification(
                                    'danger', getAAM().__('Failed to update role')
                                );
                            }
                        },
                        error: function () {
                            getAAM().notification('danger');
                        },
                        complete: function () {
                            $('#edit-role-modal').modal('hide');
                            $(_this).text(getAAM().__('Update')).attr('disabled', false);
                        }
                    });
                } else {
                    $('#edit-role-name').focus().parent().addClass('has-error');
                }
            });

            //edit role button
            $('#delete-role-btn').bind('click', function () {
                var _this = this;

                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_Role.delete',
                        _ajax_nonce: getLocal().nonce,
                        subject: 'role',
                        subjectId: $(_this).data('role')
                    },
                    beforeSend: function () {
                        $(_this).text(getAAM().__('Deleting...')).attr('disabled', true);
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            var subject = getAAM().getSubject();

                            // Bug fix https://github.com/aamplugin/advanced-access-manager/issues/102
                            if (subject.type === 'role' && subject.id === $(_this).data('role')) {
                                window.localStorage.removeItem('aam-subject');
                                location.reload();
                            } else {
                                $('#role-list').DataTable().ajax.reload();
                            }
                        } else {
                            getAAM().notification('danger', getAAM().__('Failed to delete role'));
                        }
                    },
                    error: function () {
                        getAAM().notification('danger');
                    },
                    complete: function () {
                        $('#delete-role-modal').modal('hide');
                        $(_this).text(getAAM().__('Delete role')).attr('disabled', false);
                    }
                });
            });

            //add setSubject hook
            getAAM().addHook('setSubject', function () {
                //clear highlight
                $('tbody tr', '#role-list').each(function () {
                    if ($('strong', $(this)).length) {
                        var highlight = $('strong', $(this));
                        $('.icon-cog', $(this)).toggleClass('text-muted text-info');
                        $('.icon-cog', $(this)).prop('disabled', false);
                        highlight.replaceWith($('<span/>').text(highlight.text()));
                    }
                });
            });

            //in case interface needed to be reloaded
            getAAM().addHook('refresh', function () {
                $('#role-list').DataTable().ajax.url(getLocal().ajaxurl).load();
                getAAM().fetchContent('main');
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
                var subject = getAAM().getSubject();

                return (!getAAM().isUI('principal') && subject.type === 'user' && parseInt(subject.id) === id);
            }

            /**
             *
             * @param {type} id
             * @param {type} btn
             * @returns {undefined}
             */
            function blockUser(id, btn) {
                var state = ($(btn).hasClass('icon-lock') ? 0 : 1);

                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        // TODO: Refactor and move this to the SecureLogin service
                        sub_action: 'Service_SecureLogin.toggleUserStatus',
                        _ajax_nonce: getLocal().nonce,
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
                                    'title': getAAM().__('Unlock user'),
                                    'data-original-title': getAAM().__('Unlock user')
                                });
                            } else {
                                $(btn).attr({
                                    'class': 'aam-row-action icon-lock-open-alt text-warning',
                                    'title': getAAM().__('Lock user'),
                                    'data-original-title': getAAM().__('Lock user')
                                });
                            }
                        } else {
                            getAAM().notification('danger', getAAM().__('Failed to lock user'));
                        }
                    },
                    error: function () {
                        getAAM().notification('danger');
                    }
                });
            }

            /**
             *
             * @param {*} expires
             * @param {*} action
             */
            function generateJWT() {
                if ($('#login-url-preview').length === 1) {
                    // Build the trigger
                    var trigger = {
                        action: $('#action-after-expiration').val()
                    }

                    if (trigger.action === 'change-role') {
                        trigger.meta = $('#expiration-change-role').val();
                    }

                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_Jwt.generate',
                            _ajax_nonce: getLocal().nonce,
                            subject: 'user',
                            subjectId: $('#reset-user-expiration-btn').attr('data-user-id'),
                            expires: $('#user-expires').val(),
                            trigger: trigger,
                            register: true
                        },
                        beforeSend: function () {
                            $('#login-url-preview').val(getAAM().__('Generating URL...'));
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                $('#login-url-preview').val(
                                    $('#login-url-preview').data('url').replace('%s', response.jwt)
                                );
                                $('#login-jwt').val(response.jwt);
                            } else {
                                getAAM().notification(
                                    'danger', getAAM().__('Failed to generate JWT token')
                                );
                            }
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
                }
            }

            //initialize the user list table
            $('#user-list').DataTable({
                autoWidth: false,
                ordering: true,
                dom: 'ftrip',
                stateSave: true,
                pagingType: 'simple',
                serverSide: true,
                processing: true,
                ajax: {
                    url: getLocal().ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: function (params) {
                        params.action = 'aam';
                        params.sub_action = 'Subject_User.getTable';
                        params._ajax_nonce = getLocal().nonce;
                        params.role = $('#user-list-filter').val();
                        params.subject = getAAM().getSubject().type;
                        params.subjectId = getAAM().getSubject().id;
                        params.ui = getLocal().ui;
                        params.policyId = $('#aam-policy-id').val();

                        return params;
                    }
                },
                columnDefs: [
                    { visible: false, targets: [0, 1, 4, 5] },
                    { orderable: false, targets: [0, 1, 3, 4, 5] }
                ],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: getAAM().__('Search user'),
                    info: getAAM().__('_TOTAL_ user(s)'),
                    infoFiltered: ''
                },
                initComplete: function () {
                    if (getAAM().isUI('main') && getLocal().caps.create_users) {
                        var create = $('<a/>', {
                            'href': '#',
                            'class': 'btn btn-primary'
                        })
                            .html('<i class="icon-plus"></i> ')
                            .bind('click', function () {
                                window.open(getLocal().url.addUser, '_blank');
                            })
                            .attr({
                                'data-toggle': "tooltip",
                                'title': getAAM().__('Create New User')
                            });

                        $('.dataTables_filter', '#user-list_wrapper').append(create);

                        var filter = $('<select>').attr({
                            'class': 'user-filter form-control',
                            'id': 'user-list-filter'
                        })
                            .html('<option value="">' + getAAM().__('Loading roles...') + '</option>')
                            .bind('change', function () {
                                $('#user-list').DataTable().ajax.reload();
                            });

                        $('.dataTables_filter', '#user-list_wrapper').append(filter);

                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Subject_Role.getList',
                                _ajax_nonce: getLocal().nonce
                            },
                            success: function (response) {
                                $('#user-list-filter').html(
                                    '<option value="">' + getAAM().__('Filter by role') + '</option>'
                                );
                                for (var i in response) {
                                    $('#user-list-filter').append(
                                        '<option value="' + i + '">' + response[i].name + '</option>'
                                    );
                                }
                            }
                        });
                    }
                },
                createdRow: function (row, data) {
                    if (isCurrent(data[0])) {
                        $('td:eq(0)', row).html('<strong class="aam-highlight">' + data[2] + '</strong>');
                    } else {
                        $('td:eq(0)', row).html('<span>' + data[2] + '</span>');
                    }

                    //add subtitle
                    var expire = (data[5] ? '; <i class="icon-clock text-danger"></i>' : '');
                    var role   = (data[1] ? `${getAAM().__('Role')}: <b>${data[1]}</b>; ` : '');
                    $('td:eq(0)', row).append(
                        $('<i/>', { 'class': 'aam-row-subtitle' }).html(
                            `${role}${getAAM().__('ID')}: <b>${data[0]}</b> ${expire}`
                        )
                    );

                    var actions = data[3].split(',');
                    var container = $('<div/>', { 'class': 'aam-row-actions' });

                    if ($.trim(data[3])) {
                        $.each(actions, function (i, action) {
                            switch (action) {
                                case 'manage':
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-cog ' + (isCurrent(data[0]) ? 'text-muted' : 'text-primary')
                                    }).bind('click', function () {
                                        if (!$(this).prop('disabled')) {
                                            $(this).prop('disabled', true);
                                            getAAM().setSubject('user', data[0], data[2], data[4]);
                                            $('td:eq(0) span', row).replaceWith(
                                                '<strong class="aam-highlight">' + data[2] + '</strong>'
                                            );

                                            $('i.icon-cog', container).attr(
                                                'class', 'aam-row-action icon-spin4 animate-spin'
                                            );

                                            if (getAAM().isUI('main')) {
                                                getAAM().fetchContent('main');

                                                $('i.icon-spin4', container).attr(
                                                    'class', 'aam-row-action icon-cog text-muted'
                                                );
                                            } else if (getAAM().isUI('post')) {
                                                getAAM().fetchPartial('post-access-form', function (content) {
                                                    $('#metabox-post-access-form').html(content);
                                                    getAAM().triggerHook('load-access-form', [
                                                        $('#content-object-type').val(),
                                                        $('#content-object-id').val(),
                                                        $(this)
                                                    ]);

                                                    $('i.icon-spin4', container).attr(
                                                        'class', 'aam-row-action icon-cog text-muted'
                                                    );
                                                });
                                            }
                                        }
                                    }).attr({
                                        'data-toggle': "tooltip",
                                        'title': getAAM().__('Manage user')
                                    })).prop('disabled', (isCurrent(data[0]) ? true : false));
                                    break;

                                case 'edit':
                                    if (getAAM().isUI('main')) {
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-warning'
                                        }).bind('click', function () {
                                            // Update user's edit profile
                                            $('#edit-user-link').attr(
                                                'href',
                                                getLocal().url.editUser + '?user_id=' + data[0]
                                            );

                                            $('#edit-user-expiration-btn').attr('data-user-id', data[0]);
                                            $('#reset-user-expiration-btn').attr('data-user-id', data[0]);

                                            if (data[5]) {
                                                $('#reset-user-expiration-btn').removeClass('hidden');
                                                var settings = data[5].split('|');
                                                $('#user-expires').val(settings[0]);
                                                $('#action-after-expiration').val(settings[1]);

                                                if (settings[1] === 'change-role') {
                                                    $('#expiration-change-role-holder').removeClass('hidden');
                                                    getAAM().loadRoleList(settings[2]);
                                                } else {
                                                    getAAM().loadRoleList();
                                                    $('#expiration-change-role-holder').addClass('hidden');
                                                }

                                                // set JWT if defined
                                                if (settings.length === 4) {
                                                    $('#login-url-preview').val(
                                                        $('#login-url-preview').data('url').replace('%s', settings[3])
                                                    );
                                                    $('#login-jwt').val(settings[3]);
                                                }
                                            } else {
                                                $('#reset-user-expiration-btn, #expiration-change-role-holder').addClass('hidden');
                                                $('#user-expires, #action-after-expiration, #login-url-preview, #login-url').val('');
                                                getAAM().loadRoleList();
                                            }

                                            $('#edit-user-modal').modal('show');

                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Edit user')
                                        }));
                                    }
                                    break;

                                case 'no-edit':
                                    if (getAAM().isUI('main')) {
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-muted'
                                        }));
                                    }
                                    break;

                                case 'lock':
                                    if (getAAM().isUI('main')) {
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-lock-open-alt text-warning'
                                        }).bind('click', function () {
                                            blockUser(data[0], $(this));
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Lock user')
                                        }));
                                    }
                                    break;

                                case 'no-lock':
                                    if (getAAM().isUI('main')) {
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-lock-open-alt text-muted'
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Lock user')
                                        }));
                                    }
                                    break;

                                case 'unlock':
                                    if (getAAM().isUI('main')) {
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-lock text-danger'
                                        }).bind('click', function () {
                                            blockUser(data[0], $(this));
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Unlock user')
                                        }));
                                    }
                                    break;

                                case 'no-unlock':
                                        if (getAAM().isUI('main')) {
                                            $(container).append($('<i/>', {
                                                'class': 'aam-row-action icon-lock text-muted'
                                            }).attr({
                                                'data-toggle': "tooltip",
                                                'title': getAAM().__('Unlock user')
                                            }));
                                        }
                                        break;

                                case 'attach':
                                    if (getAAM().isUI('principal')) {
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-check-empty'
                                        }).bind('click', function () {
                                            getAAM().applyPolicy(
                                                {
                                                    type: 'user',
                                                    id: data[0]
                                                },
                                                $('#aam-policy-id').val(),
                                                1,
                                                this
                                            );
                                        }));
                                    }
                                    break;

                                case 'detach':
                                    if (getAAM().isUI('principal')) {
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-check text-success'
                                        }).bind('click', function () {
                                            getAAM().applyPolicy(
                                                {
                                                    type: 'user',
                                                    id: data[0]
                                                },
                                                $('#aam-policy-id').val(),
                                                0,
                                                this
                                            );
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

            $('#action-after-expiration').bind('change', function () {
                if ($(this).val() === 'change-role') {
                    $('#expiration-change-role-holder').removeClass('hidden');
                } else {
                    $('#expiration-change-role-holder').addClass('hidden');
                }
            });

            $('#request-login-url').bind('click', function () {
                generateJWT();
            });

            $('#user-expiration-datapicker').datetimepicker({
                icons: {
                    time: "icon-clock",
                    date: "icon-calendar",
                    up: "icon-angle-up",
                    down: "icon-angle-down",
                    previous: "icon-angle-left",
                    next: "icon-angle-right"
                },
                inline: true,
                minDate: new Date(),
                sideBySide: true
            });

            $('#edit-user-modal').on('show.bs.modal', function () {
                try {
                    if ($.trim($('#user-expires').val())) {
                        $('#user-expiration-datapicker').data('DateTimePicker').defaultDate(
                            new Date($('#user-expires').val())
                        );
                    } else {
                        var tomorrow = new Date();
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        $('#user-expiration-datapicker').data('DateTimePicker').defaultDate(
                            tomorrow
                        );
                    }
                } catch (e) {
                    // do nothing. Prevent from any kind of corrupted data
                }
            });

            $('#user-expiration-datapicker').on('dp.change', function (res) {
                $('#user-expires').val(res.date.unix());
            });

            //edit role button
            $('#edit-user-expiration-btn').bind('click', function () {
                var _this = this;

                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_User.saveExpiration',
                        _ajax_nonce: getLocal().nonce,
                        user: $(_this).attr('data-user-id'),
                        expires: $('#user-expires').val(),
                        after: $('#action-after-expiration').val(),
                        role: $('#expiration-change-role').val(),
                        jwt: $('#login-jwt').val()
                    },
                    beforeSend: function () {
                        $(_this).text(getAAM().__('Saving...')).attr('disabled', true);
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#user-list').DataTable().ajax.reload();
                        } else {
                            getAAM().notification('danger', response.reason);
                        }
                    },
                    error: function () {
                        getAAM().notification('danger');
                    },
                    complete: function () {
                        $('#edit-user-modal').modal('hide');
                        $(_this).text(getAAM().__('Save')).attr('disabled', false);
                    }
                });
            });

            //reset user button
            $('#reset-user-expiration-btn').bind('click', function () {
                var _this = this;

                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_User.resetExpiration',
                        _ajax_nonce: getLocal().nonce,
                        user: $(_this).attr('data-user-id')
                    },
                    beforeSend: function () {
                        $(_this).text(getAAM().__('Resetting...')).attr('disabled', true);
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#user-list').DataTable().ajax.reload();
                        } else {
                            getAAM().notification('danger', response.reason);
                        }
                    },
                    error: function () {
                        getAAM().notification('danger');
                    },
                    complete: function () {
                        $('#edit-user-modal').modal('hide');
                        $(_this).text(getAAM().__('Reset')).attr('disabled', false);
                    }
                });
            });

            //add setSubject hook
            getAAM().addHook('setSubject', function () {
                //clear highlight
                $('tbody tr', '#user-list').each(function () {
                    if ($('strong', $(this)).length) {
                        var highlight = $('strong', $(this));
                        $('.icon-cog', $(this)).toggleClass('text-muted text-info');
                        $('.icon-cog', $(this)).prop('disabled', false);
                        highlight.replaceWith('<span>' + highlight.text() + '</span>');
                    }
                });
            });

            //in case interface needed to be reloaded
            getAAM().addHook('refresh', function () {
                $('#user-list').DataTable().ajax.url(getLocal().ajaxurl).load();
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

            $('document').ready(function () {
                $('#manage-visitor').bind('click', function () {
                    var _this = this;

                    getAAM().setSubject('visitor', null, getAAM().__('Anonymous'), 0);
                    $('i.icon-cog', $(this)).attr('class', 'icon-spin4 animate-spin');

                    if (getAAM().isUI('main')) {
                        getAAM().fetchContent('main');
                        $('i.icon-spin4', $(this)).attr('class', 'icon-cog');
                    } else if (getAAM().isUI('post')) {
                        getAAM().fetchPartial('post-access-form', function (content) {
                            $('#metabox-post-access-form').html(content);

                            getAAM().triggerHook('load-access-form', [
                                $('#content-object-type').val(),
                                $('#content-object-id').val(),
                                null,
                                function () {
                                    $('i.icon-spin4', $(_this)).attr('class', 'icon-cog');
                                }
                            ]);
                        });
                    }
                });

                $('#attach-policy-visitor').bind('click', function () {
                    var has = parseInt($(this).attr('data-has')) ? true : false;
                    var effect = (has ? 0 : 1);
                    var btn = $(this);

                    btn.text(getAAM().__('Processing...'));

                    getAAM().applyPolicy(
                        {
                            type: 'visitor'
                        },
                        $('#aam-policy-id').val(),
                        effect,
                        function (response) {
                            if (response.status === 'success') {
                                if (effect) {
                                    btn.text(getAAM().__('Detach Policy From Visitors'));
                                } else {
                                    btn.text(getAAM().__('Attach Policy To Visitors'));
                                }
                                btn.attr('data-has', effect);
                            } else {
                                getAAM().notification(
                                    'danger',
                                    getAAM().__('Failed to apply policy changes')
                                );
                                if (effect) {
                                    btn.text(getAAM().__('Attach Policy To Visitors'));
                                } else {
                                    btn.text(getAAM().__('Detach Policy From Visitors'));
                                }
                            }
                        }
                    );
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

            $('document').ready(function () {
                $('#manage-default').bind('click', function () {
                    var _this = this;

                    getAAM().setSubject(
                        'default', null, getAAM().__('All Users, Roles and Visitor'), 0
                    );

                    $('i.icon-cog', $(this)).attr('class', 'icon-spin4 animate-spin');
                    if (getAAM().isUI('main')) {
                        getAAM().fetchContent('main');
                        $('i.icon-spin4', $(this)).attr('class', 'icon-cog');
                    } else if (getAAM().isUI('post')) {
                        getAAM().fetchPartial('post-access-form', function (content) {
                            $('#metabox-post-access-form').html(content);
                            getAAM().triggerHook('load-access-form', [
                                $('#content-object-type').val(),
                                $('#content-object-id').val(),
                                null,
                                function () {
                                    $('i.icon-spin4', $(_this)).attr('class', 'icon-cog');
                                }
                            ]);
                        });
                    }
                });

                $('#attach-policy-default').bind('click', function () {
                    var has = parseInt($(this).attr('data-has')) ? true : false;
                    var effect = (has ? 0 : 1);
                    var btn = $(this);

                    btn.text(getAAM().__('Processing...'));

                    getAAM().applyPolicy(
                        {
                            type: 'default'
                        },
                        $('#aam-policy-id').val(),
                        effect,
                        function (response) {
                            if (response.status === 'success') {
                                if (effect) {
                                    btn.text(getAAM().__('Detach Policy From Everybody'));
                                } else {
                                    btn.text(getAAM().__('Attach Policy To Everybody'));
                                }
                                btn.attr('data-has', effect);
                            } else {
                                getAAM().notification(
                                    'danger',
                                    getAAM().__('Failed to apply policy changes')
                                );
                                if (effect) {
                                    btn.text(getAAM().__('Attach Policy To Everybody'));
                                } else {
                                    btn.text(getAAM().__('Detach Policy From Everybody'));
                                }
                            }
                        }
                    );
                });
            });

        })(jQuery);

        /**
         * Policy Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {type} subject
             * @param {type} id
             * @param {type} effect
             * @param {type} btn
             * @returns {undefined}
             */
            function save(subject, id, effect, btn) {
                $('#aam-policy-overwrite').show();

                getAAM().applyPolicy(subject, id, effect, btn);
            }

            /**
             * Delete policy
             *
             * @param {Int}  id
             */
            function deletePolicy(id, btn) {
                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Policy.delete',
                        _ajax_nonce: getLocal().nonce,
                        id: id
                    },
                    beforeSend: function () {
                        $(btn).attr('data-original', $(btn).text());
                        $(btn).text(getAAM().__('Deleting...')).attr('disabled', true);
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#policy-list').DataTable().ajax.reload();
                        } else {
                            getAAM().notification(
                                'danger'
                            );
                        }
                    },
                    error: function () {
                        getAAM().notification('danger');
                    },
                    complete: function () {
                        $('#delete-policy-modal').modal('hide');
                        $(btn).text($(btn).attr('data-original')).attr(
                            'disabled', false
                        );
                    }
                });
            }

            /**
             *
             */
            function generatePolicy(cb, create) {
                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        "Accept": "application/json"
                    },
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Policy.generate',
                        _ajax_nonce: getLocal().nonce,
                        createNewPolicy: create,
                        subject: getAAM().getSubject().type,
                        subjectId: getAAM().getSubject().id
                    },
                    beforeSend: function () {
                    },
                    success: function (response) {
                        cb(response);
                    },
                    complete: function() {
                        $('i', '#policy-generator').attr('class', 'icon-file-code')
                    }
                });
            }

            /**
             *
             * @returns {undefined}
             */
            function initialize() {
                var container = '#policy-content';

                if ($(container).length) {
                    //reset button
                    $('#policy-reset').bind('click', function () {
                        getAAM().reset('Main_Policy.reset', $(this));
                    });

                    $('#delete-policy-btn').bind('click', function() {
                        deletePolicy($(this).attr('data-id'));
                    });

                    $('#policy-list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        dom: 'ftrip',
                        pagingType: 'simple',
                        processing: true,
                        stateSave: true,
                        serverSide: false,
                        ajax: {
                            url: getLocal().ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Policy.getTable',
                                _ajax_nonce: getLocal().nonce,
                                subject: getAAM().getSubject().type,
                                subjectId: getAAM().getSubject().id
                            }
                        },
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search Policy'),
                            info: getAAM().__('_TOTAL_ Policies'),
                            infoFiltered: ''
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 3, 4] }
                        ],
                        initComplete: function () {
                            if (getLocal().caps.manage_policies) {
                                var create = $('<a/>', {
                                    'href': '#',
                                    'class': 'btn btn-sm btn-primary'
                                }).html('<i class="icon-plus"></i> ' + getAAM().__('Create'))
                                    .bind('click', function () {
                                        window.open(getLocal().url.addPolicy, '_blank');
                                    });

                                var install = $('<a/>', {
                                    'href': '#modal-install-policy',
                                    'class': 'btn btn-sm btn-success aam-outer-left-xxs',
                                    'data-toggle': 'modal'
                                }).html('<i class="icon-download-cloud"></i> ' + getAAM().__('Install'));

                                $('.dataTables_filter', '#policy-list_wrapper').append(install);
                                $('.dataTables_filter', '#policy-list_wrapper').append(create);
                            }
                        },
                        createdRow: function (row, data) {
                            var actions = data[2].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'attach':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-check-empty'
                                        }).bind('click', function () {
                                            save({
                                                type: getAAM().getSubject().type,
                                                id: getAAM().getSubject().id
                                            }, data[0], ($(this).hasClass('icon-check-empty') ? 1 : 0), this);
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Apply Policy')
                                        }));
                                        break;

                                    case 'no-attach':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-check-empty'
                                        }));
                                        break;

                                    case 'detach':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-success icon-check'
                                        }).bind('click', function () {
                                            save({
                                                type: getAAM().getSubject().type,
                                                id: getAAM().getSubject().id
                                            }, data[0], ($(this).hasClass('icon-check') ? 0 : 1), this);
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Revoke Policy')
                                        }));
                                        break;

                                    case 'no-detach':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-check'
                                        }));
                                        break;

                                    case 'edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-warning'
                                        }).bind('click', function () {
                                            window.open(data[3], '_blank');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Edit Policy')
                                        }));
                                        break;

                                    case 'no-edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-pencil'
                                        }));
                                        break;

                                    case 'delete':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-trash-empty text-danger'
                                        }).bind('click', function () {
                                            var message = $('.aam-confirm-message', '#delete-policy-modal').data('message');

                                            // replace some dynamic parts
                                            message = message.replace('%s', '<b>' + data[4] + '</b>');
                                            $('.aam-confirm-message', '#delete-policy-modal').html(message);

                                            $('#delete-policy-btn').attr('data-id', data[0]);
                                            $('#delete-policy-modal').modal('show');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Delete Policy')
                                        }));
                                        break;

                                    case 'no-delete':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-trash-empty'
                                        }));
                                        break;

                                    default:
                                        break;
                                }
                            });
                            $('td:eq(1)', row).html(container);

                            $('td:eq(0)', row).html(data[1]);
                        }
                    });

                    var policy = null;

                    function reset() {
                        $('#policy-details').addClass('aam-ghost');
                        $('#install-policy').prop('disabled', true).text(getAAM().__('Install'));
                        $('#policy-title,#policy-description,#policy-subjects').empty();
                        policy = null;
                    }

                    function buildSubject(subject, effect) {
                        var response;

                        const badge = effect ? '<span class="badge danger">apply</span>' : '<span class="badge success">exclude</span>';

                        if (subject === 'default') {
                            response = getAAM().__('Everybody') + ' ' + badge;
                        } else if (subject === 'visitor') {
                            response = getAAM().__('Visitors') + ' ' + badge;
                        } else if (subject.search('role') === 0) {
                            response = getAAM().__('Role') + ' ' + subject.substr(5) + ' ' + badge;
                        } else if (subject.search('user') === 0) {
                            const uid = subject.substr(5);

                            if (uid === 'current') {
                                response = getAAM().__('Current User') + ' ' + badge;
                            } else {
                                response = getAAM().__('User ID') + ' ' + subject.substr(5) + ' ' + badge;
                            }
                        }

                        return response;
                    }

                    $('#policy-id').bind('change', function() {
                        const id = $.trim($(this).val());

                        // Reset modal
                        reset();

                        if (id) {
                            $.ajax(`${getLocal().system.apiEndpoint}/policy/${id}`, {
                                type: 'GET',
                                dataType: 'json',
                                headers: {
                                    "Accept": "application/json"
                                },
                                success: function (response) {
                                    $('#policy-title').text(response.metadata.title);
                                    $('#policy-description').text(response.metadata.description);
                                    $('#policy-details').removeClass('aam-ghost');
                                    $('#install-policy').prop('disabled', false);

                                    var assignees = [];

                                    // Build the list if assignees
                                    $.each(response.metadata.assignee, function(i, val) {
                                        assignees.push(buildSubject(val, true));
                                    });

                                    $.each(response.metadata.override, function(i, val) {
                                        assignees.push(buildSubject(val, false));
                                    });

                                    if (assignees.length) {
                                        $('#policy-subjects').html(assignees.join(';&nbsp;'));
                                    } else {
                                        $('#policy-subjects').html(getAAM().__('Policy is not assigned to anybody'));
                                    }

                                    policy = response;
                                },
                                error: function (response) {
                                    getAAM().notification('danger', response.responseJSON.reason);
                                }
                            });
                        }
                    });

                    $('#install-policy').bind('click', function() {
                        $(this).prop('disabled', true).text(getAAM().__('Installing...'));

                        getAAM().queueRequest(function () {
                            $.ajax(getLocal().ajaxurl, {
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'aam',
                                    sub_action: 'Main_Policy.install',
                                    _ajax_nonce: getLocal().nonce,
                                    metadata: JSON.stringify(policy.metadata),
                                    'aam-policy': JSON.stringify(policy.policy)
                                },
                                success: function (response) {
                                    if (response.status === 'success') {
                                        getAAM().notification(
                                            'success',
                                            getAAM().__('Access Policy was installed successfully')
                                        );
                                        $('#policy-list').DataTable().ajax.reload();
                                        $('#modal-install-policy').modal('hide');
                                        window.open(response.redirect, '_blank');
                                    } else {
                                        getAAM().notification('danger', response.errors);
                                    }
                                },
                                error: function () {
                                    getAAM().notification('danger');
                                }
                            });
                        });
                    });

                    $('#modal-install-policy').on('shown.bs.modal', function() {
                        $('#policy-id').val('').focus();
                        reset();
                    });
                }
            }

            $('#policy-generator').tooltip({
                container: 'body'
            });

            // Generate Policy action
            $('#generate-access-policy').bind('click', function() {
                const btn = $('i', '#policy-generator');

                btn.attr('class', 'icon-spin4 animate-spin');
                generatePolicy(function(response) {
                    getAAM().downloadFile(
                        response.policy,
                        response.title + '.json',
                        'application/json'
                    )
                }, false)
            });

            // Create new Policy action
            $('#create-access-policy').bind('click', function() {
                const btn = $('i', '#policy-generator');

                btn.attr('class', 'icon-spin4 animate-spin');
                generatePolicy(function(response) {
                    window.open(response.redirect, '_blank');
                }, true)
            });

            getAAM().addHook('init', initialize);

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
             * @param {type} items
             * @param {type} status
             * @param {type} successCallback
             * @returns {undefined}
             */
            function save(items, status, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_Menu.save',
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id,
                            _ajax_nonce: getLocal().nonce,
                            items: items,
                            status: status
                        },
                        success: function (response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
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
                            var _this = $(this);
                            var status = ($('i', $(this)).hasClass('icon-eye-off') ? 1 : 0);
                            var target = _this.data('target');

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');

                            var items = new Array(_this.data('menu-id'));

                            $('input', target).each(function () {
                                $(this).attr('checked', status ? true : false);
                                items.push($(this).data('menu-id'));
                            });

                            save(items, status, function (result) {
                                if (result.status === 'success') {
                                    $('#aam-menu-overwrite').show();

                                    if (status) { //locked the menu
                                        $('.aam-inner-tab', target).append(
                                            $('<div/>', { 'class': 'aam-lock' })
                                        );
                                        _this.removeClass('btn-danger').addClass('btn-primary');
                                        _this.html('<i class="icon-eye"></i>' + getAAM().__('Show Menu'));
                                        //add menu restricted indicator
                                        var ind = $('<i/>', {
                                            'class': 'aam-panel-title-icon icon-eye-off text-danger'
                                        });
                                        $('.panel-title', target + '-heading').append(ind);
                                    } else {
                                        $('.aam-lock', target).remove();
                                        _this.removeClass('btn-primary').addClass('btn-danger');
                                        _this.html(
                                            '<i class="icon-eye-off"></i>' + getAAM().__('Restrict Menu')
                                        );
                                        $('.panel-title .icon-eye-off', target + '-heading').remove();
                                    }
                                } else {
                                    _this.attr('checked', !status);
                                }
                            });
                        });
                    });

                    $('.aam-menu-item').each(function () {
                        $(this).bind('click', function () {
                            $('#menu-item-name').html($(this).data('name'));
                            $('#menu-item-cap').html($(this).data('cap'));
                            $('#menu-item-uri').html($(this).data('uri'));
                            $('#menu-item-id').html($(this).data('id'));
                        });
                    });

                    $('input[type="checkbox"]', '#admin-menu').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);

                            save(
                                [_this.data('menu-id')],
                                _this.is(':checked') ? 1 : 0,
                                function (result) {
                                    if (result.status === 'success') {
                                        $('#aam-menu-overwrite').show();
                                        if (_this.is(':checked')) {
                                            _this.next().attr('data-original-title', getAAM().__('Uncheck to allow'));
                                        } else {
                                            _this.next().attr('data-original-title', getAAM().__('Check to restrict'));
                                        }
                                    }
                                }
                            );
                        });
                    });

                    //reset button
                    $('#menu-reset').bind('click', function () {
                        getAAM().reset('Main_Menu.reset', $(this));
                    });
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);

        /**
         * Toolbar Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {type} items
             * @param {type} status
             * @param {type} successCallback
             * @returns {undefined}
             */
            function save(items, status, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_Toolbar.save',
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id,
                            _ajax_nonce: getLocal().nonce,
                            items: items,
                            status: status
                        },
                        success: function (response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
                });
            }

            /**
             *
             * @returns {undefined}
             */
            function initialize() {
                if ($('#toolbar-content').length) {
                    $('.aam-restrict-toolbar').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);
                            var status = ($('i', $(this)).hasClass('icon-eye-off') ? 1 : 0);
                            var target = _this.data('target');

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');

                            var items = new Array(_this.data('toolbar'));

                            $('input', target).each(function () {
                                $(this).attr('checked', status ? true : false);
                                items.push($(this).data('toolbar'));
                            });

                            save(items, status, function (result) {
                                if (result.status === 'success') {
                                    $('#aam-toolbar-overwrite').show();

                                    if (status) { //locked the menu
                                        $('.aam-inner-tab', target).append(
                                            $('<div/>', { 'class': 'aam-lock' })
                                        );
                                        _this.removeClass('btn-danger').addClass('btn-primary');
                                        _this.html('<i class="icon-eye"></i>' + getAAM().__('Show Menu'));
                                        //add menu restricted indicator
                                        var ind = $('<i/>', {
                                            'class': 'aam-panel-title-icon icon-eye-off text-danger'
                                        });
                                        $('.panel-title', target + '-heading').append(ind);
                                    } else {
                                        $('.aam-lock', target).remove();
                                        _this.removeClass('btn-primary').addClass('btn-danger');
                                        _this.html(
                                            '<i class="icon-eye-off"></i>' + getAAM().__('Restrict Menu')
                                        );
                                        $('.panel-title .icon-eye-off', target + '-heading').remove();
                                    }
                                } else {
                                    _this.attr('checked', !status);
                                }
                            });
                        });
                    });

                    $('.aam-toolbar-item').each(function () {
                        $(this).bind('click', function () {
                            $('#toolbar-item-name').html($(this).data('name'));
                            $('#toolbar-item-id').html($(this).data('id'));
                            $('#toolbar-item-uri').html($(this).data('uri'));
                        });
                    });

                    //reset button
                    $('#toolbar-reset').bind('click', function () {
                        getAAM().reset('Main_Toolbar.reset', $(this));
                    });

                    $('input[type="checkbox"]', '#toolbar-list').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);
                            save(
                                [$(this).data('toolbar')],
                                $(this).is(':checked') ? 1 : 0,
                                function (result) {
                                    if (result.status === 'success') {
                                        $('#aam-toolbar-overwrite').show();

                                        if (_this.is(':checked')) {
                                            _this.next().attr('data-original-title', getAAM().__('Uncheck to show'));
                                        } else {
                                            _this.next().attr('data-original-title', getAAM().__('Check to hide'));
                                        }
                                    }
                                }
                            );
                        });
                    });
                }
            }

            getAAM().addHook('init', initialize);

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
             * @param {type} items
             * @param {type} status
             * @param {type} successCallback
             * @returns {undefined}
             */
            function save(items, status, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_Metabox.save',
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id,
                            _ajax_nonce: getLocal().nonce,
                            items: items,
                            status: status
                        },
                        success: function (response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
                });
            }

            /**
             *
             * @returns {undefined}
             */
            function getContent() {
                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'html',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Metabox.getContent',
                        _ajax_nonce: getLocal().nonce,
                        subject: getAAM().getSubject().type,
                        subjectId: getAAM().getSubject().id
                    },
                    success: function (response) {
                        $('#metabox-content').replaceWith(response);
                        $('#metabox-content').addClass('active');
                        initialize();
                    },
                    error: function () {
                        getAAM().notification('danger');
                    }
                });
            }

            /**
             *
             * @param {type} endpoints
             * @param {type} index
             * @param {type} btn
             * @returns {undefined}
             */
            function fetchData(endpoints, index, btn) {
                $.ajax(endpoints[index], {
                    type: 'GET',
                    complete: function () {
                        if (index < endpoints.length) {
                            fetchData(endpoints, index + 1, btn);
                        } else {
                            btn.attr('class', 'icon-arrows-cw');
                            getContent();
                        }
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
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Metabox.prepareInitialization',
                                _ajax_nonce: getLocal().nonce
                            },
                            beforeSend: function () {
                                $('i', '#refresh-metabox-list').attr(
                                    'class', 'icon-spin4 animate-spin'
                                );
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    fetchData(
                                        response.endpoints,
                                        0,
                                        $('i', '#refresh-metabox-list')
                                    );
                                } else {
                                    getAAM().notification(
                                        'danger',
                                        getAAM().__('Failed to retrieve mataboxes')
                                    );
                                }
                            },
                            error: function () {
                                getAAM().notification('danger');
                                $('i', '#refresh-metabox-list').attr(
                                    'class', 'icon-arrows-cw'
                                );
                            }
                        });
                    });

                    $('#init-url-btn').bind('click', function () {
                        var url = $('#init-url').val();
                        url += (url.indexOf('?') === -1 ? '?' : '&') + 'init=metabox';

                        $.ajax(url, {
                            type: 'GET',
                            beforeSend: function () {
                                $('#init-url-btn').text(getAAM().__('Processing...'));
                            },
                            complete: function () {
                                $('#init-url-btn').text(getAAM().__('Initialize'));
                                $('#init-url-modal').modal('hide');
                                getContent();
                            }
                        });
                    });

                    $('.aam-metabox-item').each(function () {
                        $(this).bind('click', function () {
                            $('#metabox-title').html($(this).data('title'));
                            $('#metabox-screen-id').html($(this).data('screen'));
                            $('#metabox-id').html($(this).data('id'));
                        });
                    });

                    //reset button
                    $('#metabox-reset').bind('click', function () {
                        getAAM().reset('Main_Metabox.reset', $(this));
                    });

                    $('input[type="checkbox"]', '#metabox-list').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);
                            save(
                                [$(this).data('metabox')],
                                $(this).is(':checked'),
                                function (result) {
                                    if (result.status === 'success') {
                                        $('#aam-metabox-overwrite').show();

                                        if (_this.is(':checked')) {
                                            _this.next().attr('data-original-title', getAAM().__('Uncheck to show'));
                                        } else {
                                            _this.next().attr('data-original-title', getAAM().__('Check to hide'));
                                        }
                                    }
                                }
                            );
                        });
                    });
                }
            }

            getAAM().addHook('init', initialize);

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
            function toggle(capability, btn) {
                var granted = $(btn).hasClass('icon-check-empty');

                //show indicator
                $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');

                getAAM().queueRequest(function () {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_Capability.save',
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id,
                            _ajax_nonce: getLocal().nonce,
                            capability: capability,
                            effect: granted
                        },
                        success: function (result) {
                            if (result.status === 'success') {
                                if (granted) {
                                    $(btn).attr('class', 'aam-row-action text-success icon-check');
                                } else {
                                    $(btn).attr('class', 'aam-row-action text-muted icon-check-empty');
                                }
                            } else {
                                if (granted) {
                                    getAAM().notification(
                                        'danger',
                                        getAAM().__('WordPress core does not allow to grant this capability')
                                    );
                                    $(btn).attr('class', 'aam-row-action text-muted icon-check-empty');
                                } else {
                                    $(btn).attr('class', 'aam-row-action text-success icon-check');
                                }
                                getAAM().notification(getAAM().__('Failed to process request'));
                            }
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
                });
            }

            /**
             * Delete capability
             *
             * @param {String}  capability
             * @param {Boolean} subjectOnly
             * @param {Object}  btn
             */
            function deleteCapability(capability, subjectOnly, btn) {
                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Capability.delete',
                        _ajax_nonce: getLocal().nonce,
                        subject: getAAM().getSubject().type,
                        subjectId: getAAM().getSubject().id,
                        capability: capability,
                        subjectOnly: subjectOnly
                    },
                    beforeSend: function () {
                        $(btn).attr('data-original', $(btn).text());
                        $(btn).text(getAAM().__('Deleting...')).attr('disabled', true);
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#capability-list').DataTable().ajax.reload();
                        } else {
                            getAAM().notification(
                                'danger', response.message
                            );
                        }
                    },
                    error: function () {
                        getAAM().notification('danger');
                    },
                    complete: function () {
                        $('#delete-capability-modal').modal('hide');
                        $(btn).text($(btn).attr('data-original')).attr(
                            'disabled', false
                        );
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
                            url: getLocal().ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Capability.getTable',
                                _ajax_nonce: getLocal().nonce,
                                subject: getAAM().getSubject().type,
                                subjectId: getAAM().getSubject().id
                            }
                        },
                        columnDefs: [
                            { visible: false, targets: [0] }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search Capability'),
                            info: getAAM().__('_TOTAL_ capability(s)'),
                            infoFiltered: '',
                            infoEmpty: getAAM().__('No capabilities'),
                            lengthMenu: '_MENU_'
                        },
                        createdRow: function (row, data) {
                            var actions = data[3].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'unchecked':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-check-empty'
                                        }).bind('click', function () {
                                            toggle(data[0], this);
                                        }));
                                        break;

                                    case 'checked':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-success icon-check'
                                        }).bind('click', function () {
                                            toggle(data[0], this);
                                        }));
                                        break;

                                    case 'no-unchecked':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-check-empty'
                                        }));
                                        break;

                                    case 'no-checked':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-check'
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

                                    case 'no-edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-muted'
                                        }));
                                        break;

                                    case 'delete':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-trash-empty text-danger'
                                        }).bind('click', function () {
                                            var message = $('.aam-confirm-message', '#delete-capability-modal').data('message');

                                            // replace some dynamic parts
                                            message = message.replace('%s', '<b>' + data[0] + '</b>');
                                            message = message.replace('%n', '<b>' + getAAM().getSubject().name + '</b>')
                                            $('.aam-confirm-message', '#delete-capability-modal').html(message);

                                            // Update delete button message
                                            var btn = $('#delete-subject-cap-btn').data('message');
                                            btn = btn.replace('%n', getAAM().getSubject().name);

                                            $('#delete-subject-cap-btn').text(btn);

                                            if (getAAM().getSubject().type !== 'role') {
                                                $('#delete-all-roles-cap-btn').hide();
                                            } else {
                                                $('#delete-all-roles-cap-btn').show();
                                            }

                                            $('#capability-id').val(data[0]);
                                            $('#delete-subject-cap-btn').attr('data-cap', data[0]);
                                            $('#delete-all-roles-cap-btn').attr('data-cap', data[0]);
                                            $('#delete-capability-modal').modal('show');
                                        }));
                                        break;

                                    case 'no-delete':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-trash-empty text-muted'
                                        }));
                                        break;

                                    default:
                                        getAAM().triggerHook('decorate-capability-row', {
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
                        $('#assign-new-capability').attr('checked', true);
                        $('#new-capability-name').focus();
                    });

                    $('#add-capability').bind('click', function () {
                        $('#add-capability-modal').modal('show');
                    });

                    $('#add-capability-btn').bind('click', function () {
                        var _this = this;

                        var capability = $.trim($('#new-capability-name').val());
                        $('#new-capability-name').parent().removeClass('has-error');
                        var assign = $('#assign-new-capability').is(':checked');

                        if (capability) {
                            $.ajax(getLocal().ajaxurl, {
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'aam',
                                    sub_action: 'Main_Capability.save',
                                    _ajax_nonce: getLocal().nonce,
                                    capability: capability,
                                    assignToMe: assign,
                                    effect: true,
                                    subject: getAAM().getSubject().type,
                                    subjectId: getAAM().getSubject().id
                                },
                                beforeSend: function () {
                                    $(_this).text(getAAM().__('Saving...')).attr('disabled', true);
                                },
                                success: function (response) {
                                    if (response.status === 'success') {
                                        $('#add-capability-modal').modal('hide');
                                        $('#capability-list').DataTable().ajax.reload();
                                    } else {
                                        getAAM().notification(
                                            'danger', getAAM().__('Failed to add new capability')
                                        );
                                    }
                                },
                                error: function () {
                                    getAAM().notification('danger');
                                },
                                complete: function () {
                                    $(_this).text(getAAM().__('Add Capability')).attr('disabled', false);
                                }
                            });
                        } else {
                            $('#new-capability-name').parent().addClass('has-error');
                        }
                    });

                    $('#update-capability-btn').bind('click', function () {
                        var btn = this;
                        var cap = $.trim($('#capability-id').val());

                        if (cap) {
                            $.ajax(getLocal().ajaxurl, {
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'aam',
                                    sub_action: 'Main_Capability.update',
                                    subject: getAAM().getSubject().type,
                                    subjectId: getAAM().getSubject().id,
                                    _ajax_nonce: getLocal().nonce,
                                    capability: $(this).attr('data-cap'),
                                    updated: cap
                                },
                                beforeSend: function () {
                                    $(btn).text(getAAM().__('Saving...')).attr('disabled', true);
                                },
                                success: function (response) {
                                    if (response.status === 'success') {
                                        $('#capability-list').DataTable().ajax.reload();
                                    } else {
                                        getAAM().notification(
                                            'danger', response.message
                                        );
                                    }
                                },
                                error: function () {
                                    getAAM().notification('danger');
                                },
                                complete: function () {
                                    $('#edit-capability-modal').modal('hide');
                                    $(btn).text(getAAM().__('Update Capability')).attr(
                                        'disabled', false
                                    );
                                }
                            });
                        }
                    });

                    $('#delete-subject-cap-btn').bind('click', function () {
                        deleteCapability($(this).attr('data-cap'), true, $(this));
                    });

                    $('#delete-all-roles-cap-btn').bind('click', function () {
                        deleteCapability($(this).attr('data-cap'), false, $(this));
                    });
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);


        /**
         * Posts & Terms Interface
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
                type: null,
                id:   null
            };

            /**
             *
             * @param {*} param
             * @param {*} value
             * @param {*} object
             * @param {*} object_id
             * @param {*} successCallback
             */
            function save(param, value, object, object_id, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_Post.save',
                            _ajax_nonce: getLocal().nonce,
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id,
                            param: param,
                            value: value,
                            object: object,
                            objectId: object_id
                        },
                        success: function (response) {
                            if (response.status === 'failure') {
                                getAAM().notification('danger', response.error);
                            } else {
                                $('#post-overwritten').removeClass('hidden');
                                //add some specific attributes to reset button
                                $('#content-reset').attr({
                                    'data-type': object,
                                    'data-id': object_id
                                });
                            }
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
                });
            }

            /**
             *
             * @param {*} object
             * @param {*} id
             * @param {*} btn
             * @param {*} callback
             */
            function loadAccessForm(object, id, btn, callback) {
                if ($.inArray(object, ['cat', 'tag']) !== -1) {
                    object = 'term';
                } else if ($.inArray(object, ['taxonomy-category', 'taxonomy-tag']) !== -1) {
                    object = 'taxonomy';
                }

                //reset the form first
                var container = $('.aam-access-form');

                //show overlay if present
                $('.aam-overlay', container).show();

                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'html',
                    data: {
                        action: 'aam',
                        sub_action: 'renderContent',
                        partial: 'post-access-form',
                        _ajax_nonce: getLocal().nonce,
                        type: object,
                        id: id,
                        subject: getAAM().getSubject().type,
                        subjectId: getAAM().getSubject().id
                    },
                    beforeSend: function () {
                        if (btn) {
                            $(btn).attr('data-class', $(btn).attr('class'));
                            $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
                        }
                    },
                    success: function (response) {
                        $('#aam-access-form-container').html(response);
                        $('#post-list_wrapper').addClass('aam-hidden');
                        container.addClass('active');

                        initializeAccessForm(object, id);

                        if (typeof callback === 'function') {
                            callback.call();
                        }
                    },
                    error: function () {
                        getAAM().notification('danger');
                    },
                    complete: function () {
                        if (btn){
                            $(btn).attr('class', $(btn).attr('data-class')).removeAttr('data-class');
                        }
                        //hide overlay if present
                        $('.aam-overlay', container).hide();
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
                }).append($('<i/>', { 'class': 'icon-angle-double-right' })).append(title);
                $('.aam-post-breadcrumb').append(level);
            }

            /**
             *
             * @param {*} object
             * @param {*} id
             */
            function initializeAccessForm(object, id) {
                // Initialize the checkbox events
                $('.aam-row-action', '#aam-access-form-container').each(function () {
                    // Initialize each access property
                    $(this).bind('click', function () {
                        var btn     = $(this);
                        var checked = !btn.hasClass('icon-check');

                        btn.attr('class', 'aam-row-action icon-spin4 animate-spin');
                        save(
                            btn.data('property'),
                            (btn.data('trigger') ? {enabled: checked} : checked),
                            object,
                            id,
                            function () {
                                loadAccessForm(object, id, null, function() {
                                    // Trigger modal to collection additional data
                                    if (btn.data('trigger') && checked) {
                                        $('#' + btn.data('trigger')).trigger('click');
                                    }
                                });
                            }
                        );
                    });
                });

                // Initialize advanced options modals (the "change" link)
                $('.advanced-post-option').each(function () {
                    $(this).bind('click', function () {
                        var container = $(this).attr('href');

                        //add attributes to the .extended-post-access-btn
                        $('.btn-save', container).attr({
                            'data-ref': $(this).attr('data-ref')
                        });
                    });
                });

                $('[data-toggle="toggle"]', '#aam-access-form-container').bootstrapToggle();

                // Initialize the Reset to default button
                $('#content-reset').bind('click', function () {
                    var type = $(this).attr('data-type');
                    var id = $(this).attr('data-id');

                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_Post.reset',
                            _ajax_nonce: getLocal().nonce,
                            type: type,
                            id: id,
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id
                        },
                        beforeSend: function () {
                            var label = $('#content-reset').text();
                            $('#content-reset').attr('data-original-label', label);
                            $('#content-reset').text(getAAM().__('Resetting...'));
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                $('#post-overwritten').addClass('hidden');
                                loadAccessForm(type, id);
                            }
                        },
                        complete: function () {
                            $('#content-reset').text(
                                $('#content-reset').attr('data-original-label')
                            );
                        }
                    });
                });

                // Initialize the "Hidden Areas" modal
                $('#save-hidden-btn').bind('click', function() {
                    $(this).text(getAAM().__('Saving...'));

                    save(
                        $(this).attr('data-ref'),
                        {
                            enabled: true,
                            frontend: $('#hidden-frontend').prop('checked'),
                            backend: $('#hidden-backend').prop('checked'),
                            api: $('#hidden-api').prop('checked')
                        },
                        object,
                        id,
                        function () {
                            $('#modal-hidden').modal('hide');
                            loadAccessForm(object, id);
                        }
                    );
                });

                // Initialize the "Teaser Message" modal
                $('#save-teaser-btn').bind('click', function() {
                    $(this).text(getAAM().__('Saving...'));

                    save(
                        $(this).attr('data-ref'),
                        {
                            enabled: true,
                            message: $('#aam-teaser-message').val()
                        },
                        object,
                        id,
                        function () {
                            $('#modal-teaser').modal('hide');
                            loadAccessForm(object, id);
                        }
                    );
                });

                // Initialize the "Limited Access" modal
                $('#save-limited-btn').bind('click', function() {
                    $(this).text(getAAM().__('Saving...'));

                    save(
                        $(this).attr('data-ref'),
                        {
                            enabled: true,
                            threshold: $('#aam-access-threshold').val()
                        },
                        object,
                        id,
                        function () {
                            $('#modal-limited').modal('hide');
                            loadAccessForm(object, id);
                        }
                    );
                });

                // Reset LIMIT counter
                $('#reset-limited-btn').bind('click', function() {
                    getAAM().queueRequest(function () {
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Post.resetCounter',
                                _ajax_nonce: getLocal().nonce,
                                subject: getAAM().getSubject().type,
                                subjectId: getAAM().getSubject().id,
                                object: object,
                                objectId: id
                            },
                            beforeSend: function() {
                                $('#reset-limited-btn').text(
                                    getAAM().__('Resetting...')
                                ).attr('disabled', true);
                            },
                            success: function (response) {
                                if (response.status === 'failure') {
                                    getAAM().notification('danger', response.error);
                                } else {
                                    getAAM().notification(
                                        'success',
                                        getAAM().__('Counter was reset successfully')
                                    );
                                    $('#modal-limited').modal('hide');
                                    loadAccessForm(object, id);
                                }
                            },
                            error: function () {
                                getAAM().notification('danger');
                            },
                            complete: function() {
                                $('#reset-limited-btn').text(
                                    getAAM().__('Reset')
                                ).attr('disabled', false);
                            }
                        });
                    });
                });

                // Initialize the "Access Redirect" modal
                $('.post-redirect-type').each(function () {
                    $(this).bind('click', function () {
                        $('.post-redirect-value').hide();
                        $(`#post-redirect-${$(this).val()}-value-container`).show();
                        $('#post-redirect-code-value-container').show();
                    });
                });

                $('#save-redirect-btn').bind('click', function() {
                    $(this).text(getAAM().__('Saving...'));
                    const type = $('.post-redirect-type:checked').val();

                    save(
                        $(this).attr('data-ref'),
                        {
                            enabled: true,
                            type: type,
                            destination: $(`#post-redirect-${type}-value`).val(),
                            httpCode: $('#post-redirect-code-value').val()
                        },
                        object,
                        id,
                        function () {
                            $('#modal-redirect').modal('hide');
                            loadAccessForm(object, id);
                        }
                    );
                });

                // Initialize the "Password Protected" modal
                $('#save-password-btn').bind('click', function() {
                    $(this).text(getAAM().__('Saving...'));

                    save(
                        $(this).attr('data-ref'),
                        {
                            enabled: true,
                            password: $('#aam-access-password').val()
                        },
                        object,
                        id,
                        function () {
                            $('#modal-password').modal('hide');
                            loadAccessForm(object, id);
                        }
                    );
                });

                // Initialize the "Ceased" modal
                $('#save-ceased-btn').bind('click', function() {
                    $(this).text(getAAM().__('Saving...'));

                    save(
                        $(this).attr('data-ref'),
                        {
                            enabled: true,
                            after: $('#aam-expire-datetime').val()
                        },
                        object,
                        id,
                        function () {
                            $('#modal-cease').modal('hide');
                            loadAccessForm(object, id);
                        }
                    );
                });

                const def = $('#aam-expire-datetime').val();

                $('#post-expiration-datapicker').datetimepicker({
                    icons: {
                        time: "icon-clock",
                        date: "icon-calendar",
                        up: "icon-angle-up",
                        down: "icon-angle-down",
                        previous: "icon-angle-left",
                        next: "icon-angle-right"
                    },
                    inline: true,
                    defaultDate: $.trim(def) ? new Date(def * 1000) : new Date(),
                    sideBySide: true
                });
                $('#post-expiration-datapicker').on('dp.change', function (res) {
                    $('#aam-expire-datetime').val(res.date.unix());
                });

                getAAM().triggerHook('init-access-form');
            }

            getAAM().addHook('load-access-form', function(params) {
                loadAccessForm(...params);
            });

            getAAM().addHook('save-post-settings', function(params) {
                save(...params);
            });

            /**
             *
             */
            function initialize() {
                if ($('#post-content').length) {
                    //reset filter to default
                    filter.type = null;
                    filter.id = null;

                    //initialize the role list table
                    $('#post-list').DataTable({
                        autoWidth: false,
                        ordering: true,
                        pagingType: 'simple',
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: getLocal().ajaxurl,
                            type: 'POST',
                            data: function (data) {
                                data.action = 'aam';
                                data.sub_action = 'Main_Post.getTable';
                                data._ajax_nonce = getLocal().nonce;
                                data.subject = getAAM().getSubject().type;
                                data.subjectId = getAAM().getSubject().id;
                                data.type = filter.type;
                                data.typeId = filter.id;
                            }
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 1, 5, 6, 7] },
                            { orderable: false, targets: [0, 1, 2, 4, 5, 6] }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ object(s)'),
                            infoFiltered: '',
                            lengthMenu: '_MENU_'
                        },
                        initComplete: function () {
                            $('#post-list_filter .form-control').bind('change', function () {
                                if ($(this).val()) {
                                    $(this).addClass('highlight');
                                } else {
                                    $(this).removeClass('highlight');
                                }
                            });
                        },
                        rowCallback: function (row, data) {
                            // Object type icon
                            var icon = 'icon-doc-text-inv';
                            var tooltip = getAAM().__('Post');

                            switch (data[2]) {
                                case 'type':
                                    icon = 'icon-box';
                                    tooltip = getAAM().__('Post Type');
                                    break;

                                case 'taxonomy-category':
                                    icon = 'icon-folder';
                                    tooltip = getAAM().__('Hierarchical Taxonomy');
                                    break;

                                case 'cat':
                                    icon = 'icon-folder';
                                    tooltip = getAAM().__('Hierarchical Term');
                                    break;

                                case 'taxonomy-tag':
                                    icon = 'icon-tag';
                                    tooltip = getAAM().__('Tag Taxonomy');
                                    break;

                                case 'tag':
                                    icon = 'icon-tag';
                                    tooltip = getAAM().__('Tag');
                                    break;

                                default:
                                    break;
                            }

                            if (data[6]) {
                                $('td:eq(0)', row).html($('<i/>', {
                                    'class': icon + ' aam-access-overwritten',
                                    'data-toggle': "tooltip",
                                    'title': getAAM().__('Customized Settings')
                                }));
                            } else {
                                $('td:eq(0)', row).html($('<i/>', {
                                    'class': icon,
                                    'data-toggle': "tooltip",
                                    'title': tooltip
                                }));
                            }

                            // Update the title to a link
                            if (data[2] === 'type') {
                                $('td:eq(1)', row).html($('<a/>', {
                                    href: '#'
                                }).bind('click', function () {
                                    //visual feedback - show loading icon
                                    $('td:eq(0)', row).html(
                                        '<i class="icon-spin4 animate-spin"></i>'
                                    );
                                    //set filter
                                    filter.type = 'type';
                                    filter.id = data[0];

                                    //finally reload the data
                                    $('#post-list').DataTable().search('');
                                    $('#post-list').DataTable().ajax.reload();

                                    //update the breadcrumb
                                    addBreadcrumbLevel('type', data[0], data[3]);
                                }).html(data[3]));
                                $('td:eq(1)', row).append('<sup> ' + getAAM().__('post type') + '</sup>');

                                // Add additional information about post type
                                $('td:eq(1)', row).append(
                                    $('<i class="aam-row-subtitle"></i>')
                                    .append($('<span/>').text(getAAM().__('Slug:') + ' '))
                                    .append($('<strong/>').text(data[0]))
                                );
                            } else if ($.inArray(data[2], ['taxonomy-category', 'taxonomy-tag']) !== -1) {
                                $('td:eq(1)', row).html($('<a/>', {
                                    href: '#'
                                }).bind('click', function () {
                                    //visual feedback - show loading icon
                                    $('td:eq(0)', row).html(
                                        '<i class="icon-spin4 animate-spin"></i>'
                                    );
                                    //set filter
                                    filter.type = 'taxonomy';
                                    filter.id = data[0];

                                    //finally reload the data
                                    $('#post-list').DataTable().search('');
                                    $('#post-list').DataTable().ajax.reload();

                                    //update the breadcrumb
                                    addBreadcrumbLevel('taxonomy', data[0], data[3]);
                                }).html(data[3]));
                                $('td:eq(1)', row).append('<sup> ' + getAAM().__('taxonomy') + '</sup>');

                                $('td:eq(1)', row).append(
                                    $('<i class="aam-row-subtitle"></i>')
                                    .append($('<span/>').text(getAAM().__('Slug:') + ' '))
                                    .append($('<strong/>').text(data[0]))
                                );
                            } else if (data[2] === 'cat') {
                                $('td:eq(1)', row).html($('<span/>').text(data[3]));

                                let sub = $('<i class="aam-row-subtitle"></i>');

                                if (data[5]) {
                                    sub.append($('<span/>').text(getAAM().__('Parent') + ': '));
                                    sub.append($('<strong/>').text(data[5] + '; '));
                                }

                                sub.append($('<span/>').text(getAAM().__('ID:') + ' '));
                                sub.append($('<strong/>').text(data[0].split('|')[0] + '; '));
                                sub.append($('<span/>').text(getAAM().__('Slug:') + ' '));
                                sub.append($('<strong/>').text(data[7]));

                                $('td:eq(1)', row).append(sub);
                            } else if (data[2] === 'tag') {
                                $('td:eq(1)', row).html($('<span/>').text(data[3]));

                                $('td:eq(1)', row).append(
                                    $('<i class="aam-row-subtitle"></i>')
                                    .append($('<span/>').text(getAAM().__('ID:') + ' '))
                                    .append($('<strong/>').text(data[0].split('|')[0] + '; '))
                                    .append($('<span/>').text(getAAM().__('Slug:') + ' '))
                                    .append($('<strong/>').text(data[7]))
                                );
                            } else {
                                $('td:eq(1)', row).html($('<span/>').text(data[3]));

                                let sub = $('<i class="aam-row-subtitle"></i>');

                                if (data[5]) {
                                    sub.append($('<span/>').text(getAAM().__('Parent') + ': '));
                                    sub.append($('<strong/>').text(data[5] + '; '));
                                }

                                sub.append($('<span/>').text(getAAM().__('ID:') + ' '));
                                sub.append($('<strong/>').text(data[0] + '; '));
                                sub.append($('<span/>').text(getAAM().__('Slug:') + ' '));
                                sub.append($('<strong/>').text(data[7]));

                                $('td:eq(1)', row).append(sub);
                            }

                            //update the actions
                            var actions = data[4].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'drilldown':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-success icon-level-down'
                                        }).bind('click', function () {
                                            $('td:eq(1) > a', row).trigger('click');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Drill-Down')
                                        }));
                                        $('.tooltip').remove();
                                        break;

                                    case 'manage':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-info icon-cog'
                                        }).bind('click', function () {
                                            loadAccessForm(data[2], data[0], $(this), function () {
                                                addBreadcrumbLevel('edit', data[2], data[3]);
                                            });
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Manage Access')
                                        }));
                                        $('.tooltip').remove();
                                        break;

                                    case 'edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-warning icon-pencil'
                                        }).bind('click', function () {
                                            window.open(data[1], '_blank');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Edit')
                                        }));
                                        break;

                                    case 'no-edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-pencil'
                                        }));
                                        break;

                                    default:
                                        getAAM().triggerHook('post-action', {
                                            container: container,
                                            action: action,
                                            data: data
                                        });
                                        break;
                                }
                            });
                            $('td:eq(2)', row).html(container);
                        }
                    });

                    //initialize the breadcrumb
                    $('.aam-post-breadcrumb').delegate('a', 'click', function () {
                        filter.type = $(this).data('level');
                        filter.id   = $(this).data('id');

                        $('#post-list').DataTable().ajax.reload();
                        $(this).nextAll().remove();
                        $('.aam-slide-form').removeClass('active');
                        $('#post-list_wrapper').removeClass('aam-hidden');
                        $('#post-overwritten').addClass('hidden');
                    });

                    //go back button
                    $('.aam-slide-form').delegate('.post-back', 'click', function () {
                        $('.aam-slide-form').removeClass('active');
                        $('#post-list_wrapper').removeClass('aam-hidden');
                        $('.aam-post-breadcrumb span:last').remove();
                        $('#post-term--overwritten').addClass('hidden');
                    });
                }

                if ($('#aam-access-form-container').is(':empty') === false) {
                    if ($('#content-object-type').val()) {
                        loadAccessForm(
                            $('#content-object-type').val(),
                            $('#content-object-id').val()
                        );
                    }
                }
            }

            getAAM().addHook('init', initialize);

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
             * @param {type} items
             * @param {type} status
             * @param {type} successCallback
             * @returns {undefined}
             */
            function save(param, value, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_Redirect.save',
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id,
                            _ajax_nonce: getLocal().nonce,
                            param: param,
                            value: value
                        },
                        success: function (response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
                });
            }

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
                            save(
                                $(this).attr('name'),
                                $(this).val(),
                                function (result) {
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
                            save(
                                $(this).attr('name'),
                                $(this).val(),
                                function (result) {
                                    if (result.status === 'success') {
                                        $('#aam-redirect-overwrite').show();
                                    }
                                }
                            );
                        });
                    });

                    $('#redirect-reset').bind('click', function () {
                        getAAM().reset('Main_Redirect.reset', $(this));
                    });
                }
            }

            getAAM().addHook('init', initialize);

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
             * @param {type} items
             * @param {type} status
             * @param {type} successCallback
             * @returns {undefined}
             */
            function save(param, value, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_LoginRedirect.save',
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id,
                            _ajax_nonce: getLocal().nonce,
                            param: param,
                            value: value
                        },
                        success: function (response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
                });
            }

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
                            save(
                                $(this).attr('name'),
                                $(this).val(),
                                function (result) {
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
                            save(
                                $(this).attr('name'),
                                val,
                                function (result) {
                                    if (result.status === 'success') {
                                        $('#aam-login-redirect-overwrite').show();
                                    }
                                }
                            );
                        });
                    });

                    $('#login-redirect-reset').bind('click', function () {
                        getAAM().reset('Main_LoginRedirect.reset', $(this));
                    });
                }
            }

            getAAM().addHook('init', initialize);

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
             * @param {type} items
             * @param {type} status
             * @param {type} successCallback
             * @returns {undefined}
             */
            function save(param, value, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_LogoutRedirect.save',
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id,
                            _ajax_nonce: getLocal().nonce,
                            param: param,
                            value: value
                        },
                        success: function (response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
                });
            }

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
                            save(
                                $(this).attr('name'),
                                $(this).val(),
                                function (result) {
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
                            save(
                                $(this).attr('name'),
                                $(this).val(),
                                function (result) {
                                    if (result.status === 'success') {
                                        $('#aam-logout-redirect-overwrite').show();
                                    }
                                }
                            );
                        });
                    });

                    $('#logout-redirect-reset').bind('click', function () {
                        getAAM().reset('Main_LogoutRedirect.reset', $(this));
                    });
                }
            }

            getAAM().addHook('init', initialize);

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
            function save(param, value, cb) {
                getAAM().queueRequest(function () {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_404Redirect.save',
                            _ajax_nonce: getLocal().nonce,
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id,
                            param: param,
                            value: value
                        },
                        success: function (response) {
                            if (typeof cb === 'function') {
                                cb(response);
                            }
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
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
                            $('.404redirect-action').hide();

                            //show the specific one
                            $($(this).data('action')).show();

                            //save redirect type
                            save(
                                $(this).attr('name'),
                                $(this).val(),
                                function (result) {
                                    if (result.status === 'success') {
                                        $('#aam-404redirect-overwrite').show();
                                    }
                                }
                            );
                        });
                    });

                    $('input[type="text"],select,textarea', container).each(function () {
                        $(this).bind('change', function () {
                            //save redirect type
                            save($(this).attr('name'), $(this).val());
                        });
                    });

                    $('#404redirect-reset').bind('click', function () {
                        getAAM().reset('Main_404Redirect.reset', $(this));
                    });
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);

        /**
         * API Routes Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {type} type
             * @param {type} route
             * @param {type} method
             * @param {type} btn
             * @returns {undefined}
             */
            function save(type, route, method, btn) {
                var value = $(btn).hasClass('icon-check-empty');

                getAAM().queueRequest(function () {
                    //show indicator
                    $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');

                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Main_Route.save',
                            _ajax_nonce: getLocal().nonce,
                            subject: getAAM().getSubject().type,
                            subjectId: getAAM().getSubject().id,
                            type: type,
                            route: route,
                            method: method,
                            value: value
                        },
                        success: function (response) {
                            if (response.status === 'failure') {
                                getAAM().notification('danger', response.error);
                                updateBtn(btn, value ? 0 : 1);
                            } else {
                                $('#aam-route-overwrite').removeClass('hidden');
                                updateBtn(btn, value);
                            }
                        },
                        error: function () {
                            updateBtn(btn, value ? 0 : 1);
                            getAAM().notification('danger');
                        }
                    });
                });
            }

            /**
             *
             * @param {type} btn
             * @param {type} value
             * @returns {undefined}
             */
            function updateBtn(btn, value) {
                if (value) {
                    $(btn).attr('class', 'aam-row-action text-danger icon-check');
                } else {
                    $(btn).attr('class', 'aam-row-action text-muted icon-check-empty');
                }
            }

            /**
             *
             * @returns {undefined}
             */
            function initialize() {
                if ($('#route-content').length) {
                    //initialize the role list table
                    $('#route-list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        pagingType: 'simple',
                        serverSide: false,
                        ajax: {
                            url: getLocal().ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Route.getTable',
                                _ajax_nonce: getLocal().nonce,
                                subject: getAAM().getSubject().type,
                                subjectId: getAAM().getSubject().id
                            }
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 1] },
                            { className: 'text-center', targets: [0, 1] }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ route(s)'),
                            infoFiltered: '',
                            emptyTable: getAAM().__('No API endpoints found. You might have APIs disabled.'),
                            infoEmpty: getAAM().__('Nothing to show'),
                            lengthMenu: '_MENU_'
                        },
                        createdRow: function (row, data) {
                            // decorate the method
                            var method = $('<span/>', {
                                'class': 'aam-api-method ' + data[2].toLowerCase()
                            }).text(data[2]);

                            $('td:eq(0)', row).html(method);

                            var actions = data[4].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'unchecked':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-check-empty'
                                        }).bind('click', function () {
                                            save(data[1], data[0], data[2], this);
                                        }));
                                        break;

                                    case 'checked':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-danger icon-check'
                                        }).bind('click', function () {
                                            save(data[1], data[0], data[2], this);
                                        }));
                                        break;

                                    default:
                                        break;
                                }
                            });
                            $('td:eq(2)', row).html(container);
                        }
                    });

                    //reset button
                    $('#route-reset').bind('click', function () {
                        getAAM().reset('Main_Route.reset', $(this));
                    });
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);

        /**
         * URI Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {
            function initialize() {
                var container = '#uri-content';

                if ($(container).length) {
                    $('input[type="radio"]', container).each(function () {
                        $(this).bind('click', function () {
                            var action = $(this).data('action');

                            $('.aam-uri-access-action').hide();

                            if (action) {
                                $(action).show();
                            }

                            if ($(this).val() === 'page' || $(this).val() === 'url') {
                                $('#uri-access-deny-redirect-code').show();
                            }
                        });
                    });

                    //reset button
                    $('#uri-reset').bind('click', function () {
                        getAAM().reset('Main_Uri.reset', $(this));
                    });

                    $('#uri-save-btn').bind('click', function (event) {
                        event.preventDefault();

                        var uri  = $('#uri-rule').val();
                        var original = $(this).attr('data-original-uri');
                        var type = $('input[name="uri.access.type"]:checked').val();
                        var val  = $('#uri-access-deny-' + type + '-value').val();
                        var code = $('#uri-access-deny-redirect-code-value').val();

                        if (uri && type) {
                            $.ajax(getLocal().ajaxurl, {
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'aam',
                                    sub_action: 'Main_Uri.save',
                                    _ajax_nonce: getLocal().nonce,
                                    subject: getAAM().getSubject().type,
                                    subjectId: getAAM().getSubject().id,
                                    uri: uri,
                                    edited_uri: original,
                                    type: type,
                                    value: val,
                                    code: code
                                },
                                beforeSend: function () {
                                    $('#uri-save-btn').text(
                                        getAAM().__('Saving...')
                                    ).attr('disabled', true);
                                },
                                success: function (response) {
                                    if (response.status === 'success') {
                                        $('#uri-list').DataTable().ajax.reload();
                                        $('#aam-uri-overwrite').show();
                                    } else {
                                        getAAM().notification(
                                            'danger', getAAM().__('Failed to save URI rule')
                                        );
                                    }
                                },
                                error: function () {
                                    getAAM().notification('danger');
                                },
                                complete: function () {
                                    $('#uri-model').modal('hide');
                                    $('#uri-save-btn').text(getAAM().__('Save')).attr('disabled', false);
                                }
                            });
                        }
                    });

                    $('#uri-delete-btn').bind('click', function (event) {
                        event.preventDefault();

                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Uri.delete',
                                _ajax_nonce: getLocal().nonce,
                                subject: getAAM().getSubject().type,
                                subjectId: getAAM().getSubject().id,
                                uri: $('#uri-delete-btn').attr('data-uri')
                            },
                            beforeSend: function () {
                                $('#uri-delete-btn').text(
                                    getAAM().__('Deleting...')
                                ).attr('disabled', true);
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    $('#uri-list').DataTable().ajax.reload();
                                } else {
                                    getAAM().notification(
                                        'danger',
                                        getAAM().__('Failed to delete URI rule')
                                    );
                                }
                            },
                            error: function () {
                                getAAM().notification('danger');
                            },
                            complete: function () {
                                $('#uri-delete-model').modal('hide');
                                $('#uri-delete-btn').text(getAAM().__('Delete')).attr('disabled', false);
                            }
                        });
                    });

                    $('#uri-list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        dom: 'ftrip',
                        pagingType: 'simple',
                        processing: true,
                        stateSave: true,
                        serverSide: false,
                        ajax: {
                            url: getLocal().ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Uri.getTable',
                                _ajax_nonce: getLocal().nonce,
                                subject: getAAM().getSubject().type,
                                subjectId: getAAM().getSubject().id
                            }
                        },
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ URI(s)'),
                            infoFiltered: ''
                        },
                        columnDefs: [
                            { visible: false, targets: [2, 3] }
                        ],
                        initComplete: function () {
                            var create = $('<a/>', {
                                'href': '#',
                                'class': 'btn btn-primary'
                            }).html('<i class="icon-plus"></i> ' + getAAM().__('Create'))
                                .bind('click', function () {
                                    $('.form-clearable', '#uri-model').val('');
                                    $('.aam-uri-access-action').hide();
                                    $('#uri-save-btn').removeAttr('data-original-uri');
                                    $('input[type="radio"]', '#uri-model').prop('checked', false);
                                    $('#uri-model').modal('show');
                                });

                            $('.dataTables_filter', '#uri-list_wrapper').append(create);
                        },
                        createdRow: function (row, data) {
                            var actions = data[4].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-warning'
                                        }).bind('click', function () {
                                            $('.form-clearable', '#uri-model').val('');
                                            $('.aam-uri-access-action').hide();
                                            $('#uri-rule').val(data[0]);
                                            $('#uri-save-btn').attr('data-original-uri', data[0]);
                                            $('input[value="' + data[1] + '"]', '#uri-model').prop('checked', true).trigger('click');
                                            $('#uri-access-deny-' + data[1] + '-value').val(data[2]);
                                            $('#uri-access-deny-redirect-code-value').val(data[3]);
                                            $('#uri-model').modal('show');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Edit Rule')
                                        }));
                                        break;

                                    case 'no-edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-muted'
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Inherited')
                                        }));
                                        break;

                                    case 'delete':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-trash-empty text-danger'
                                        }).bind('click', function () {
                                            $('#uri-delete-btn').attr('data-uri', data[0]);
                                            $('#uri-delete-model').modal('show');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Delete Rule')
                                        }));
                                        break;

                                    case 'no-delete':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-trash-empty text-muted'
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Inherited')
                                        }));
                                        break;

                                    default:
                                        break;
                                }
                            });

                            // Decorate the type of access
                            var type = $('<span/>');

                            switch(data[1]) {
                                case 'default':
                                case 'message':
                                    type.html(getAAM().__('Denied'));
                                    type.attr('class', 'badge danger');
                                    break;

                                case 'login':
                                case 'page':
                                case 'url':
                                    type.html(getAAM().__('Redirected'));
                                    type.attr('class', 'badge redirect');
                                    break;

                                case 'callback':
                                    type.html(getAAM().__('Callback'));
                                    type.attr('class', 'badge callback');
                                    break;

                                default:
                                    type.html(getAAM().__('Allowed'));
                                    type.attr('class', 'badge success');
                                    break;
                            }

                            $('td:eq(2)', row).html(container);

                            $('td:eq(1)', row).html(type);
                        }
                    });
                }
            }

            getAAM().addHook('init', initialize);
        })(jQuery);

        /**
         * JWT Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {type} expires
             * @returns {undefined}
             */
            function generateJWT(expires, refreshable) {
                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Jwt.generate',
                        _ajax_nonce: getLocal().nonce,
                        subject: getAAM().getSubject().type,
                        subjectId: getAAM().getSubject().id,
                        expires: expires,
                        refreshable: refreshable,
                        register: false
                    },
                    beforeSend: function () {
                        $('#jwt-token-preview').val(
                            getAAM().__('Generating token...')
                        );

                        $('#jwt-url-preview').val(
                            getAAM().__('Generating URL...')
                        );
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#jwt-token-preview').val(response.jwt);
                            $('#jwt-url-preview').val(
                                $('#jwt-url-preview').data('url').replace('%s', response.jwt)
                            );
                        } else {
                            getAAM().notification(
                                'danger', getAAM().__('Failed to generate JWT token')
                            );
                        }
                    },
                    error: function () {
                        getAAM().notification('danger');
                    }
                });
            }

            /**
             *
             */
            function initialize() {
                var container = '#jwt-content';

                if ($(container).length) {
                    $('#jwt-expiration-datapicker').datetimepicker({
                        icons: {
                            time: "icon-clock",
                            date: "icon-calendar",
                            up: "icon-angle-up",
                            down: "icon-angle-down",
                            previous: "icon-angle-left",
                            next: "icon-angle-right"
                        },
                        minDate: new Date(),
                        inline: true,
                        sideBySide: true
                    });

                    $('#create-jwt-modal').on('show.bs.modal', function () {
                        try {
                            var tomorrow = new Date();
                            tomorrow.setDate(tomorrow.getDate() + 1);
                            $('#jwt-expiration-datapicker').data('DateTimePicker').defaultDate(
                                tomorrow
                            );
                            $('#jwt-expires').val('');
                        } catch (e) {
                            // do nothing. Prevent from any kind of corrupted data
                        }
                    });

                    $('#jwt-expiration-datapicker').on('dp.change', function (res) {
                        $('#jwt-expires').val(res.date.unix());
                        generateJWT(
                            $('#jwt-expires').val(),
                            $('#jwt-refreshable').is(':checked')
                        );
                    });

                    $('#jwt-refreshable').on('change', function () {
                        generateJWT(
                            $('#jwt-expires').val(),
                            $('#jwt-refreshable').is(':checked')
                        );
                    });

                    $('#jwt-list').DataTable({
                        autoWidth: false,
                        ordering: true,
                        dom: 'ftrip',
                        pagingType: 'simple',
                        processing: true,
                        stateSave: false,
                        serverSide: false,
                        ajax: {
                            url: getLocal().ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Jwt.getTable',
                                _ajax_nonce: getLocal().nonce,
                                subject: getAAM().getSubject().type,
                                subjectId: getAAM().getSubject().id
                            }
                        },
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ token(s)'),
                            infoFiltered: '',
                            emptyTable: getAAM().__('No JWT tokens have been generated.'),
                            infoEmpty: getAAM().__('Nothing to show'),
                            lengthMenu: '_MENU_'
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 1] },
                            { orderable: false, targets: [0, 1, 2, 4] }
                        ],
                        initComplete: function () {
                            var create = $('<a/>', {
                                'href': '#',
                                'class': 'btn btn-primary'
                            }).html('<i class="icon-plus"></i> ' + getAAM().__('Create'))
                                .bind('click', function () {
                                    $('#create-jwt-modal').modal('show');
                                });

                            $('.dataTables_filter', '#jwt-list_wrapper').append(create);
                        },
                        createdRow: function (row, data) {
                            // Render status
                            if (data[2] === true) {
                                $('td:eq(0)', row).html(
                                    '<i class="icon-ok-circled text-success"></i>'
                                );
                            } else {
                                $('td:eq(0)', row).html(
                                    '<i class="icon-cancel-circled text-danger"></i>'
                                );
                            }

                            var actions = data[4].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'delete':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-trash-empty text-danger'
                                        }).bind('click', function () {
                                            $('#jwt-delete-btn').attr('data-id', data[0]);
                                            $('#delete-jwt-modal').modal('show');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Delete Token')
                                        }));
                                        break;

                                    case 'view':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-eye text-success'
                                        }).bind('click', function () {
                                            $('#view-jwt-token').val(data[0]);
                                            $('#view-jwt-url').val(data[1]);
                                            $('#view-jwt-modal').modal('show');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('View Token')
                                        }));
                                        break;

                                    default:
                                        break;
                                }
                            });
                            $('td:eq(2)', row).html(container);
                        }
                    });

                    $('#create-jwt-btn').bind('click', function () {
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Jwt.save',
                                _ajax_nonce: getLocal().nonce,
                                subject: getAAM().getSubject().type,
                                subjectId: getAAM().getSubject().id,
                                token: $('#jwt-token-preview').val()
                            },
                            beforeSend: function () {
                                $('#create-jwt-btn').html(getAAM().__('Creating...'));
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    $('#create-jwt-modal').modal('hide');
                                    $('#jwt-list').DataTable().ajax.reload();
                                } else {
                                    getAAM().notification('danger', response.reason);
                                }
                            },
                            error: function () {
                                getAAM().notification('danger');
                            },
                            complete: function () {
                                $('#create-jwt-btn').html(getAAM().__('Create'));
                            }
                        });
                    });

                    $('#jwt-delete-btn').bind('click', function () {
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Jwt.delete',
                                _ajax_nonce: getLocal().nonce,
                                subject: getAAM().getSubject().type,
                                subjectId: getAAM().getSubject().id,
                                token: $('#jwt-delete-btn').attr('data-id')
                            },
                            beforeSend: function () {
                                $('#jwt-delete-btn').html(getAAM().__('Deleting...'));
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    $('#delete-jwt-modal').modal('hide');
                                    $('#jwt-list').DataTable().ajax.reload();
                                } else {
                                    getAAM().notification('danger', response.reason);
                                }
                            },
                            error: function () {
                                getAAM().notification('danger');
                            },
                            complete: function () {
                                $('#jwt-delete-btn').html(getAAM().__('Delete'));
                            }
                        });
                    });

                    $('[data-toggle="toggle"]', container).bootstrapToggle();
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);

        /**
         * Add-ons Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {*} license
             * @param {*} slug
             * @param {*} expire
             */
            function registerLicense(license, slug, expire) {
                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Addons_Manager.registerLicense',
                        _ajax_nonce: getLocal().nonce,
                        license: license,
                        slug: slug,
                        expire: expire
                    }
                });
            }

            /**
             *
             * @param {type} data
             * @param {type} cb
             * @returns {undefined}
             */
            function validateLicense(license, cb, error) {
                $.ajax(`${getLocal().system.apiEndpoint}/download/${license}`, {
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        "Accept": "application/json"
                    },
                    success: function (response) {
                        cb(response);
                    },
                    error: function (response) {
                        error(response.responseJSON);
                    }
                });
            }

            /**
             *
             * @param {*} license
             * @param {*} type
             * @param {*} cb
             * @param {*} error
             */
            function registerDomain(license, type, cb, error) {
                $.ajax(`${getLocal().system.apiEndpoint}/register/${license}`, {
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    data: JSON.stringify({
                        is_dev: (type === 'dev')
                    }),
                    success: function (response) {
                        cb(response);
                    },
                    error: function (response) {
                        error(response.responseJSON);
                    }
                });
            }

            /**
             *
             * @param {*} cb
             */
            function checkForUpdates(cb) {
                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Addons_Manager.getRegistry',
                        _ajax_nonce: getLocal().nonce
                    },
                    success: function(response) {
                        $.ajax(`${getLocal().system.apiEndpoint}/registry`, {
                            type: 'POST',
                            dataType: 'json',
                            data: JSON.stringify(response),
                            contentType: 'application/json',
                            headers: {
                                "Accept": "application/json"
                            },
                            success: function (response) {
                                $.ajax(getLocal().ajaxurl, {
                                    type: 'POST',
                                    dataType: 'json',
                                    data: {
                                        action: 'aam',
                                        sub_action: 'Addons_Manager.checkForPluginUpdates',
                                        _ajax_nonce: getLocal().nonce,
                                        payload: JSON.stringify(response)
                                    },
                                    success: function() {
                                        cb();
                                    }
                                });
                            },
                            error: function (response) {
                                getAAM().notification(
                                    'danger', response.responseJSON.reason
                                );
                            }
                        });
                    }
                });
            }

            /**
             *
             * @returns {undefined}
             */
            function initialize() {
                if ($('#extension-content').length) {
                    $('[data-toggle="toggle"]', '.extensions-metabox').bootstrapToggle();

                    //init refresh list button
                    $('#download-extension').bind('click', function () {
                        $('#extension-key').parent().removeClass('error');

                        var _this = $(this);
                        var license = $.trim($('#extension-key').val());

                        if (!license) {
                            $('#extension-key').parent().addClass('error');
                            $('#extension-key').focus();
                            return;
                        }

                        $('i', _this).attr('class', 'icon-spin4 animate-spin');
                        validateLicense(license, function (response) {
                            if (response) {
                                getAAM().downloadFile(
                                    response.content,
                                    response.slug + '.zip',
                                    'application/zip'
                                );
                                $('#downloaded-info-modal').modal('show');

                                // Store the license in the internal add-ons registry
                                registerLicense(license, response.slug, response.expire);
                            }
                            $('i', _this).attr('class', 'icon-download-cloud');
                        }, function (response) {
                            getAAM().notification('danger', response.reason);
                            $('i', _this).attr('class', 'icon-download-cloud');
                        });
                    });

                    $('.register-license').each(function() {
                        $(this).bind('click', function () {
                            $('#extension-key').parent().removeClass('error');

                            var _this = $(this);
                            var license = $.trim($('#extension-key').val());

                            if (!license) {
                                $('#extension-key').parent().addClass('error');
                                $('#extension-key').focus();
                                return;
                            }

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');

                            registerDomain(license, $(this).data('type'), function (response) {
                                getAAM().notification(
                                    'success',
                                    'The website has been registered successfully'
                                );
                                // Store the license in the internal add-ons registry
                                registerLicense(license, response.slug, response.expire);
                                $('i', _this).attr('class', 'icon-check');
                            }, function (response) {
                                getAAM().notification('danger', response.reason);
                                $('i', _this).attr('class', 'icon-check');
                            });
                        });
                    });

                    $('#check-for-updates').bind('click', function() {
                        $('i', $(this)).attr('class', 'icon-spin4 animate-spin');
                        checkForUpdates(function() {
                            $('i', '#check-for-updates').attr('class', 'icon-arrows-cw');
                            getAAM().fetchContent('extensions');
                        });
                    });
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);

        /**
         * Settings Interface
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
             * @returns {undefined}
             */
            function save(param, value) {
                getAAM().queueRequest(function () {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'aam',
                            sub_action: 'Settings_Manager.save',
                            _ajax_nonce: getLocal().nonce,
                            param: param,
                            value: value
                        },
                        error: function () {
                            getAAM().notification('danger');
                        }
                    });
                });
            }

            /**
             *
             * @returns {undefined}
             */
            function initialize() {
                if ($('.aam-feature.settings').length) {
                    $('[data-toggle="toggle"]', '.aam-feature.settings').bootstrapToggle();

                    $('#service-list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        pagingType: 'simple',
                        data: JSON.parse($('#service-list-json').text()),
                        columns: [
                            { data: 'setting', visible: false },
                            { data: 'title', visible: false },
                            { data: 'description' },
                            { data: 'status' }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search Service'),
                            info: getAAM().__('_TOTAL_ service(s)'),
                            infoFiltered: '',
                            infoEmpty: getAAM().__('Nothing to show'),
                            lengthMenu: '_MENU_'
                        },
                        createdRow: function (row, data) {
                            $('td:eq(0)', row).html(
                                '<b>' + data.title + '</b><br/><i>' + data.description + '</i>'
                            );

                            // Build toggler
                            var checked = (parseInt(data.status) === 1 ? 'checked' : '');
                            var toggler = '<input data-toggle="toggle" name="' + data.setting + '" id="utility-' + data.setting + '" ' + checked + ' type="checkbox" data-on="' + getAAM().__('Enabled') + '" data-off="' + getAAM().__('Disabled') + '" data-size="small" />';

                            $('td:eq(1)', row).html(toggler);

                            $('[data-toggle="toggle"]', row).bootstrapToggle();
                            $('input[type="checkbox"]', row).bind('change', function () {
                                save(
                                    $(this).attr('name'),
                                    $(this).prop('checked')
                                );
                            });
                        }
                    });

                    $('input[type="checkbox"]', '.aam-feature.settings').bind('change', function () {
                        save(
                            $(this).attr('name'),
                            $(this).prop('checked')
                        );
                    });

                    $('#clear-settings').bind('click', function () {
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Settings_Manager.clearSettings',
                                _ajax_nonce: getLocal().nonce,
                            },
                            beforeSend: function () {
                                $('#clear-settings').prop('disabled', true);
                                $('#clear-settings').text(getAAM().__('Processing...'));
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    getAAM().notification(
                                        'success',
                                        getAAM().__('All settings has been cleared successfully')
                                    );
                                    location.reload();
                                } else {
                                    getAAM().notification('danger', response.reason);
                                }
                            },
                            error: function () {
                                getAAM().notification('danger');
                            },
                            complete: function () {
                                $('#clear-settings').prop('disabled', false);
                                $('#clear-settings').text(getAAM().__('Clear'));
                                $('#clear-settings-modal').modal('hide');
                            }
                        });
                    });
                }
            }

            getAAM().addHook('init', initialize);

            // ConfigPress hook
            getAAM().addHook('menu-feature-click', function (feature) {
                if (feature === 'configpress'
                    && !$('#configpress-editor').next().hasClass('CodeMirror')) {
                    var editor = CodeMirror.fromTextArea(
                        document.getElementById("configpress-editor"), {}
                    );

                    editor.on("blur", function () {
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Settings_ConfigPress.save',
                                _ajax_nonce: getLocal().nonce,
                                config: editor.getValue()
                            },
                            error: function () {
                                getAAM().notification('danger');
                            }
                        });
                    });
                }
            });

            // Import/Export feature
            if (window.File && window.FileReader && window.FileList && window.Blob) {
                $('#file-api-error').remove();

                $('#export-settings').bind('click', function() {
                    getAAM().queueRequest(function () {
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Settings_Manager.exportSettings',
                                _ajax_nonce: getLocal().nonce,
                            },
                            beforeSend: function () {
                                $('#export-settings').prop('disabled', true);
                                $('#export-settings').text(getAAM().__('Processing...'));
                            },
                            success: function (response) {
                                getAAM().notification(
                                    'success',
                                    getAAM().__('Settings has been exported successfully')
                                );
                                getAAM().downloadFile(
                                    response.result,
                                    'aam-settings.json',
                                    'application/json'
                                )
                            },
                            error: function () {
                                getAAM().notification('danger');
                            },
                            complete: function () {
                                $('#export-settings').prop('disabled', false);
                                $('#export-settings').text(getAAM().__('Download Exported Settings'));
                            }
                        });
                    });
                });

                // Handle the selected file
                $('#aam-settings').bind('change', function(e) {
                    // Read the content for the selected file and evaluate it
                    const reader = new FileReader();

                    reader.onload = function() {
                        try {
                            JSON.parse(reader.result);

                            // Import AAM settings
                            getAAM().queueRequest(function () {
                                $.ajax(getLocal().ajaxurl, {
                                    type: 'POST',
                                    dataType: 'json',
                                    data: {
                                        action: 'aam',
                                        sub_action: 'Settings_Manager.importSettings',
                                        _ajax_nonce: getLocal().nonce,
                                        payload: reader.result
                                    },
                                    beforeSend: function () {
                                        $('#aam-settings').prop('disabled', true);
                                    },
                                    success: function (response) {
                                        if (response.status === 'success') {
                                            getAAM().notification(
                                                'success',
                                                getAAM().__('Settings has been imported successfully')
                                            );
                                            location.reload();
                                        } else {
                                            getAAM().notification(
                                                'danger',
                                                response.reason
                                            );
                                        }
                                    },
                                    error: function () {
                                        getAAM().notification('danger');
                                    },
                                    complete: function () {
                                        $('#aam-settings').prop('disabled', false);
                                    }
                                });
                            });
                        } catch(ex) {
                            getAAM().notification(
                                'danger',
                                getAAM().__('Invalid settings')
                            );
                        }
                    }

                    reader.readAsText(e.target.files[0]);
                });

            } else {
                $('#import-export-container').remove();
            }
        })(jQuery);

        /**
         * Top subject bar
         */
        (function ($) {
            $('#reset-subject-settings').bind('click', function() {
                const subject = getAAM().getSubject();

                $('#reset-subject-msg').html(
                    $('#reset-subject-msg')
                        .data('message')
                        .replace('%s', '<b>' + subject.name + '</b>')
                );
                $('#reset-subject-modal').modal('show');
            });

            $('#reset-subject-btn').bind('click', function() {
                const _this = $(this);

                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Settings_Manager.clearSubjectSettings',
                        _ajax_nonce: getLocal().nonce,
                        subject: getAAM().getSubject().type,
                        subjectId: getAAM().getSubject().id
                    },
                    beforeSend: function () {
                        _this.text(getAAM().__('Resetting...')).prop('disabled', true);
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            getAAM().fetchContent('main');
                            $('#reset-subject-modal').modal('hide');
                        } else {
                            getAAM().notification('danger', response.reason);
                        }
                    },
                    error: function () {
                        getAAM().notification('danger');
                    },
                    complete: function () {
                        _this.text(getAAM().__('Reset')).prop('disabled', false);
                    }
                });
            });
        })(jQuery);

        getAAM().fetchContent('main'); //fetch default AAM content
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

        /**
         * Request queue
         */
        this.queue = {
            requests: [],
            processing: false
        };

        /**
         *
         * @type AAM
         */
        var _this = this;

        $(document).ajaxComplete(function () {
            _this.queue.processing = false;

            if (_this.queue.requests.length > 0) {
                _this.queue.processing = true;
                _this.queue.requests.shift().call(_this);
            }
        });
    }

    /**
     *
     * @param {type} request
     * @returns {undefined}
     */
    AAM.prototype.queueRequest = function (request) {
        this.queue.requests.push(request);

        if (this.queue.processing === false) {
            this.queue.processing = true;
            this.queue.requests.shift().call(this);
        }
    };

    /**
     *
     */
    AAM.prototype.applyPolicy = function (subject, policyId, effect, btn) {
        //show indicator
        if (typeof btn !== 'function') {
            $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
        }

        getAAM().queueRequest(function () {
            $.ajax(getLocal().ajaxurl, {
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'aam',
                    sub_action: 'Main_Policy.save',
                    subject: subject.type,
                    subjectId: subject.id,
                    _ajax_nonce: getLocal().nonce,
                    id: policyId,
                    effect: effect
                },
                success: function (response) {
                    if (typeof btn === 'function') {
                        btn(response);
                    } else {
                        if (response.status === 'success') {
                            if (effect) {
                                $(btn).attr('class', 'aam-row-action text-success icon-check');
                            } else {
                                $(btn).attr('class', 'aam-row-action text-muted icon-check-empty');
                            }
                        } else {
                            if (effect) {
                                getAAM().notification(
                                    'danger',
                                    getAAM().__('Failed to apply policy changes')
                                );
                                $(btn).attr('class', 'aam-row-action text-muted icon-check-empty');
                            } else {
                                $(btn).attr('class', 'aam-row-action text-success icon-check');
                            }
                        }
                    }
                },
                error: function () {
                    getAAM().notification('danger');
                }
            });
        });
    }

    /**
     *
     */
    AAM.prototype.loadRoleList = function (selected, target) {
        target = (typeof target === 'undefined' ? '#expiration-change-role' : target);
        $.ajax(getLocal().ajaxurl, {
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'aam',
                sub_action: 'Subject_Role.getList',
                _ajax_nonce: getLocal().nonce
            },
            beforeSend: function () {
                $(target).html(
                    '<option value="">' + getAAM().__('Loading...') + '</option>'
                );
            },
            success: function (response) {
                $(target).html(
                    '<option value="">' + getAAM().__('Select Role') + '</option>'
                );
                for (var i in response) {
                    $(target).append(
                        '<option value="' + i + '">' + response[i].name + '</option>'
                    );
                }

                $(target).val(selected);
            }
        });
    }

    /**
     *
     * @returns {undefined}
     */
    AAM.prototype.initializeMenu = function () {
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
     * @param {type} view
     * @returns {undefined}
     */
    AAM.prototype.fetchContent = function (view) {
        var _this = this;

        var data = {
            action: 'aam',
            sub_action: 'renderContent',
            _ajax_nonce: getLocal().nonce,
            partial: view,
            subject: this.getSubject().type,
            subjectId: this.getSubject().id
        };

        $.ajax(getLocal().ajaxurl, {
            type: 'POST',
            dataType: 'html',
            data: data,
            beforeSend: function () {
                if ($('#aam-initial-load').length === 0) {
                    $('#aam-content').html(
                        $('<div/>', { 'class': 'aam-loading' }).append($('<i/>', {
                            'class': 'icon-spin4 animate-spin'
                        }))
                    );
                }
            },
            success: function (response) {
                $('#aam-content').html(response);
                //init menu
                _this.initializeMenu();
                //trigger initialization hook
                _this.triggerHook('init');

                // There is more than one Services available to manage
                if ($('#feature-list').length) {
                    //activate one of the menu items
                    var item = $('li:eq(0)', '#feature-list');

                    if (location.hash !== '') {
                        var hash = location.hash.substr(1);
                        if ($('li[data-feature="' + hash + '"]', '#feature-list').length) {
                            item = $('li[data-feature="' + hash + '"]', '#feature-list');
                        }
                    }

                    item.trigger('click');
                } else {
                    $('.aam-feature:eq(0)').addClass('active');
                }

                $('.aam-sidebar .metabox-holder').hide();
                $('.aam-sidebar .shared-metabox').show();
                $('.aam-sidebar .' + view + '-metabox').show();

                if (view !== 'main') { //hide subject and user/role manager
                    $('#aam-subject-banner').hide();
                } else {
                    $('#aam-subject-banner').show();
                }
            }
        });
    };

    /**
     *
     * @param {type} view
     * @param {type} success
     * @param {type} failure
     * @returns {undefined}
     */
    AAM.prototype.fetchPartial = function (view, success) {
        var _this = this;

        //referred object ID like post, page or any custom post type
        var object = window.location.search.match(/&id\=([^&]*)/);
        var type = window.location.search.match(/&type\=([^&]*)/);

        $.ajax(getLocal().ajaxurl, {
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'aam',
                sub_action: 'renderContent',
                _ajax_nonce: getLocal().nonce,
                partial: view,
                subject: this.getSubject().type,
                subjectId: this.getSubject().id,
                id: object ? object[1] : null,
                type: type ? type[1] : null
            },
            success: function (response) {
                success.call(_this, response);
            },
            error: function() {
                getAAM().notification('danger');
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
     * @param {String} result
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
        // Read default subject and set it for AAM object
        if ($('#aam-subject-type').length > 0) {
            this.setSubject(
                $('#aam-subject-type').val(),
                $('#aam-subject-id').val(),
                $('#aam-subject-name').val(),
                $('#aam-subject-level').val()
            );
        } else if (window.localStorage.getItem('aam-subject')) {
            const subject = JSON.parse(window.localStorage.getItem('aam-subject'));
            this.setSubject(
                subject.type,
                subject.id,
                subject.name,
                subject.level
            );
        } else if (getLocal().subject.type) {
            this.setSubject(
                getLocal().subject.type,
                getLocal().subject.id,
                getLocal().subject.name,
                getLocal().subject.level
            );
        } else {
            $('#aam-subject-banner').addClass('hidden');
        }

        //load the UI javascript support
        UI();

        // Migration log downloader
        if ($('#download-migration-log').length) {
            $('#download-migration-log').bind('click', function() {
                getAAM().downloadFile(
                    $('#migration-errors-container').html(),
                    'migration-error.log',
                    'text/plain'
                );
            });
        }

        //initialize help context
        $('.aam-help-menu').each(function () {
            var target = $(this).data('target');

            if (target) {
                $(this).bind('click', function () {
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

        //help tooltip
        $('body').delegate('[data-toggle="tooltip"]', 'hover', function (event) {
            event.preventDefault();
            $(this).tooltip({
                'placement': $(this).data('placement') || 'top',
                'container': 'body'
            });
            $(this).tooltip('show');
        });

        $('.aam-area').each(function () {
            $(this).bind('click', function () {
                $('.aam-area').removeClass('text-danger');
                $(this).addClass('text-danger');
                getAAM().fetchContent($(this).data('type'));
            });
        });

        // preventDefault for all links with # href
        $('#aam-container').delegate('a[href="#"]', 'click', function (event) {
            event.preventDefault();
        });

        // Initialize clipboard
        var clipboard = new ClipboardJS('.aam-copy-clipboard');

        clipboard.on('success', function (e) {
            getAAM().notification('success', getAAM().__('Data has been saved to clipboard'));
        });

        clipboard.on('error', function (e) {
            getAAM().notification('danger', getAAM().__('Failed to save data to clipboard'));
        });

        // Listen to page size change and update iframe height accordingly
        const container = document.getElementById('aam-container');
        new ResizeSensor(container, function() {
            window.parent.postMessage({frameHeight: container.clientHeight}, '*');
        });
    };

    /**
     *
     * @param {type} label
     * @returns {unresolved}
     */
    AAM.prototype.__ = function (label) {
        return (getLocal().translation[label] ? getLocal().translation[label] : label);
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

        // Persist the subject in the local storage
        window.localStorage.setItem('aam-subject', JSON.stringify(this.subject));

        if (getAAM().isUI('main')) {
            // First set the type of the subject
            $('.aam-current-subject').text(
                type.charAt(0).toUpperCase() + type.slice(1) + ': '
            );

            // Second set the name of the subject
            $('.aam-current-subject').append($('<strong/>').text(name));

            // Highlight screen if the same level
            if (parseInt(level) >= getLocal().level || type === 'default') {
                $('.aam-current-subject').addClass('danger');
            } else {
                $('.aam-current-subject').removeClass('danger');
            }
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
        var notification = $('<div/>', { 'class': 'aam-sticky-note ' + status });

        if (!message) {
            switch (status) {
                case 'success':
                    message = getAAM().__('Operation completed successfully');
                    break;

                case 'danger':
                    message = getAAM().__('Unexpected application error');
                    break;

                default:
                    break;
            }
        }

        notification.append($('<span/>').text(message));
        $('.wrap').append(notification);

        setTimeout(function () {
            $('.aam-sticky-note').remove();
        }, 9000);
    };

    /**
     *
     * @param {type} object
     * @param {type} btn
     * @returns {undefined}
     */
    AAM.prototype.reset = function (sub_action, btn) {
        getAAM().queueRequest(function () {
            $.ajax(getLocal().ajaxurl, {
                type: 'POST',
                data: {
                    action: 'aam',
                    sub_action: sub_action,
                    _ajax_nonce: getLocal().nonce,
                    subject: this.getSubject().type,
                    subjectId: this.getSubject().id,
                },
                beforeSend: function () {
                    var label = btn.text();
                    btn.attr('data-original-label', label);
                    btn.text(getAAM().__('Resetting...'));
                },
                success: function () {
                    getAAM().fetchContent('main');
                },
                error: function () {
                    getAAM().notification('danger');
                },
                complete: function () {
                    btn.text(btn.attr('data-original-label'));
                }
            });
        });
    };

    /**
     *
     * @param {type} type
     * @returns {Boolean}
     */
    AAM.prototype.isUI = function (type) {
        return (getLocal().ui === type);
    };

    /**
     *
     */
    AAM.prototype.downloadFile = function(content, filename, mime) {
        const binaryString = window.atob(content); // Comment this if not using base64
        const bytes = new Uint8Array(binaryString.length);
        const base64 = bytes.map((byte, i) => binaryString.charCodeAt(i));

        var blob = new Blob([base64], { type: mime || 'application/octet-stream' });

        if (typeof window.navigator.msSaveBlob !== 'undefined') {
            // IE workaround for "HTML7007: One or more blob URLs were
            // revoked by closing the blob for which they were created.
            // These URLs will no longer resolve as the data backing
            // the URL has been freed."
            window.navigator.msSaveBlob(blob, filename);
        }
        else {
            var blobURL = window.URL.createObjectURL(blob);
            var tempLink = document.createElement('a');
            tempLink.style.display = 'none';
            tempLink.href = blobURL;
            tempLink.setAttribute('download', filename);

            // Safari thinks _blank anchor are pop ups. We only want to set _blank
            // target if the browser does not support the HTML5 download attribute.
            // This allows you to download files in desktop safari if pop up blocking
            // is enabled.
            if (typeof tempLink.download === 'undefined') {
                tempLink.setAttribute('target', '_blank');
            }

            document.body.appendChild(tempLink);
            tempLink.click();
            document.body.removeChild(tempLink);
            window.URL.revokeObjectURL(blobURL);
        }
    }

    /**
     *
     * @returns {aamLocal}
     */
    var getLocal = function () {
        return aamLocal;
    }

    AAM.prototype.getLocal = getLocal;

    /**
     *
     * @returns {aamL#14.AAM|AAM}
     */
    function getAAM() {
        return aam;
    }

    /**
     * Initialize UI
     */
    $(document).ready(function () {
        $.aam = aam = new AAM();
        getAAM().initialize();
    });

})(jQuery);