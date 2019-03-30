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
     * @param {type} id
     * @param {type} btn
     * @param {type} rowAction
     * @returns {undefined}
     */
    function switchToUser(id, btn, rowAction) {
       $.ajax(getLocal().ajaxurl, {
           type: 'POST',
           dataType: 'json',
           data: {
               action: 'aam',
               sub_action: 'Subject_User.switchToUser',
               _ajax_nonce: getLocal().nonce,
               user: id
           },
           beforeSend: function () {
                $(btn).attr(
                    'class', 
                    'icon-spin4 animate-spin ' + (rowAction ? 'aam-row-action' : 'aam-switch-user')
                );
           },
           success: function (response) {
               if (response.status === 'success') {
                   location.href = response.redirect;
               } else {
                   getAAM().notification('danger', response.reason);
               }
           },
           error: function () {
               getAAM().notification('danger', getAAM().__('Application error'));
           },
           complete: function () {
                $(btn).attr(
                    'class', 
                    'icon-exchange ' + (rowAction ? 'aam-row-action text-success' : 'aam-switch-user')
                );
           }
       });
    }
    
    /**
     * 
     * @param {type} id
     * @param {type} btn
     * @returns {undefined}
     */
    function applyPolicy(subject, policyId, effect, btn) {
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
                    getAAM().notification(
                        'danger', getAAM().__('Application Error')
                    );
                }
            });
        });
    }

    /**
     * 
     * @param {type} selected
     * @returns {undefined}
     */
    function loadRoleList(selected, target) {
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

                return (subject.type === 'role' && subject.id === id);
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
                            '<option value="">' + getAAM().__('No Role') + '</option>'
                        );
                        for (var i in response) {
                            $('.inherit-role-list').append(
                                '<option value="' + i + '">' + response[i].name + '</option>'
                            );
                        }
                        if ($.aamEditRole) {
                            $('#inherit-role').val($.aamEditRole[0]);
                        }
                        getAAM().triggerHook('post-get-role-list', {
                           list : response
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
                        id: $('#object-id').val()
                    }
                },
                columnDefs: [
                    {visible: false, targets: [0, 1, 4]},
                    {orderable: false, targets: [0, 1, 3, 4]}
                ],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: getAAM().__('Search Role'),
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
                        $('<i/>', {'class': 'aam-row-subtitle'}).html(
                            getAAM().applyFilters(
                                'role-subtitle', 
                                getAAM().__('Users') + ': <b>' + parseInt(data[1]) + '</b>; ID: <b>' + data[0] + '</b>',
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
                                        getAAM().setSubject('role', data[0], title, data[4]);
                                        $('td:eq(0) span', row).replaceWith(
                                            '<strong class="aam-highlight">' + title + '</strong>'
                                        );
                                        $('i.icon-cog', container).attr(
                                            'class', 'aam-row-action icon-cog text-muted'
                                        );
                                        if (getAAM().isUI('main')) {
                                            $('i.icon-cog', container).attr(
                                                'class', 'aam-row-action icon-spin4 animate-spin'
                                            );
                                            getAAM().fetchContent('main');
                                            $('i.icon-spin4', container).attr(
                                                'class', 'aam-row-action icon-cog text-muted'
                                            );
                                        } else {
                                            getAAM().fetchPartial('postform', function(content) {
                                                $('#metabox-post-access-form').html(content);
                                                getAAM().loadAccessForm(
                                                    $('#load-post-object-type').val(), 
                                                    $('#load-post-object').val(), 
                                                    $(this)
                                                );
                                            });
                                        }
                                    }
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': getAAM().__('Manage Role')
                                }).prop('disabled', (isCurrent(data[0]) ? true: false)));
                                break;

                            case 'edit':
                                if (getAAM().isUI('main')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-pencil text-warning'
                                    }).bind('click', function () {
                                        resetForm('#edit-role-modal .modal-body');
                                        $('#edit-role-btn').data('role', data[0]);
                                        $('#edit-role-name').val(data[2]);
                                        $('#edit-role-modal').modal('show');
                                        fetchRoleList(data[0]);
                                        
                                        //TODO - Rewrite JavaScript to support $.aam 
                                        $.aamEditRole = data;
                                        
                                        getAAM().triggerHook('edit-role-modal', data);
                                    }).attr({
                                        'data-toggle': "tooltip",
                                        'title': getAAM().__('Edit Role')
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
                                        'title': getAAM().__('Clone Role')
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
                                        'title': getAAM().__('Delete Role')
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
                                    }).bind('click', function() {
                                        applyPolicy(
                                            {
                                                type: 'role',
                                                id: data[0]
                                            },
                                            $('#object-id').val(),
                                            ($(this).hasClass('icon-check-empty') ? 1 : 0),
                                            this
                                        );
                                    }));
                                }
                                break;
                                
                            case 'no-attach':
                                if (getAAM().isUI('principal')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-check-empty text-muted'
                                    }));
                                }
                                break;
                                
                            case 'detach':
                                if (getAAM().isUI('principal')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-check text-success'
                                    }).bind('click', function() {
                                        applyPolicy(
                                            {
                                                type: 'role',
                                                id: data[0]
                                            },
                                            $('#object-id').val(),
                                            ($(this).hasClass('icon-check') ? 0 : 1),
                                            this
                                        );
                                    }));
                                }
                                break;
                                
                            case 'no-detach':
                                if (getAAM().isUI('principal')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-check text-muted'
                                    }));
                                }
                                break;

                            default:
                                if (getAAM().isUI('main')) {
                                    getAAM().triggerHook('role-action', {
                                        container: container,
                                        action   : action,
                                        data     : data
                                    });
                                }
                                break;
                        }
                    });
                    $('td:eq(1)', row).html(container);

                    getAAM().triggerHook('decorate-role-row', {
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
                        $('.icon-cog.text-muted', this).attr('disabled', false);
                        $('.icon-cog.text-muted', this).toggleClass('text-muted text-info');
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
                    _ajax_nonce: getLocal().nonce
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
                            getAAM().notification('danger', getAAM().__('Application error'));
                        },
                        complete: function () {
                            $('#add-role-modal').modal('hide');
                            $(_this).text(getAAM().__('Add Role')).attr('disabled', false);
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
                    _ajax_nonce: getLocal().nonce,
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
                            } else {
                                getAAM().notification(
                                    'danger', getAAM().__('Failed to update role')
                                );
                            }
                        },
                        error: function () {
                            getAAM().notification('danger', getAAM().__('Application error'));
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
                            $('#role-list').DataTable().ajax.reload();
                        } else {
                            getAAM().notification('danger', getAAM().__('Failed to delete role'));
                        }
                    },
                    error: function () {
                        getAAM().notification('danger', getAAM().__('Application error'));
                    },
                    complete: function () {
                        $('#delete-role-modal').modal('hide');
                        $(_this).text(getAAM().__('Delete Role')).attr('disabled', false);
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

                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Subject_User.block',
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
                                    'title': getAAM().__('Unlock User'),
                                    'data-original-title': getAAM().__('Unlock User')
                                });
                            } else {
                                $(btn).attr({
                                    'class': 'aam-row-action icon-lock-open-alt text-warning',
                                    'title': getAAM().__('Lock User'),
                                    'data-original-title': getAAM().__('Lock User')
                                });
                            }
                        } else {
                            getAAM().notification('danger', getAAM().__('Failed to block user'));
                        }
                    },
                    error: function () {
                        getAAM().notification('danger', getAAM().__('Application error'));
                    }
                });
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
                    data: function(params) {
                       params.action = 'aam';
                       params.sub_action = 'Subject_User.getTable';
                       params._ajax_nonce = getLocal().nonce;
                       params.role = $('#user-list-filter').val();
                       params.subject = getAAM().getSubject().type;
                       params.subjectId = getAAM().getSubject().id;
                       params.ui = getLocal().ui;
                       params.id = $('#object-id').val();
                       
                       return params;
                    }
                },
                columnDefs: [
                    {visible: false, targets: [0, 1, 4, 5]},
                    {orderable: false, targets: [0, 1, 3, 4, 5]}
                ],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: getAAM().__('Search User'),
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
                            'class': 'user-filter',
                            'id': 'user-list-filter'
                        })
                        .html('<option value="">' + getAAM().__('Loading roles...') + '</option>')
                        .bind('change', function() {
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
                                    '<option value="">' + getAAM().__('Filter By Role') + '</option>'
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
                    var expire = (data[5] ? '; <i class="icon-clock"></i>' : '');
                    $('td:eq(0)', row).append(
                        $('<i/>', {'class': 'aam-row-subtitle'}).html(
                            getAAM().__('Role') + ': ' + data[1] + '; ID: <b>' + data[0] + '</b>' + expire
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
                                        getAAM().setSubject('user', data[0], data[2], data[4]);
                                        $('td:eq(0) span', row).replaceWith(
                                            '<strong class="aam-highlight">' + data[2] + '</strong>'
                                        );
                                        $('i.icon-cog', container).attr('class', 'aam-row-action icon-cog text-muted');

                                        if (getAAM().isUI('main')) {
                                            $('i.icon-cog', container).attr('class', 'aam-row-action icon-spin4 animate-spin');
                                            getAAM().fetchContent('main');
                                            $('i.icon-spin4', container).attr('class', 'aam-row-action icon-cog text-muted');
                                        } else {
                                            getAAM().fetchPartial('postform', function(content) {
                                                $('#metabox-post-access-form').html(content);
                                                getAAM().loadAccessForm(
                                                    $('#load-post-object-type').val(), 
                                                    $('#load-post-object').val(), 
                                                    $(this)
                                                );
                                            });
                                        }
                                    }
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': getAAM().__('Manage User')
                                })).prop('disabled', (isCurrent(data[0]) ? true: false));
                                break;
                                
                            case 'edit':
                                if (getAAM().isUI('main')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-pencil text-info'
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
                                                loadRoleList(settings[2]);
                                            } else {
                                                loadRoleList();
                                                $('#expiration-change-role-holder').addClass('hidden');
                                            }
                                        } else {
                                            $('#reset-user-expiration-btn').addClass('hidden');
                                            $('#user-expires, #action-after-expiration').val('');
                                            loadRoleList();
                                        }

                                        $('#edit-user-modal').modal('show');
                                        
                                    }).attr({
                                        'data-toggle': "tooltip",
                                        'title': getAAM().__('Edit User')
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
                                        'title': getAAM().__('Lock User')
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
                                        'title': getAAM().__('Unlock User')
                                    }));
                                }
                                break;

                            case 'switch':
                                if (getAAM().isUI('main')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-exchange text-success'
                                    }).bind('click', function () {
                                        switchToUser(data[0], $(this), true);
                                    }).attr({
                                        'data-toggle': "tooltip",
                                        'title': getAAM().__('Switch To User')
                                    }));
                                }
                                break;
                                
                            case 'no-switch':
                                if (getAAM().isUI('main')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-exchange text-muted'
                                    }));
                                }
                                break;
                                
                            case 'attach':
                                if (getAAM().isUI('principal')) {
                                    $(container).append($('<i/>', {
                                        'class': 'aam-row-action icon-check-empty'
                                    }).bind('click', function() {
                                        applyPolicy(
                                            {
                                                type: 'user',
                                                id: data[0]
                                            },
                                            $('#object-id').val(),
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
                                    }).bind('click', function() {
                                        applyPolicy(
                                            {
                                                type: 'user',
                                                id: data[0]
                                            },
                                            $('#object-id').val(),
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
            
            $('#action-after-expiration').bind('change', function() {
               if ($(this).val() === 'change-role') {
                   $('#expiration-change-role-holder').removeClass('hidden');
               } else {
                   $('#expiration-change-role-holder').addClass('hidden');
               }
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
                sideBySide: true
            });

            $('#edit-user-modal').on('show.bs.modal', function() {
                try{
                    if ($.trim($('#user-expires').val())) {
                        $('#user-expiration-datapicker').data('DateTimePicker').defaultDate(
                            $('#user-expires').val()
                        );
                    } else {
                        var tomorrow = new Date();
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        $('#user-expiration-datapicker').data('DateTimePicker').defaultDate(
                            tomorrow
                        );
                    }
                } catch(e) {
                    // do nothing. Prevent from any kind of corrupted data
                }
            });

            $('#user-expiration-datapicker').on('dp.change', function(res) {
                $('#user-expires').val(
                    res.date.format('MM/DD/YYYY, H:mm Z')
                );
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
                        role: $('#expiration-change-role').val()
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
                        getAAM().notification('danger', getAAM().__('Application error'));
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
                        sub_action: 'Subject_User.saveExpiration',
                        _ajax_nonce: getLocal().nonce,
                        user: $(_this).attr('data-user-id')
                    },
                    beforeSend: function () {
                        $(_this).text(getAAM().__('Reseting...')).attr('disabled', true);
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#user-list').DataTable().ajax.reload();
                        } else {
                            getAAM().notification('danger', response.reason);
                        }
                    },
                    error: function () {
                        getAAM().notification('danger', getAAM().__('Application error'));
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

            $('document').ready(function() {
                 $('#manage-visitor').bind('click', function () {
                    var _this = this;

                    getAAM().setSubject('visitor', null, getAAM().__('Anonymous'), 0);
                    $('i.icon-cog', $(this)).attr('class', 'icon-spin4 animate-spin');

                    if (getAAM().isUI('main')) {
                        getAAM().fetchContent('main');
                        $('i.icon-spin4', $(this)).attr('class', 'icon-cog');
                    } else {
                        getAAM().fetchPartial('postform', function(content) {
                            $('#metabox-post-access-form').html(content);
                            getAAM().loadAccessForm(
                                $('#load-post-object-type').val(), 
                                $('#load-post-object').val(), 
                                null, 
                                function () {
                                    $('i.icon-spin4', $(_this)).attr('class', 'icon-cog');
                                }
                            );
                        });
                    }
                });
                
                $('#attach-policy-visitor').bind('click', function() {
                    var has = parseInt($(this).attr('data-has')) ? true : false;
                    var effect = (has ? 0 : 1);
                    var btn = $(this);
                    
                    btn.text(getAAM().__('Processing...'));
                    
                    applyPolicy(
                        {
                            type: 'visitor'
                        },
                        $('#object-id').val(),
                        effect,
                        function(response) {
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

            $('document').ready(function() {
                $('#manage-default').bind('click', function () {
                    var _this = this;

                    getAAM().setSubject('default', null, getAAM().__('All Users, Roles and Visitor'), 0);
                    $('i.icon-cog', $(this)).attr('class', 'icon-spin4 animate-spin');
                    if (getAAM().isUI('main')) {
                        getAAM().fetchContent('main');
                        $('i.icon-spin4', $(this)).attr('class', 'icon-cog');
                    } else {
                        getAAM().fetchPartial('postform', function(content) {
                            $('#metabox-post-access-form').html(content);
                            getAAM().loadAccessForm(
                                $('#load-post-object-type').val(), 
                                $('#load-post-object').val(), 
                                null, 
                                function () {
                                    $('i.icon-spin4', $(_this)).attr('class', 'icon-cog');
                                }
                            );
                        });
                    }
                });
                
                $('#attach-policy-default').bind('click', function() {
                    var has = parseInt($(this).attr('data-has')) ? true : false;
                    var effect = (has ? 0 : 1);
                    var btn = $(this);
                    
                    btn.text(getAAM().__('Processing...'));
                    
                    applyPolicy(
                        {
                            type: 'default'
                        },
                        $('#object-id').val(),
                        effect,
                        function(response) {
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
             * @param {type} data
             * @param {type} cb
             * @returns {undefined}
             */
            function downloadLicense(data, cb) {
                $.ajax(getLocal().system.apiEndpoint + '/download', {
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        license: data.license,
                        domain: getLocal().system.domain,
                        uid: getLocal().system.uid
                    },
                    success: function (package) {
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Policy.install',
                                _ajax_nonce: getLocal().nonce,
                                license: data.license,
                                package: package
                            },
                            success: function (response) {
                                if (response.status !== 'success') {
                                    getAAM().notification('danger', getAAM().__(response.error));
                                }
                            },
                            error: function () {
                                getAAM().notification(
                                    'danger', 
                                    getAAM().__('Application error')
                                );
                            },
                            complete: function() {
                                cb();
                            }
                        });
                    },
                    error: function (response) {
                        getAAM().notification(
                            'danger', response.responseJSON.message
                        );
                    }
                });
            }
            
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
                
                applyPolicy(subject, id, effect, btn);
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
                    
                    $('#download-policy').bind('click', function() {
                        var license = $.trim($('#policy-license-key').val());
                        
                        if (license) {
                            $(this).text(getAAM().__('Downloading'));
                            downloadLicense({
                                license: license
                            }, function() {
                                $('#download-policy').text(getAAM().__('Download'));
                                $('#policy-list').DataTable().ajax.reload();
                                $('#download-policy-modal').modal('hide');
                            });
                        } else {
                            $('#policy-license-key').focus();
                        }
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
                            {visible: false, targets: [0,3]}
                        ],
                        initComplete: function () {
                            var create = $('<a/>', {
                                'href': '#',
                                'class': 'btn btn-primary'
                            }).html('<i class="icon-plus"></i> ' + getAAM().__('Create'))
                            .bind('click', function () {
                                window.open(getLocal().url.addPolicy, '_blank');
                            });
                            
                            /*var download = $('<a/>', {
                                'href': '#',
                                'class': 'btn btn-success'
                            }).html('<i class="icon-download-cloud"></i> ' + getAAM().__('Download'))
                            .bind('click', function () {
                               $('#download-policy-modal').modal('show');
                            });

                            $('.dataTables_filter', '#policy-list_wrapper').append(download);*/
                            $('.dataTables_filter', '#policy-list_wrapper').append(create);
                        },
                        createdRow: function (row, data) {
                            var actions = data[2].split(',');

                            var container = $('<div/>', {'class': 'aam-row-actions'});
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

                                    default:
                                        break;
                                }
                            });
                            $('td:eq(1)', row).html(container);

                            $('td:eq(0)', row).html(data[1]);
                        }
                    });
                }
            }
            
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
                getAAM().queueRequest(function() {
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
                        success: function(response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification(
                                'danger', getAAM().__('Application Error')
                            );
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
                                        $('.aam-inner-tab', target).append(
                                                $('<div/>', {'class': 'aam-lock'})
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

                    $('input[type="checkbox"]', '#admin-menu').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);
                            save(
                                [_this.data('menu-id')], 
                                _this.attr('checked') ? 1 : 0,
                                function(result) {
                                    if (result.status === 'success') {
                                        $('#aam-menu-overwrite').show();
                                        if (_this.attr('checked')) {
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
                getAAM().queueRequest(function() {
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
                        success: function(response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification(
                                'danger', getAAM().__('Application Error')
                            );
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
                            var _this  = $(this);
                            var status = ($('i', $(this)).hasClass('icon-eye-off') ? 1 : 0);
                            var target = _this.data('target');

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');

                            var items = new Array(_this.data('toolbar'));

                            $('input', target).each(function () {
                                $(this).attr('checked', status ? true : false);
                                items.push($(this).data('toolbar'));
                            });

                            save(items, status, function(result) {
                                if (result.status === 'success') {
                                    $('#aam-toolbar-overwrite').show();

                                    if (status) { //locked the menu
                                        $('.aam-inner-tab', target).append(
                                                $('<div/>', {'class': 'aam-lock'})
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
                    
                    //reset button
                    $('#toolbar-reset').bind('click', function () {
                        getAAM().reset('Main_Toolbar.reset', $(this));
                    });

                    $('input[type="checkbox"]', '#toolbar-list').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);
                            save(
                                [$(this).data('toolbar')],
                                $(this).attr('checked') ? 1 : 0,
                                function(result) {
                                    if (result.status === 'success') {
                                        $('#aam-toolbar-overwrite').show();
                                        
                                        if (_this.attr('checked')) {
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
                getAAM().queueRequest(function() {
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
                        success: function(response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification(
                                'danger', getAAM().__('Application Error')
                            );
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
                        getAAM().notification(
                            'danger', getAAM().__('Application error')
                        );
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
                                getAAM().notification(
                                    'danger', getAAM().__('Application error')
                                );
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
                                $('#init-url-btn').text(getAAM().__('Processing'));
                            },
                            complete: function () {
                                $('#init-url-btn').text(getAAM().__('Initialize'));
                                $('#init-url-modal').modal('hide');
                                getContent();
                            }
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
                                $(this).attr('checked') ? 1 : 0,
                                function(result) {
                                    if (result.status === 'success') {
                                        $('#aam-metabox-overwrite').show();
                                        
                                        if (_this.attr('checked')) {
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
            function save(capability, btn) {
                var granted = $(btn).hasClass('icon-check-empty') ? 1 : 0;

                //show indicator
                $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
                
                getAAM().queueRequest(function() {
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
                            status: granted
                        },
                        success: function(result) {
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
                            }
                        },
                        error: function () {
                            getAAM().notification(
                                'danger', getAAM().__('Application Error')
                            );
                        }
                    });
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
                            {visible: false, targets: [0]}
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search Capability'),
                            info: getAAM().__('_TOTAL_ capability(s)'),
                            infoFiltered: '',
                            infoEmpty: getAAM().__('Nothing to show'),
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
                                            var message = $('.aam-confirm-message', '#delete-capability-modal');
                                            $(message).html(message.data('message').replace(
                                                    '%s', '"' + data[0] + '"')
                                            );
                                            $('#capability-id').val(data[0]);
                                            $('#delete-capability-btn').attr('data-cap', data[0]);
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
                    });

                    $('#add-capability').bind('click', function () {
                        $('#add-capability-modal').modal('show');
                    });

                    $('#add-capability-btn').bind('click', function () {
                        var _this = this;

                        var capability = $.trim($('#new-capability-name').val());
                        $('#new-capability-name').parent().removeClass('has-error');

                        if (capability) {
                            $.ajax(getLocal().ajaxurl, {
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'aam',
                                    sub_action: 'Main_Capability.add',
                                    _ajax_nonce: getLocal().nonce,
                                    capability: capability,
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
                                    getAAM().notification('danger', getAAM().__('Application error'));
                                },
                                complete: function () {
                                    $(_this).text(getAAM().__('Add Capability')).attr('disabled', false);
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
                            $.ajax(getLocal().ajaxurl, {
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'aam',
                                    sub_action: 'Main_Capability.update',
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
                                    getAAM().notification('danger', getAAM().__('Application error'));
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

                    $('#delete-capability-btn').bind('click', function () {
                        var btn = this;

                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Main_Capability.delete',
                                _ajax_nonce: getLocal().nonce,
                                subject: getAAM().getSubject().type,
                                subjectId: getAAM().getSubject().id,
                                capability: $(this).attr('data-cap')
                            },
                            beforeSend: function () {
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
                                getAAM().notification('danger', getAAM().__('Application error'));
                            },
                            complete: function () {
                                $('#delete-capability-modal').modal('hide');
                                $(btn).text(getAAM().__('Delete Capability')).attr(
                                        'disabled', false
                                );
                            }
                        });
                    });

                    //reset button
                    $('#capability-reset').bind('click', function () {
                        getAAM().reset('Main_Capability.reset', $(this));
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
                getAAM().queueRequest(function() {
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
                                $('#post-reset').attr({
                                    'data-type': object,
                                    'data-id': object_id
                                });
                            }
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification(
                                'danger', getAAM().__('Application error')
                            );
                        }
                    });
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
             * @param {type} object
             * @param {type} id
             * @param {type} btn
             * @param {type} callback
             * @returns {undefined}
             */
            getAAM().loadAccessForm = function(object, id, btn, callback) {
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
                        var value   = (checked ? 1 : 0);

                        _this.attr('class', 'aam-row-action icon-spin4 animate-spin');
                        save(
                            _this.data('property'),
                            value,
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
                
                        if ($(this).data('trigger') && value) {
                            $('#' + $(this).data('trigger')).trigger('click');
                        }
                    });
                });
                
                $('.advanced-post-option').each(function() {
                    $(this).bind('click', function() {
                        var container = $(this).attr('href');
                        var option = objectAccess.access[$(this).data('ref')];
                        var field  = $($('.extended-post-access-btn', container).data('field'));

                        //add attributes to the .extended-post-access-btn
                        $('.extended-post-access-btn', container).attr({
                            'data-ref': $(this).attr('data-ref'),
                            'data-preview': $(this).attr('data-preview')
                        });

                        //set field value
                        field.val(option);
                    });
                 });

                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Post.getAccess',
                        _ajax_nonce: getLocal().nonce,
                        type: object,
                        id: id,
                        subject: getAAM().getSubject().type,
                        subjectId: getAAM().getSubject().id
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
                                var cname = (response.access[property] ? 'text-danger icon-check' : 'text-muted icon-check-empty');
                                checkbox.attr({
                                    'class': 'aam-row-action ' + cname
                                });
                            } else {
                                $('.option-preview[data-ref="' + property + '"]').text(
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
                                $(this).attr('data-dynamic-post-label').replace(/%s/g, '"' + marker + '"')
                            );
                        });
                    },
                    error: function () {
                        getAAM().notification('danger', getAAM().__('Application error'));
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
                            }
                        },
                        columnDefs: [
                            {visible: false, targets: [0, 1, 5, 6]},
                            {orderable: false, targets: [0, 1, 2, 4, 5, 6]}
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ object(s)'),
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
                            var icon = 'icon-doc-text-inv';
                            
                            switch (data[2]) {
                                case 'type':
                                    icon = 'icon-box';
                                    break;

                                case 'term':
                                    icon = 'icon-folder';
                                    break;

                                default:
                                    break;
                            }
                            
                            if (data[6]) {
                                $('td:eq(0)', row).html($('<i/>', {
                                    'class': icon + ' aam-access-overwritten',
                                    'data-toggle': "tooltip",
                                    'title': getAAM().__('Settings Customized')
                                }));
                            } else {
                                $('td:eq(0)', row).html($('<i/>', {
                                    'class': icon
                                }));
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
                            } else if (data[2] === 'term') {
                                $('td:eq(1)', row).html($('<span/>').text(data[3]));
                                
                                var sub = $('<i class="aam-row-subtitle"></i>');
                                
                                if (data[5]) {
                                    sub.append($('<span/>').text(getAAM().__('Parent:') + ' '));
                                    sub.append($('<strong/>').text(data[5] + '; '));
                                } else {
                                    sub.append($('<span/>').text(getAAM().__('Parent:') + ' none; '));
                                }
                                
                                sub.append($('<span/>').text(getAAM().__('ID:') + ' '));
                                sub.append($('<strong/>').text(data[0].split('|')[0]));
                                
                                $('td:eq(1)', row).append(sub);
                            } else {
                                $('td:eq(1)', row).html($('<span/>').text(data[3]));
                                
                                var sub = $('<i class="aam-row-subtitle"></i>');
                                
                                if (data[5]) {
                                    sub.append($('<span/>').text(getAAM().__('Parent:') + ' '));
                                    sub.append($('<strong/>').text(data[5] + '; '));
                                } else {
                                    sub.append($('<span/>').text(getAAM().__('Parent:') + ' none; '));
                                }
                                
                                sub.append($('<span/>').text(getAAM().__('ID:') + ' '));
                                sub.append($('<strong/>').text(data[0]));
                                
                                $('td:eq(1)', row).append(sub);
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
                                            'title': getAAM().__('Drill-Down')
                                        }));
                                        $('.tooltip').remove();
                                        break;

                                    case 'manage':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-info icon-cog'
                                        }).bind('click', function () {
                                            getAAM().loadAccessForm(data[2], data[0], $(this), function () {
                                                addBreadcrumbLevel('edit', data[2], data[3]);
                                            });
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Manage Access')
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
                                            'title': getAAM().__('Edit')
                                        }));
                                        break;
                                        
                                    case 'no-edit' :
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-pencil'
                                        }));
                                        break;
                                        
                                    case 'pin' :
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-pin'
                                        }).bind('click', function () {
                                            var _btn = $(this);
                                            $.ajax(getLocal().ajaxurl, {
                                                type: 'POST',
                                                dataType: 'json',
                                                data: {
                                                    action: 'aam',
                                                    sub_action: 'PlusPackage.setDefaultTerm',
                                                    _ajax_nonce: getLocal().nonce,
                                                    id: data[0],
                                                    subject: getAAM().getSubject().type,
                                                    subjectId: getAAM().getSubject().id
                                                },
                                                beforeSend: function () {
                                                    $(_btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
                                                },
                                                error: function () {
                                                    getAAM().notification('danger', getAAM().__('Application error'));
                                                },
                                                complete: function () {
                                                    $('#post-list').DataTable().ajax.reload();
                                                }
                                            });
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Mark As Default')
                                        }));
                                        break;
                                        
                                    case 'pinned' :
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-danger icon-pin'
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Is Default Category')
                                        }));
                                        break;


                                    default:
                                        getAAM().triggerHook('post-action', {
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
                            beforeSend: function() {
                                var label = $('#post-reset').text();
                                $('#post-reset').attr('data-original-label', label);
                                $('#post-reset').text(getAAM().__('Resetting...'));
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    $('#post-overwritten').addClass('hidden');
                                    getAAM().loadAccessForm(type, id);
                                }
                            },
                            complete: function() {
                                $('#post-reset').text(
                                    $('#post-reset').attr('data-original-label')
                                );
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
                        getAAM().loadAccessForm(
                            $('#load-post-object-type').val(), 
                            $('#load-post-object').val()
                        );
                    }
                    
                    $('.extended-post-access-btn').each(function() {
                        $(this).bind('click', function() {
                           var _this = $(this);
                           var label = _this.text();
                           var value = $(_this.data('field')).val();
                           
                           _this.text(getAAM().__('Saving...'));
                           
                           save(
                                _this.attr('data-ref'),
                                value,
                                _this.attr('data-type'),
                                _this.attr('data-id'),
                                function(response) {
                                    if (response.status === 'success') {
                                        objectAccess.access[_this.attr('data-ref')] = value;
                                        $(_this.attr('data-preview')).text(response.preview);
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
                    
                    // post REDIRECT rules
                    $('#modal-redirect').on('show.bs.modal', function() {
                        $('.post-redirect-action').hide();
                        $('.post-redirect-value').val('');
                        $('.post-redirect-type').prop('checked', false);

                        if (getAAM().getSubject().type === 'visitor') {
                            $('#post-login-redirect-visitor').removeClass('hidden');
                        } else {
                            $('#post-login-redirect-visitor').addClass('hidden');
                        }
                        
                        if ($('#post-redirect-rule').val()) {
                            var rule = $('#post-redirect-rule').val().split('|');
                            $('.post-redirect-type[value="' + rule[0] + '"]').prop('checked', true);
                            $('#post-redirect-' + rule[0] + '-action').show();
                            $('#post-redirect-' + rule[0] + '-value').val(rule[1]);
                        }   
                    });
                    
                    $('.post-redirect-type').each(function() {
                       $(this).bind('click', function() {
                           $('#post-redirect-rule').val($(this).val());
                           $('.post-redirect-action').hide();
                           $('#post-redirect-' + $(this).val() + '-action').show();
                       });
                    });
                    
                    $('.post-redirect-value').each(function() {
                       $(this).bind('change', function() {
                           var val = $('#post-redirect-rule').val().split('|');
                           val[1] = $(this).val();
                           $('#post-redirect-rule').val(val.join('|'));
                       });
                    });
                    
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
                        sideBySide: true
                    });
                    
                    $('#modal-access-expires').on('show.bs.modal', function() {
                        if ($.trim($('#aam-expire-datetime').val())) {
                            $('#post-expiration-datapicker').data('DateTimePicker').defaultDate(
                                    $('#aam-expire-datetime').val()
                            );
                        } else {
                            $('#post-expiration-datapicker').data('DateTimePicker').defaultDate(
                                    new Date()
                            );
                        }
                    });
                    
                    $('#post-expiration-datapicker').on('dp.change', function(res) {
                        $('#aam-expire-datetime').val(
                                res.date.format('MM/DD/YYYY, h:mm a')
                        );
                    });
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
                getAAM().queueRequest(function() {
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
                        success: function(response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification(
                                'danger', getAAM().__('Application Error')
                            );
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
                            save(
                                $(this).attr('name'), 
                                $(this).val(), 
                                function(result) {
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
                getAAM().queueRequest(function() {
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
                        success: function(response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification(
                                'danger', getAAM().__('Application Error')
                            );
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
                            save(
                                $(this).attr('name'), 
                                val, 
                                function(result) {
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
                getAAM().queueRequest(function() {
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
                        success: function(response) {
                            successCallback(response);
                        },
                        error: function () {
                            getAAM().notification(
                                'danger', getAAM().__('Application Error')
                            );
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
                            save(
                                $(this).attr('name'), 
                                $(this).val(), 
                                function(result) {
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
            function save(param, value) {
                getAAM().queueRequest(function() {
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
                        error: function () {
                            getAAM().notification(
                                'danger', getAAM().__('Application error')
                            );
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
                var value = $(btn).hasClass('icon-check-empty') ? 1 : 0;
                
                getAAM().queueRequest(function() {
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
                            getAAM().notification(
                                'danger', getAAM().__('Application error')
                            );
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
                            {visible: false, targets: [0]},
                            {className: 'text-center', targets: [0, 1]}
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search Route'),
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
                            
                            $('td:eq(0)', row).html(
                                $('<small/>').text(data[1] === 'restful' ? 'JSON' : 'XML')
                            );
                            
                            $('td:eq(1)', row).html(method);
                            
                            var actions = data[4].split(',');

                            var container = $('<div/>', {'class': 'aam-row-actions'});
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
                            $('td:eq(3)', row).html(container);
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
                            $('.aam-uri-access-action').hide();
                            if ($(this).data('action')) {
                                $($(this).data('action')).show();
                            }
                        });
                    });
                    
                    //reset button
                    $('#uri-reset').bind('click', function () {
                        getAAM().reset('Main_Uri.reset', $(this));
                    });

                    $('#uri-save-btn').bind('click', function(event) {
                        event.preventDefault();

                        var uri = $('#uri-rule').val();
                        var type = $('input[name="uri.access.type"]:checked').val();
                        var val  = $('#uri-access-deny-' + type + '-value').val();
                        
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
                                    type: type,
                                    value: val,
                                    id: $('#uri-save-btn').attr('data-id')
                                },
                                beforeSend: function () {
                                    $('#uri-save-btn').text(getAAM().__('Saving...')).attr('disabled', true);
                                },
                                success: function (response) {
                                    if (response.status === 'success') {
                                        $('#uri-list').DataTable().ajax.reload();
                                    } else {
                                        getAAM().notification(
                                            'danger', getAAM().__('Failed to save URI rule')
                                        );
                                    }
                                },
                                error: function () {
                                    getAAM().notification(
                                        'danger', getAAM().__('Application error')
                                    );
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
                                id: $('#uri-delete-btn').data('id')
                            },
                            beforeSend: function () {
                                $('#uri-delete-btn').text(getAAM().__('Deleting...')).attr('disabled', true);
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    $('#uri-list').DataTable().ajax.reload();
                                } else {
                                    getAAM().notification('danger', getAAM().__('Failed to delete URI rule'));
                                }
                            },
                            error: function () {
                                getAAM().notification('danger', getAAM().__('Application error'));
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
                            searchPlaceholder: getAAM().__('Search URI'),
                            info: getAAM().__('_TOTAL_ URI(s)'),
                            infoFiltered: ''
                        },
                        columnDefs: [
                            {visible: false, targets: [0,2,3]}
                        ],
                        initComplete: function () {
                            var create = $('<a/>', {
                                'href': '#',
                                'class': 'btn btn-primary'
                            }).html('<i class="icon-plus"></i> ' + getAAM().__('Create'))
                            .bind('click', function () {
                                $('.form-clearable', '#uri-model').val('');
                                $('.aam-uri-access-action').hide();
                                $('input[type="radio"]', '#uri-model').prop('checked', false);
                                $('#uri-save-btn').removeAttr('data-id');
                                $('#uri-model').modal('show');
                            });

                            $('.dataTables_filter', '#uri-list_wrapper').append(create);
                        },
                        createdRow: function (row, data) {
                            var actions = data[4].split(',');

                            var container = $('<div/>', {'class': 'aam-row-actions'});
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-warning'
                                        }).bind('click', function () {
                                            $('.form-clearable', '#uri-model').val('');
                                            $('.aam-uri-access-action').hide();
                                            $('#uri-rule').val(data[1]);
                                            $('input[value="' + data[2] + '"]', '#uri-model').prop('checked', true).trigger('click');
                                            $('#uri-access-deny-' + data[2] + '-value').val(data[3]);
                                            $('#uri-save-btn').attr('data-id', data[0]);
                                            $('#uri-model').modal('show');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Edit Rule')
                                        }));
                                        break;

                                    case 'delete':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-trash-empty text-danger'
                                        }).bind('click', function () {
                                            $('#uri-delete-btn').attr('data-id', data[0]);
                                            $('#uri-delete-model').modal('show');
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Delete Rule')
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
            function generateJWT(expires) {
                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Main_Jwt.generate',
                        _ajax_nonce: getLocal().nonce,
                        subject: getAAM().getSubject().type,
                        subjectId: getAAM().getSubject().id,
                        expires: expires
                    },
                    beforeSend: function () {
                        $('#jwt-token-preview').val(getAAM().__('Generating token...'));
                        $('#jwt-url-preview').val(getAAM().__('Generating URL...'));
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
                        getAAM().notification('danger', getAAM().__('Application error'));
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

                    $('#create-jwt-modal').on('show.bs.modal', function() {
                        try{
                            var tomorrow = new Date();
                            tomorrow.setDate(tomorrow.getDate() + 1);
                            $('#jwt-expiration-datapicker').data('DateTimePicker').defaultDate(
                                tomorrow
                            );
                            $('#jwt-expires').val('');
                        } catch(e) {
                            // do nothing. Prevent from any kind of corrupted data
                        }
                    });
        
                    $('#jwt-expiration-datapicker').on('dp.change', function(res) {
                        $('#jwt-expires').val(
                            res.date.format('MM/DD/YYYY, H:mm Z')
                        );
                        generateJWT(
                            $('#jwt-expires').val()
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
                            searchPlaceholder: getAAM().__('Search Tokens'),
                            info: getAAM().__('_TOTAL_ token(s)'),
                            infoFiltered: '',
                            emptyTable: getAAM().__('No JWT tokens have been generated.'),
                            infoEmpty: getAAM().__('Nothing to show'),
                            lengthMenu: '_MENU_'
                        },
                        columnDefs: [
                            {visible: false, targets: [0, 1]},
                            {orderable: false, targets: [0, 1, 2, 4]}
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
                            if (data[2] === 'valid') {
                                $('td:eq(0)', row).html(
                                    '<i class="icon-ok-circled text-success"></i>'
                                );
                            } else {
                                $('td:eq(0)', row).html(
                                    '<i class="icon-cancel-circled text-danger"></i>'
                                );
                            }

                            var actions = data[4].split(',');

                            var container = $('<div/>', {'class': 'aam-row-actions'});
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

                    $('#create-jwt-btn').bind('click', function() {
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
                                getAAM().notification(
                                    'danger', getAAM().__('Application error')
                                );
                            },
                            complete: function() {
                                $('#create-jwt-btn').html(getAAM().__('Create'));
                            }
                        });
                    });

                    $('#jwt-delete-btn').bind('click', function() {
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
                                getAAM().notification(
                                    'danger', getAAM().__('Application error')
                                );
                            },
                            complete: function() {
                                $('#jwt-delete-btn').html(getAAM().__('Delete'));
                            }
                        });
                    });
                }
            }
            
            getAAM().addHook('init', initialize);
            
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
                $.ajax(getLocal().system.apiEndpoint + '/download', {
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        license: data.license,
                        domain: getLocal().system.domain,
                        uid: getLocal().system.uid
                    },
                    success: function (package) {
                        if (package.error === true) {
                            getAAM().notification('danger', package.message);
                        } else {
                            $.ajax(getLocal().ajaxurl, {
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'aam',
                                    sub_action: 'Extension_Manager.install',
                                    _ajax_nonce: getLocal().nonce,
                                    license: data.license,
                                    package: package
                                },
                                success: function (response) {
                                    if (response.status === 'success') {
                                        setTimeout(function () {
                                            getAAM().fetchContent('extensions');
                                        }, 500);
                                    } else {
                                        getAAM().notification('danger', response.error);
                                        if (typeof package.content !== 'undefined') {
                                            dump = package;
                                            $('#installation-error').text(response.error);
                                            $('#extension-notification-modal').modal('show');
                                        }
                                    }
                                },
                                error: function () {
                                    getAAM().notification(
                                        'danger', 
                                        getAAM().__('Application error')
                                    );
                                },
                                complete: function() {
                                    cb();
                                }
                            });
                        }
                    },
                    error: function (response) {
                        getAAM().notification(
                            'danger', response.responseJSON.message
                        );
                    }
                });
            }
            
            /**
             * 
             * @param {type} data
             * @returns {undefined}
             */
            function updateStatus(data) {
                getAAM().queueRequest(function() {
                    $.ajax(getLocal().ajaxurl, {
                        type: 'POST',
                        dataType: 'json',
                        data: data,
                        success: function (response) {
                            if (response.status === 'success') {
                                getAAM().notification(
                                    'success', 
                                    getAAM().__('Extension status was updated successfully')
                                );
                            } else {
                                getAAM().notification(
                                    'danger', getAAM().__(response.error)
                                );
                            }
                        },
                        error: function () {
                            getAAM().notification(
                                'danger', getAAM().__('Application error')
                            );
                        },
                        complete: function () {
                            location.reload();
                        }
                    });
                });
            }

            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                if ($('#extension-content').length) {
                    $('[data-toggle="toggle"]', '.extensions-metabox').bootstrapToggle();
                    
                    //check for updates
                    $('#aam-update-check').bind('click', function() {
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Extension_Manager.check',
                                _ajax_nonce: getLocal().nonce
                            },
                            beforeSend: function () {
                                $('#aam-update-check i').attr('class', 'icon-spin4 animate-spin');
                            },
                            complete: function () {
                                getAAM().fetchContent('extensions');
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
                            _ajax_nonce: getLocal().nonce,
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
                                _ajax_nonce: getLocal().nonce,
                                license: _this.data('license')
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
                                _ajax_nonce: getLocal().nonce,
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
                                _ajax_nonce: getLocal().nonce,
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
                                _ajax_nonce: getLocal().nonce,
                                license: _this.data('license')
                            }, function() {
                                $('i', _this).attr('class', 'icon-download-cloud');
                            });
                        });
                    });
                    
                    $('#fix-extension-dir-issue').bind('click', function(event) {
                        event.preventDefault();
                        
                        $('i', this).attr('class', 'icon-spin4 animate-spin');
                        
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Extension_Manager.fixDirectoryIssue',
                                _ajax_nonce: getLocal().nonce
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    $('#extension-dir-warning').remove();
                                    getAAM().notification(
                                        'success', 
                                        getAAM().__('The issue has been resolved')
                                    );
                                } else {
                                    $('#extension-dir-issue-modal').modal('show');
                                }
                            },
                            error: function() {
                                getAAM().notification('danger', getAAM().__('Application error'));
                            },
                            complete: function () {
                                $('i', '#fix-extension-dir-issue').attr('class', 'icon-wrench');
                            }
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

            getAAM().addHook('init', initialize);

        })(jQuery);
        
        /**
         * Get Started Interface
         * 
         * @param {type} $
         * 
         * @returns {undefined}
         */
        (function ($) {
            
            /**
             * 
             * @returns {undefined}
             */
            function initialize() {
                $('#ack-get-started').bind('click', function () {
                    getAAM().queueRequest(function() {
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Settings_Manager.save',
                                _ajax_nonce: getLocal().nonce,
                                param: 'core.settings.getStarted',
                                value: 0
                            },
                            beforeSend: function() {
                                $('#ack-get-started').text(
                                        getAAM().__('Saving...')
                                );
                            },
                            success: function() {
                                location.reload();
                            },
                            error: function () {
                                getAAM().notification(
                                    'danger', getAAM().__('Application Error')
                                );
                                $('#ack-get-started').text(
                                        getAAM().__('OK, got it')
                                );
                            }
                        });
                    });
                });
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
                getAAM().queueRequest(function() {
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
                            getAAM().notification(
                                'danger', getAAM().__('Application Error')
                            );
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
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Settings_Manager.clearSettings',
                                _ajax_nonce: getLocal().nonce
                            },
                            beforeSend: function() {
                                $('#clear-settings').prop('disabled', true);
                                $('#clear-settings').text(getAAM().__('Wait...'));
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    getAAM().notification(
                                        'success', 
                                        getAAM().__('All settings has been cleared successfully')
                                    );
                                } else {
                                    getAAM().notification('danger', response.reason);
                                }
                            },
                            error: function () {
                                getAAM().notification('danger', getAAM().__('Application Error'));
                            },
                            complete: function() {
                                $('#clear-settings').prop('disabled', false);
                                $('#clear-settings').text(getAAM().__('Clear'));
                                $('#clear-settings-modal').modal('hide');
                            }
                        });
                    });
                    
                    $('#clear-cache').bind('click', function () {
                        $.ajax(getLocal().ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Settings_Manager.clearCache',
                                _ajax_nonce: getLocal().nonce
                            },
                            beforeSend: function() {
                                $('#clear-cache').prop('disabled', true);
                                $('#clear-cache').text(getAAM().__('Wait...'));
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    getAAM().notification(
                                        'success', 
                                        getAAM().__('The cache has been cleared successfully')
                                    );
                                } else {
                                    getAAM().notification('danger', response.reason);
                                }
                            },
                            error: function () {
                                getAAM().notification('danger', getAAM().__('Application Error'));
                            },
                            complete: function() {
                                $('#clear-cache').prop('disabled', false);
                                $('#clear-cache').text(getAAM().__('Clear'));
                            }
                        });
                    });
                }
            }

            getAAM().addHook('init', initialize);
            
            //ConfigPress hook
            getAAM().addHook('menu-feature-click', function(feature) {
                if (feature === 'configpress' 
                        && !$('#configpress-editor').next().hasClass('CodeMirror')) {
                    var editor = CodeMirror.fromTextArea(
                        document.getElementById("configpress-editor"), {}
                    );

                    editor.on("blur", function(){
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
                                getAAM().notification(
                                    'danger', 
                                    getAAM().__('Application error')
                                );
                            }
                        });
                    });
                } 
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
        
        $(document).ajaxComplete(function() {
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
    AAM.prototype.queueRequest = function(request) {
        this.queue.requests.push(request);
        
        if (this.queue.processing === false) {
            this.queue.processing = true;
            this.queue.requests.shift().call(this);
        }
    };
    
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
     * @param {type} view
     * @returns {undefined}
     */
    AAM.prototype.fetchContent = function (view) {
        var _this = this;
        
        //referred object ID like post, page or any custom post type
        var object   = window.location.search.match(/&oid\=([^&]*)/);
        var type     = window.location.search.match(/&otype\=([^&]*)/);
        
        var data = {
            action: 'aamc',
            _ajax_nonce: getLocal().nonce,
            uiType: view,
            subject: this.getSubject().type,
            subjectId: this.getSubject().id,
            oid: object ? object[1] : null,
            otype: type ? type[1] : null
        };
        
    if (getAAM().isUI('main') && (typeof aamEnvData !== 'undefined')) {
            data.menu = aamEnvData.menu;
            data.submenu = aamEnvData.submenu;
            data.toolbar = aamEnvData.toolbar;
        }
        
        $.ajax(getLocal().url.site, {
            type: 'POST',
            dataType: 'html',
            data: data,
            beforeSend: function () {
                if ($('#aam-initial-load').length === 0) {
                    $('#aam-content').html(
                        $('<div/>', {'class': 'aam-loading'}).append($('<i/>', {
                            'class': 'icon-spin4 animate-spin'
                            })
                        )
                    );
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
     * @returns {undefined}
     */
    AAM.prototype.fetchPartial = function (view, success) {
        var _this = this;
        
        //referred object ID like post, page or any custom post type
        var object   = window.location.search.match(/&oid\=([^&]*)/);
        var type     = window.location.search.match(/&otype\=([^&]*)/);

        $.ajax(getLocal().url.site, {
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'aamc',
                _ajax_nonce: getLocal().nonce,
                uiType: view,
                subject: this.getSubject().type,
                subjectId: this.getSubject().id,
                oid: object ? object[1] : null,
                otype: type ? type[1] : null
            },
            success: function (response) {
                success.call(_this, response);
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
        //read default subject and set it for AAM object
        if (getLocal().subject.type) {
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
        
        //help tooltip
        $('body').delegate('[data-toggle="tooltip"]', 'hover', function (event) {
            event.preventDefault();
            $(this).tooltip({
                'placement' : $(this).data('placement') || 'top',
                'container' : 'body'
            });
            $(this).tooltip('show');
        });
        
        $('body').delegate('.aam-switch-user', 'click', function () {
            switchToUser(getAAM().getSubject().id, $(this), false);
        });
        
        $('.aam-area').each(function() {
           $(this).bind('click', function() {
               $('.aam-area').removeClass('text-danger');
               $(this).addClass('text-danger');
               getAAM().fetchContent($(this).data('type')); 
           });
        });

        // preventDefault for all links with # href
        $('#aam-container').delegate('a[href="#"]', 'click', function(event) {
            event.preventDefault();
        });

        // Initialize clipboard
        var clipboard = new ClipboardJS('.aam-copy-clipboard');

        clipboard.on('success', function(e) {
            getAAM().notification('success', 'Data has been saved to clipboard');
        });

        clipboard.on('error', function(e) {
            getAAM().notification('danger', 'Failed to save data to clipboard');
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
        
        //update the header
        // First set the type of the subject
        $('.aam-current-subject').text(
            type.charAt(0).toUpperCase() + type.slice(1) + ': '
        );

        // Second set the name of the subject
        $('.aam-current-subject').append($('<strong/>').text(name));

        if (type === 'user') {
            $('.aam-current-subject').append(
                '<i data-toggle="tooltip" title="Switch To User" data-placement="right" class="icon-exchange aam-switch-user"></i>'
            );
        }
        //highlight screen if the same level
        if (parseInt(level) >= getLocal().level || type === 'default') {
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
     * @param {type} object
     * @param {type} btn
     * @returns {undefined}
     */
    AAM.prototype.reset = function(sub_action, btn) {
        getAAM().queueRequest(function() {
            $.ajax(getLocal().ajaxurl, {
                type: 'POST',
                data: {
                    action: 'aam',
                    sub_action: sub_action,
                    _ajax_nonce: getLocal().nonce,
                    subject: this.getSubject().type,
                    subjectId: this.getSubject().id,
                },
                beforeSend: function() {
                    var label = btn.text();
                    btn.attr('data-original-label', label);
                    btn.text(getAAM().__('Resetting...'));
                },
                success: function () {
                    getAAM().fetchContent('main');
                },
                error: function () {
                    getAAM().notification(
                        'danger', getAAM().__('Application error')
                    );
                },
                complete: function() {
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
    AAM.prototype.isUI = function(type) {
        return (getLocal().ui === type);
    };

    /**
     * Initialize UI
     */
    $(document).ready(function () {
        $.aam = aam = new AAM();
        getAAM().initialize();
    });
    
    /**
     * 
     * @returns {aamLocal}
     */
    function getLocal() {
        return aamLocal;
    }
    
    /**
     * 
     * @returns {aamL#14.AAM|AAM}
     */
    function getAAM() {
        return aam;
    }

})(jQuery);