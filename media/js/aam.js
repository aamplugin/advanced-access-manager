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
     * Internal cache
     */
    const cache = {
        roles: null
    };

    /**
     * Reset cache value
     *
     * @param {string} ns
     *
     * @returns {void}
     */
    function ResetCache(ns) {
        cache[ns] = null;
    }

    /**
     * Get list of roles
     *
     * @param {callback} cb
     *
     * @returns {void}
     */
    function GetRoles(cb) {
        if (cache.roles === null) {
            $.ajax(`${getLocal().rest_base}aam/v2/roles`, {
                type: 'GET',
                headers: {
                    'X-WP-Nonce': getLocal().rest_nonce
                },
                dataType: 'json',
                success: function (response) {
                    cache.roles = response; // cache the roles

                    cb(response);
                }
            });
        } else {
            cb(cache.roles);
        }
    }

    /**
     *
     * @returns {undefined}
     */
    function UI() {

        /**
         * Security score tab
         */
        (function($) {
            if ($('#security_gauge').length) {
                Gauge(document.getElementById('security_gauge'), {
                    min: 0,
                    max: 100,
                    dialStartAngle: 180,
                    dialEndAngle: 0,
                    value: $('#security_gauge').data('score'),
                    label: function(value) {
                        return value;
                    },
                    color: function(value) {
                        let result = '#3c763d';

                        if(value < 75) {
                            result = '#a94442';
                        } else if(value <= 90) {
                            result = '#8a6d3b';
                        }

                        return result;
                    }
                });
            }

            $('#security_audit_tab').bind('click', function () {
                $('.aam-area').removeClass('text-danger');
                getAAM().fetchContent('audit', () => {
                    $('#run_security_scan').trigger('click');
                });
            });
            $('#goto_security_audit_tab').bind('click', function () {
                $('.aam-area').removeClass('text-danger');
                getAAM().fetchContent('audit');
            });
        })(jQuery);

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
             * Load the list of roles
             *
             * @param {type} exclude
             */
            function LoadRolesDropdown(exclude, cb = null) {
                // Display the indicator that the list of roles is loading
                $('.aam-role-list').html(
                    '<option value="">' + getAAM().__('Loading...') + '</option>'
                );

                GetRoles((response) => {
                    $('.aam-role-list').html(
                        '<option value="">' + getAAM().__('No role') + '</option>'
                    );

                    for (var i in response) {
                        if (exclude !== response[i].slug) {
                            $('.aam-role-list').append(
                                '<option value="' + response[i].slug + '">' + response[i].name + '</option>'
                            );
                        }
                    }

                    if ($.aamEditRole) {
                        $('.aam-role-list').val($.aamEditRole[0]);
                    }

                    getAAM().triggerHook('post-get-role-list', {
                        list: response
                    });

                    if (cb) {
                        cb();
                    }

                    //TODO - Rewrite JavaScript to support $.aam
                    $.aamEditRole = null;
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

                $('.error-container', container).addClass('hidden');
            }

            /**
             *
             * @param {*} role
             * @returns
             */
            function prepareRoleEndpoint(role) {
                return getLocal().rest_base + 'aam/v2/role/' + encodeURIComponent(role);
            }

            /**
             *
             */
            function initialize() {
                if (!$('#role-list').hasClass('dataTable')) {
                    const fields = [
                        'user_count',
                        'permissions'
                    ];

                    getAAM().applyFilters('role-list-fields', fields);

                    // Prepare the RESTful API endpoint
                    let url = `${getLocal().rest_base}aam/v2/roles`;

                    if (url.indexOf('rest_route') === -1) {
                        url += `?fields=${fields.join(',')}`;
                    } else {
                        url += `&fields=${fields.join(',')}`;
                    }

                    // Initialize the role list table
                    $('#role-list').DataTable({
                        autoWidth: false,
                        ordering: true,
                        dom: 'ftrip',
                        pagingType: 'simple',
                        processing: true,
                        stateSave: true,
                        serverSide: false,
                        ajax: {
                            url,
                            type: 'GET',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            dataType: 'json',
                            dataSrc: function (json) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(json, (_, role) => {
                                    const actions = [];

                                    if (role.permissions.includes('allow_manage')) {
                                        actions.push('manage');
                                    }

                                    if (role.permissions.includes('allow_edit')) {
                                        actions.push('edit');
                                    } else {
                                        actions.push('no-edit');
                                    }

                                    if (role.permissions.includes('allow_delete')) {
                                        actions.push('delete');
                                    } else {
                                        actions.push('no-delete');
                                    }

                                    if (role.permissions.includes('allow_clone')) {
                                        actions.push('clone');
                                    } else {
                                        actions.push('no-clone');
                                    }

                                    data.push([
                                        role.slug,
                                        role.user_count,
                                        role.name,
                                        actions.join(','),
                                        0,
                                        role
                                    ])
                                });

                                return data;
                            },
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
                                    '<span class="aam-highlight">' + data[2] + '</span>'
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
                                        getAAM().__('Users') + ': <b>' + parseInt(data[1]) + '</b>; Slug: <b>' + data[0] + '</b>',
                                        data[5]
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
                                            var title = $('td:eq(0) span', row).html();

                                            getAAM().setSubject('role', data[0], title, data[4]);

                                            $('td:eq(0) span', row).replaceWith(
                                                '<span class="aam-highlight">' + title + '</span>'
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
                                                getAAM().triggerHook('load-access-form', [
                                                    {
                                                        resource_type: $('#content_resource_type').val(),
                                                        resource_id: $('#content_resource_id').val()
                                                    },
                                                    function() {
                                                        $('i.icon-spin4', container).attr(
                                                            'class', 'aam-row-action icon-cog text-muted'
                                                        );
                                                    }
                                                ]);
                                            }
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Manage role')
                                        }));
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

                                                if (data[1] > 0) {
                                                    $('#edit-role-slug').prop('disabled', true);
                                                } else {
                                                    $('#edit-role-slug').prop('disabled', false);
                                                }

                                                LoadRolesDropdown(data[0], () => {
                                                    getAAM().triggerHook(
                                                        'edit-role-modal',
                                                        data[5]
                                                    );
                                                });

                                                //TODO - Rewrite JavaScript to support $.aam
                                                $.aamEditRole = data;
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

                    $('#add-role-modal').on('show.bs.modal', function (e) {
                        LoadRolesDropdown();

                        //clear add role form first
                        $('input', '#add-role-modal').val('');
                        $('input[name="name"]', '#add-role-modal').focus();
                    });

                    $('#edit-role-modal').on('show.bs.modal', function () {
                        $('input[name="name"]', '#edit-role-modal').focus();
                    });

                    //add role button
                    $('#add-role-btn').bind('click', function () {
                        var _this = this;

                        ResetCache('roles');

                        $('input[name="name"]', '#add-role-modal').parent().removeClass(
                            'has-error'
                        );

                        var data = {};

                        $('input,select', '#add-role-modal .modal-body').each(function () {
                            if ($(this).attr('name')) {
                                if ($(this).attr('type') === 'checkbox') {
                                    data[$(this).attr('name')] = $(this).is(':checked') ? true : false;
                                } else {
                                    const val = $.trim($(this).val());

                                    if (val) {
                                        data[$(this).attr('name')] = val;
                                    }
                                }
                            }
                        });


                        if (data.name) {
                            $.ajax(`${getLocal().rest_base}aam/v2/roles`, {
                                type: 'POST',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce
                                },
                                dataType: 'json',
                                data: data,
                                beforeSend: function () {
                                    $('.error-container').addClass('hidden');
                                    $(_this).text(getAAM().__('Saving...')).attr('disabled', true);
                                },
                                success: function (response) {
                                    getAAM().setSubject(
                                        'role',
                                        response.slug,
                                        response.name
                                    );

                                    getAAM().fetchContent('main');
                                    $('#role-list').DataTable().ajax.reload();

                                    $('#add-role-modal').modal('hide');
                                },
                                error: function (err) {
                                    $('.error-container').removeClass('hidden');

                                    // Error summary
                                    $('#role-error-summary').text(
                                        'Failed to create new role for the following reason(s)'
                                    );
                                    $('#role-error-list').empty();

                                    $.each(err.responseJSON.errors, (_, e) => {
                                        $('#role-error-list').append(`<li>${e[0]}</li>`);
                                    });
                                },
                                complete: function () {
                                    $(_this).text(getAAM().__('Add role')).attr('disabled', false);
                                }
                            });
                        } else {
                            $('input[name="name"]', '#add-role-modal').focus().parent().addClass(
                                'has-error'
                            );
                        }
                    });

                    //edit role button
                    $('#edit-role-btn').bind('click', function () {
                        var _this = this;

                        ResetCache('roles');

                        $('#edit-role-name').parent().removeClass('has-error');
                        $('#edit-role-slug').parent().removeClass('has-error');

                        const data = {};

                        $('input,select', '#edit-role-modal .modal-body').each(function () {
                            if ($(this).attr('name')) {
                                if ($(this).attr('type') === 'checkbox') {
                                    data[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
                                } else if (!$(this).prop('disabled')) {
                                    const v = $.trim($(this).val());

                                    data[$(this).attr('name')] = v;
                                }
                            }
                        });

                        if (data.name) {
                            $.ajax(prepareRoleEndpoint($(_this).data('role')), {
                                type: 'POST',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'PATCH'
                                },
                                dataType: 'json',
                                data: data,
                                beforeSend: function () {
                                    $('.error-container').addClass('hidden');
                                    $(_this).text(getAAM().__('Saving...')).attr('disabled', true);
                                },
                                success: function (response) {
                                    // If role's slug changed, update the current subject
                                    if (data.new_slug && $(_this).data('role') !== data.new_slug) {
                                        getAAM().setSubject(
                                            'role',
                                            response.slug,
                                            response.name
                                        );
                                    }

                                    location.reload();
                                },
                                error: function (err) {
                                    $('.error-container').removeClass('hidden');

                                    // Error summary
                                    $('#edit-role-error-summary').text(
                                        'Failed to update role for the following reason(s)'
                                    );
                                    $('#edit-role-error-list').empty();

                                    $.each(err.responseJSON.errors, (_, e) => {
                                        $('#edit-role-error-list').append(`<li>${e[0]}</li>`);
                                    });
                                },
                                complete: function () {
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

                        ResetCache('roles');

                        $.ajax(prepareRoleEndpoint($(_this).data('role')), {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            beforeSend: function () {
                                $(_this).text(getAAM().__('Deleting...')).attr('disabled', true);
                            },
                            success: function () {
                                var subject = getAAM().getSubject();

                                if (subject.type === 'role'
                                    && subject.id === $(_this).data('role')
                                ) {
                                    location.reload();
                                } else {
                                    $('#role-list').DataTable().ajax.reload();
                                }
                            },
                            error: function (response) {
                                getAAM().notification(
                                    'danger',
                                    getAAM().__('I\'m having trouble deleting the role.'),
                                    {
                                        request: `aam/v2/role/${$(_this).data('role')}`,
                                        response: response.responseJSON
                                    }
                                );
                            },
                            complete: function () {
                                $('#delete-role-modal').modal('hide');

                                $(_this).text(
                                    getAAM().__('Delete role')
                                ).attr('disabled', false);
                            }
                        });
                    });
                }
            }

            //add setSubject hook
            getAAM().addHook('access-level-changed', function () {
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

            getAAM().addHook('init', initialize);

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
             * Update user status
             *
             * @param {number} id
             * @param {object} btn
             *
             * @returns {void}
             */
            function updateUserStatus(id, btn) {
                const status = ($(btn).hasClass('icon-lock') ? 'active' : 'inactive');

                $.ajax({
                    url: `${getLocal().rest_base}aam/v2/user/${id}?fields=status`,
                    type: 'POST',
                    headers: {
                        'X-WP-Nonce': getLocal().rest_nonce
                    },
                    data: {
                        status
                    },
                    beforeSend: function () {
                        $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
                    },
                    success: function (response) {
                        if (response.status === 'inactive') {
                            $(btn).attr({
                                'class': 'aam-row-action icon-lock text-danger',
                                'title': getAAM().__('Unlock user'),
                                'data-original-title': getAAM().__('Unlock user')
                            });
                        } else {
                            $(btn).attr({
                                'class': 'aam-row-action icon-lock-open text-success',
                                'title': getAAM().__('Lock user'),
                                'data-original-title': getAAM().__('Lock user')
                            });
                        }
                    },
                    error: function (response) {
                        getAAM().notification('danger', response, {
                            request: `aam/v2/user/${id}?fields=status`,
                            payload: { status },
                            response
                        });
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
                    const type = $('#action-after-expiration').val();

                    const payload = {
                        user_id: $('#reset-user-expiration-btn').attr('data-user-id'),
                        expires_at: $('#user-expires').val(),
                    };

                    if (type) {
                        payload.additional_claims = {
                            trigger: {
                                type
                            }
                        }

                        if (type === 'change_role') {
                            payload.additional_claims.trigger.to_role = $('#expiration-change-role').val();
                        }
                    }

                    $.ajax(`${getLocal().rest_base}aam/v2/jwts`, {
                        type: 'POST',
                        dataType: 'json',
                        data: payload,
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        beforeSend: function () {
                            $('#login-url-preview').val(getAAM().__('Generating URL...'));
                        },
                        success: function (response) {
                            $('#login-url-preview').val(
                                $('#login-url-preview').data('url').replace('%s', response.token)
                            );
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: 'aam/v2/jwts',
                                payload,
                                response
                            });
                        }
                    });
                }
            }

            // Initialize the user list table
            $('#user-list').DataTable({
                autoWidth: false,
                ordering: false,
                dom: 'ftrip',
                stateSave: true,
                pagingType: 'simple',
                serverSide: true,
                processing: true,
                ajax: function(filters, cb) {
                    const fields = [
                        'roles',
                        'display_name',
                        'permissions',
                        'user_level',
                        'expiration'
                    ];

                    if(getAAM().isUI('principal')) {
                        fields.push('policies');
                    }

                    $.ajax({
                        url: `${getLocal().rest_base}aam/v2/users`,
                        type: 'GET',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        data: {
                            search: filters.search.value,
                            per_page: filters.length,
                            offset: filters.start,
                            fields: fields.join(','),
                            role: $('#user-list-filter').val()
                        },
                        success: function (response) {
                            const result = {
                                data: [],
                                recordsTotal: 0,
                                recordsFiltered: 0
                            };

                            // Transform the received data into DT format
                            const policyId = parseInt($('#aam-policy-id').val(), 10);

                            $.each(response.list, (_, user) => {
                                const actions = [];

                                if (getLocal().ui === 'principal' && policyId) {
                                    if (user.policies && user.policies.includes(policyId)) {
                                        actions.push('detach');
                                    } else {
                                        actions.push('attach');
                                    }
                                } else {
                                    if (user.permissions.includes('allow_manage')) {
                                        actions.push('manage');
                                    }

                                    if (user.permissions.includes('allow_edit')) {
                                        actions.push('edit');
                                    }

                                    if (user.permissions.includes('allow_unlock')) {
                                        actions.push('unlock');
                                    } else if (user.permissions.includes('allow_lock')) {
                                        actions.push('lock');
                                    }
                                }

                                result.data.push([
                                    user.id,
                                    user.roles.join(', '),
                                    user.display_name,
                                    actions.join(','),
                                    user.user_level,
                                    user.expiration || null
                                ]);
                            });

                            result.recordsTotal    = response.summary.total_count;
                            result.recordsFiltered = response.summary.filtered_count;

                            cb(result);
                        }
                    });
                },
                columnDefs: [
                    { visible: false, targets: [0, 1, 4, 5] }
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
                            .html('<option value="">' + getAAM().__('Loading...') + '</option>')
                            .bind('change', function () {
                                $('#user-list').DataTable().ajax.reload();
                            });

                        $('.dataTables_filter', '#user-list_wrapper').append(filter);

                        GetRoles((response) => {
                            $('#user-list-filter').html(
                                '<option value="">' + getAAM().__('Filter by role') + '</option>'
                            );

                            for (var i in response) {
                                $('#user-list-filter').append(
                                    '<option value="' + response[i].slug + '">' + response[i].name + '</option>'
                                );
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
                                                getAAM().triggerHook('load-access-form', [
                                                    {
                                                        resource_type: $('#content_resource_type').val(),
                                                        resource_id: $('#content_resource_id').val()
                                                    },
                                                    function() {
                                                        $('i.icon-spin4', container).attr(
                                                            'class', 'aam-row-action icon-cog text-muted'
                                                        );
                                                    }
                                                ]);
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
                                                $('#user-expires').val(data[5].expires_at);
                                                $('#action-after-expiration').val(data[5].trigger.type);

                                                if (data[5].trigger.type === 'change_role') {
                                                    $('#expiration-change-role-holder').removeClass('hidden');
                                                    getAAM().loadRoleList(data[5].trigger.to_role);
                                                } else {
                                                    getAAM().loadRoleList();
                                                    $('#expiration-change-role-holder').addClass('hidden');
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

                                case 'lock':
                                    if (getAAM().isUI('main')) {
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-lock-open text-success'
                                        }).bind('click', function () {
                                            updateUserStatus(data[0], $(this));
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
                                            updateUserStatus(data[0], $(this));
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Unlock user')
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
                if ($(this).val() === 'change_role') {
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
                $('#user-expires').val(res.date.format());
            });

            //edit role button
            $('#edit-user-expiration-btn').bind('click', function () {
                var _this = this;

                // Get currently editing user ID
                const id = $(_this).attr('data-user-id');

                // Preparing the payload
                const payload = {
                    expires_at: $('#user-expires').val()
                };

                // Gathering expiration attributes
                const expiration_trigger = $('#action-after-expiration').val() || 'logout';

                if (expiration_trigger) {
                    payload.trigger = {
                        type: expiration_trigger
                    };

                    if (expiration_trigger === 'change_role') {
                        payload.trigger.to_role = $('#expiration-change-role').val();
                    }
                }

                $.ajax({
                    url: `${getLocal().rest_base}aam/v2/user/${id}`,
                    type: 'POST',
                    headers: {
                        'X-WP-Nonce': getLocal().rest_nonce
                    },
                    data: {
                        expiration: payload
                    },
                    beforeSend: function () {
                        $(_this).text(getAAM().__('Saving...')).attr('disabled', true);
                    },
                    success: function () {
                        $('#user-list').DataTable().ajax.reload();
                    },
                    error: function (response) {
                        getAAM().notification('danger', response, {
                            request: `aam/v2/user/${id}`,
                            payload: { expiration: payload },
                            response
                        });
                    },
                    complete: function () {
                        $('#edit-user-modal').modal('hide');
                        $(_this).text(getAAM().__('Save')).attr('disabled', false);
                    }
                });
            });

            // Reset user
            $('#reset-user-expiration-btn').bind('click', function () {
                var _this = this;

                const id = $(_this).attr('data-user-id');

                $.ajax({
                    url: `${getLocal().rest_base}aam/v2/user/${id}`,
                    type: 'POST',
                    headers: {
                        'X-WP-Nonce': getLocal().rest_nonce,
                        'X-HTTP-Method-Override': 'DELETE'
                    },
                    beforeSend: function () {
                        $(_this).text(getAAM().__('Resetting...')).attr('disabled', true);
                    },
                    success: function () {
                        $('#user-list').DataTable().ajax.reload();
                    },
                    error: function (response) {
                        getAAM().notification('danger', response, {
                            request: `aam/v2/user/${id}`,
                            response
                        });
                    },
                    complete: function () {
                        $('#edit-user-modal').modal('hide');
                        $(_this).text(getAAM().__('Reset')).attr('disabled', false);
                    }
                });
            });

            //add setSubject hook
            getAAM().addHook('access-level-changed', function () {
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
                    var _this = $(this);

                    getAAM().setSubject('visitor', null, getAAM().__('Anonymous'), 0);
                    $('i.icon-cog', _this).attr('class', 'icon-spin4 animate-spin');

                    if (getAAM().isUI('main')) {
                        getAAM().fetchContent('main');
                        $('i.icon-spin4', _this).attr('class', 'icon-cog');
                    } else if (getAAM().isUI('post')) {
                        getAAM().triggerHook('load-access-form', [
                            {
                                resource_type: $('#content_resource_type').val(),
                                resource_id: $('#content_resource_id').val()
                            },
                            function() {
                                $('i.icon-spin4', _this).attr('class', 'icon-cog');
                            }
                        ]);
                    }
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
                    var _this = $(this);

                    getAAM().setSubject(
                        'default', null, getAAM().__('All Users, Roles and Visitor'), 0
                    );

                    $('i.icon-cog', _this).attr('class', 'icon-spin4 animate-spin');
                    if (getAAM().isUI('main')) {
                        getAAM().fetchContent('main');
                        $('i.icon-spin4', _this).attr('class', 'icon-cog');
                    } else if (getAAM().isUI('post')) {
                        getAAM().triggerHook('load-access-form', [
                            {
                                resource_type: $('#content_resource_type').val(),
                                resource_id: $('#content_resource_id').val()
                            },
                            function() {
                                $('i.icon-spin4', _this).attr('class', 'icon-cog');
                            }
                        ]);
                    }
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
             * @param {type} id
             * @param {type} effect
             * @param {type} btn
             *
             * @returns {undefined}
             */
            function TogglePolicy(id, effect, btn) {
                getAAM().queueRequest(function () {
                    const payload = {
                        effect
                    };

                    // Show indicator
                    $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');

                    const endpoint = getAAM().prepareApiEndpoint(
                        '/policy/' + id
                    );

                    $.ajax(endpoint, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'PUT'
                        },
                        dataType: 'json',
                        data: payload,
                        success: function () {
                            $('#aam-policy-overwrite').show();

                            if (effect === 'attach') {
                                $(btn).attr(
                                    'class',
                                    'aam-row-action icon-check'
                                );
                            } else {
                                $(btn).attr(
                                    'class',
                                    'aam-row-action icon-check-empty'
                                );
                            }
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: '/policy/' + id,
                                payload,
                                response
                            });
                        }
                    });
                });
            }

            /**
             *
             * @param {*} al_type
             * @param {*} al_id
             * @param {*} policy_id
             * @param {*} effect
             * @param {*} cb
             */
            function ToggleAccessLevelPolicy(al_type, al_id, policy_id, effect, cb) {
                getAAM().queueRequest(function () {
                    const payload = {
                        effect
                    };

                    const endpoint = getAAM().prepareApiEndpoint(
                        '/policy/' + policy_id, true, {
                            type: al_type,
                            id: al_id
                        }
                    );

                    $.ajax(endpoint, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'PUT'
                        },
                        dataType: 'json',
                        data: payload,
                        success: function () {
                            cb();
                        }
                    });
                });
            }

            /**
             * Delete policy
             *
             * @param {Int}  id
             */
            function DeletePolicy(id, btn) {
                const endpoint = getAAM().prepareApiEndpoint(
                    '/policy/' + id
                );

                getAAM().queueRequest(function () {
                    $.ajax(endpoint, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'DELETE'
                        },
                        dataType: 'json',
                        beforeSend: function () {
                            $(btn).attr('data-original', $(btn).text());
                            $(btn).text(getAAM().__('Deleting...')).attr(
                                'disabled', true
                            );
                        },
                        success: function () {
                            $('#policy_list').DataTable().ajax.reload();
                        },
                        error: function (response) {
                            getAAM().notification('danger', response);
                        },
                        complete: function () {
                            $('#delete-policy-modal').modal('hide');

                            $(btn).text($(btn).attr('data-original')).attr(
                                'disabled', false
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
                var container = '#policy-content';

                if ($(container).length) {
                    // Reset button
                    $('#policy_reset').bind('click', function () {
                        const btn      = this;
                        const endpoint = getAAM().prepareApiEndpoint(
                            '/policies'
                        );

                        getAAM().queueRequest(function () {
                            $.ajax(endpoint, {
                                type: 'POST',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                dataType: 'json',
                                beforeSend: function () {
                                    $(btn).text(getAAM().__('Resetting...')).attr(
                                        'disabled', true
                                    );
                                },
                                success: function () {
                                    $('#policy_list').DataTable().ajax.reload();
                                },
                                error: function (response) {
                                    getAAM().notification('danger', response);
                                },
                                complete: function () {
                                    $('#aam-policy-overwrite').hide();
                                    $(btn).text(getAAM().__('Reset To Default')).attr(
                                        'disabled', false
                                    );
                                }
                            });
                        });
                    });

                    $('#delete-policy-btn').bind('click', function() {
                        DeletePolicy($(this).attr('data-id'));
                    });

                    $('#policy_list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        dom: 'ftrip',
                        pagingType: 'simple',
                        processing: true,
                        stateSave: true,
                        serverSide: false,
                        ajax: {
                            url: getAAM().prepareApiEndpoint('/policies?fields=excerpt,permissions'),
                            type: 'GET',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            dataType: 'json',
                            dataSrc: function (json) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(json, (_, policy) => {
                                    data.push([
                                        policy.id,
                                        policy.title,
                                        policy.permissions,
                                        policy
                                    ])
                                });

                                return data;
                            },
                        },
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search Policy'),
                            info: getAAM().__('_TOTAL_ Policies'),
                            infoFiltered: ''
                        },
                        columnDefs: [
                            { visible: false, targets: [ 0, 3 ] }
                        ],
                        initComplete: function () {
                            const create = $('<a/>', {
                                'href': '#',
                                'class': 'btn btn-sm btn-primary'
                            }).html('<i class="icon-plus"></i> ' + getAAM().__('Create'))
                                .bind('click', function () {
                                    window.open(getLocal().url.addPolicy, '_blank');
                                });

                            $('.dataTables_filter', '#policy_list_wrapper').append(create);
                        },
                        createdRow: function (row, data) {
                            const container = $('<div/>', { 'class': 'aam-row-actions' });
                            const checked   = (data[3].is_attached ? 'icon-check' : 'icon-check-empty');

                            if (data[2].includes('toggle_policy')) {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action ' + checked
                                }).bind('click', function () {
                                    TogglePolicy(
                                        data[0],
                                        ($(this).hasClass('icon-check-empty') ? 'attach' : 'detach'),
                                        this
                                    );
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': getAAM().__('Toggle Policy')
                                }));
                            } else {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action text-muted ' + checked
                                }));
                            }

                            if (data[2].includes('edit_policy')) {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action icon-pencil text-warning'
                                }).bind('click', function () {
                                    window.open(
                                        getLocal().url.editPost + `?post=${data[0]}&action=edit`,
                                        '_blank'
                                    );
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': getAAM().__('Edit Policy')
                                }));
                            } else {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action text-muted icon-pencil'
                                }));
                            }

                            if (data[2].includes('delete_policy')) {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action icon-trash-empty text-danger'
                                }).bind('click', function () {
                                    let message = $(
                                        '.aam-confirm-message', '#delete-policy-modal'
                                    ).data('message');

                                    // replace some dynamic parts
                                    message = message.replace(
                                        '%s', '<b>' + data[3].title + '</b>'
                                    );
                                    $('.aam-confirm-message', '#delete-policy-modal').html(message);

                                    $('#delete-policy-btn').attr('data-id', data[0]);
                                    $('#delete-policy-modal').modal('show');
                                }).attr({
                                    'data-toggle': "tooltip",
                                    'title': getAAM().__('Delete Policy')
                                }));
                            } else {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action text-muted icon-trash-empty'
                                }));
                            }
                            $('td:eq(1)', row).html(container);

                            $('td:eq(0)', row).html(
                                data[3].title + '<br/><small>' + data[3].excerpt + '</small>'
                            );
                        }
                    });
                }

                // Policy Assignee metabox
                if ($('#policy_principle_selector').length) {
                    // Query params to the request
                    const policy_id = parseInt($('#aam-policy-id').val(), 10);

                    const fields = [
                        'permissions'
                    ];

                    // Prepare the RESTful API endpoint
                    let url = `${getLocal().rest_base}aam/v2/roles`;

                    if (url.indexOf('rest_route') === -1) {
                        url += `?fields=${fields.join(',')}`;
                    } else {
                        url += `&fields=${fields.join(',')}`;
                    }

                    url += `&context=policy_assignee&policy_id=${policy_id}`;

                    $('#policy_principle_role_list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        dom: 'ftrip',
                        pagingType: 'simple',
                        processing: true,
                        stateSave: true,
                        serverSide: false,
                        ajax: {
                            url,
                            type: 'GET',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            dataType: 'json',
                            dataSrc: function (json) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(json, (_, role) => {
                                    data.push([
                                        role.slug,
                                        role.name,
                                        role.permissions,
                                        role
                                    ])
                                });

                                return data;
                            },
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 3] },
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search role'),
                            info: getAAM().__('_TOTAL_ role(s)'),
                            infoFiltered: ''
                        },
                        createdRow: function (row, data) {
                            $('td:eq(0)', row).html('<span>' + data[1] + '</span>');

                            // Add subtitle
                            $('td:eq(0)', row).append(
                                $('<i/>', { 'class': 'aam-row-subtitle' }).html(
                                    getAAM().applyFilters(
                                        'role-subtitle',
                                        'ID: <b>' + data[0] + '</b>',
                                        data
                                    )
                                )
                            );

                            const checked   = data[3].is_attached ? 'icon-check' : 'icon-check-empty';
                            const container = $(
                                '<div/>', { 'class': 'aam-row-actions' }
                            );

                            if (data[2].includes('toggle_role_policy')) {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action ' + checked
                                }).bind('click', function () {
                                    const btn    = $(this);
                                    const effect = btn.hasClass('icon-check-empty') ? 'attach' : 'detach';
                                    btn.attr('class', 'aam-row-action icon-spin4 animate-spin');

                                    ToggleAccessLevelPolicy(
                                        'role',
                                        data[0],
                                        policy_id,
                                        effect,
                                        () => {
                                            if (effect === 'attach') {
                                                btn.attr(
                                                    'class',
                                                    'aam-row-action icon-check'
                                                );
                                            } else {
                                                btn.attr(
                                                    'class',
                                                    'aam-row-action icon-check-empty'
                                                );
                                            }
                                        }
                                    );
                                }));
                            } else {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action text-muted ' + checked
                                }));
                            }

                            $('td:eq(1)', row).html(container);
                        }
                    });

                    $('#policy_principle_user_list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        dom: 'ftrip',
                        stateSave: true,
                        pagingType: 'simple',
                        serverSide: true,
                        processing: true,
                        ajax: function(filters, cb) {
                            const fields = [
                                'display_name',
                                'permissions'
                            ];

                            $.ajax({
                                url: `${getLocal().rest_base}aam/v2/users`,
                                type: 'GET',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce
                                },
                                data: {
                                    search: filters.search.value,
                                    per_page: filters.length,
                                    offset: filters.start,
                                    fields: fields.join(','),
                                    context: 'policy_assignee',
                                    policy_id
                                },
                                success: function (response) {
                                    const result = {
                                        data: [],
                                        recordsTotal: 0,
                                        recordsFiltered: 0
                                    };

                                    $.each(response.list, (_, user) => {
                                        result.data.push([
                                            user.id,
                                            user.display_name,
                                            user.permissions,
                                            user
                                        ]);
                                    });

                                    result.recordsTotal    = response.summary.total_count;
                                    result.recordsFiltered = response.summary.filtered_count;

                                    cb(result);
                                }
                            });
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 3] }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search user'),
                            info: getAAM().__('_TOTAL_ user(s)'),
                            infoFiltered: ''
                        },
                        createdRow: function (row, data) {
                            $('td:eq(0)', row).append(
                                $('<i/>', { 'class': 'aam-row-subtitle' }).html(
                                    `${getAAM().__('ID')}: <b>${data[0]}</b>`
                                )
                            );

                            const checked   = data[3].is_attached ? 'icon-check' : 'icon-check-empty';
                            const container = $(
                                '<div/>', { 'class': 'aam-row-actions' }
                            );

                            if (data[2].includes('toggle_user_policy')) {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action ' + checked
                                }).bind('click', function () {
                                    const btn    = $(this);
                                    const effect = btn.hasClass('icon-check-empty') ? 'attach' : 'detach';

                                    btn.attr('class', 'aam-row-action icon-spin4 animate-spin');

                                    ToggleAccessLevelPolicy(
                                        'user',
                                        data[0],
                                        policy_id,
                                        effect,
                                        () => {
                                            if (effect === 'attach') {
                                                btn.attr(
                                                    'class',
                                                    'aam-row-action icon-check'
                                                );
                                            } else {
                                                btn.attr(
                                                    'class',
                                                    'aam-row-action icon-check-empty'
                                                );
                                            }
                                        }
                                    );
                                }));
                            } else {
                                $(container).append($('<i/>', {
                                    'class': 'aam-row-action text-muted ' + checked
                                }));
                            }

                            $('td:eq(1)', row).html(container);
                        }
                    });

                    $('#toggle_visitor_policy').bind('click', function() {
                        const effect = $(this).data('has') === '1' ? 'detach' : 'attach';
                        $(this).text(getAAM().__('Processing...')).prop('disabled', true);

                        ToggleAccessLevelPolicy(
                            'visitor',
                            null,
                            policy_id,
                            effect,
                            () => {
                                if (effect === 'attach') {
                                    $(this).text(getAAM().__('Detach Policy From Visitors')).prop('disabled', false)
                                } else {
                                    $(this).text(getAAM().__('Attach Policy To Visitors')).prop('disabled', false)
                                }
                            }
                        );

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
             * @param {Number}   item
             * @param {Boolean}  is_restricted
             * @param {Callback} cb
             *
             * @returns {Void}
             */
            function Save(item, is_restricted, cb) {
                getAAM().queueRequest(function () {
                    const payload = {
                        effect: is_restricted ? 'deny' : 'allow'
                    };

                    const endpoint = getAAM().prepareApiEndpoint(
                        '/backend-menu/' + item
                    );

                    $.ajax(endpoint, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'PATCH'
                        },
                        dataType: 'json',
                        data: payload,
                        success: function (response) {
                            cb(response);
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: '/backend-menu/' + encodeURI(item),
                                payload,
                                response
                            });
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
                            var status = $('i', $(this)).hasClass('icon-lock');
                            var target = _this.data('target');

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');

                            Save(_this.data('menu-id'), status, function () {
                                getAAM().fetchContent('main');
                            });
                        });
                    });

                    $('.aam-menu-item').each(function () {
                        $(this).bind('click', function () {
                            $('#menu-item-name').html($(this).data('name'));
                            $('#menu-item-slug').html($(this).data('slug'));
                            $('#menu-item-cap').html($(this).data('cap'));
                            $('#menu-item-path').html($(this).data('path'));
                        });
                    });

                    $('.aam-accordion-action', '#admin-menu').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);

                            const status = _this.hasClass('icon-lock-open');

                            // Show loading indicator
                            _this.attr(
                                'class',
                                'aam-accordion-action icon-spin4 animate-spin'
                            );

                            Save(
                                _this.data('menu-id'),
                                status,
                                () => {
                                    $('#aam-menu-overwrite').show();

                                    if (status) {
                                        _this.attr(
                                            'class',
                                            'aam-accordion-action icon-lock text-danger'
                                        );
                                    } else {
                                        _this.attr(
                                            'class',
                                            'aam-accordion-action icon-lock-open text-success'
                                        );
                                    }
                                }
                            );
                        });
                    });

                    // Reset button
                    $('#menu-reset').bind('click', function () {
                        const _this = $(this);

                        getAAM().queueRequest(function () {
                            $.ajax(getAAM().prepareApiEndpoint(`/backend-menu`), {
                                type: 'POST',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                dataType: 'json',
                                beforeSend: function () {
                                    _this.attr('data-original-label', _this.text());
                                    _this.text(getAAM().__('Resetting...'));
                                },
                                success: function () {
                                    getAAM().fetchContent('main');
                                },
                                error: function (response) {
                                    getAAM().notification('danger', response, {
                                        request: '/backend-menu',
                                        response
                                    });
                                },
                                complete: function () {
                                    _this.text(_this.attr('data-original-label'));
                                }
                            });
                        });
                    });

                    $(
                        '[data-toggle="toggle"]',
                        '#admin_menu-content'
                    ).bootstrapToggle();

                    getAAM().triggerHook('init-backend-menu');
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);

        /**
         * Admin Toolbar Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {Number}   item
             * @param {Boolean}  status
             * @param {Callback} successCallback
             *
             * @returns {Void}
             */
            function save(item, is_hidden, cb) {
                getAAM().queueRequest(function () {
                    const payload  = { effect: is_hidden ? 'deny' : 'allow' };
                    const endpoint = getAAM().prepareApiEndpoint(
                        `/admin-toolbar/${item}`
                    );

                    $.ajax(endpoint, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'PATCH'
                        },
                        dataType: 'json',
                        data: payload,
                        success: function (response) {
                            cb(response);
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: endpoint,
                                payload,
                                response
                            });
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
                            var status = $('i', $(this)).hasClass('icon-lock');

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');

                            save(_this.data('toolbar'), status, function () {
                                getAAM().fetchContent('main');
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

                    // Reset button
                    $('#toolbar_reset').bind('click', function () {
                        const _this = $(this);

                        getAAM().queueRequest(function () {
                            $.ajax(getAAM().prepareApiEndpoint(`/admin-toolbar`), {
                                type: 'POST',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                dataType: 'json',
                                beforeSend: function () {
                                    _this.attr('data-original-label', _this.text());
                                    _this.text(getAAM().__('Resetting...'));
                                },
                                success: function () {
                                    getAAM().fetchContent('main');
                                },
                                error: function (response) {
                                    getAAM().notification('danger', response, {
                                        request: 'aam/v2/admin-toolbar',
                                        response
                                    });
                                },
                                complete: function () {
                                    _this.text(_this.attr('data-original-label'));
                                }
                            });
                        });
                    });

                    $('.aam-accordion-action', '#toolbar_list').each(function () {
                        $(this).bind('click', function () {
                            var _this    = $(this);
                            const status = _this.hasClass('icon-lock-open');

                            // Show loading indicator
                            _this.attr(
                                'class',
                                'aam-accordion-action icon-spin4 animate-spin'
                            );

                            save(
                                [_this.data('toolbar')],
                                status,
                                function () {
                                    $('#aam_toolbar_overwrite').show();

                                    if (status) {
                                        _this.attr(
                                            'class',
                                            'aam-accordion-action icon-lock text-danger'
                                        );
                                    } else {
                                        _this.attr(
                                            'class',
                                            'aam-accordion-action icon-lock-open text-success'
                                        );
                                    }
                                }
                            );
                        });
                    });

                    $('[data-toggle="toggle"]', '#toolbar-content').bootstrapToggle();

                    getAAM().triggerHook('init-admin-toolbar');
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);

        /**
         * Metaboxes Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {*} slug
             * @param {*} screen_id
             * @param {*} is_hidden
             * @param {*} cb
             */
            function SetPermission(slug, screen_id, is_hidden, cb) {
                getAAM().queueRequest(function () {
                    const data = {
                        effect: is_hidden ? 'deny' : 'allow',
                        screen_id
                    };

                    $.ajax(getAAM().prepareApiEndpoint(`/metabox/${slug}`), {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'PATCH'
                        },
                        dataType: 'json',
                        data,
                        success: function (response) {
                            cb(response);
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: `/metabox/${slug}`,
                                payload: data,
                                response
                            });
                        }
                    });
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
                            getAAM().fetchContent('main');
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
                        $('i', '#refresh-metabox-list').attr(
                            'class', 'icon-spin4 animate-spin icon-arrows-cw'
                        );
                        fetchData(
                            JSON.parse($('#aam_screen_list').text()),
                            0,
                            $('i', '#refresh-metabox-list')
                        );
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

                                setTimeout(() => {
                                    getAAM().fetchContent('main');
                                }, 1000);
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

                    // Reset button
                    $('#metabox-reset').bind('click', function () {
                        const _this = $(this);

                        getAAM().queueRequest(function () {
                            $.ajax(getAAM().prepareApiEndpoint(`/metaboxes`), {
                                type: 'POST',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                dataType: 'json',
                                beforeSend: function () {
                                    _this.attr('data-original-label', _this.text());
                                    _this.text(getAAM().__('Resetting...'));
                                },
                                success: function () {
                                    getAAM().fetchContent('main');
                                },
                                error: function (response) {
                                    getAAM().notification('danger', response, {
                                        request: '/metaboxes',
                                        response
                                    });
                                },
                                complete: function () {
                                    _this.text(_this.attr('data-original-label'));
                                }
                            });
                        });
                    });

                    $('.aam-accordion-action', '#metabox-list').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);

                            const status = _this.hasClass('icon-lock-open');

                            // Show loading indicator
                            _this.attr(
                                'class',
                                'aam-accordion-action icon-spin4 animate-spin'
                            );

                            SetPermission(
                                $(this).data('metabox'),
                                $(this).data('screen'),
                                status,
                                function () {
                                    $('#aam-metabox-overwrite').show();

                                    if (status) {
                                        _this.attr(
                                            'class',
                                            'aam-accordion-action icon-lock text-danger'
                                        );
                                    } else {
                                        _this.attr(
                                            'class',
                                            'aam-accordion-action icon-lock-open text-success'
                                        );
                                    }
                                }
                            );
                        });
                    });

                    getAAM().triggerHook('init-metabox');
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);

        /**
         * Widgets Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {String}   slug
             * @param {Boolean}  status
             * @param {Callback} successCallback
             *
             * @returns {Void}
             */
            function save(slug, is_hidden, cb) {
                getAAM().queueRequest(function () {
                    $.ajax(getAAM().prepareApiEndpoint(`/widget/${slug}`), {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'PATCH'
                        },
                        dataType: 'json',
                        data: { effect : is_hidden ? 'deny' : 'allow' },
                        success: function (response) {
                            cb(response);
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: `aam/v2/widget/${slug}`,
                                payload: { effect : is_hidden ? 'deny' : 'allow' },
                                response
                            });
                        }
                    });
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
                            getAAM().fetchContent('main');
                        }
                    }
                });
            }

            /**
             *
             * @returns {undefined}
             */
            function initialize() {
                if ($('#widget-content').length) {
                    //init refresh list button
                    $('#refresh_widget_list').bind('click', function () {
                        $('i', '#refresh_widget_list').attr(
                            'class', 'icon-spin4 animate-spin icon-arrows-cw'
                        );
                        fetchData(
                            JSON.parse($('#aam_widget_screen_list').text()),
                            0,
                            $('i', '#refresh_widget_list')
                        );
                    });

                    $('.aam-widget-item').each(function () {
                        $(this).bind('click', function () {
                            $('#widget_title').html($(this).data('title'));
                            $('#widget_screen_id').html($(this).data('screen'));
                            $('#widget_id').html($(this).data('id'));
                        });
                    });

                    // Reset button
                    $('#widget_reset').bind('click', function () {
                        const _this = $(this);

                        getAAM().queueRequest(function () {
                            $.ajax(getAAM().prepareApiEndpoint('/widgets'), {
                                type: 'POST',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                dataType: 'json',
                                beforeSend: function () {
                                    _this.attr('data-original-label', _this.text());
                                    _this.text(getAAM().__('Resetting...'));
                                },
                                success: function () {
                                    getAAM().fetchContent('main');
                                },
                                error: function (response) {
                                    getAAM().notification('danger', response, {
                                        request: 'aam/v2/widgets',
                                        response
                                    });
                                },
                                complete: function () {
                                    _this.text(_this.attr('data-original-label'));
                                }
                            });
                        });
                    });

                    $('.aam-accordion-action', '#widget-list').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);

                            const status = _this.hasClass('icon-lock-open');

                            // Show loading indicator
                            _this.attr(
                                'class',
                                'aam-accordion-action icon-spin4 animate-spin'
                            );

                            save(
                                $(this).data('widget'),
                                status,
                                function () {
                                    $('#aam-widget-overwrite').show();

                                    if (status) {
                                        _this.attr(
                                            'class',
                                            'aam-accordion-action icon-lock text-danger'
                                        );
                                    } else {
                                        _this.attr(
                                            'class',
                                            'aam-accordion-action icon-lock-open text-success'
                                        );
                                    }
                                }
                            );
                        });
                    });

                    getAAM().triggerHook('init-widget');
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

                // Show indicator
                $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');

                // Prepare request payload
                const payload = {
                    [granted ? 'add_capabilities' : 'deprive_capabilities'] : [
                        capability
                    ]
                };

                // Determine endpoint
                let endpoint = '';

                if (getAAM().getSubject().type === 'role') {
                    endpoint += '/role/' + encodeURIComponent(getAAM().getSubject().id);
                } else if (getAAM().getSubject().type === 'user') {
                    endpoint += '/user/' + getAAM().getSubject().id;
                }

                getAAM().queueRequest(function () {
                    $.ajax(getAAM().prepareApiEndpoint(endpoint), {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'PATCH'
                        },
                        dataType: 'json',
                        data: payload,
                        success: function () {
                            if (granted) {
                                $(btn).attr(
                                    'class',
                                    'aam-row-action text-success icon-check'
                                );
                            } else {
                                $(btn).attr(
                                    'class',
                                    'aam-row-action text-muted icon-check-empty'
                                );
                            }
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: endpoint,
                                payload,
                                response
                            });
                        }
                    });
                });
            }

            /**
             * Delete capability
             *
             * @param {String}  capability
             * @param {Object}  btn
             */
            function deleteCapability(capability, btn) {
                getAAM().queueRequest(function () {
                    $.ajax(getAAM().prepareApiEndpoint(`/capability/${encodeURIComponent(capability)}`), {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'DELETE'
                        },
                        data: {
                            globally: true
                        },
                        dataType: 'json',
                        beforeSend: function () {
                            $(btn).attr('data-original', $(btn).text());
                            $(btn).text(getAAM().__('Deleting...')).attr('disabled', true);
                        },
                        success: function () {
                            $('#capability-list').DataTable().ajax.reload();
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: `/capability/${encodeURIComponent(capability)}`,
                                payload,
                                response
                            });
                        },
                        complete: function () {
                            $('#delete-capability-modal').modal('hide');

                            $(btn).text(getAAM().__('Delete For All Roles')).attr(
                                'disabled', false
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
                    const data = {
                        fields: 'description,permissions,is_granted'
                    };

                    // Initialize the capability list table
                    const capTable = $('#capability-list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        pagingType: 'simple',
                        serverSide: false,
                        ajax: {
                            url: getAAM().prepareApiEndpoint('/capabilities'),
                            type: 'GET',
                            data,
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            dataType: 'json',
                            dataSrc: function (json) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(json, (_, capability) => {
                                    const actions = [];

                                    let prefix = capability.permissions.includes('allow_toggle') ? '' : 'no-';

                                    if (capability.is_granted) {
                                        actions.push(`${prefix}checked`);
                                    } else {
                                        actions.push(`${prefix}unchecked`);
                                    }

                                    prefix = capability.permissions.includes('allow_update') ? '' : 'no-';
                                    actions.push(`${prefix}edit`);

                                    prefix = capability.permissions.includes('allow_delete') ? '' : 'no-';
                                    actions.push(`${prefix}delete`);

                                    data.push([
                                        capability.slug,
                                        capability.description,
                                        actions.join(','),
                                        capability.is_granted,
                                        capability
                                    ])
                                });

                                return data;
                            },
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 3, 4] }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search Capability'),
                            info: getAAM().__('_TOTAL_ capability(s)'),
                            infoFiltered: '',
                            infoEmpty: getAAM().__('No capabilities'),
                            lengthMenu: '_MENU_'
                        },
                        createdRow: function (row, data, _, cells) {
                            var actions = data[2].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'unchecked':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-muted icon-check-empty'
                                        }).bind('click', function () {
                                            capTable.cell(cells[4]).data(true);
                                            toggle(data[0], this);
                                        }));
                                        break;

                                    case 'checked':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-success icon-check'
                                        }).bind('click', function () {
                                            capTable.cell(cells[4]).data(false);
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
                                            $('#update-capability-slug').val(data[0]);
                                            $('#update-capability-btn').attr('data-cap', data[0]);
                                            $('#update-capability-modal').modal('show');
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
                                            let message = $(
                                                '.aam-confirm-message',
                                                '#delete-capability-modal'
                                            ).data('message');

                                            // replace some dynamic parts
                                            message = message.replace(
                                                '%s', '<b>' + data[0] + '</b>'
                                            );

                                            $(
                                                '.aam-confirm-message',
                                                '#delete-capability-modal'
                                            ).html(message);

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
                            $('td:eq(1)', row).html(container);

                            $('td:eq(0)', row).html(
                                `<strong>${data[0]}</strong><br/>
                                <small>${data[1] || 'No description provided'}</small>`
                            );
                        }
                    });

                    $('a', '#capability-groups').each(function () {
                        $(this).bind('click', function () {
                            var table = $('#capability-list').DataTable();

                            if ($(this).data('assigned') === true) {
                                table.column(3).search(true).draw();
                            } else if ($(this).data('unassigned') === true) {
                                table.column(3).search(false).draw();
                            } else if ($(this).data('clear') === true) {
                                table.columns().search('').draw();
                            }
                        });
                    });

                    $('#add-capability-modal').on('show.bs.modal', function () {
                        $('#add_capability_error').addClass('hidden');
                        $('#ignore_capability_format_container').addClass('hidden');

                        $('#new-capability-name').val('');
                        $('#new-capability-name').focus();
                    });

                    $('#update-capability-modal').on('show.bs.modal', function () {
                        $('#update_capability_error').addClass('hidden');
                        $('#ignore_update_capability_format_container').addClass('hidden');
                    });

                    $('#new-capability-name').bind('change', function() {
                        const cap = $('#new-capability-name').val();

                        if (/^[a-z0-9_\-]+$/.test(cap) === false) {
                            $('#add_capability_error').html(
                                $('#add_capability_error').data('message').replace('%s', cap)
                            ).removeClass('hidden');
                            $('#ignore_capability_format_container').removeClass('hidden');
                        } else {
                            $('#add_capability_error').addClass('hidden');
                            $('#ignore_capability_format_container').addClass('hidden');
                        }
                    });

                    $('#update-capability-slug').bind('change', function() {
                        const cap = $('#update-capability-slug').val();

                        if (/^[a-z0-9_\-]+$/.test(cap) === false) {
                            $('#update_capability_error').html(
                                $('#update_capability_error').data('message').replace('%s', cap)
                            ).removeClass('hidden');
                            $('#ignore_update_capability_format_container').removeClass('hidden');
                        } else {
                            $('#update_capability_error').addClass('hidden');
                            $('#ignore_update_capability_format_container').addClass('hidden');
                        }
                    });

                    $('#add-capability').bind('click', function () {
                        $('#add-capability-modal').modal('show');
                    });

                    $('#add-capability-btn').bind('click', function () {
                        var _this = this;

                        const slug   = $.trim($('#new-capability-name').val());
                        const ignore = $('#ignore_capability_format').is(':checked');

                        if (slug && (/^[a-z0-9_\-]+$/.test(slug) || ignore)) {
                            const payload = {
                                slug,
                                ignore_format: ignore
                            };

                            getAAM().queueRequest(function () {
                                $.ajax(getAAM().prepareApiEndpoint('/capabilities'), {
                                    type: 'POST',
                                    headers: {
                                        'X-WP-Nonce': getLocal().rest_nonce
                                    },
                                    dataType: 'json',
                                    data: payload,
                                    beforeSend: function () {
                                        $(_this).text(getAAM().__('Saving...')).attr('disabled', true);
                                    },
                                    success: function () {
                                        $('#add-capability-modal').modal('hide');
                                        $('#capability-list').DataTable().ajax.reload();
                                    },
                                    error: function (response) {
                                        getAAM().notification('danger', response, {
                                            request: 'aam/v2/capabilities',
                                            payload,
                                            response
                                        });
                                    },
                                    complete: function () {
                                        $(_this).text(getAAM().__('Add Capability')).attr('disabled', false);
                                    }
                                });
                            });
                        } else {
                            $('#new-capability-name').parent().addClass('has-error');
                        }
                    });

                    $('#update-capability-btn').bind('click', function () {
                        const btn      = this;
                        const old_slug = $(this).attr('data-cap');
                        const slug     = $.trim($('#update-capability-slug').val());
                        const ignore   = $('#ignore_update_capability_format').is(':checked');

                        if (slug && (/^[a-z0-9_\-]+$/.test(slug) || ignore)) {
                            // Prepare request payload
                            const payload = {
                                slug,
                                ignore_format: ignore,
                                globally: true
                            };

                            getAAM().queueRequest(function () {
                                $.ajax(getAAM().prepareApiEndpoint(`/capability/${encodeURIComponent(old_slug)}`), {
                                    type: 'POST',
                                    headers: {
                                        'X-WP-Nonce': getLocal().rest_nonce,
                                        'X-HTTP-Method-Override': 'PATCH'
                                    },
                                    dataType: 'json',
                                    data: payload,
                                    beforeSend: function () {
                                        $(btn).text(getAAM().__('Saving...')).attr('disabled', true);
                                    },
                                    success: function () {
                                        $('#capability-list').DataTable().ajax.reload();
                                    },
                                    error: function (response) {
                                        getAAM().notification('danger', response, {
                                            request: `aam/v2/capability/${encodeURIComponent(old_slug)}`,
                                            payload,
                                            response
                                        });
                                    },
                                    complete: function () {
                                        $('#update-capability-modal').modal('hide');

                                        $(btn).text(getAAM().__('Update For All Roles')).attr(
                                            'disabled', false
                                        );
                                    }
                                });
                            });
                        }
                    });

                    $('#delete-capability-btn').bind('click', function () {
                        deleteCapability($(this).attr('data-cap'), $(this));
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
             * Breadcrumb
             */
            let breadcrumb = [
                {
                    label: getAAM().__('Root'),
                    level_type: null,
                    level_id: null
                },
                {
                    label: getAAM().__('Post Types'),
                    level_type: 'type_list',
                    level_id: null
                }
            ];

            // Internal cache
            let cache = {};

            /**
             *
             * @returns
             */
            function CurrentLevel() {
                return breadcrumb[breadcrumb.length - 1];
            }

            /**
             * Render the breadcrumb
             *
             * @param {Boolean} reload
             */
            function RenderBreadcrumb(reload) {
                // Resetting the breadcrumb
                $('.aam-post-breadcrumb').empty();

                $.each(breadcrumb, function(i, level) {
                    if (level.level_type === null) { // Root, append home icon
                        $('.aam-post-breadcrumb').append(
                            '<i class="icon-home"></i>'
                        );
                    } else {
                        $('.aam-post-breadcrumb').append(
                            '<i class="icon-angle-double-right"></i>'
                        );
                    }

                    if (i === breadcrumb.length - 1) { // last element
                        $('.aam-post-breadcrumb').append(
                            $('<span/>').text(level.label)
                        );
                    } else {
                        $('.aam-post-breadcrumb').append(
                            $('<a/>').attr({
                                'href': '#',
                                'data-type': level.level_type,
                                'data-id': level.level_id,
                                'data-level': i
                            }).bind('click', function(event) {
                                event.preventDefault();

                                breadcrumb = breadcrumb.slice(
                                    0, $(this).data('level') + 1
                                );

                                // Take into consideration the "Root" level click
                                if (breadcrumb.length === 1) {
                                    breadcrumb.push({
                                        label: getAAM().__('Post Types'),
                                        level_type: 'type_list',
                                        level_id: null
                                    });
                                    $('.aam-type-taxonomy-filter').val('type_list');
                                }

                                $('#aam_content_access_form').removeClass('active');

                                RenderBreadcrumb();
                            }).text(level.label)
                        );
                    }
                });

                AdjustList(reload);
            }

            /**
             *
             * @param {*} node
             */
            function AddToBreadcrumb(node) {
                // If the last breadcrumb item has the same level, replace it
                const last = breadcrumb[breadcrumb.length - 1];

                if (node.level_type === last.level_type) {
                    breadcrumb.pop();
                }

                breadcrumb.push(node);

                RenderBreadcrumb(node.reload);
            }

            /**
             *
             * @param {*} level_type
             * @param {*} level_id
             * @param {*} label
             */
            function ReplaceInBreadcrumb(level_type, level_id, label) {
                breadcrumb.pop();

                AddToBreadcrumb({
                    level_type,
                    level_id,
                    label
                });
            }

            /**
             *
             */
            function NavigateBack() {
                breadcrumb.pop();
                RenderBreadcrumb(false);
            }

            /**
             * Save a single permission
             *
             * @param {String}   permission
             * @param {Object}   payload
             * @param {Callback} cb
             *
             * @returns {Void}
             */
            function UpdateContentPermission(permission, payload, cb = null) {
                getAAM().queueRequest(function () {
                    const resource_type    = $('#content_resource_type').val();
                    const resource_id      = $('#content_resource_id').val();
                    const permission_scope = JSON.parse(
                        $('#content_permission_scope').val()
                    );

                    // If there is additional resource permission scope, add it
                    const query = [];

                    for(key in permission_scope) {
                        query.push(`${key}=${permission_scope[key]}`);
                    }

                    $.ajax(getAAM().prepareApiEndpoint(
                        `/${resource_type}/${resource_id}/${permission}?${query.join('&')}`
                    ), {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'PATCH'
                        },
                        contentType: 'application/json; charset=UTF-8',
                        dataType: 'json',
                        data: JSON.stringify(payload),
                        success: function (response) {
                            if (cb) {
                                cb(response);
                            }

                            $('#content_resource_settings').text(JSON.stringify(
                                response.permissions
                            ));

                            // Update indicator
                            if (resource_type === 'post_type') {
                                $.each(cache.post_types.data, (_, p) => {
                                    if (p[0] === resource_id) {
                                        p[4].is_customized = true;
                                    }
                                });
                            } else if (resource_type === 'taxonomy') {
                                $.each(cache.taxonomies.data, (_, p) => {
                                    if (p[0] === resource_id) {
                                        p[4].is_customized = true;
                                    }
                                });
                            }
                        },
                        error: function (response) {
                            getAAM().notification('danger', response);
                        }
                    });
                });
            }

            /**
             * Render the content access form
             *
             * This is the SSR to reduce complexity for the frontend implementation
             *
             * @param {Object}   target
             * @param {Callback} callback
             */
            function RenderAccessForm(target = null, callback = null) {
                // Reset the form first
                var container = $('#aam_content_access_form');

                // Show overlay if present
                $('.aam-overlay', container).show();

                if (target === null) {
                    let scope = {};

                    if ($('#content_permission_scope').length) {
                        scope = JSON.parse($('#content_permission_scope').val());
                    }

                    target = Object.assign(scope, {
                        resource_type: $('#content_resource_type').val(),
                        resource_id: $('#content_resource_id').val()
                    });
                }

                // Prepare payload for the SSR rendering
                const payload = Object.assign({
                    action: 'aam',
                    sub_action: 'renderContent',
                    partial: 'content-access-form',
                    _ajax_nonce: getLocal().nonce,
                }, target);

                $.ajax(getLocal().ajaxurl, {
                    type: 'POST',
                    dataType: 'html',
                    data: getAAM().prepareAjaxRequestPayload(payload),
                    success: function (response) {
                        $('#aam_access_form_container').html(response);

                        $('#content_list_container .dataTables_wrapper').addClass(
                            'hidden'
                        );
                        container.addClass('active');

                        InitializeAccessForm();

                        if (typeof callback === 'function') {
                            callback.call();
                        }
                    },
                    error: function (response) {
                        getAAM().notification('danger', response);
                    }
                });
            }

            /**
             *
             * @param {*} permission
             * @returns
             */
            function GetResourcePermission(permission) {
                const permissions = JSON.parse($('#content_resource_settings').text());

                return permissions[permission];
            }

            /**
             *
             * @param {*} resource_type
             * @param {*} resource_id
             */
            function InitializeAccessForm(init_toggles = true) {
                if (init_toggles) {
                    $('[data-toggle="toggle"]', '#aam_access_form_container').bootstrapToggle();
                }

                // Permission toggles
                $('input[data-toggle="toggle"]', '#permission_toggles').each(function() {
                    $(this).bind('change', function() {
                        UpdateContentPermission(
                            $(this).data('permission'),
                            { effect: $(this).prop('checked') ? 'deny' : 'allow' },
                            () => RenderAccessForm()
                        );
                    });
                });

                // Initialize the Content Visibility modal functionality
                if ($('#modal_content_visibility').length) {
                    $('#save_list_permission').bind('click', function() {
                        // Prepare the payload
                        let payload = GetResourcePermission('list');

                        if (payload === undefined) {
                            payload = {
                                effect: 'deny',
                                on: []
                            }
                        } else {
                            payload.on = [];
                        }

                        $(
                            'input[data-toggle="toggle"]',
                            '#modal_content_visibility'
                        ).each(function() {
                            if ($(this).prop('checked')) {
                                payload.on.push($(this).attr('name'));
                            }
                        });

                        if (payload.on.length > 0) {
                            $(this).prop('disabled', true).text(getAAM().__('Saving'));

                            UpdateContentPermission(
                                'list',
                                getAAM().applyFilters('aam-list-permission-payload', payload),
                                () => {
                                    $('#modal_content_visibility').modal('hide');

                                    // Reload the access form
                                    RenderAccessForm();
                                }
                            );
                        }
                    });
                }

                // Initialize the Content Restriction modal functionality
                if ($('#modal_content_restriction').length) {
                    // Initialize the restriction type toggles
                    $('input[type="radio"]', '#restriction_types').each(function () {
                        $(this).bind('click', function () {
                            $('#restriction_type_extra > div').addClass('hidden');
                            $('#restriction_type_extra > div[data-type="' + $(this).val() + '"').removeClass('hidden')
                        });
                    });

                    // Initialize the redirect type element
                    $('#restricted_redirect_type').bind('change', function() {
                        $('.restricted-redirect-type').addClass('hidden');
                        $('.restricted-redirect-type[data-type="' + $(this).val() + '"]').removeClass('hidden')
                    });

                    // Initialize the expiration picker
                    const def = $('#aam_expire_datetime').val();

                    $('#content_expire_datepicker').datetimepicker({
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

                    $('#content_expire_datepicker').on('dp.change', function (res) {
                        $('#aam_expire_datetime').val(res.date.unix());
                    });

                    $('#save_read_permission').bind('click', function() {
                        const payload = {
                            effect: 'deny'
                        }

                        // Depending on the restriction type, collect additional
                        // attributes
                        const restriction_type = $(
                            'input[name="content_restriction_type"]:checked',
                            '#restriction_types'
                        ).val();

                        payload.restriction_type = restriction_type;

                        if (restriction_type === 'teaser_message') {
                            payload.message = $.trim(
                                $('#aam_teaser_message').val()
                            );
                        } else if (restriction_type === 'redirect') {
                            const redirect = {
                                // The the redirect type
                                type: $('#restricted_redirect_type').val()
                            };

                            if (redirect.type === 'page_redirect') {
                                redirect.redirect_page_id = $('#restricted_redirect_page_id').val();
                            } else if (redirect.type === 'url_redirect') {
                                redirect.redirect_url = $('#restricted_redirect_url').val();
                            } else if (redirect.type === 'trigger_callback') {
                                redirect.callback = $('#restricted_callback').val();
                            }

                            payload.redirect = redirect;
                        } else if (restriction_type === 'password_protected') {
                            payload.password = $.trim(
                                $('#restricted_password').val()
                            );
                        } else if (restriction_type === 'expire') {
                            payload.expires_after = $('#aam_expire_datetime').val();
                        }

                        $(this).prop('disabled', true).text(getAAM().__('Saving'));

                        UpdateContentPermission(
                            'read',
                            getAAM().applyFilters('aam-read-permission-payload', payload),
                            () => {
                                $('#modal_content_restriction').modal('hide');

                                // Reload the access form
                                RenderAccessForm();
                            }
                        );
                    });
                }

                // Initialize the Reset to default button
                $('#content_reset').bind('click', function () {
                    const resource_type    = encodeURIComponent($('#content_resource_type').val());
                    const resource_id      = $('#content_resource_id').val();
                    const permission_scope = JSON.parse(
                        $('#content_permission_scope').val()
                    );

                    // If there is additional resource permission scope, add it
                    const query = [];

                    for(key in permission_scope) {
                        query.push(`${key}=${permission_scope[key]}`);
                    }

                    if (CurrentLevel().permissions) {
                        CurrentLevel().permissions = undefined;
                    }

                    $.ajax(getAAM().prepareApiEndpoint(
                        `/${resource_type}/${resource_id}?${query.join('&')}`
                    ), {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'DELETE'
                        },
                        beforeSend: function () {
                            $('#content_reset').attr(
                                'data-original-label', $('#content_reset').text()
                            );
                            $('#content_reset').text(getAAM().__('Resetting...'));
                        },
                        success: function () {
                            $('#post-overwritten').addClass('hidden');

                            RenderAccessForm();

                            // Manually update the data in a table because both
                            // Post Types & Taxonomies are static tables
                            if (['post_type', 'taxonomy'].includes(resource_type)) {
                                let row = null;

                                if (resource_type === 'post_type') {
                                    row = cache.post_types.data.filter(
                                        t => t[0] === resource_id
                                    ).pop();
                                } else {
                                    row = cache.taxonomies.data.filter(
                                        t => t[0] === resource_id
                                    ).pop();
                                }

                                row[4].is_inherited  = true;
                                row[4].is_customized = false;
                            }
                        },
                        complete: function () {
                            $('#content_reset').text(
                                $('#content_reset').attr('data-original-label')
                            );
                        }
                    });
                });

                // Allow third-party plugins to initialize the Access Form
                getAAM().triggerHook('init-access-form', {
                    RenderAccessForm,
                    UpdateContentPermission,
                    GetResourcePermission
                });
            }

            getAAM().addHook('load-access-form', function(params) {
                RenderAccessForm(...params);
            });

            getAAM().addHook('save-post-settings', function(params) {
                save(...params);
            });

            /**
             * Get the list of post types
             *
             * It is a static list, so we are caching it
             *
             * @param {Callback} cb
             */
            function FetchPostTypeList(cb) {
                if (cache.post_types === undefined) {
                    // Fetching the list of all registered post types.
                    $.ajax(getAAM().prepareApiEndpoint(`/post_types`), {
                        type: 'GET',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        success: function (response) {
                            const types = [];

                            $.each(response.list, (_, item) => {
                                types.push([
                                    item.slug,
                                    item.icon || (item.is_hierarchical ? 'dashicons-admin-page' : 'dashicons-media-default'),
                                    item.title,
                                    ['drilldown', 'manage'],
                                    item
                                ]);
                            });

                            cache.post_types = {
                                data: types,
                                recordsTotal: response.summary.total_count,
                                recordsFiltered: response.summary.filtered_count
                            };

                            cb(cache.post_types);
                        }
                    });
                } else {
                    cb(cache.post_types);
                }
            }

            /**
             * Get the list of taxonomies
             *
             * This is another static list, so we are caching it as well
             *
             * @param {Callback} cb
             */
            function FetchTaxonomyList(cb) {
                // Fetching the list of all registered post types.
                if (cache.taxonomies === undefined) {
                    $.ajax(getAAM().prepareApiEndpoint(`/taxonomies`), {
                        type: 'GET',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        success: function (response) {
                            const taxonomies = [];

                            $.each(response.list, (_, item) => {
                                taxonomies.push([
                                    item.slug,
                                    item.is_hierarchical ? 'dashicons-category' : 'dashicons-tag',
                                    item.title,
                                    ['drilldown', 'manage'],
                                    item
                                ]);
                            });

                            cache.taxonomies = {
                                data: taxonomies,
                                recordsTotal: response.summary.total_count,
                                recordsFiltered: response.summary.filtered_count
                            };

                            cb(cache.taxonomies);
                        }
                    });
                } else {
                    cb(cache.taxonomies);
                }
            }

            /**
             * Fetch list of posts
             *
             * @param {Object}   filters
             * @param {Callback} cb
             */
            function FetchPostList(filters, cb) {
                // Fetching the list of posts
                $.ajax(getAAM().prepareApiEndpoint(`/posts`), {
                    type: 'GET',
                    headers: {
                        'X-WP-Nonce': getLocal().rest_nonce
                    },
                    data: {
                        post_type: CurrentLevel().level_id,
                        offset: filters.start,
                        per_page: filters.length,
                        search: filters.search.value
                    },
                    success: function (response) {
                        const result = {
                            data: [],
                            recordsTotal: 0,
                            recordsFiltered: 0
                        };

                        if (response && response.list) {
                            $.each(response.list, (_, item) => {
                                result.data.push([
                                    item.id,
                                    item.icon || (item.is_hierarchical ? 'dashicons-admin-page' : 'dashicons-media-default'),
                                    item.title,
                                    ['edit', 'manage'],
                                    item
                                ]);
                            });

                            result.recordsTotal = response.summary.total_count;
                            result.recordsFiltered = response.summary.filtered_count;
                        }

                        cb(result);
                    }
                });
            }

            /**
             *
             * @param {*} filters
             * @param {*} cb
             */
            function FetchTermList(filters, cb) {
                const payload = {
                    taxonomy: CurrentLevel().level_id,
                    offset: filters.start,
                    per_page: filters.length,
                    search: filters.search.value
                };

                if (CurrentLevel().post_type) {
                    payload.post_type = CurrentLevel().post_type;
                }

                // Fetching the list of terms
                $.ajax(getAAM().prepareApiEndpoint(`/terms`), {
                    type: 'GET',
                    headers: {
                        'X-WP-Nonce': getLocal().rest_nonce
                    },
                    data: payload,
                    success: function (response) {
                        const result = {
                            data: [],
                            recordsTotal: 0,
                            recordsFiltered: 0
                        };

                        $.each(response.list, (_, item) => {
                            result.data.push([
                                item.id,
                                item.is_hierarchical ? 'dashicons-category' : 'dashicons-tag',
                                item.title,
                                getAAM().applyFilters(
                                    'aam-term-actions',
                                    ['edit', 'manage'],
                                    item
                                ),
                                item
                            ]);
                        });

                        result.recordsTotal = response.summary.total_count;
                        result.recordsFiltered = response.summary.filtered_count;

                        cb(result);
                    }
                });
            }

            /**
             * Adjust the list of content items based on where are we
             *
             * @param {Boolean} reload
             */
            function AdjustList(reload = true) {
                const current = CurrentLevel();

                if ([null, 'type_list'].includes(current.level_type)) {
                    PrepareTypeListTable(reload);
                } else if (current.level_type === 'taxonomy_list') {
                    PrepareTaxonomyListTable(reload);
                } else if (current.level_type === 'type_posts') {
                    PreparePostListTable(reload);
                } else if (current.level_type === 'type_terms') {
                    PrepareTermListTable(current, reload);
                } else if (current.level_type === 'taxonomy_terms') {
                    PrepareTermListTable(current, reload)
                }
            }

            /**
             *
             * @returns
             */
            function RenderTypeTaxonomySwitch(){
                const current = CurrentLevel();
                const options = [
                    {
                        value: 'type_list',
                        label: getAAM().__('Post Types'),
                        selected: [null, 'type_list'].includes(current.level_type)
                    },
                    {
                        value: 'taxonomy_list',
                        label: getAAM().__('Taxonomies'),
                        selected: current.level_type === 'taxonomy_list'
                    }
                ];

                return $('<select>').attr({
                    'class': 'form-control input-sm aam-ml-1 aam-type-taxonomy-filter aam-filtered-list'
                }).html(
                    options.map(o => `'<option value="${o.value}" ${o.selected ? 'selected' : ''}>${o.label}</option>`).join('')
                ).bind('change', function () {
                    $(`.aam-type-taxonomy-filter option[value="${$(this).val()}"]`).prop(
                        'selected', true
                    );

                    ReplaceInBreadcrumb(
                        $(this).val(), null, $('option:selected', $(this)).text()
                    );
                });
            }

            /**
             *
             * @param {*} filter_id
             */
            function RenderPostTaxonomySwitch(filter_id) {
                FetchTaxonomyList(function(response) {
                    const current = CurrentLevel();
                    const options = [
                        {
                            value: `type_posts:${current.level_id}`,
                            label: getAAM().__('Only %s List').replace('%s', CurrentLevel().label),
                            selected: current.level_type === 'type'
                        }
                    ];
                    $.each(response.data.filter(t => t[4].post_types.includes(current.level_id)), function(_, item) {
                        options.push({
                            value: `type_terms:${current.level_id}:${item[0]}`,
                            label: item[2],
                            selected: current.level_type === 'taxonomy' && current.level_id === item[0]
                        });
                    });

                    const filter_controller = $('<select>').attr({
                        'class': 'form-control input-sm aam-ml-1 aam-post-taxonomy-filter aam-filtered-list'
                    }).html(
                        options.map(o => `'<option value="${o.value}" ${o.selected ? 'selected' : ''}>${o.label}</option>`).join('')
                    ).bind('change', function () {
                        const value = $(this).val();
                        const [
                            level_type, post_type, taxonomy
                        ] = $(this).val().split(':');

                        AddToBreadcrumb({
                            level_type,
                            level_id: taxonomy,
                            label: $(`.aam-post-taxonomy-filter option:selected`).text(),
                            post_type,
                            taxonomy
                        });

                        $(`.aam-post-taxonomy-filter option[value="${value}"]`).prop(
                            'selected', true
                        );
                    });

                    if ($('.aam-post-taxonomy-filter', filter_id).length) {
                        $('.aam-post-taxonomy-filter', filter_id).replaceWith(filter_controller);
                    } else {
                        $(filter_id).append(filter_controller);
                    }
                });
            }

            /**
             *
             * @param {*} data
             */
            function NavigateToAccessForm(data) {
                if (data.btn) {
                    $(data.btn).attr('data-class', $(data.btn).attr('class'));
                    $(data.btn).attr(
                        'class',
                        'aam-row-action icon-spin4 animate-spin'
                    );
                }

                // Determine the targeting resource
                const target = {
                    resource_type: data.level_type,
                    resource_id: data.level_id
                };

                if (data.post_type !== undefined) {
                    target.post_type = data.post_type;
                }

                if (data.taxonomy !== undefined) {
                    target.taxonomy = data.taxonomy;
                }

                RenderAccessForm(target, () => {
                    // Update the breadcrumb
                    AddToBreadcrumb({
                        level_type: data.level_type,
                        level_id: data.level_id,
                        label: data.label,
                        post_type: data.post_type,
                        taxonomy: data.taxonomy,
                        is_access_form: true
                    });

                    if (data.btn){
                        $(data.btn).attr(
                            'class',
                            $(data.btn).attr('data-class')
                        ).removeAttr('data-class');
                    }
                });
            }

            /**
             *
             */
            function PrepareTypeListTable() {
                $('#content_list_container .dataTables_wrapper').addClass('hidden');
                $('#content_list_container .table').addClass('hidden');

                if (!$('#post_type_list').hasClass('dataTable')) {
                    $('#post_type_list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        pagingType: 'simple',
                        processing: true,
                        saveState: true,
                        ajax: function(_, cb) {
                            FetchPostTypeList(cb);
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 4] },
                            { searchable: false, targets: [0, 1, 3, 4] }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ type(s)'),
                            infoFiltered: '',
                            lengthMenu: '_MENU_'
                        },
                        initComplete: function () {
                            // Adding the root level controls
                            $('#post_type_list_length').append(RenderTypeTaxonomySwitch());
                        },
                        rowCallback: function(row, data) {
                            let overwritten = '';

                            if (data[4].is_customized) {
                                overwritten = ' aam-access-overwritten';
                            }

                            $('td:eq(0)', row).html(
                                `<div class="dashicons-before ${data[1]}${overwritten}"></div>`
                            );

                            // Decorating the post type title & make it actionable
                            $('td:eq(1)', row).html($('<a/>', {
                                href: '#'
                            }).bind('click', function () {
                                AddToBreadcrumb({
                                    level_type: 'type_posts',
                                    level_id: data[0],
                                    label: data[2]
                                });
                            }).html(data[2]));

                            $('td:eq(1)', row).append(`<sup>${data[0]}</sup>`);

                            const container = $('<div/>', { 'class': 'aam-row-actions' });

                            $.each(data[3], function (_, action) {
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
                                            NavigateToAccessForm({
                                                level_type: 'post_type',
                                                level_id: data[0],
                                                label: data[2],
                                                btn: $(this)
                                            });
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Manage Access')
                                        }));
                                        $('.tooltip').remove();
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
                } else {
                    $('#post_type_list').DataTable().ajax.reload(null, false);
                }

                $('#post_type_list_wrapper .table').removeClass('hidden');
                $('#post_type_list_wrapper').removeClass('hidden');
            }

            /**
             *
             */
            function PrepareTaxonomyListTable() {
                $('#content_list_container .dataTables_wrapper').addClass('hidden');
                $('#content_list_container .table').addClass('hidden');

                if (!$('#taxonomy_list').hasClass('dataTable')) {
                    $('#taxonomy_list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        pagingType: 'simple',
                        processing: true,
                        saveState: true,
                        ajax: function(_, cb) {
                            FetchTaxonomyList(cb);
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 4] },
                            { searchable: false, targets: [0, 1, 3, 4] }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ taxonomy(s)'),
                            infoFiltered: '',
                            lengthMenu: '_MENU_'
                        },
                        initComplete: function () {
                            // Adding the root level controls
                            $('#taxonomy_list_length').append(RenderTypeTaxonomySwitch());
                        },
                        rowCallback: function(row, data) {
                            let overwritten = '';

                            if (data[4].is_customized) {
                                overwritten = ' aam-access-overwritten';
                            }

                            $('td:eq(0)', row).html(
                                `<div class="dashicons-before ${data[1]}${overwritten}"></div>`
                            );

                            // Decorating the post type title & make it actionable
                                $('td:eq(1)', row).html($('<a/>', {
                                href: '#'
                            }).bind('click', function () {
                                AddToBreadcrumb({
                                    level_type: 'taxonomy_terms',
                                    level_id: data[0],
                                    label: data[2],
                                    taxonomy: data[0]
                                });
                            }).html(data[2]));

                            $('td:eq(1)', row).append(`<sup>${data[0]}</sup>`);

                            const container = $('<div/>', { 'class': 'aam-row-actions' });

                            $.each(data[3], function (_, action) {
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
                                            NavigateToAccessForm({
                                                level_type: 'taxonomy',
                                                level_id: data[0],
                                                label: data[2],
                                                btn: $(this)
                                            });
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Manage Access')
                                        }));
                                        $('.tooltip').remove();
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
                } else {
                    $('#taxonomy_list').DataTable().ajax.reload(null, false);
                }

                $('#taxonomy_list_wrapper .table').removeClass('hidden');
                $('#taxonomy_list_wrapper').removeClass('hidden');
            }

            /**
             *
             */
            function PreparePostListTable(reload = false) {
                $('#content_list_container .dataTables_wrapper').addClass('hidden');
                $('#content_list_container .table').addClass('hidden');

                if (!$('#post_list').hasClass('dataTable')) {
                    $('#post_list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        processing: true,
                        serverSide: true,
                        pagingType: 'simple_numbers',
                        ajax: function(filters, cb) {
                            FetchPostList(filters, cb);
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 4] },
                            { searchable: false, targets: [0, 1, 3, 4] }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ post(s)'),
                            infoFiltered: '',
                            lengthMenu: '_MENU_'
                        },
                        initComplete: function () {
                            // Adding the root level controls
                            RenderPostTaxonomySwitch('#post_list_length');
                        },
                        rowCallback: function(row, data) {
                            let overwritten = '';

                            if (data[4].is_customized) {
                                overwritten = ' aam-access-overwritten';
                            }

                            $('td:eq(0)', row).html(
                                `<div class="dashicons-before ${data[1]}${overwritten}"></div>`
                            );

                            // Decorating the post type title & make it actionable
                                $('td:eq(1)', row).html($('<a/>', {
                                href: '#'
                            }).bind('click', function () {
                                NavigateToAccessForm({
                                    level_type: 'post',
                                    level_id: data[0],
                                    label: data[2],
                                    btn: $('.icon-cog', row)
                                });
                            }).html(data[2]));

                            $('td:eq(1)', row).append(`<sup>ID: ${data[0]}</sup>`);

                            const container = $('<div/>', { 'class': 'aam-row-actions' });

                            $.each(data[3], function (_, action) {
                                switch (action) {
                                    case 'manage':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-info icon-cog'
                                        }).bind('click', function () {
                                            NavigateToAccessForm({
                                                level_type: 'post',
                                                level_id: data[0],
                                                label: data[2],
                                                btn: $(this)
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
                                            window.open(
                                                getLocal().url.editPost + `?post=${data[0]}&action=edit`,
                                                '_blank'
                                            );
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
                } else {
                    // Reload list of posts
                    $('#post_list').DataTable().ajax.reload(null, reload);
                    // Reload the list of taxonomies
                    RenderPostTaxonomySwitch('#post_list_length');
                }

                $('#post_list_wrapper .table').removeClass('hidden');
                $('#post_list_wrapper').removeClass('hidden');
            }

            /**
             *
             * @param {*} scope
             */
            function PrepareTermListTable(scope = null, reload = false) {
                $('#content_list_container .dataTables_wrapper').addClass('hidden');
                $('#content_list_container .table').addClass('hidden');

                if (scope && scope.post_type) {
                    $('#term_list').attr('data-post-type', scope.post_type);
                } else {
                    $('#term_list').removeAttr('data-post-type');
                }

                if (scope && scope.taxonomy) {
                    $('#term_list').attr('data-taxonomy', scope.taxonomy);
                } else {
                    $('#term_list').removeAttr('data-taxonomy');
                }

                if (!$('#term_list').hasClass('dataTable')) {
                    $('#term_list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        pagingType: 'simple',
                        processing: true,
                        serverSide: true,
                        pagingType: 'simple_numbers',
                        ajax: function(filters, cb) {
                            FetchTermList(filters, cb);
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 4] },
                            { searchable: false, targets: [0, 1, 3, 4] }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ term(s)'),
                            infoFiltered: '',
                            lengthMenu: '_MENU_'
                        },
                        rowCallback: function(row, data) {
                            let overwritten = '';

                            if (data[4].is_customized) {
                                overwritten = ' aam-access-overwritten';
                            }

                            $('td:eq(0)', row).html(
                                `<div class="dashicons-before ${data[1]}${overwritten}"></div>`
                            );

                            // Decorating the term title & make it actionable
                                $('td:eq(1)', row).html($('<a/>', {
                                href: '#'
                            }).bind('click', function () {
                                const post_type = $('#term_list').attr('data-post-type');
                                const taxonomy  = $('#term_list').attr('data-taxonomy');

                                NavigateToAccessForm({
                                    level_type: 'term',
                                    level_id: data[0],
                                    label: data[2],
                                    btn:  $('.icon-cog', row),
                                    post_type,
                                    taxonomy
                                });
                            }).html(data[2]));

                            $('td:eq(1)', row).append(`<sup>ID: ${data[0]}</sup>`);

                            const container = $('<div/>', { 'class': 'aam-row-actions' });

                            $.each(data[3], function (_, action) {
                                switch (action) {
                                    case 'manage':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action text-info icon-cog'
                                        }).bind('click', function () {
                                            const post_type = $('#term_list').attr('data-post-type');
                                            const taxonomy  = $('#term_list').attr('data-taxonomy');

                                            NavigateToAccessForm({
                                                level_type: 'term',
                                                level_id: data[0],
                                                label: data[2],
                                                btn: $(this),
                                                post_type,
                                                taxonomy
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
                                            const url = getLocal().url.editTerm + `?taxonomy=${data[4].taxonomy}&tag_ID=${data[0]}`;
                                            window.open(url, '_blank');
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
                                        getAAM().triggerHook('term-item-action', {
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
                } else {
                    $('#term_list').DataTable().ajax.reload(null, reload);
                }

                $('#term_list_wrapper .table').removeClass('hidden');
                $('#term_list_wrapper').removeClass('hidden');
            }

            /**
             *
             */
            function initialize() {
                if ($('#post-content').length) {
                    // Go back button
                    $('.aam-slide-form').delegate('.post-back', 'click', function () {
                        $('.aam-slide-form').removeClass('active');
                        NavigateBack();
                    });

                    RenderBreadcrumb();
                } else if ($('#aam_post_access_metabox').length) {
                    InitializeAccessForm(false);
                }

                const current_level = CurrentLevel();

                if (current_level && current_level.is_access_form) {
                    // Determine the targeting resource
                    const target = {
                        resource_type: current_level.level_type,
                        resource_id: current_level.level_id
                    };

                    if (current_level.post_type !== undefined) {
                        target.post_type = current_level.post_type;
                    }

                    if (current_level.taxonomy !== undefined) {
                        target.taxonomy = current_level.taxonomy;
                    }

                    RenderAccessForm(target);
                }
            }

            getAAM().addHook('init', initialize);
            getAAM().addHook('access-level-changed', function() {
                cache = {};
            });

        })(jQuery);

        /**
         * Access Denied Redirect Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {*} payload
             * @param {*} successCallback
             */
            function save(payload, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getAAM().prepareApiEndpoint('/redirect/access-denied'), {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        dataType: 'json',
                        data: payload,
                        success: function (response) {
                            successCallback(response);
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: '/redirect/access-denied',
                                payload,
                                response
                            });
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
                            // Determine area
                            const area = $(this).data('group');

                            // Hide group
                            $('.' + area).hide();

                            // Show the specific one
                            $($(this).data('action')).show();

                            // Now, if the redirect type is default, then
                            // save the data, otherwise save only when more detail
                            // provided
                            const type = $(this).val();

                            // If type is default or message, also capture the HTTP
                            // status code
                            const http_status_code = $(`#${area}-${type}-status-code`).val();

                            if (['default', 'login_redirect'].includes(type)) {
                                save({ area, type, http_status_code }, () => {
                                    $('#aam-redirect-overwrite').show();
                                });
                            }
                        });
                    });

                    $('input[type="text"],select,textarea', container).each(function () {
                        $(this).bind('change', function () {
                            const value = $.trim($(this).val());

                            let area;
                            if ($(this).attr('id') === 'frontend-page') {
                                area = 'frontend';
                            } else if ($(this).attr('id') === 'backend-page') {
                                area = 'backend';
                            } else {
                                area = $(this).data('group');
                            }

                            // Determining type
                            const type = $(`input[name="${area}.redirect.type"]:checked`).val();

                            const payload = {
                                area,
                                type
                            };

                            if (type === 'page_redirect') {
                                payload.redirect_page_id = value;
                            } else if (type === 'url_redirect') {
                                payload.redirect_url = value;
                            } else if (type === 'trigger_callback') {
                                payload.callback = value;
                            } else if (type === 'custom_message') {
                                if ($(this).attr('name').indexOf('message.code') !== -1) {
                                    payload.http_status_code = value;
                                    payload.message     = $(`textarea[name="${area}.redirect.message"]`).val();
                                } else {
                                    payload.message     = value;
                                    payload.http_status_code = $(`#${area}-message-status-code`).val()
                                }
                            } else if (type === 'default') {
                                payload.http_status_code = value;
                            }

                            //save redirect type
                            save(payload, () => {
                                $('#aam-redirect-overwrite').show();
                            });
                        });
                    });

                    $('#redirect-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(getAAM().prepareApiEndpoint(`/redirect/access-denied`), {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            dataType: 'json',
                            beforeSend: function () {
                                var label = _btn.text();
                                _btn.attr('data-original-label', label);
                                _btn.text(getAAM().__('Resetting...'));
                            },
                            success: function () {
                                getAAM().fetchContent('main');
                            },
                            error: function (response) {
                                getAAM().notification('danger', response, {
                                    request: '/redirect/access-denied',
                                    response
                                });
                            },
                            complete: function () {
                                _btn.text(_btn.attr('data-original-label'));
                            }
                        });
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
             * @param {payload}  payload
             * @param {function} successCallback
             *
             * @returns {void}
             */
            function save(payload, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getAAM().prepareApiEndpoint('/redirect/login'), {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        dataType: 'json',
                        data: payload,
                        success: function (response) {
                            successCallback(response);
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: 'aam/v2/redirect/login',
                                payload,
                                response
                            });
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
                            // Hide all fields
                            $('.login-redirect-action').hide();

                            // Show the specific one
                            $($(this).data('action')).show();

                            // Now, if the login redirect type is default, then
                            // save the data, otherwise save only when more detail
                            // provided
                            const type = $(this).val();

                            if (type === 'default') {
                                save({ type }, () => {
                                    $('#aam-login-redirect-overwrite').show();
                                });
                            }
                        });
                    });

                    $('input[type="text"],select', container).each(function () {
                        $(this).bind('change', function () {
                            const value = $.trim($(this).val());
                            const type  = $('input[name="login.redirect.type"]:checked').val();

                            const payload = {
                                type
                            };

                            if (type === 'page_redirect') {
                                payload.redirect_page_id = value;
                            } else if (type === 'url_redirect') {
                                payload.redirect_url = value;
                            } else {
                                payload.callback = value;
                            }

                            // Save redirect type
                            save(payload, () => {
                                $('#aam-login-redirect-overwrite').show();
                            });
                        });
                    });

                    $('#login-redirect-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(getAAM().prepareApiEndpoint(`/redirect/login`), {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            dataType: 'json',
                            beforeSend: function () {
                                var label = _btn.text();
                                _btn.attr('data-original-label', label);
                                _btn.text(getAAM().__('Resetting...'));
                            },
                            success: function () {
                                getAAM().fetchContent('main');
                            },
                            error: function (response) {
                                getAAM().notification('danger', response, {
                                    request: 'aam/v2/redirect/login',
                                    response
                                });
                            },
                            complete: function () {
                                _btn.text(_btn.attr('data-original-label'));
                            }
                        });
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
            function save(payload, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getAAM().prepareApiEndpoint('/redirect/logout'), {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        dataType: 'json',
                        data: payload,
                        success: function (response) {
                            successCallback(response);
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: 'aam/v2/redirect/logout',
                                payload,
                                response
                            });
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

                            // Now, if the login redirect type is default, then
                            // save the data, otherwise save only when more detail
                            // provided
                            const type = $(this).val();

                            if (type === 'default') {
                                save({ type }, () => {
                                    $('#aam-logout-redirect-overwrite').show();
                                });
                            }
                        });
                    });

                    $('input[type="text"],select', container).each(function () {
                        $(this).bind('change', function () {
                            const value = $.trim($(this).val());
                            const type  = $('input[name="logout.redirect.type"]:checked').val();

                            const payload = {
                                type
                            };

                            if (type === 'page_redirect') {
                                payload.redirect_page_id = value;
                            } else if (type === 'url_redirect') {
                                payload.redirect_url = value;
                            } else {
                                payload.callback = value;
                            }

                            // Save redirect type
                            save(payload, () => {
                                $('#aam-logout-redirect-overwrite').show();
                            });
                        });
                    });

                    $('#logout-redirect-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(getAAM().prepareApiEndpoint(`/redirect/logout`), {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            dataType: 'json',
                            beforeSend: function () {
                                var label = _btn.text();
                                _btn.attr('data-original-label', label);
                                _btn.text(getAAM().__('Resetting...'));
                            },
                            success: function () {
                                getAAM().fetchContent('main');
                            },
                            error: function (response) {
                                getAAM().notification('danger', response, {
                                    request: 'aam/v2/redirect/logout',
                                    response
                                });
                            },
                            complete: function () {
                                _btn.text(_btn.attr('data-original-label'));
                            }
                        });
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
             * @param {type} items
             * @param {type} status
             * @param {type} successCallback
             * @returns {undefined}
             */
            function save(payload, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(getAAM().prepareApiEndpoint('/redirect/not-found'), {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        dataType: 'json',
                        data: payload,
                        success: function (response) {
                            successCallback(response);
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: '/redirect/not-found',
                                payload,
                                response
                            });
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

                            // Now, if the login redirect type is default, then
                            // save the data, otherwise save only when more detail
                            // provided
                            const type = $(this).val();

                            if (['default', 'login_redirect'].includes(type)) {
                                save({ type }, () => {
                                    $('#aam-404redirect-overwrite').show();
                                });
                            }
                        });
                    });

                    $('input[type="text"],select', container).each(function () {
                        $(this).bind('change', function () {
                            const value = $.trim($(this).val());
                            const type  = $('input[name="not_found_redirect_type"]:checked').val();

                            const payload = {
                                type
                            };

                            if (type === 'page_redirect') {
                                payload.redirect_page_id = value;
                            } else if (type === 'url_redirect') {
                                payload.redirect_url = value;
                            } else {
                                payload.callback = value;
                            }

                            // Save redirect type
                            save(payload, () => {
                                $('#aam-404redirect-overwrite').show();
                            });
                        });
                    });

                    $('#404redirect-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(getAAM().prepareApiEndpoint(`/redirect/not-found`), {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            dataType: 'json',
                            beforeSend: function () {
                                var label = _btn.text();
                                _btn.attr('data-original-label', label);
                                _btn.text(getAAM().__('Resetting...'));
                            },
                            success: function () {
                                getAAM().fetchContent('main');
                            },
                            error: function (response) {
                                getAAM().notification('danger', response, {
                                    request: '/redirect/not-found',
                                    response
                                });
                            },
                            complete: function () {
                                _btn.text(_btn.attr('data-original-label'));
                            }
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
             * @param {type} id
             * @param {type} btn
             * @returns {undefined}
             */
            function save(id, btn) {
                const is_restricted = $(btn).hasClass('icon-check-empty');

                getAAM().queueRequest(function () {
                    // Show indicator
                    $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');

                    const payload = {
                        effect: is_restricted ? 'deny' : 'allow'
                    };

                    $.ajax(getAAM().prepareApiEndpoint(`/api-route/${id}`), {
                        type: 'POST',
                        dataType: 'json',
                        data: payload,
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                        },
                        success: function () {
                            $('#aam-route-overwrite').removeClass('hidden');
                            updateBtn(btn, is_restricted);
                        },
                        error: function (response) {
                            updateBtn(btn, !is_restricted);

                            getAAM().notification('danger', response, {
                                request: `/api-route/${id}`,
                                payload,
                                response
                            });
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
             * @param {*} text
             * @returns
             */
            function escapeHtml(text) {
                var map = {
                  '&': '&amp;',
                  '<': '&lt;',
                  '>': '&gt;',
                  '"': '&quot;',
                  "'": '&#039;'
                };

                return text.replace(/[&<>"']/g, function(m) { return map[m]; });
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
                            url: getAAM().prepareApiEndpoint(`/api-routes`),
                            type: 'GET',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            dataType: 'json',
                            dataSrc: function (routes) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(routes, (_, route) => {
                                    data.push([
                                        route.id,
                                        route.method,
                                        escapeHtml(route.endpoint),
                                        route.is_restricted ? 'checked' : 'unchecked'
                                    ]);
                                });

                                return data;
                            }
                        },
                        columnDefs: [
                            { visible: false, targets: [0] },
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
                                'class': 'aam-api-method ' + data[1].toLowerCase()
                            }).text(data[1]);

                            $('td:eq(0)', row).html(method);

                            var actions = data[3].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
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
                                            'class': 'aam-row-action text-danger icon-check'
                                        }).bind('click', function () {
                                            save(data[0], this);
                                        }));
                                        break;

                                    default:
                                        break;
                                }
                            });
                            $('td:eq(2)', row).html(container);
                        }
                    });

                    // Reset button
                    $('#route-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(getAAM().prepareApiEndpoint(`/api-routes`), {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            dataType: 'json',
                            beforeSend: function () {
                                var label = _btn.text();
                                _btn.attr('data-original-label', label);
                                _btn.text(getAAM().__('Resetting...'));
                            },
                            success: function () {
                                getAAM().fetchContent('main');
                            },
                            error: function (response) {
                                getAAM().notification('danger', response, {
                                    request: '/api-routes',
                                    response
                                });
                            },
                            complete: function () {
                                _btn.text(_btn.attr('data-original-label'));
                            }
                        });
                    });

                    $('[data-toggle="toggle"]', '#route-content').bootstrapToggle();

                    getAAM().triggerHook('init-api-route');
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);

        /**
         * URL Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {*} rule
             */
            function ResetUrlManageForm(rule = null) {
                // Clearing all values and resetting the form to default
                $('.form-clearable', '#uri-model').val('');
                $('.aam-uri-access-action').hide();
                $('#url_save_btn').removeAttr('data-url-id');
                $('input[type="radio"]', '#uri-model').prop('checked', false);
                $('#uri-model').modal('show');

                // If rule is provided, populating the values
                if (rule !== null) {
                    $('#url_save_btn').attr('data-url-id', encodeURI(rule.id));

                    // Settings edit form attributes
                    $('#url_rule_url').val(rule.url_schema);

                    let restriction_type = rule.effect === 'allow' ? 'allow' : 'deny';
                    let http_status_code = null;

                    if (rule.redirect !== undefined) {
                        restriction_type = rule.redirect.type;
                        http_status_code = rule.redirect.http_status_code;
                    }

                    $(`#url_access_${restriction_type}`, '#uri-model').prop(
                        'checked', true
                    ).trigger('click');

                    if (restriction_type === 'custom_message') {
                        $('#url_access_custom_message_value').val(
                            rule.redirect.message
                        );
                    } else if (restriction_type === 'page_redirect') {
                        $('#url_access_page_redirect_value').val(
                            rule.redirect.redirect_page_id
                        );
                    } else if (restriction_type === 'url_redirect') {
                        $('#url_access_url_redirect_value').val(
                            rule.redirect.redirect_url
                        );
                    } else if (restriction_type === 'trigger_callback') {
                        $('#url_access_trigger_callback_value').val(
                            rule.redirect.callback
                        );
                    }

                    $('#url_access_http_redirect_code').val(http_status_code);
                }

                getAAM().triggerHook('aam-reset-url-manage-form', {
                    container: $('#uri-model'),
                    rule
                });
            }
            /**
             *
             */
            function initialize() {
                const container = '#url-content';

                if ($(container).length) {
                    $('input[name="uri.access.type"]', container).each(function () {
                        $(this).bind('click', function () {
                            const type = $(this).val();

                            $('.aam-uri-access-action').hide();
                            $(`#url_access_${type}_attrs`).show();

                            if (['page_redirect', 'url_redirect'].includes(type)) {
                                $('#url_access_http_status_code').show();
                            }
                        });
                    });

                    // Reset button
                    $('#uri-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(getAAM().prepareApiEndpoint(`/urls`), {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            dataType: 'json',
                            beforeSend: function () {
                                var label = _btn.text();
                                _btn.attr('data-original-label', label);
                                _btn.text(getAAM().__('Resetting...'));
                            },
                            success: function () {
                                getAAM().fetchContent('main');
                            },
                            error: function (response) {
                                getAAM().notification('danger', response, {
                                    request: 'aam/v2/urls',
                                    response
                                });
                            },
                            complete: function () {
                                _btn.text(_btn.attr('data-original-label'));
                            }
                        });
                    });

                    $('#url_save_btn').bind('click', function (event) {
                        event.preventDefault();

                        const url_schema = $('#url_rule_url').val();
                        const code       = $('#url_access_http_redirect_code').val();
                        const type       = $('input[name="uri.access.type"]:checked').val();

                        const editing_url = $(this).attr('data-url-id');

                        if (url_schema && type) {
                            // Preparing the payload
                            const payload = {
                                effect: type === 'allow' ? 'allow' : 'deny',
                                url_schema
                            }

                            if (type === 'custom_message') {
                                payload.redirect = {
                                    type,
                                    message: $.trim(
                                        $('#url_access_custom_message_value').val()
                                    )
                                }
                            } else if (type === 'page_redirect') {
                                payload.redirect = {
                                    type,
                                    redirect_page_id: parseInt(
                                        $('#url_access_page_redirect_value').val(), 10
                                    )
                                }
                            } else if (type === 'url_redirect') {
                                payload.redirect = {
                                    type,
                                    redirect_url: $.trim(
                                        $('#url_access_url_redirect_value').val()
                                    )
                                }
                            } else if (type === 'trigger_callback') {
                                payload.redirect = {
                                    type,
                                    callback: $.trim(
                                        $('#url_access_trigger_callback_value').val()
                                    )
                                }
                            } else if (type === 'login_redirect') {
                                payload.redirect = {
                                    type
                                }
                            }

                            if (code
                                && ['page_redirect', 'url_redirect'].includes(type)
                            ) {
                                payload.redirect.http_status_code = parseInt(code, 10);
                            }

                            let endpoint = `/url`;

                            if (editing_url) {
                                endpoint += '/' + editing_url;
                            } else {
                                endpoint += 's'
                            }

                            $.ajax(getAAM().prepareApiEndpoint(endpoint), {
                                type: 'POST',
                                contentType: 'application/json',
                                dataType: 'json',
                                data: JSON.stringify(
                                    getAAM().applyFilters('aam-url-access-payload', payload)
                                ),
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce
                                },
                                beforeSend: function () {
                                    $('#url_save_btn').text(
                                        getAAM().__('Saving...')
                                    ).attr('disabled', true);
                                },
                                success: function () {
                                    $('#uri-list').DataTable().ajax.reload();
                                    $('#aam-uri-overwrite').show();
                                },
                                error: function (response) {
                                    getAAM().notification('danger', response, {
                                        request: endpoint,
                                        payload,
                                        response
                                    });
                                },
                                complete: function () {
                                    $('#uri-model').modal('hide');
                                    $('#url_save_btn')
                                        .text(getAAM().__('Save'))
                                        .attr('disabled', false);
                                }
                            });
                        }
                    });

                    $('#uri-delete-btn').bind('click', function (event) {
                        event.preventDefault();

                        const url = $('#uri-delete-btn').attr('data-url-id');

                        $.ajax(getAAM().prepareApiEndpoint(`/url/${url}`), {
                            type: 'POST',
                            dataType: 'json',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            beforeSend: function () {
                                $('#uri-delete-btn').text(
                                    getAAM().__('Deleting...')
                                ).attr('disabled', true);
                            },
                            success: function () {
                                $('#uri-list').DataTable().ajax.reload();
                            },
                            error: function (response) {
                                getAAM().notification('danger', response, {
                                    request: `aam/v2/url/${url}`,
                                    response
                                });
                            },
                            complete: function () {
                                $('#uri-delete-model').modal('hide');
                                $('#uri-delete-btn').text(
                                    getAAM().__('Delete')
                                ).attr('disabled', false);
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
                            url: getAAM().prepareApiEndpoint(`/urls`),
                            type: 'GET',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            dataType: 'json',
                            dataSrc: function (json) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(json, (_, rule) => {
                                    const actions = ['edit'];

                                    if (rule.is_customized) {
                                        actions.push('delete');
                                    } else {
                                        actions.push('no-delete');
                                    }

                                    let restriction_type = null;

                                    if (rule.effect === 'allow') {
                                        restriction_type = 'allow';
                                    } else if (rule.redirect) {
                                        restriction_type = rule.redirect.type;
                                    } else {
                                        restriction_type = 'deny';
                                    }

                                    data.push([
                                        rule.url_schema,
                                        restriction_type,
                                        actions,
                                        rule
                                    ]);
                                });

                                return data;
                            },
                        },
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ URL(s)'),
                            infoFiltered: ''
                        },
                        columnDefs: [
                            { visible: false, targets: [3] }
                        ],
                        initComplete: function () {
                            var create = $('<a/>', {
                                'href': '#',
                                'class': 'btn btn-primary'
                            }).html('<i class="icon-plus"></i> ' + getAAM().__('Create'))
                                .bind('click', function () {
                                    ResetUrlManageForm();
                                });

                            $('.dataTables_filter', '#uri-list_wrapper').append(create);
                        },
                        createdRow: function (row, data) {
                            const container = $(
                                '<div/>', { 'class': 'aam-row-actions' }
                            );

                            $.each(data[2], function (i, action) {
                                switch (action) {
                                    case 'edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-warning'
                                        }).bind('click', function () {
                                            ResetUrlManageForm(data[3]);
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
                                            $('#uri-delete-btn').attr(
                                                'data-url-id', encodeURI(data[3].id)
                                            );
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
                                case 'allow':
                                    type.html(getAAM().__('Allowed'));
                                    type.attr('class', 'badge success');
                                    break;

                                case 'deny':
                                case 'custom_message':
                                    type.html(getAAM().__('Denied'));
                                    type.attr('class', 'badge danger');
                                    break;

                                case 'login_redirect':
                                case 'page_redirect':
                                case 'url_redirect':
                                    type.html(getAAM().__('Redirected'));
                                    type.attr('class', 'badge redirect');
                                    break;

                                case 'trigger_callback':
                                    type.html(getAAM().__('Callback'));
                                    type.attr('class', 'badge callback');
                                    break;

                                default:
                                    getAAM().triggerHook('aam-decorate-url-rule', {
                                        badge: type,
                                        rule: data[3]
                                    });
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
         * Users & Roles (aka Identity) Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
         (function ($) {

            const current_selections = {
                identity_type: 'role',
                identity_id: null,
                re_init: false
            };

            /**
             *
             * @param {*} permission
             * @param {*} is_denied
             */
            function SavePermission(permission, is_denied) {
                if (current_selections.re_init === false) {
                    getAAM().queueRequest(function () {
                        const endpoint = `/identity/${current_selections.identity_type}/${current_selections.identity_id}/${permission}`;

                        $.ajax(getAAM().prepareApiEndpoint(endpoint), {
                            type: 'POST',
                            contentType: 'application/json',
                            dataType: 'json',
                            data: JSON.stringify({
                                effect: is_denied ? 'deny' : 'allow'
                            }),
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            success: function (response) {
                                $(`#aam_${current_selections.identity_type}_identity_overwrite`).removeClass('hidden');
                                $(`#${current_selections.identity_type}_identity_list`).DataTable().ajax.reload(null, false);

                                getAAM().triggerHook('identity-permission-saved', {
                                    current_selections,
                                    permissions: response
                                });
                            },
                            error: function (response) {
                                getAAM().notification('danger', response, {
                                    request: endpoint,
                                    payload: {
                                        effect: is_denied ? 'deny' : 'allow'
                                    },
                                    response
                                });
                            }
                        });
                    });
                }
            }

            /**
             *
             * @param {*} cb
             */
            function ResetPermissions(cb) {
                getAAM().queueRequest(function () {
                    const endpoint = `/identity/${current_selections.identity_type}/${current_selections.identity_id}`;

                    $.ajax(getAAM().prepareApiEndpoint(endpoint), {
                        type: 'POST',
                        contentType: 'application/json',
                        dataType: 'json',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'DELETE'
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: endpoint,
                                response
                            });
                        },
                        complete: function() {
                            cb();

                            getAAM().triggerHook('identity-permission-reset', {
                                current_selections
                            });
                        }
                    });
                });
            }

            /**
             *
             * @param {*} container
             * @param {*} type
             * @param {*} id
             */
            function InitializePermissionForm(container, type, id, cb = null) {
                // Initialize the permissions
                current_selections.re_init       = true;
                current_selections.identity_type = type;
                current_selections.identity_id   = id;

                getAAM().queueRequest(function () {
                    $.ajax(getAAM().prepareApiEndpoint(`/identity/${type}/${id}`), {
                        type: 'GET',
                        headers: {
                            'X-WP-Nonce': $.aam.getLocal().rest_nonce
                        },
                        success: function (response) {
                            $(`${container} input[type="checkbox"]`).prop(
                                'checked', false
                            ).trigger('change');

                            $.each(response.permissions, function(permission, c) {
                                if (c.effect === 'deny') {
                                    $(`${container} input[name="${permission}"]`).prop(
                                        'checked', true
                                    ).trigger('change');
                                }
                            });

                            if (response.is_customized) {
                                $('.aam-overwrite', container).removeClass('hidden');
                            } else {
                                $('.aam-overwrite', container).addClass('hidden');
                            }

                            current_selections.re_init = false;

                            $(`#${type}_identity_list_wrapper`).addClass('hidden');
                            $(container).addClass('active');
                        },
                        error: function (response) {
                            $.aam.notification('danger', response);
                        },
                        complete: function() {
                            if (cb) {
                                cb();
                            }
                        }
                    });
                });
            }

            /**
             *
             */
            function initialize() {
                const container = '#identity-content';

                if ($(container).length) {
                    $('[data-toggle="toggle"]', '#identity-content').bootstrapToggle();

                    $('.aam-identity-go-back').bind('click', function() {
                        const type = current_selections.identity_type;

                        $(`#${type}_identity_list_wrapper`).removeClass('hidden');
                        $(`#aam_${type}_permissions_form`).removeClass('active');

                        getAAM().triggerHook('user-identity-go-back', {
                            current_selections
                        });
                    });

                    $('.aam-identity-reset').bind('click', function() {
                        const btn = $(this);

                        btn.text(getAAM().__('Resetting...')).prop('disabled', true);

                        ResetPermissions(function() {
                            btn.text(getAAM().__('Reset to default')).prop('disabled', false);
                            $('.aam-identity-overwrite').addClass('hidden');
                            $('.aam-identity-go-back').trigger('click');
                        });
                    });

                    const identity_types_filter = $('<select>').attr({
                        'class': 'form-control input-sm aam-ml-1 aam-max-width aam-identity-type'
                    }).bind('change', function () {
                        $('#identity_list_container > .dataTables_wrapper').addClass('hidden');

                        current_selections.identity_type = $(this).val() || 'role';

                        $(`#${current_selections.identity_type}_identity_list_wrapper`).removeClass('hidden');

                        $('.aam-identity-type').val(current_selections.identity_type);
                    });

                    const types = getAAM().applyFilters('aam-identity-types', [
                        { key: '', label: getAAM().__('Identity Types') },
                        { key: 'role', label: getAAM().__('Roles') },
                        { key: 'user', label: getAAM().__('Users') },
                    ]);

                    $.each(types, (_, type) => {
                        identity_types_filter.append(
                            `<option value="${type.key}">${type.label}</option>`
                        );
                    });

                    $('#user_identity_list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        stateSave: true,
                        pagingType: 'simple',
                        serverSide: true,
                        processing: true,
                        ajax: function(filters, cb) {
                            $.ajax({
                                url: getAAM().prepareApiEndpoint('/identity/users'),
                                type: 'GET',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce
                                },
                                data: {
                                    search: filters.search.value,
                                    per_page: filters.length,
                                    offset: filters.start
                                },
                                success: function (response) {
                                    const result = {
                                        data: [],
                                        recordsTotal: 0,
                                        recordsFiltered: 0
                                    };

                                    $.each(response.list, (_, user) => {
                                        result.data.push([
                                            user.id,
                                            user.display_name,
                                            '',
                                            user
                                        ]);
                                    });

                                    result.recordsTotal    = response.summary.total_count;
                                    result.recordsFiltered = response.summary.filtered_count;

                                    cb(result);
                                }
                            });
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 3] }
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search user'),
                            info: getAAM().__('_TOTAL_ user(s)'),
                            infoFiltered: '',
                            lengthMenu: '_MENU_'
                        },
                        initComplete: function () {
                            $('#user_identity_list_length').append(
                                $(identity_types_filter).clone(true)
                            );

                            // Determine btn color
                            const btn_class = $('#user_identity_list').data('has-default') ? 'btn-warning' : 'btn-primary';

                            $('#user_identity_list_wrapper > .row:eq(0)').after(`
                                <div class="row"><div class="col-sm-12"><table class="table table-bordered no-margin-bottom">
                                    <tbody>
                                        <tr class="aam-info">
                                            <td class="text-left"><b>Premium Feature.</b> Set default permissions for all users effortlessly. Each user will automatically inherit these predefined permissions, with the flexibility to customize and override them individually as needed.</td>
                                            <td class="text-center">
                                                <a href="#" class="btn btn-xs ${btn_class}" disabled id="set_default_user_permissions">Set Permissions</a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table></div></div>
                            `);

                            getAAM().triggerHook('user-identity-table-initialized', {
                                current_selections,
                                api: {
                                    InitializePermissionForm
                                }
                            });
                        },
                        createdRow: function (row, data) {
                            var container = $('<div/>', { 'class': 'aam-row-actions' });

                            $(container).append($('<i/>', {
                                'class': 'aam-row-action icon-cog ' + (data[3].is_customized ? 'aam-access-overwritten' : 'text-info')
                            }).bind('click', function () {
                                // Initialize the permissions
                                InitializePermissionForm(
                                    '#aam_user_permissions_form',
                                    'user',
                                    data[0]
                                );
                            }).attr({
                                'data-toggle': "tooltip",
                                'title': getAAM().__('Manage User Permissions')
                            }));

                            $('td:eq(1)', row).html(container);
                        }
                    });

                    $('#role_identity_list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        pagingType: 'simple',
                        processing: true,
                        stateSave: true,
                        serverSide: false,
                        ajax: {
                            url: getAAM().prepareApiEndpoint('/identity/roles'),
                            type: 'GET',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            dataType: 'json',
                            dataSrc: function (json) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(json, (_, role) => {
                                    data.push([
                                        role.id,
                                        role.name,
                                        '',
                                        role
                                    ])
                                });

                                return data;
                            },
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 3] },
                        ],
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search role'),
                            info: getAAM().__('_TOTAL_ role(s)'),
                            infoFiltered: '',
                            lengthMenu: '_MENU_'
                        },
                        initComplete: function () {
                            $('#role_identity_list_length').append(
                                $(identity_types_filter).clone(true)
                            );

                            // Determine btn color
                            const btn_class = $('#role_identity_list').data('has-default') ? 'btn-warning' : 'btn-primary';

                            $('#role_identity_list_wrapper > .row:eq(0)').after(`
                                <div class="row"><div class="col-sm-12"><table class="table table-bordered no-margin-bottom">
                                    <tbody>
                                        <tr class="aam-info">
                                            <td class="text-left"><b>Premium Feature.</b> Set default permissions for all roles effortlessly. Roles will automatically inherit these predefined permissions, with the flexibility to customize and override them individually as needed.</td>
                                            <td class="text-center">
                                                <a href="#" class="btn btn-xs ${btn_class}" disabled id="set_default_role_permissions">Set Permissions</a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table></div></div>
                            `);

                            getAAM().triggerHook('role-identity-table-initialized', {
                                current_selections,
                                api: {
                                    InitializePermissionForm
                                }
                            });
                        },
                        createdRow: function (row, data) {
                            var container = $('<div/>', { 'class': 'aam-row-actions' });

                            $(container).append($('<i/>', {
                                'class': 'aam-row-action icon-cog ' + (data[3].is_customized ? 'aam-access-overwritten' : 'text-info')
                            }).bind('click', function () {
                                InitializePermissionForm(
                                    '#aam_role_permissions_form',
                                    'role',
                                    data[0]
                                );
                            }).attr({
                                'data-toggle': "tooltip",
                                'title': getAAM().__('Manage Role Permissions')
                            }));

                            $('td:eq(1)', row).html(container);
                        }
                    });

                    $('#identity_list_container > .dataTables_wrapper').addClass('hidden');
                    $('#role_identity_list_wrapper').removeClass('hidden');

                    // Initialize the permission settings
                    $('#identity_list_container input[type="checkbox"]').each(function() {
                        $(this).bind('change', function() {
                            SavePermission(
                                $(this).attr('name'),
                                $(this).prop('checked')
                            );
                        });
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
             * Delete token
             *
             * @param {String}   id
             * @param {Callback} before_cb
             * @param {Callback} after_cb
             *
             * @returns {Void}
             */
            function DeleteToken(id, cb) {
                const payload = {
                    user_id: getAAM().getSubject().id
                };

                $.ajax(`${getLocal().rest_base}aam/v2/jwt/${id}`, {
                    type: 'POST',
                    dataType: 'json',
                    data: payload,
                    headers: {
                        'X-WP-Nonce': getLocal().rest_nonce,
                        'X-HTTP-Method-Override': 'DELETE'
                    },
                    success: function () {
                        cb();
                    },
                    error: function (response) {
                        getAAM().notification('danger', response, {
                            request: `aam/v2/jwt/${id}`,
                            payload,
                            response
                        });
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

                    let jwtClaimsEditor;

                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);

                    $('#create-jwt-modal').on('show.bs.modal', function () {
                        try {
                            $('#jwt-expiration-datapicker').data('DateTimePicker').defaultDate(
                                tomorrow
                            );
                            $('#jwt-expires').val(tomorrow.toISOString());

                            $('#aam-jwt-claims-editor').val('{}');

                            if (!$('#aam-jwt-claims-editor').next().hasClass('CodeMirror')) {
                                jwtClaimsEditor = wp.CodeMirror.fromTextArea(
                                    document.getElementById("aam-jwt-claims-editor"),
                                    {
                                        type: 'application/json'
                                    }
                                );
                            }
                        } catch (e) {
                            // do nothing. Prevent from any kind of corrupted data
                        }
                    });

                    $('#jwt-expiration-datapicker').on('dp.change', function (res) {
                        $('#jwt-expires').val(res.date.toISOString());
                    });

                    // Prepare the URL endpoint
                    let url  = `${getLocal().rest_base}aam/v2/jwts`;
                        url += `?user_id=${getAAM().getSubject().id}&fields=claims,signed_url`;

                    $('#jwt-list').DataTable({
                        autoWidth: false,
                        ordering: true,
                        dom: 'ftrip',
                        pagingType: 'simple',
                        processing: true,
                        stateSave: false,
                        serverSide: false,
                        ajax: {
                            url,
                            type: 'GET',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            dataType: 'json',
                            dataSrc: function (tokens) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(tokens, (_, token) => {
                                    let details;

                                    if (token.is_valid) {
                                        details = 'Expires On: ' + (new Date(token.claims.exp * 1000)).toDateString()
                                    } else {
                                        details = 'Invalid Token: ' + token.error;
                                    }

                                    data.push([
                                        token.id,
                                        token.token,
                                        token.signed_url || '',
                                        token.is_valid,
                                        details,
                                        'view,delete'
                                    ]);
                                });

                                return data;
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
                            { visible: false, targets: [0, 1, 2] },
                            { orderable: false, targets: [0, 1, 2, 3, 5] }
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
                            if (data[3] === true) {
                                $('td:eq(0)', row).html(
                                    '<i class="icon-ok-circled text-success"></i>'
                                );
                            } else {
                                $('td:eq(0)', row).html(
                                    '<i class="icon-cancel-circled text-danger"></i>'
                                );
                            }

                            // Token details
                            $('td:eq(1)', row).html(
                                data[0] + '<br/><small>' + data[4] + '</small>'
                            )

                            var actions = data[5].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'delete':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-trash-empty text-danger'
                                        }).bind('click', function () {
                                            if (data[3]) {
                                                $('#jwt-delete-btn').attr('data-id', data[0]);
                                                $('#delete-jwt-modal').modal('show');
                                            } else {
                                                $(this).attr(
                                                    'class', 'aam-row-action icon-spin4 animate-spin'
                                                );

                                                DeleteToken(data[0], () => {
                                                    $('#jwt-list').DataTable().ajax.reload();
                                                })
                                            }
                                        }).attr({
                                            'data-toggle': "tooltip",
                                            'title': getAAM().__('Delete Token')
                                        }));
                                        break;

                                    case 'view':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-eye text-success'
                                        }).bind('click', function () {
                                            $('#view-jwt-token').val(data[1]);

                                            if (data[2] !== '') {
                                                $('#view-jwt-url').val(data[2]);
                                                $('#jwt-passwordless-url-container').removeClass('hidden');
                                            } else {
                                                $('#jwt-passwordless-url-container').addClass('hidden');
                                            }

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
                        // Preparing the payload
                        const payload = {
                            user_id: getAAM().getSubject().id,
                            is_refreshable: $('#jwt-refreshable').is(':checked'),
                            expires_at: $('#jwt-expires').val()
                        }

                        try {
                            const claims = JSON.parse(jwtClaimsEditor.getValue());

                            if (Object.keys(claims).length > 0) {
                                payload.additional_claims = JSON.stringify(claims);
                            }
                        } catch (e) {
                            console.log(e);
                        }

                        $.ajax(`${getLocal().rest_base}aam/v2/jwts?fields=token,signed_url`, {
                            type: 'POST',
                            dataType: 'json',
                            data: payload,
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            beforeSend: function () {
                                $('#create-jwt-btn').html(getAAM().__('Creating...'));
                            },
                            success: function (response) {
                                $('#create-jwt-modal').modal('hide');
                                $('#jwt-list').DataTable().ajax.reload();

                                jwtClaimsEditor.setValue('{}');

                                $('#view-jwt-token').val(response.token);
                                $('#view-jwt-url').val(response.signed_url);
                                $('#view-jwt-modal').modal('show');
                            },
                            error: function (response) {
                                getAAM().notification('danger', response, {
                                    request: `aam/v2/jwts?fields=token,signed_url`,
                                    payload,
                                    response
                                });
                            },
                            complete: function () {
                                $('#create-jwt-btn').html(getAAM().__('Create'));
                            }
                        });
                    });

                    $('#jwt-delete-btn').bind('click', function () {
                        $('#jwt-delete-btn').html(getAAM().__('Deleting...')).prop(
                            'disabled', true
                        );

                        DeleteToken($('#jwt-delete-btn').attr('data-id'), () => {
                            $('#jwt-delete-btn').html(getAAM().__('Delete')).prop(
                                'disabled', false
                            );
                            $('#delete-jwt-modal').modal('hide');
                            $('#jwt-list').DataTable().ajax.reload();
                        });
                    });

                    $('[data-toggle="toggle"]', container).bootstrapToggle();
                }
            }

            getAAM().addHook('init', initialize);

        })(jQuery);

        /**
         * Security Audit Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             */
            const queue = [];

            /**
             *
             */
            let issues_index = {};

            /**
             *
             */
            function TriggerAudit(reset = false) {
                getAAM().queueRequest(function () {
                    const current_step = queue[0];
                    const step_title   = $(`#check_${current_step}_status`).data('title');
                    const indicator    = $(`.aam-security-audit-step[data-step="${current_step}"]`);
                    const payload      = {
                        step: current_step,
                        reset
                    };

                    if (issues_index[current_step] === undefined) {
                        issues_index[current_step] = {};
                    }

                    indicator.attr(
                        'class', 'aam-security-audit-step icon-spin4 animate-spin'
                    );

                    $.ajax(`${getLocal().rest_base}aam/v2/audit`, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        dataType: 'json',
                        data: payload,
                        success: function (response) {
                            // Append the list of identified issues to the list
                            // if (Array.isArray(response.issues)) {
                            //     $.each(response.issues, (_, issue) => {
                            //         $(`#issue_list_${current_step} tbody`).append(
                            //             '<tr><td><strong>' + issue.type.toUpperCase() + ':</strong> ' + issue.reason + '</td></tr>'
                            //         );

                            //         // Also increment the issue index
                            //         if (issues_index[current_step][issue.type] === undefined) {
                            //             issues_index[current_step][issue.type] = 0;
                            //         }

                            //         issues_index[current_step][issue.type]++;
                            //     });

                            //     $(`#issue_list_${current_step}`).removeClass('hidden');
                            // }

                            if (response.is_completed) {
                                queue.shift(); // Remove completed step

                                // Visual feedback that the step is completed
                                const styles = ['aam-security-audit-step'];
                                if (response.check_status === 'ok') {
                                    styles.push('icon-ok-circled', 'text-success');
                                } else if (response.check_status === 'critical') {
                                    styles.push('icon-cancel-circled', 'text-danger');
                                } else if (response.check_status === 'warning') {
                                    styles.push('icon-attention-circled', 'text-warning');
                                } else if (response.check_status === 'notice') {
                                    styles.push('icon-info-circled', 'text-info');
                                }

                                indicator.attr('class', styles.join(' '));

                                // Computing the number of issues
                                const summary = [];

                                for(const type in issues_index[current_step]) {
                                    const c = issues_index[current_step][type];
                                    const p = c > 1;

                                    summary.push(
                                        c + ' ' + getAAM().__(type + (p ? 's' : ''))
                                    );
                                }

                                $(`#check_${current_step}_status`).html(
                                    step_title + ' - <b>DONE ' + (summary.length ? '(' + summary.join(', ') + ')' : '(OK)' ) + '</b>'
                                );

                                if (queue.length) {
                                    TriggerAudit();
                                } else {
                                    $('#execute_security_audit')
                                        .text(getAAM().__('Execute the Security Audit'))
                                        .attr('disabled', false);

                                    const url = new URL(window.location);

                                    if (url.searchParams.get('aam_page') === 'audit') {
                                        window.location.reload();
                                    } else {
                                        url.searchParams.set('aam_page', 'audit');
                                        window.location.href = url.toString();
                                    }
                                }
                            } else {
                                $(`#check_${current_step}_status`).text(
                                    step_title + ' - ' + (response.progress * 100).toFixed(2) + '%'
                                );

                                TriggerAudit();
                            }
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: `aam/v2/audit`,
                                payload,
                                response
                            });
                        }
                    });
                });
            }

            /**
             *
             */
            function DownloadReport(btn) {
                getAAM().queueRequest(function () {
                    btn
                        .text(getAAM().__('Generating Report...'))
                        .prop('disabled', true);


                    $.ajax(`${getLocal().rest_base}aam/v2/audit/report`, {
                        type: 'GET',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'Accept': 'text/csv'
                        },
                        success: function (response) {
                            getAAM().downloadFile(
                                response, 'audit-report.csv', 'text/csv', false
                            );
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: `aam/v2/audit/report`,
                                response
                            });
                        },
                        complete: function() {
                            btn
                                .text(getAAM().__('Download Latest Report'))
                                .prop('disabled', false);
                        }
                    });
                });
            }

            /**
             *
             * @param {*} id
             * @param {*} list
             */
            function HydrateList(id, list) {
                if (Array.isArray(list)) {
                    $(id).removeClass('hidden');

                    $.each(list, function(_, text) {
                        $(id + ' > ul').append($('<li/>').text(text));
                    });
                }
            }

            /**
             *
             */
            function HydrateExecutiveSummary(data) {
                $('#executive_summary_prompt').addClass('hidden');
                $('#executive_summary_container').removeClass('hidden');

                $('#executive_summary_overview').text(data.summary);

                HydrateList('#executive_summary_critical', data.critical);
                HydrateList('#executive_summary_concerns', data.concerns);
                HydrateList('#executive_summary_recommendations', data.recommendations);

                if (data.references && Array.isArray(data.references)) {
                    $('#executive_summary_references').removeClass('hidden');

                    $.each(data.references, function(_, link) {
                        $('#executive_summary_references > ul').append($('<li/>').append(
                            $('<a/>').text(link).attr({
                                href: link,
                                target: '_blank'
                            })
                        ));
                    });
                }
            }

            /**
             *
             * @param {*} btn
             */
            function PrepareExecutiveSummary(btn) {
                $('#executive_summary_error').addClass('hidden');

                getAAM().queueRequest(function () {
                    btn.text(
                        getAAM().__('Preparing Summary. It May Take Up To 20 Sec...')
                    ).prop('disabled', true);

                    $.ajax(`${getLocal().rest_base}aam/v2/audit/summary`, {
                        type: 'GET',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.status === 'success') {
                                HydrateExecutiveSummary(response.results);
                            } else {
                                $('#executive_summary_error')
                                    .text(response.reason)
                                    .removeClass('hidden');
                            }
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: `aam/v2/audit/summary`,
                                response
                            });
                        },
                        complete: function() {
                            btn
                                .text(getAAM().__('Prepare My Executive Summary'))
                                .prop('disabled', false);
                        }
                    });
                });
            }

            /**
             *
             * @returns {undefined}
             */
            function initialize() {
                if ($('#audit-content').length) {
                    $('#run_security_scan').bind('click', function () {
                        if (!$(this).prop('disabled')) {
                            $(this)
                                .text(getAAM().__('Running Scan...'))
                                .prop('disabled', true);

                            // Hide the download report container
                            $('#download_report_container').addClass('hidden');

                            // Reset all previous results
                            $('.aam-detected-issues tbody').empty();
                            $('.aam-security-audit-step').attr(
                                'class', 'icon-circle-thin text-info aam-security-audit-step'
                            );
                            $('.aam-check-status').each(function() {
                                $(this).text($(this).data('title'));
                            });

                            // Reset the issues index
                            issues_index = {};

                            // Getting the queue of steps to execute
                            $('.aam-security-audit-step').each(function() {
                                queue.push($(this).data('step'));
                            });

                            // Triggering the queue loop and perform the audit
                            // step-by-step
                            TriggerAudit(true);
                        }
                    });

                    $('.download-latest-report').bind('click', function() {
                        DownloadReport($(this));
                    });

                    $('#prepare_executive_summary').bind('click', function(event) {
                        event.preventDefault();

                        if (!$(this).prop('disabled')) {
                            PrepareExecutiveSummary($(this));
                        }
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
            function Save(param, value) {
                getAAM().queueRequest(function () {
                    const endpoint = `${getLocal().rest_base}aam/v2/config/${param}`;
                    const payload  = { value };

                    $.ajax(endpoint, {
                        type: 'POST',
                        dataType: 'json',
                        data: payload,
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: endpoint,
                                payload,
                                response
                            });
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
                            var checked = data.status ? 'checked' : '';
                            var toggler = '<input data-toggle="toggle" name="' + data.setting + '" id="utility-' + data.setting + '" ' + checked + ' type="checkbox" data-on="' + getAAM().__('Enabled') + '" data-off="' + getAAM().__('Disabled') + '" data-size="small" />';

                            $('td:eq(1)', row).html(toggler);

                            $('[data-toggle="toggle"]', row).bootstrapToggle();
                            $('input[type="checkbox"]', row).bind('change', function () {
                                Save(
                                    $(this).attr('name'),
                                    $(this).prop('checked')
                                );
                            });
                        }
                    });

                    $('input[type="checkbox"]', '.aam-feature.settings').bind('change', function () {
                        let value;

                        if ($(this).prop('checked')) {
                            value = $(this).data('value-on') || true;
                        } else {
                            value = $(this).data('value-off') || false;
                        }

                        Save($(this).attr('name'), value);
                    });

                    $('#clear-settings').bind('click', function () {
                        $('#clear-settings').prop('disabled', true);
                        $('#clear-settings').text(getAAM().__('Processing...'));

                        getAAM().queueRequest(function () {
                            $.ajax(`${getLocal().rest_base}aam/v2/core/reset`, {
                                type: 'POST',
                                dataType: 'json',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                error: function (response) {
                                    getAAM().notification('danger', response, {
                                        request: 'aam/v2/aam',
                                        response
                                    });
                                },
                                complete: function () {
                                    $('#clear-settings').prop('disabled', false);
                                    $('#clear-settings').text(getAAM().__('Clear'));
                                    $('#clear-settings-modal').modal('hide');

                                    location.reload();
                                }
                            });
                        });
                    });
                }
            }

            getAAM().addHook('init', initialize);

            // ConfigPress hook
            getAAM().addHook('menu-feature-click', function (feature) {
                if (feature === 'configpress'
                    && !$('#aam-configpress-editor').next().hasClass('CodeMirror')) {
                    var editor = wp.CodeMirror.fromTextArea(
                        document.getElementById("aam-configpress-editor"), {}
                    );

                    editor.on("blur", function () {
                        getAAM().queueRequest(function () {
                            const payload = {
                                ini: editor.getValue()
                            };

                            $.ajax(`${getLocal().rest_base}aam/v2/configpress`, {
                                type: 'POST',
                                dataType: 'json',
                                data: payload,
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce
                                },
                                error: function (response) {
                                    getAAM().notification('danger', response, {
                                        request: 'aam/v2/configpress',
                                        payload,
                                        response
                                    });
                                }
                            });
                        });
                    });
                }
            });

            // Import/Export feature
            if (window.File && window.FileReader && window.FileList && window.Blob) {
                $('#file-api-error').remove();

                $('#export-settings').bind('click', function() {
                    getAAM().queueRequest(function () {
                        $.ajax(`${getLocal().rest_base}aam/v2/core/export`, {
                            dataType: 'json',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
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
                                    JSON.stringify(response),
                                    'aam-settings.json',
                                    'application/json',
                                    false
                                )
                            },
                            error: function (response) {
                                getAAM().notification('danger', response);
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
                            const content = JSON.parse(reader.result);

                            // Import AAM settings
                            getAAM().queueRequest(function () {
                                $.ajax(`${getLocal().rest_base}aam/v2/core/import`, {
                                    type: 'POST',
                                    dataType: 'json',
                                    contentType: 'application/json; charset=UTF-8',
                                    data: JSON.stringify({
                                        dataset: content.dataset
                                    }),
                                    headers: {
                                        'X-WP-Nonce': getLocal().rest_nonce
                                    },
                                    beforeSend: function () {
                                        $('#aam-settings').prop('disabled', true);
                                    },
                                    success: function () {
                                        getAAM().notification(
                                            'success',
                                            getAAM().__('Settings has been imported successfully')
                                        );
                                        location.reload();
                                    },
                                    error: function (response) {
                                        getAAM().notification('danger', response);
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
         * Welcome Interface
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
                if ($('#welcome-content').length) {
                    $('#intro_videos_block').on('show.bs.collapse', function(e) {
                        $('.panel-body', e.target).contents().filter(function() {
                            return this.nodeType === 8; // Node.COMMENT_NODE
                        }).replaceWith(function() {
                            return this.data;
                        });
                    });
                }
            }

            getAAM().addHook('init', initialize);

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

                getAAM().queueRequest(function () {
                    $.ajax(getAAM().prepareApiEndpoint(`/settings`), {
                        type: 'POST',
                        dataType: 'json',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'DELETE'
                        },
                        beforeSend: function () {
                            _this.text(getAAM().__('Resetting...')).prop('disabled', true);
                        },
                        success: function () {
                            getAAM().fetchContent('main');
                            $('#reset-subject-modal').modal('hide');
                    },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: 'aam/v2/settings',
                                response
                            });
                        },
                        complete: function () {
                            _this.text(getAAM().__('Reset')).prop('disabled', false);
                        }
                    });
                });
            });
        })(jQuery);
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
    AAM.prototype.loadRoleList = function (selected, target) {
        target = (typeof target === 'undefined' ? '#expiration-change-role' : target);

        $(target).html(
            '<option value="">' + getAAM().__('Loading...') + '</option>'
        );

        GetRoles((response) => {
            $(target).html(
                '<option value="">' + getAAM().__('Select Role') + '</option>'
            );
            for (var i in response) {
                $(target).append(
                    '<option value="' + response[i].slug + '">' + response[i].name + '</option>'
                );
            }

            $(target).val(selected);
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
     * @param {*} view
     * @param {*} cb
     */
    AAM.prototype.fetchContent = function (view, cb = null) {
        var _this = this;

        var payload = {
            action: 'aam',
            sub_action: 'renderContent',
            _ajax_nonce: getLocal().nonce,
            partial: view,
            access_level: this.getSubject().type
        };

        if (payload.access_level === 'role') {
            payload.role_id = this.getSubject().id;
        } else if (payload.access_level === 'user') {
            payload.user_id = this.getSubject().id;
        }

        $.ajax(getLocal().ajaxurl, {
            type: 'POST',
            dataType: 'html',
            data: payload,
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
                // Init menu
                _this.initializeMenu();

                // Trigger initialization hook
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

                if (cb) {
                    cb();
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

        const payload = {
            action: 'aam',
            sub_action: 'renderContent',
            _ajax_nonce: getLocal().nonce,
            partial: view,
            access_level: this.getSubject().type,
            id: object ? object[1] : null,
            type: type ? type[1] : null
        }

        if (payload.access_level === 'role') {
            payload.role_id = this.getSubject().id;
        } else if (payload.access_level === 'user') {
            payload.user_id = this.getSubject().id;
        }

        $.ajax(getLocal().ajaxurl, {
            type: 'POST',
            dataType: 'html',
            data: payload,
            success: function (response) {
                success.call(_this, response);
            },
            error: function(response) {
                getAAM().notification('danger', response);
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
                $('#aam-subject-name').val()
            );
        } else if (getLocal().subject.type) {
            this.setSubject(
                getLocal().subject.type,
                getLocal().subject.id,
                getLocal().subject.name
            );
        } else {
            $('#aam-subject-banner').addClass('hidden');
        }

        //load the UI javascript support
        UI();

        // Initialize help context
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

        // Help tooltip
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

        const query = new URLSearchParams(location.search);

        if (query.has('aam_page')) {
            $('.aam-area').removeClass('text-danger');
            $('.aam-area[data-type="' + query.get('aam_page') + '"]').addClass(
                'text-danger'
            );

            getAAM().fetchContent(query.get('aam_page'));
        } else {
            getAAM().fetchContent('main'); // Fetch default AAM content
        }

        // preventDefault for all links with # href
        $('#aam-container').delegate('a[href="#"]', 'click', function (event) {
            event.preventDefault();
        });

        // Initialize clipboard
        var clipboard = new ClipboardJS('.aam-copy-clipboard');

        clipboard.on('success', function (e) {
            getAAM().notification(
                'success',
                getAAM().__('Data has been saved to clipboard')
            );
        });

        clipboard.on('error', function (e) {
            getAAM().notification(
                'danger',
                getAAM().__('Failed to save data to clipboard')
            );
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
     * @returns {undefined}
     */
    AAM.prototype.setSubject = function (type, id, name) {
        this.subject = {
            type: type,
            id: id,
            name: name
        };

        // Reset all roles
       if ($('#role-list').is('.dataTable')) {
            $('#role-list').DataTable().rows().eq(0).each(function(i) {
                $(
                    'td:eq(0) span',
                    $('#role-list').DataTable().row(i).node()
                ).removeClass('aam-highlight');

                $(
                    '.icon-cog',
                    $('#role-list').DataTable().row(i).node()
                ).attr('class', 'aam-row-action icon-cog text-info');
            });
        }

        if (getAAM().isUI('main')) {
            // First set the type of the subject
            $('.aam-current-subject').text(
                type.charAt(0).toUpperCase() + type.slice(1) + ': '
            );

            // Second set the name of the subject
            $('.aam-current-subject').append($('<strong/>').text(name));
        }

        this.triggerHook('access-level-changed');
    };

    /**
     *
     * @returns {aam_L1.AAM.subject}
     */
    AAM.prototype.getSubject = function () {
        return this.subject;
    };

    /**
     * Show notification
     *
     * @param {String} status
     * @param {Object} response
     * @param {Object} metadata
     *
     * @returns {Void}
     */
    AAM.prototype.notification = function (status, response, metadata = null) {
        let notification_header;
        let notification_message;

        // Determine the visible message
        if (typeof response === 'string') {
            notification_message = response;
        } else if (response && response.responseJSON && response.responseJSON.message) {
            notification_message = response.responseJSON.message;
        }

        switch (status) {
            case 'success':
                notification_header  = 'Success';
                notification_message = getAAM().__(
                    notification_message || 'Operation completed successfully'
                );
                break;

            case 'danger':
                notification_header = 'No go';
                notification_message = notification_message || 'An unexpected application issue has arisen. Please feel free to report this issue to us, and we will promptly provide you with a solution.'
                break;

            default:
                break;
        }

        if (status === 'success') {
            $.toast({
                text: notification_message,
                heading: notification_header,
                icon: 'success',
                showHideTransition: 'fade',
                allowToastClose: true,
                hideAfter: 6000,
                stack: 5,
                position: 'top-right',
                textAlign: 'left',
                loader: true,
                loaderBg: '#5cb85c'
            });
        } else {
            $.toast({
                text: notification_message,
                heading: notification_header,
                icon: 'error',
                showHideTransition: 'fade',
                allowToastClose: true,
                hideAfter: false,
                stack: 5,
                position: 'top-right',
                textAlign: 'left',
                loader: true,
                loaderBg: '#a94442'
            });
        }
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
    AAM.prototype.downloadFile = function(content, filename, mime, decode = true) {
        let binaryString;

        if (decode) {
            binaryString = window.atob(content);
        } else {
            binaryString = content;
        }

        const bytes  = new Uint8Array(binaryString.length);
        const base64 = bytes.map((_, i) => binaryString.charCodeAt(i));

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
     * @param {String}  url
     * @param {Boolean} include_access_level
     * @param {Object}  override
     *
     * @returns {String}
     */
    AAM.prototype.prepareApiEndpoint = function (
        url, include_access_level = true, override = null
    ) {
        let response = `${getLocal().rest_base}aam/v2${url}`;

        if (include_access_level) {
            const al     = override || getAAM().getSubject();
            const type   = al.type;
            const params = [`access_level=${type}`];

            if (type === 'role') {
                params.push(`role_id=${al.id}`);
            } else if (type === 'user') {
                params.push(`user_id=${al.id}`);
            }

            if (response.includes('?')) {
                response += '&' + params.join('&');
            } else {
                response += '?' + params.join('&');
            }
        }

        return response;
    }

    /**
     *
     * @param {*} mergeWith
     * @returns
     */
    AAM.prototype.prepareAjaxRequestPayload = function(mergeWith = {}) {
       // Prepare the payload
       const data = {
           access_level: getAAM().getSubject().type
       };

       if (data.access_level === 'role') {
           data.role_id = getAAM().getSubject().id;
       } else if (data.access_level === 'user') {
           data.user_id = getAAM().getSubject().id;
       }

       return Object.assign({}, mergeWith, data);
   }

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
        $.aam = aam = window['aam'] = new AAM();
        getAAM().initialize();
    });

})(jQuery);