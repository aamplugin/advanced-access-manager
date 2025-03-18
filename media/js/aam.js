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
            $.ajax(`${getLocal().rest_base}aam/v2/service/roles`, {
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
            function LoadRolesDropdown(exclude) {
                // Display the indicator that the list of roles is loading
                $('.inherit-role-list').html(
                    '<option value="">' + getAAM().__('Loading...') + '</option>'
                );

                GetRoles((response) => {
                    $('.inherit-role-list').html(
                        '<option value="">' + getAAM().__('No role') + '</option>'
                    );

                    for (var i in response) {
                        if (exclude !== response[i].slug) {
                            $('.inherit-role-list').append(
                                '<option value="' + response[i].slug + '">' + response[i].name + '</option>'
                            );
                        }
                    }

                    if ($.aamEditRole) {
                        $('.inherit-role-list').val($.aamEditRole[0]);
                    }

                    getAAM().triggerHook('post-get-role-list', {
                        list: response
                    });

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
                return getLocal().rest_base + 'aam/v2/service/role/' + encodeURIComponent(role);
            }

            /**
             *
             */
            function initialize() {
                if (!$('#role-list').hasClass('dataTable')) {
                    // Query params to the request
                    let policyId;

                    const fields = [
                        'user_count',
                        'permissions'
                    ];

                    if ($('#aam-policy-id').length > 0) {
                        fields.push('applied_policy_ids');

                        policyId = parseInt($('#aam-policy-id').val(), 10);
                    }

                    getAAM().applyFilters('role-list-fields', fields);

                    // Prepare the RESTful API endpoint
                    let url = `${getLocal().rest_base}aam/v2/service/roles`;

                    if (url.indexOf('rest_route') === -1) {
                        url += `?fields=${fields.join(',')}`;
                    } else {
                        url += `&fields=${fields.join(',')}`;
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

                                    if (getLocal().ui === 'principal' && policyId) {
                                        if (role.applied_policy_ids.includes(policyId)) {
                                            actions.push('detach');
                                        } else {
                                            actions.push('attach');
                                        }
                                    } else {
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

                                                LoadRolesDropdown(data[0]);

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
                            $.ajax(`${getLocal().rest_base}aam/v2/service/roles`, {
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

                                    if (v) {
                                        data[$(this).attr('name')] = v;
                                    }
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
                                        request: `aam/v2/service/role/${$(_this).data('role')}`,
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
                    url: `${getLocal().rest_base}aam/v2/service/user/${id}?fields=status`,
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
                        getAAM().notification('danger', null, {
                            request: `aam/v2/service/user/${id}?fields=status`,
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
                    const action = $('#action-after-expiration').val();

                    const payload = {
                        user_id: $('#reset-user-expiration-btn').attr('data-user-id'),
                        expires_at: $('#user-expires').val(),
                    };

                    if (action) {
                        payload.additional_claims = {
                            trigger: {
                                action
                            }
                        }

                        if (action === 'change_role') {
                            payload.additional_claims.trigger.meta = $('#expiration-change-role').val();
                        }
                    }

                    $.ajax(`${getLocal().rest_base}aam/v2/service/jwts`, {
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
                            getAAM().notification('danger', null, {
                                request: 'aam/v2/service/jwts',
                                payload,
                                response
                            });
                        }
                    });
                }
            }

            //initialize the user list table
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
                        url: `${getLocal().rest_base}aam/v2/service/users`,
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
                            .html('<option value="">' + getAAM().__('Loading roles...') + '</option>')
                            .bind('change', function () {
                                $('#user-list').DataTable().ajax.reload();
                            });

                        $('.dataTables_filter', '#user-list_wrapper').append(filter);

                        $('.inherit-role-list').html(
                            '<option value="">' + getAAM().__('Loading...') + '</option>'
                        );

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

                                // case 'protect':
                                //     if (getAAM().isUI('main')) {
                                //         $(container).append($('<i/>', {
                                //             'class': 'aam-row-action icon-asterisk text-muted'
                                //         }).bind('click', function () {
                                //             protectUser(data[0], $(this));
                                //         }).attr({
                                //             'data-toggle': "tooltip",
                                //             'title': getAAM().__('Protect user')
                                //         }));
                                //     }
                                //     break;

                                // case 'no-protect':
                                //     if (getAAM().isUI('main')) {
                                //         $(container).append($('<i/>', {
                                //             'class': 'aam-row-action icon-asterisk text-success'
                                //         }).attr({
                                //             'data-toggle': "tooltip",
                                //             'title': getAAM().__('Release user protection')
                                //         }));
                                //     }
                                //     break;

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
                    url: `${getLocal().rest_base}aam/v2/service/user/${id}`,
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
                        getAAM().notification('danger', null, {
                            request: `aam/v2/service/user/${id}`,
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
                    url: `${getLocal().rest_base}aam/v2/service/user/${id}`,
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
                        getAAM().notification('danger', null, {
                            request: `aam/v2/service/user/${id}`,
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
                getAAM().queueRequest(function () {
                    $.ajax(`${getLocal().rest_base}wp/v2/aam_policy/${id}`, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'DELETE'
                        },
                        dataType: 'json',
                        data: {
                            force: true
                        },
                        beforeSend: function () {
                            $(btn).attr('data-original', $(btn).text());
                            $(btn).text(getAAM().__('Deleting...')).attr(
                                'disabled', true
                            );
                        },
                        success: function () {
                            $('#policy-list').DataTable().ajax.reload();
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

                                // var install = $('<a/>', {
                                //     'href': '#modal-install-policy',
                                //     'class': 'btn btn-sm btn-success aam-outer-left-xxs',
                                //     'data-toggle': 'modal'
                                // }).html('<i class="icon-download-cloud"></i> ' + getAAM().__('Install'));

                                // $('.dataTables_filter', '#policy-list_wrapper').append(install);
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

                    $('#modal-install-policy').on('show.bs.modal', function() {
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
             * @param {Number}   item
             * @param {Boolean}  is_restricted
             * @param {Callback} cb
             *
             * @returns {Void}
             */
            function save(item, is_restricted, cb) {
                getAAM().queueRequest(function () {
                    const payload = getAAM().prepareRequestSubjectData({
                        is_restricted
                    });

                    $.ajax(`${getLocal().rest_base}aam/v2/service/backend-menu/${item}`, {
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
                            getAAM().notification('danger', null, {
                                request: `aam/v2/service/backend-menu/${item}`,
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

                            save(_this.data('menu-id'), status, function () {
                                $('#aam-menu-overwrite').show();

                                if (status) { //locked the menu
                                    $('.aam-menu-expended-list', target).append(
                                        $('<div/>', { 'class': 'aam-lock' }).append(
                                            getAAM().__('The entire menu is restricted with all submenus')
                                        )
                                    );
                                    _this.removeClass('btn-danger').addClass('btn-primary');
                                    _this.html('<i class="icon-lock-open"></i>' + getAAM().__('Show Menu'));

                                    var ind = $('<i/>', {
                                        'class': 'aam-panel-title-icon icon-lock text-danger'
                                    });
                                    $('.panel-title', target + '-heading').append(ind);
                                } else {
                                    _this.removeClass('btn-primary').addClass('btn-danger');

                                    _this.html(
                                        '<i class="icon-lock"></i>' + getAAM().__('Restrict Menu')
                                    );
                                    $('.panel-title .icon-lock', target + '-heading').remove();

                                    getAAM().fetchContent('main');
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

                    $('.aam-accordion-action', '#admin-menu').each(function () {
                        $(this).bind('click', function () {
                            var _this = $(this);

                            const status = _this.hasClass('icon-lock-open');

                            // Show loading indicator
                            _this.attr('class', 'aam-accordion-action icon-spin4 animate-spin');

                            save(
                                _this.data('menu-id'),
                                status,
                                function () {
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
                            $.ajax(`${getLocal().rest_base}aam/v2/service/backend-menu`, {
                                type: 'POST',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                dataType: 'json',
                                data: getAAM().prepareRequestSubjectData(),
                                beforeSend: function () {
                                    _this.attr('data-original-label', _this.text());
                                    _this.text(getAAM().__('Resetting...'));
                                },
                                success: function () {
                                    getAAM().fetchContent('main');
                                },
                                error: function (response) {
                                    getAAM().notification('danger', null, {
                                        request: 'aam/v2/service/backend-menu',
                                        response
                                    });
                                },
                                complete: function () {
                                    _this.text(_this.attr('data-original-label'));
                                }
                            });
                        });
                    });

                    $('[data-toggle="toggle"]', '#admin_menu-content').bootstrapToggle();

                    getAAM().triggerHook('init-backend-menu');
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
             * @param {Number}   item
             * @param {Boolean}  status
             * @param {Callback} successCallback
             *
             * @returns {Void}
             */
            function save(item, is_hidden, cb) {
                getAAM().queueRequest(function () {
                    const payload = getAAM().prepareRequestSubjectData({
                        is_hidden
                    });

                    $.ajax(`${getLocal().rest_base}aam/v2/service/admin-toolbar/${item}`, {
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
                            getAAM().notification('danger', null, {
                                request: `aam/v2/service/admin-toolbar/${item}`,
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
                            var target = _this.data('target');

                            $('i', _this).attr('class', 'icon-spin4 animate-spin');

                            save(_this.data('toolbar'), status, function () {
                                $('#aam-toolbar-overwrite').show();

                                if (status) { //locked the menu
                                    $('.aam-menu-expended-list', target).append(
                                        $('<div/>', { 'class': 'aam-lock' }).append(
                                            getAAM().__('The entire menu is restricted with all submenus')
                                        )
                                    );
                                    _this.removeClass('btn-danger').addClass('btn-primary');
                                    _this.html('<i class="icon-lock-open"></i>' + getAAM().__('Show Menu'));

                                    //add menu restricted indicator
                                    var ind = $('<i/>', {
                                        'class': 'aam-panel-title-icon icon-lock text-danger'
                                    });
                                    $('.panel-title', target + '-heading').append(ind);
                                } else {
                                    _this.removeClass('btn-primary').addClass('btn-danger');

                                    _this.html(
                                        '<i class="icon-lock"></i>' + getAAM().__('Hide Menu')
                                    );

                                    $('.panel-title .icon-lock', target + '-heading').remove();

                                    getAAM().fetchContent('main');
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

                    // Reset button
                    $('#toolbar-reset').bind('click', function () {
                        const _this = $(this);

                        getAAM().queueRequest(function () {
                            $.ajax(`${getLocal().rest_base}aam/v2/service/admin-toolbar`, {
                                type: 'POST',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                dataType: 'json',
                                data: getAAM().prepareRequestSubjectData(),
                                beforeSend: function () {
                                    _this.attr('data-original-label', _this.text());
                                    _this.text(getAAM().__('Resetting...'));
                                },
                                success: function () {
                                    getAAM().fetchContent('main');
                                },
                                error: function (response) {
                                    getAAM().notification('danger', null, {
                                        request: 'aam/v2/service/admin-toolbar',
                                        response
                                    });
                                },
                                complete: function () {
                                    _this.text(_this.attr('data-original-label'));
                                }
                            });
                        });
                    });

                    $('.aam-accordion-action', '#toolbar-list').each(function () {
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
                                    $('#aam-toolbar-overwrite').show();

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
         * Metaboxes & Widgets Interface
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
                    const payload = getAAM().prepareRequestSubjectData({
                        is_hidden
                    });

                    $.ajax(`${getLocal().rest_base}aam/v2/service/component/${item}`, {
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
                            getAAM().notification('danger', null, {
                                request: `aam/v2/service/component/${item}`,
                                payload,
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
                                getAAM().fetchContent('main');
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
                        const _this = $(this);

                        getAAM().queueRequest(function () {
                            $.ajax(`${getLocal().rest_base}aam/v2/service/components`, {
                                type: 'POST',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                dataType: 'json',
                                data: getAAM().prepareRequestSubjectData(),
                                beforeSend: function () {
                                    _this.attr('data-original-label', _this.text());
                                    _this.text(getAAM().__('Resetting...'));
                                },
                                success: function () {
                                    getAAM().fetchContent('main');
                                },
                                error: function (response) {
                                    getAAM().notification('danger', null, {
                                        request: 'aam/v2/service/components',
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

                            save(
                                $(this).data('metabox'),
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
         * Capabilities Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
        (function ($) {

            /**
             *
             * @param {*} attr
             * @returns
             */
            function PreparePayload(attr = {}) {
                const payload = Object.assign({}, attr);

                if (getAAM().getSubject().type === 'role') {
                    payload.role_id = getAAM().getSubject().id;
                } else if (getAAM().getSubject().type === 'user') {
                    payload.user_id = getAAM().getSubject().id;
                }

                return payload;
            }
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

                // Prepare request payload
                const payload = PreparePayload({
                    [granted ? 'add_capabilities' : 'remove_capabilities'] : [
                        capability
                    ]
                });

                // Determine endpoint
                let endpoint = `${getLocal().rest_base}aam/v2/service`;

                if (payload.role_id) {
                    endpoint += `/role/` + encodeURIComponent(payload.role_id)
                } else if (payload.user_id) {
                    endpoint += `/user/${payload.user_id}`
                }

                getAAM().queueRequest(function () {
                    $.ajax(endpoint, {
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
                            getAAM().notification('danger', null, {
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
             * @param {Boolean} subjectOnly
             * @param {Object}  btn
             */
            function deleteCapability(capability, btn, scoped = false) {
                getAAM().queueRequest(function () {
                    const payload = (scoped ? PreparePayload() : {});

                    $.ajax(`${getLocal().rest_base}aam/v2/service/capability/${encodeURIComponent(capability)}`, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'DELETE'
                        },
                        dataType: 'json',
                        data: payload,
                        beforeSend: function () {
                            $(btn).attr('data-original', $(btn).text());
                            $(btn).text(getAAM().__('Deleting...')).attr('disabled', true);
                        },
                        success: function () {
                            $('#capability-list').DataTable().ajax.reload();
                        },
                        error: function (response) {
                            getAAM().notification('danger', null, {
                                request: `aam/v2/service/capability/${encodeURIComponent(capability)}`,
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
                        fields: 'description,permissions,is_granted,is_assigned',
                        list_all: true
                    };

                    if (getAAM().getSubject().type === 'role') {
                        data.role_id = getAAM().getSubject().id;
                    } else if (getAAM().getSubject().type === 'user') {
                        data.user_id = getAAM().getSubject().id;
                    }

                    // Initialize the capability list table
                    const capTable = $('#capability-list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        pagingType: 'simple',
                        serverSide: false,
                        ajax: {
                            url: `${getLocal().rest_base}aam/v2/service/capabilities`,
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
                            const payload = PreparePayload({
                                slug,
                                ignore_format: ignore
                            });

                            getAAM().queueRequest(function () {
                                $.ajax(`${getLocal().rest_base}aam/v2/service/capabilities`, {
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
                                        getAAM().notification('danger', null, {
                                            request: 'aam/v2/service/capabilities',
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
                        const new_slug = $.trim($('#update-capability-slug').val());
                        const ignore   = $('#ignore_update_capability_format').is(':checked');

                        if (new_slug && (/^[a-z0-9_\-]+$/.test(new_slug) || ignore)) {
                            // Prepare request payload
                            const payload = {
                                new_slug,
                                ignore_format: ignore
                            };

                            getAAM().queueRequest(function () {
                                $.ajax(`${getLocal().rest_base}aam/v2/service/capability/${encodeURIComponent(old_slug)}`, {
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
                                        getAAM().notification('danger', null, {
                                            request: `aam/v2/service/capability/${encodeURIComponent(old_slug)}`,
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
                        if (getAAM().getSubject().type === 'user') {
                            deleteCapability($(this).attr('data-cap'), $(this), true);
                        }

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
            const cache = {};

            /**
             *
             * @returns
             */
            function CurrentLevel() {
                return breadcrumb[breadcrumb.length - 1];
            }

            /**
             *
             */
            function RenderBreadcrumb(reload) {
                // Resetting the breadcrumb
                $('.aam-post-breadcrumb').empty();

                $.each(breadcrumb, function(i, level) {
                    if (level.level_type === null) { // Root, append home icon
                        $('.aam-post-breadcrumb').append('<i class="icon-home"></i>');
                    } else {
                        $('.aam-post-breadcrumb').append('<i class="icon-angle-double-right"></i>');
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

                                $('.aam-access-form').removeClass('active');

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

                            // Manually update the data in a table because both
                            // Post Types & Taxonomies are static tables
                            if (['type', 'taxonomy'].includes(object)) {
                                let row = null;

                                if (object === 'type') {
                                    row = cache.post_types.data.filter(t => t[0] === object_id).pop();
                                } else {
                                    row = cache.taxonomies.data.filter(t => t[0] === object_id).pop();
                                }

                                row[4].is_inherited = false;
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
            function RenderAccessForm(level_type, level_id, btn, callback) {
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
                        type: level_type,
                        id: level_id,
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
                        $('#post-content .dataTables_wrapper').addClass('hidden');
                        container.addClass('active');

                        InitializeAccessForm(level_type, level_id);

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
             * @param {*} object
             * @param {*} id
             */
            function InitializeAccessForm(object, id) {
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
                                RenderAccessForm(object, id, null, function() {
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
                    const type   = encodeURIComponent($(this).attr('data-type'));
                    const id     = $(this).attr('data-id');
                    const obj_id = encodeURIComponent(id.split('|')[0]);

                    const payload = {};

                    if (CurrentLevel().scope) {
                        payload.scope = CurrentLevel().scope;

                        if (payload.scope === 'post') {
                            payload.post_type = CurrentLevel().scope_id;
                        }
                    }

                    $.ajax(`${getLocal().rest_base}aam/v2/service/content/${type}/${obj_id}`, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                            'X-HTTP-Method-Override': 'DELETE'
                        },
                        data: getAAM().prepareRequestSubjectData(payload),
                        beforeSend: function () {
                            var label = $('#content-reset').text();
                            $('#content-reset').attr('data-original-label', label);
                            $('#content-reset').text(getAAM().__('Resetting...'));
                        },
                        success: function () {
                            $('#post-overwritten').addClass('hidden');

                            RenderAccessForm(type, id);

                            // Manually update the data in a table because both
                            // Post Types & Taxonomies are static tables
                            if (['type', 'taxonomy'].includes(type)) {
                                let row = null;

                                if (type === 'type') {
                                    row = cache.post_types.data.filter(t => t[0] === obj_id).pop();
                                } else {
                                    row = cache.taxonomies.data.filter(t => t[0] === obj_id).pop();
                                }

                                row[4].is_inherited = true;
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
                            RenderAccessForm(object, id);
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
                            RenderAccessForm(object, id);
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
                            RenderAccessForm(object, id);
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
                                    RenderAccessForm(object, id);
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
                            RenderAccessForm(object, id);
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
                            RenderAccessForm(object, id);
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
                            RenderAccessForm(object, id);
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
                RenderAccessForm(...params);
            });

            getAAM().addHook('save-post-settings', function(params) {
                save(...params);
            });

            /**
             *
             * @param {*} cb
             */
            function FetchPostTypeList(cb) {
                if (cache.post_types === undefined) {
                    // Fetching the list of all registered post types.
                    $.ajax(`${getLocal().rest_base}aam/v2/service/content/types`, {
                        type: 'GET',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        data: getAAM().prepareRequestSubjectData(),
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
             *
             * @param {*} cb
             */
            function FetchTaxonomyList(cb) {
                // Fetching the list of all registered post types.
                if (cache.taxonomies === undefined) {
                    $.ajax(`${getLocal().rest_base}aam/v2/service/content/taxonomies`, {
                        type: 'GET',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        data: getAAM().prepareRequestSubjectData(),
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
             *
             * @param {*} filters
             * @param {*} cb
             */
            function FetchPostList(filters, cb) {
                // Fetching the list of posts
                $.ajax(`${getLocal().rest_base}aam/v2/service/content/posts`, {
                    type: 'GET',
                    headers: {
                        'X-WP-Nonce': getLocal().rest_nonce
                    },
                    data: getAAM().prepareRequestSubjectData({
                        type: CurrentLevel().level_id,
                        offset: filters.start,
                        per_page: filters.length,
                        search: filters.search.value
                    }),
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

                if (CurrentLevel().scope) {
                    payload.scope     = CurrentLevel().scope;
                    payload.post_type = CurrentLevel().scope_id;
                }

                // Fetching the list of terms
                $.ajax(`${getLocal().rest_base}aam/v2/service/content/terms`, {
                    type: 'GET',
                    headers: {
                        'X-WP-Nonce': getLocal().rest_nonce
                    },
                    data: getAAM().prepareRequestSubjectData(payload),
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
             *
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
                    PrepareTermListTable(CurrentLevel(), reload);
                } else if (current.level_type === 'taxonomy_terms') {
                    PrepareTermListTable(null, reload)
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
                        const value                       = $(this).val();
                        const [level_type, post_type, id] = $(this).val().split(':');

                        AddToBreadcrumb({
                            level_type,
                            level_id: id,
                            label: $(`.aam-post-taxonomy-filter option:selected`).text(),
                            scope: 'post',
                            scope_id: post_type
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
                RenderAccessForm(data.level_type, data.level_id, data.btn, () => {
                    // Update the breadcrumb
                    AddToBreadcrumb({
                        level_type: data.level_type,
                        level_id: data.level_id,
                        label: data.label,
                        scope: data.scope,
                        scope_id: data.scope_id,
                        is_access_form: true
                    });
                });
            }

            /**
             *
             */
            function PrepareTypeListTable() {
                $('#post-content .dataTables_wrapper').addClass('hidden');
                $('#post-content .table').addClass('hidden');

                if (!$('#type-list').hasClass('dataTable')) {
                    $('#type-list').DataTable({
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
                            $('#type-list_length').append(RenderTypeTaxonomySwitch());
                        },
                        rowCallback: function(row, data) {
                            let overwritten = '';

                            if (data[4].is_inherited === false) {
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
                                                level_type: 'type',
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
                    $('#type-list').DataTable().ajax.reload(null, false);
                }

                $('#type-list_wrapper .table').removeClass('hidden');
                $('#type-list_wrapper').removeClass('hidden');
            }

            /**
             *
             */
            function PrepareTaxonomyListTable() {
                $('#post-content .dataTables_wrapper').addClass('hidden');
                $('#post-content .table').addClass('hidden');

                if (!$('#taxonomy-list').hasClass('dataTable')) {
                    $('#taxonomy-list').DataTable({
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
                            $('#taxonomy-list_length').append(RenderTypeTaxonomySwitch());
                        },
                        rowCallback: function(row, data) {
                            let overwritten = '';

                            if (data[4].is_inherited === false) {
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
                    $('#taxonomy-list').DataTable().ajax.reload(null, false);
                }

                $('#taxonomy-list_wrapper .table').removeClass('hidden');
                $('#taxonomy-list_wrapper').removeClass('hidden');
            }

            /**
             *
             */
            function PreparePostListTable(reload = false) {
                $('#post-content .dataTables_wrapper').addClass('hidden');
                $('#post-content .table').addClass('hidden');

                if (!$('#post-list').hasClass('dataTable')) {
                    $('#post-list').DataTable({
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
                            RenderPostTaxonomySwitch('#post-list_length');
                        },
                        rowCallback: function(row, data) {
                            let overwritten = '';

                            if (!data[4].is_inherited) {
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
                    $('#post-list').DataTable().ajax.reload(null, reload);
                    // Reload the list of taxonomies
                    RenderPostTaxonomySwitch('#post-list_length');
                }

                $('#post-list_wrapper .table').removeClass('hidden');
                $('#post-list_wrapper').removeClass('hidden');
            }

            /**
             *
             * @param {*} scope
             */
            function PrepareTermListTable(scope = null, reload = false) {
                $('#post-content .dataTables_wrapper').addClass('hidden');
                $('#post-content .table').addClass('hidden');

                if (scope) {
                    $('#term-list').attr('data-scope', scope.scope);
                    $('#term-list').attr('data-scope-id', scope.scope_id);
                } else {
                    $('#term-list').removeAttr('data-scope');
                    $('#term-list').removeAttr('data-scope-id');
                }

                if (!$('#term-list').hasClass('dataTable')) {
                    $('#term-list').DataTable({
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

                            if (!data[4].is_inherited) {
                                overwritten = ' aam-access-overwritten';
                            }

                            $('td:eq(0)', row).html(
                                `<div class="dashicons-before ${data[1]}${overwritten}"></div>`
                            );

                            // Decorating the term title & make it actionable
                                $('td:eq(1)', row).html($('<a/>', {
                                href: '#'
                            }).bind('click', function () {
                                const scope  = $('#term-list').attr('data-scope');
                                let scope_id = $('#term-list').attr('data-scope-id');

                                // Preparing internal AAM term's id
                                let id = `${data[0]}|${data[4].taxonomy}`;

                                // If scope is post, then we are withing certain
                                // post type
                                if (scope === 'post') {
                                    id += `|${scope_id}`;
                                }

                                NavigateToAccessForm({
                                    level_type: 'term',
                                    level_id: id,
                                    label: data[2],
                                    btn:  $('.icon-cog', row),
                                    scope,
                                    scope_id
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
                                            const scope = $('#term-list').attr('data-scope');
                                            let scope_id = $('#term-list').attr('data-scope-id');

                                            // Preparing internal AAM term's id
                                            let id = `${data[0]}|${data[4].taxonomy}`;

                                            // If scope is post, then we are withing certain
                                            // post type
                                            if (scope === 'post') {
                                                id += `|${scope_id}`;
                                            }

                                            NavigateToAccessForm({
                                                level_type: 'term',
                                                level_id: id,
                                                label: data[2],
                                                btn: $(this),
                                                scope,
                                                scope_id
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
                    $('#term-list').DataTable().ajax.reload(null, reload);
                }

                $('#term-list_wrapper .table').removeClass('hidden');
                $('#term-list_wrapper').removeClass('hidden');
            }

            /**
             *
             */
            function initialize() {
                if ($('#post-content').length) {
                    RenderBreadcrumb();

                    // Go back button
                    $('.aam-slide-form').delegate('.post-back', 'click', function () {
                        $('.aam-slide-form').removeClass('active');
                        NavigateBack();
                    });

                    // Adjust current list when switching between subjects or pages
                    AdjustList();
                }

                const current_level = CurrentLevel();

                if (current_level && current_level.is_access_form) {
                    RenderAccessForm(
                        current_level.level_type,
                        current_level.level_id
                    );
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
             * @param {*} payload
             * @param {*} successCallback
             */
            function save(payload, successCallback) {
                getAAM().queueRequest(function () {
                    $.ajax(`${getLocal().rest_base}aam/v2/service/redirect/access-denied`, {
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
                            getAAM().notification('danger', null, {
                                request: 'aam/v2/service/redirect/access-denied',
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
                                save(getAAM().prepareRequestSubjectData({ area, type, http_status_code }), () => {
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
                            save(getAAM().prepareRequestSubjectData(payload), () => {
                                $('#aam-redirect-overwrite').show();
                            });
                        });
                    });

                    $('#redirect-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(`${getLocal().rest_base}aam/v2/service/redirect/access-denied`, {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            data: getAAM().prepareRequestSubjectData(),
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
                                getAAM().notification('danger', null, {
                                    request: 'aam/v2/service/redirect/access-denied',
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
                    $.ajax(`${getLocal().rest_base}aam/v2/service/redirect/login`, {
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
                            getAAM().notification('danger', null, {
                                request: 'aam/v2/service/redirect/login',
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
                                save(getAAM().prepareRequestSubjectData({ type }), () => {
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

                            //save redirect type
                            save(getAAM().prepareRequestSubjectData(payload), () => {
                                $('#aam-login-redirect-overwrite').show();
                            });
                        });
                    });

                    $('#login-redirect-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(`${getLocal().rest_base}aam/v2/service/redirect/login`, {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            data: getAAM().prepareRequestSubjectData(),
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
                                getAAM().notification('danger', null, {
                                    request: 'aam/v2/service/redirect/login',
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
                    $.ajax(`${getLocal().rest_base}aam/v2/service/redirect/logout`, {
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
                            getAAM().notification('danger', null, {
                                request: 'aam/v2/service/redirect/logout',
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
                                save(getAAM().prepareRequestSubjectData({ type }), () => {
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

                            //save redirect type
                            save(getAAM().prepareRequestSubjectData(payload), () => {
                                $('#aam-logout-redirect-overwrite').show();
                            });
                        });
                    });

                    $('#logout-redirect-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(`${getLocal().rest_base}aam/v2/service/redirect/logout`, {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            data: getAAM().prepareRequestSubjectData(),
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
                                getAAM().notification('danger', null, {
                                    request: 'aam/v2/service/redirect/logout',
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
                    $.ajax(`${getLocal().rest_base}aam/v2/service/redirect/not-found`, {
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
                            getAAM().notification('danger', null, {
                                request: 'aam/v2/service/redirect/not-found',
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
                                save(getAAM().prepareRequestSubjectData({ type }), () => {
                                    $('#aam-404redirect-overwrite').show();
                                });
                            }
                        });
                    });

                    $('input[type="text"],select', container).each(function () {
                        $(this).bind('change', function () {
                            const value = $.trim($(this).val());
                            const type  = $('input[name="404.redirect.type"]:checked').val();

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

                            //save redirect type
                            save(getAAM().prepareRequestSubjectData(payload), () => {
                                $('#aam-404redirect-overwrite').show();
                            });
                        });
                    });

                    $('#404redirect-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(`${getLocal().rest_base}aam/v2/service/redirect/not-found`, {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            data: getAAM().prepareRequestSubjectData(),
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
                                getAAM().notification('danger', null, {
                                    request: 'aam/v2/service/redirect/not-found',
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
                var value = $(btn).hasClass('icon-check-empty');

                getAAM().queueRequest(function () {
                    // Show indicator
                    $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');

                    const payload = getAAM().prepareRequestSubjectData({
                        is_restricted: value
                    });

                    $.ajax(`${getLocal().rest_base}aam/v2/service/api-route/${id}`, {
                        type: 'POST',
                        dataType: 'json',
                        data: payload,
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce,
                        },
                        success: function () {
                            $('#aam-route-overwrite').removeClass('hidden');
                            updateBtn(btn, value);
                        },
                        error: function (response) {
                            updateBtn(btn, value ? 0 : 1);

                            getAAM().notification('danger', null, {
                                request: `aam/v2/service/api-route/${id}`,
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
                            url: `${getLocal().rest_base}aam/v2/service/api-routes`,
                            type: 'GET',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            data: getAAM().prepareRequestSubjectData(),
                            dataType: 'json',
                            dataSrc: function (routes) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(routes, (_, route) => {
                                    data.push([
                                        route.id,
                                        route.method,
                                        escapeHtml(route.route),
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

                    //reset button
                    $('#route-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(`${getLocal().rest_base}aam/v2/service/api-routes`, {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            data: getAAM().prepareRequestSubjectData(),
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
                                getAAM().notification('danger', null, {
                                    request: 'aam/v2/service/api-routes',
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
             */
            function initialize() {
                const container = '#uri-content';

                // Currently editing rule
                let editingRule;

                if ($(container).length) {
                    $('input[type="radio"]', container).each(function () {
                        $(this).bind('click', function () {
                            var action = $(this).data('action');

                            $('.aam-uri-access-action').hide();

                            if (action) {
                                $(action).show();
                            }

                            if (['page_redirect', 'url_redirect'].includes($(this).val())) {
                                $('#uri-access-deny-redirect-code').show();
                            }
                        });
                    });

                    //reset button
                    $('#uri-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(`${getLocal().rest_base}aam/v2/service/urls`, {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            data: getAAM().prepareRequestSubjectData(),
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
                                getAAM().notification('danger', null, {
                                    request: 'aam/v2/service/urls',
                                    response
                                });
                            },
                            complete: function () {
                                _btn.text(_btn.attr('data-original-label'));
                            }
                        });
                    });

                    $('#uri-save-btn').bind('click', function (event) {
                        event.preventDefault();

                        const uri  = $('#uri-rule').val();
                        const type = $('input[name="uri.access.type"]:checked').val();
                        const code = $('#uri-access-deny-redirect-code-value').val();
                        const add  = $('#url_metadata_properties').find('select, textarea, input').serializeArray();

                        if (uri && type) {
                            const metadata = {};

                            for(let i of add) {
                                metadata[i.name] = i.value
                            }

                            // Preparing the payload
                            const payload = {
                                url: uri,
                                type: type,
                                metadata
                            }

                            if (type === 'custom_message') {
                                payload.message = $.trim(
                                    $('#uri-access-custom_message-value').val()
                                );
                            } else if (type === 'page_redirect') {
                                payload.redirect_page_id = parseInt(
                                    $('#uri-access-page_redirect-value').val(), 10
                                );
                            } else if (type === 'url_redirect') {
                                payload.redirect_url = $.trim(
                                    $('#uri-access-url_redirect-value').val()
                                );
                            } else if (type === 'trigger_callback') {
                                payload.callback = $.trim(
                                    $('#uri-access-trigger_callback-value').val()
                                );
                            }

                            if (code
                                && ['page_redirect', 'url_redirect'].includes(type)
                            ) {
                                payload.http_status_code = parseInt(code, 10);
                            }

                            let endpoint = `${getLocal().rest_base}aam/v2/service/url`;

                            if (editingRule !== null) {
                                endpoint += '/' + editingRule[0];
                            } else {
                                endpoint += 's'
                            }

                            $.ajax(endpoint, {
                                type: 'POST',
                                contentType: 'application/json',
                                dataType: 'json',
                                data: JSON.stringify(
                                    getAAM().prepareRequestSubjectData(payload)
                                ),
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce
                                },
                                beforeSend: function () {
                                    $('#uri-save-btn').text(
                                        getAAM().__('Saving...')
                                    ).attr('disabled', true);
                                },
                                success: function () {
                                    $('#uri-list').DataTable().ajax.reload();
                                    $('#aam-uri-overwrite').show();
                                },
                                error: function (response) {
                                    getAAM().notification('danger', null, {
                                        request: endpoint,
                                        payload,
                                        response
                                    });
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

                        const id = $('#uri-delete-btn').attr('data-id');

                        $.ajax(`${getLocal().rest_base}aam/v2/service/url/${id}`, {
                            type: 'POST',
                            dataType: 'json',
                            data: getAAM().prepareRequestSubjectData(),
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
                                getAAM().notification('danger', null, {
                                    request: `aam/v2/service/url/${id}`,
                                    response
                                });
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
                            url: `${getLocal().rest_base}aam/v2/service/urls`,
                            type: 'GET',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            data: getAAM().prepareRequestSubjectData(),
                            dataType: 'json',
                            dataSrc: function (json) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(json, (_, rule) => {
                                    const actions = ['edit'];

                                    if (rule.is_inherited) {
                                        actions.push('no-delete');
                                    } else {
                                        actions.push('delete');
                                    }

                                    let action = null;

                                    if (rule.type === 'custom_message') {
                                        action = rule.message;
                                    } else if (rule.type === 'trigger_callback') {
                                        action = rule.callback;
                                    } else if (rule.type === 'url_redirect') {
                                        action = rule.redirect_url;
                                    } else if (rule.type === 'page_redirect') {
                                        action = rule.redirect_page_id;
                                    }

                                    data.push([
                                        rule.id,
                                        rule.url,
                                        rule.type,
                                        action,
                                        rule.http_status_code || null,
                                        actions.join(','),
                                        rule.metadata || null
                                    ]);
                                });

                                return data;
                            },
                        },
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ URI(s)'),
                            infoFiltered: ''
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 3, 4, 6] }
                        ],
                        initComplete: function () {
                            var create = $('<a/>', {
                                'href': '#',
                                'class': 'btn btn-primary'
                            }).html('<i class="icon-plus"></i> ' + getAAM().__('Create'))
                                .bind('click', function () {
                                    editingRule = null;

                                    $('.form-clearable', '#uri-model').val('');
                                    $('.aam-uri-access-action').hide();
                                    $('#uri-save-btn').removeAttr('data-original-uri');
                                    $('input[type="radio"]', '#uri-model').prop('checked', false);
                                    $('#uri-model').modal('show');
                                });

                            $('.dataTables_filter', '#uri-list_wrapper').append(create);
                        },
                        createdRow: function (row, data) {
                            var actions = data[5].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-warning'
                                        }).bind('click', function () {
                                            editingRule = data;

                                            $('.form-clearable', '#uri-model').val('');
                                            $('.aam-uri-access-action').hide();
                                            $('#uri-rule').val(data[1]);
                                            $('input[value="' + data[2] + '"]', '#uri-model').prop('checked', true).trigger('click');
                                            $('#uri-access-' + data[2] + '-value').val(data[3]);
                                            $('#uri-access-deny-redirect-code-value').val(data[4]);
                                            $('#uri-model').modal('show');

                                            // If there are any additional metadata properties, load them
                                            if (data[6]) {
                                                for(let i in data[6]) {
                                                    $(`#${i}`).val(data[6][i]);
                                                }
                                            }
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
                                            $('#uri-delete-btn').attr('data-id', data[0]);
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

                            switch(data[2]) {
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
                                    type.html(getAAM().__('Allowed'));
                                    type.attr('class', 'badge success');
                                    break;
                            }

                            $('td:eq(2)', row).html(container);

                            $('td:eq(1)', row).html(type);
                        }
                    });

                    getAAM().triggerHook('init-uri-edit-form');
                }
            }

            getAAM().addHook('init', initialize);
        })(jQuery);

        /**
         * User Manager Interface
         *
         * @param {jQuery} $
         *
         * @returns {void}
         */
         (function ($) {

            /**
             *
             */
            function initialize() {
                const container = '#user-governance-content';

                if ($(container).length) {
                    GetRoles(function(roles) {
                        $('#user-governance-role-targets').autocomplete({
                            minLength: 3,
                            source: (request, cb) => {
                                const term = request.term
                                    .split(',')
                                    .map(v => $.trim(v))
                                    .filter(v => v != '')
                                    .pop();

                                cb(roles.map(r => ({
                                    label: r.name,
                                    value: r.slug
                                })).filter(r => (new RegExp('^' + term, 'i')).test(r.label)));
                            },
                            select: function(event, ui ) {
                                event.preventDefault();
                                const current = $('#user-governance-role-targets').val();
                                const values  = current.split(',').map(v => $.trim(v));

                                // Remove last value as it is something that was typed
                                values.pop();

                                values.push(ui.item.value);

                                $('#user-governance-role-targets').val(values.join(', ') + ', ');
                            }
                        });
                    });

                    let resolved_users = [];
                    let editing_rule   = null;

                    $('#user-governance-user-targets').autocomplete({
                        minLength: 3,
                        source: (request, cb) => {
                            const term = request.term
                                .split(',')
                                .map(v => $.trim(v))
                                .filter(v => v != '')
                                .pop();

                            if (!resolved_users.includes(term)){
                                $.ajax(`${getLocal().rest_base}wp/v2/users?context=edit&search=${term}`, {
                                    type: 'GET',
                                    contentType: 'application/json',
                                    dataType: 'json',
                                    headers: {
                                        'X-WP-Nonce': getLocal().rest_nonce
                                    },
                                    success: function (response) {
                                        cb(response.map(u => ({
                                            label: u.name,
                                            value: u.username
                                        })));
                                    },
                                    error: function (response) {
                                        getAAM().notification('danger', null, {
                                            request: `wp/v2/users?context=edit&search=${term}`,
                                            response
                                        });
                                    },
                                    complete: function () {
                                    }
                                });
                            }
                        },
                        select: function(event, ui ) {
                            event.preventDefault();
                            const current = $('#user-governance-user-targets').val();
                            const values  = current.split(',').map(v => $.trim(v));

                            // Remove last value as it is something that was typed
                            values.pop();

                            values.push(ui.item.value);

                            resolved_users = values;

                            $('#user-governance-user-targets').val(values.join(', ') + ', ');
                        }
                    });

                    $('#user-governance-rule-type').bind('change', function() {
                        const type = $(this).val();

                        // Hiding all the input values first
                        $('.user-governance-targets').addClass('hidden');

                        // Now, hiding all the controls
                        $('.user-governance-control').addClass('hidden');

                        // Show proper input field based on rule type
                        $('.user-governance-targets').each(function() {
                            if ($(this).data('rule-types').split(',').includes(type)) {
                                $(this).removeClass('hidden');
                            }
                        });

                        // Show proper controls based on rule type
                        $('.user-governance-control').each(function() {
                            if ($(this).data('rule-types').split(',').includes(type)) {
                                $(this).removeClass('hidden');
                            }
                        });
                    });

                    $('[data-toggle="toggle"]', container).bootstrapToggle();

                    //reset button
                    $('#user-governance-reset').bind('click', function () {
                        const _btn = $(this);

                        $.ajax(`${getLocal().rest_base}aam/v2/service/identity-governance`, {
                            type: 'POST',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            data: getAAM().prepareRequestSubjectData(),
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
                                getAAM().notification('danger', null, {
                                    request: 'aam/v2/service/identity-governance',
                                    response
                                });
                            },
                            complete: function () {
                                _btn.text(_btn.attr('data-original-label'));
                            }
                        });
                    });

                    $('#user-governance-save-btn').bind('click', function (event) {
                        event.preventDefault();

                        // Collecting all the necessary information
                        const data = {
                            rule_type: $('#user-governance-rule-type').val(),
                            permissions: []
                        }

                        let valid = data.rule_type ? true : false;

                        $('.user-governance-control', '#user-governance-model').each(function() {
                            if ($(this).data('rule-types').split(',').includes(data.rule_type)) {
                                data.permissions.push({
                                    permission: $('input[data-toggle="toggle"]', this).attr('name'),
                                    effect: $('input[data-toggle="toggle"]', this).prop('checked') ? 'deny' : 'allow'
                                });
                            }
                        });

                        $('.user-governance-targets').each(function() {
                            if ($(this).data('rule-types').split(',').includes(data.rule_type)) {
                                const name = $('.form-control', this).attr('name');
                                data[name] = $('.form-control', this).val().split(',')
                                    .map(u => $.trim(u))
                                    .filter(u => u);

                                if (data[name].length === 0) {
                                    valid = false;
                                }
                            }
                        });

                        if (valid) {
                            $.ajax(`${getLocal().rest_base}aam/v2/service/identity-governance`, {
                                type: 'POST',
                                contentType: 'application/json',
                                dataType: 'json',
                                data: JSON.stringify(
                                    getAAM().prepareRequestSubjectData(data)
                                ),
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce
                                },
                                beforeSend: function () {
                                    $('#user-governance-save-btn').text(
                                        getAAM().__('Saving...')
                                    ).attr('disabled', true);
                                },
                                success: function () {
                                    $('#user-governance-list').DataTable().ajax.reload();
                                    $('#aam-user-governance-overwrite').show();
                                },
                                error: function (response) {
                                    getAAM().notification('danger', null, {
                                        request: 'aam/v2/service/identity-governance',
                                        payload: data,
                                        response
                                    });
                                },
                                complete: function () {
                                    $('#user-governance-model').modal('hide');
                                    $('#user-governance-save-btn').text(getAAM().__('Save')).attr('disabled', false);
                                }
                            });
                        }
                    });

                    $('#user-governance-update-btn').bind('click', function (event) {
                        event.preventDefault();

                        // Collecting all the necessary information
                        const data = editing_rule;

                        // Reset permissions
                        data.permissions = [];

                        $('.user-governance-control').each(function() {
                            if ($(this).data('rule-types').split(',').includes(data.rule_type)) {
                                data.permissions.push({
                                    permission: $('input[data-toggle="toggle"]', this).attr('name'),
                                    effect: $('input[data-toggle="toggle"]', this).prop('checked') ? 'deny' : 'allow'
                                });
                            }
                        });


                        $.ajax(`${getLocal().rest_base}aam/v2/service/identity-governance/${editing_rule.id}`, {
                            type: 'POST',
                            contentType: 'application/json',
                            dataType: 'json',
                            data: JSON.stringify(
                                getAAM().prepareRequestSubjectData(data)
                            ),
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            beforeSend: function () {
                                $('#user-governance-update-btn').text(
                                    getAAM().__('Updating...')
                                ).attr('disabled', true);
                            },
                            success: function () {
                                $('#user-governance-list').DataTable().ajax.reload();
                                $('#aam-user-governance-overwrite').show();
                            },
                            error: function (response) {
                                getAAM().notification('danger', null, {
                                    request: `aam/v2/service/identity-governance/${editing_rule.id}`,
                                    payload: data,
                                    response
                                });
                            },
                            complete: function () {
                                $('#user-governance-edit-model').modal('hide');
                                $('#user-governance-update-btn')
                                    .text(getAAM().__('Update'))
                                    .attr('disabled', false);
                            }
                        });
                    });

                    $('#user-governance-delete-btn').bind('click', function (event) {
                        event.preventDefault();

                        const id = $('#user-governance-delete-btn').attr('data-id');

                        $.ajax(`${getLocal().rest_base}aam/v2/service/identity-governance/${id}`, {
                            type: 'POST',
                            dataType: 'json',
                            data: getAAM().prepareRequestSubjectData(),
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            beforeSend: function () {
                                $('#user-governance-delete-btn').text(
                                    getAAM().__('Deleting...')
                                ).attr('disabled', true);
                            },
                            success: function () {
                                $('#user-governance-list').DataTable().ajax.reload();
                            },
                            error: function (response) {
                                getAAM().notification('danger', null, {
                                    request: `aam/v2/service/identity-governance/${id}`,
                                    response
                                });
                            },
                            complete: function () {
                                $('#user-governance-delete-model').modal('hide');
                                $('#user-governance-delete-btn').text(getAAM().__('Delete')).attr('disabled', false);
                            }
                        });
                    });

                    $('#user-governance-list').DataTable({
                        autoWidth: false,
                        ordering: false,
                        dom: 'ftrip',
                        pagingType: 'simple',
                        processing: true,
                        stateSave: true,
                        serverSide: false,
                        ajax: {
                            url: `${getLocal().rest_base}aam/v2/service/identity-governance`,
                            type: 'GET',
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce
                            },
                            data: getAAM().prepareRequestSubjectData(),
                            dataType: 'json',
                            dataSrc: function (json) {
                                // Transform the received data into DT format
                                const data = [];

                                $.each(json, (_, rule) => {
                                    const actions = ['edit'];

                                    if (rule.is_inherited) {
                                        actions.push('no-delete');
                                    } else {
                                        actions.push('delete');
                                    }

                                    data.push([
                                        rule.id,
                                        rule.display_name,
                                        rule,
                                        actions.join(',')
                                    ]);
                                });

                                return data;
                            },
                        },
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: getAAM().__('Search'),
                            info: getAAM().__('_TOTAL_ URI(s)'),
                            infoFiltered: ''
                        },
                        columnDefs: [
                            { visible: false, targets: [0, 2] }
                        ],
                        initComplete: function () {
                            var create = $('<a/>', {
                                'href': '#',
                                'class': 'btn btn-primary'
                            }).html('<i class="icon-plus"></i> ' + getAAM().__('Create'))
                                .bind('click', function () {
                                    $('.form-clearable', '#user-governance-model').val('');
                                    $('#user-governance-model').modal('show');

                                    $('input[data-toggle="toggle"', '#user-governance-model').bootstrapToggle(
                                        'off'
                                    );

                                    $('.user-governance-targets').addClass('hidden');
                                    $('.user-governance-control').addClass('hidden');

                                    editing_rule = null;
                                });

                            $('.dataTables_filter', '#user-governance-list_wrapper').append(create);
                        },
                        createdRow: function (row, data) {
                            var actions = data[3].split(',');

                            var container = $('<div/>', { 'class': 'aam-row-actions' });
                            $.each(actions, function (i, action) {
                                switch (action) {
                                    case 'edit':
                                        $(container).append($('<i/>', {
                                            'class': 'aam-row-action icon-pencil text-warning'
                                        }).bind('click', function () {
                                            // Now, hiding all the controls
                                            $('#user-governance-edit-model .user-governance-control').addClass('hidden');

                                            // Show proper controls based on rule type
                                            $('#user-governance-edit-model .user-governance-control').each(function() {
                                                if ($(this).data('rule-types').split(',').includes(data[2].rule_type)) {
                                                    $(this).removeClass('hidden');
                                                }
                                            });

                                            // Show only controls that are applicable to the rule type
                                            $.each(data[2].permissions, function(_, p) {
                                                $('input[name="' + p.permission + '"]', '#user-governance-edit-model').bootstrapToggle(
                                                    p.effect === 'deny' ? 'on': 'off'
                                                );
                                            });

                                            editing_rule = data[2];

                                            $('#user-governance-update-btn').attr('data-id', data[0]);
                                            $('#user-governance-edit-model').modal('show');
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
                                            $('#user-governance-delete-btn').attr('data-id', data[0]);
                                            $('#user-governance-delete-model').modal('show');
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

                            $('td:eq(1)', row).html(container);

                            // Decorate the display row
                            $('td:eq(0)', row).html(
                                data[1] + '<sup>' + data[2].rule_type + '</sup>'
                            )
                        }
                    });

                    getAAM().triggerHook('init-user-governance-edit-form');
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

                    $('#create-jwt-modal').on('show.bs.modal', function () {
                        try {
                            var tomorrow = new Date();
                            tomorrow.setDate(tomorrow.getDate() + 1);
                            $('#jwt-expiration-datapicker').data('DateTimePicker').defaultDate(
                                tomorrow
                            );
                            $('#jwt-expires').val('');

                            $('#aam-jwt-claims-editor').val('{\n  \n}')

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
                    let url  = `${getLocal().rest_base}aam/v2/service/jwts`;
                        url += `?user_id=${getAAM().getSubject().id}&fields=claims,token,id,signed_url,is_valid`;

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
                                        details = 'Token is no longer valid';
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
                                payload.additional_claims = claims;
                            }
                        } catch (e) {
                            console.log(e);
                        }

                        $.ajax(`${getLocal().rest_base}aam/v2/service/jwts?fields=token,signed_url`, {
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

                                $('#view-jwt-token').val(response.token);
                                $('#view-jwt-url').val(response.signed_url);
                                $('#view-jwt-modal').modal('show');
                            },
                            error: function (response) {
                                getAAM().notification('danger', null, {
                                    request: `aam/v2/service/jwts?fields=token,signed_url`,
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
                        const payload = {
                            user_id: getAAM().getSubject().id
                        };

                        $.ajax(`${getLocal().rest_base}aam/v2/service/jwt/${$('#jwt-delete-btn').attr('data-id')}`, {
                            type: 'POST',
                            dataType: 'json',
                            data: payload,
                            headers: {
                                'X-WP-Nonce': getLocal().rest_nonce,
                                'X-HTTP-Method-Override': 'DELETE'
                            },
                            beforeSend: function () {
                                $('#jwt-delete-btn').html(getAAM().__('Deleting...'));
                            },
                            success: function () {
                                $('#delete-jwt-modal').modal('hide');
                                $('#jwt-list').DataTable().ajax.reload();
                            },
                            error: function (response) {
                                getAAM().notification('danger', null, {
                                    request: `aam/v2/service/jwt/${$('#jwt-delete-btn').attr('data-id')}`,
                                    payload,
                                    response
                                });
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
             * @returns {undefined}
             */
            function initialize() {
                if ($('#extension-content').length) {
                    $('[data-toggle="toggle"]', '.extensions-metabox').bootstrapToggle();

                    //init refresh list button
                    $('#download-extension').bind('click', function () {
                        const license = $.trim($('#extension-key').val());

                        if (license) {
                            window.open(
                                `${getLocal().system.apiEndpoint}/download/${license}`,
                                '_blank'
                            );
                        }
                    });
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

                    $.ajax(`${getLocal().rest_base}aam/v2/service/audit`, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        dataType: 'json',
                        data: payload,
                        success: function (response) {
                            // Append the list of identified issues to the list
                            if (Array.isArray(response.issues)) {
                                $.each(response.issues, (_, issue) => {
                                    $(`#issue_list_${current_step} tbody`).append(
                                        '<tr><td><strong>' + issue.type.toUpperCase() + ':</strong> ' + issue.reason + '</td></tr>'
                                    );

                                    // Also increment the issue index
                                    if (issues_index[current_step][issue.type] === undefined) {
                                        issues_index[current_step][issue.type] = 0;
                                    }

                                    issues_index[current_step][issue.type]++;
                                });

                                $(`#issue_list_${current_step}`).removeClass('hidden');
                            }

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
                                    url.searchParams.set('aam_page', 'audit');
                                    window.location.href = url.toString();
                                }
                            } else {
                                $(`#check_${current_step}_status`).text(
                                    step_title + ' - ' + (response.progress * 100).toFixed(2) + '%'
                                );

                                TriggerAudit();
                            }
                        },
                        error: function (response) {
                            getAAM().notification('danger', null, {
                                request: `aam/v2/service/audit`,
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


                    $.ajax(`${getLocal().rest_base}aam/v2/service/audit/report`, {
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
                            getAAM().notification('danger', null, {
                                request: `aam/v2/service/audit/report`,
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
             * @param {*} btn
             */
            function ShareReport(btn) {
                getAAM().queueRequest(function () {
                    btn.text(getAAM().__('Sharing Report...')).prop('disabled', true);

                    $.ajax(`${getLocal().rest_base}aam/v2/service/audit/share`, {
                        type: 'POST',
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        dataType: 'json',
                        data: {
                            email: $('#audit_report_email').val()
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                getAAM().notification(
                                    'success',
                                    'Report Shared Successfully. We will come back to you with next steps asap.'
                                );
                            }

                            $('#share_audit_confirmation_modal').modal('hide');
                        },
                        error: function (response) {
                            getAAM().notification('danger', response, {
                                request: `aam/v2/service/audit/share`,
                                response
                            });
                        },
                        complete: function() {
                            btn
                                .text(getAAM().__('Share'))
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
                    $('#execute_security_audit').bind('click', function () {
                        $(this)
                            .text(getAAM().__('Running Audit. Do Not Refresh The Page'))
                            .attr('disabled', true);

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
                    });

                    $('.download-latest-report').bind('click', function() {
                        DownloadReport($(this));
                    });

                    $('#share_audit_report').bind('click', function() {
                        ShareReport($(this));
                    });

                    $('#audit_report_email').bind('change', function() {
                        const email = $.trim($(this).val());

                        if (email) {
                            $('#share_audit_report').prop('disabled', false);
                        } else {
                            $('#share_audit_report').prop('disabled', true);
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
            function save(param, value) {
                getAAM().queueRequest(function () {
                    const payload = {
                        key: param,
                        value
                    };

                    $.ajax(`${getLocal().rest_base}aam/v2/service/configs`, {
                        type: 'POST',
                        dataType: 'json',
                        data: payload,
                        headers: {
                            'X-WP-Nonce': getLocal().rest_nonce
                        },
                        error: function (response) {
                            getAAM().notification('danger', null, {
                                request: 'aam/v2/service/configs',
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
                                save(
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

                        save($(this).attr('name'), value);
                    });

                    $('#clear-settings').bind('click', function () {
                        $('#clear-settings').prop('disabled', true);
                        $('#clear-settings').text(getAAM().__('Processing...'));

                        getAAM().queueRequest(function () {
                            $.ajax(`${getLocal().rest_base}aam/v2/service/configs`, {
                                type: 'POST',
                                dataType: 'json',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                error: function (response) {
                                    getAAM().notification('danger', null, {
                                        request: 'aam/v2/service/configs',
                                        response
                                    });
                                },
                                complete: function () {
                                    $('#clear-settings').prop('disabled', false);
                                    $('#clear-settings').text(getAAM().__('Clear'));
                                    $('#clear-settings-modal').modal('hide');
                                }
                            });
                        });

                        getAAM().queueRequest(function () {
                            $.ajax(`${getLocal().rest_base}aam/v2/service/settings`, {
                                type: 'POST',
                                dataType: 'json',
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce,
                                    'X-HTTP-Method-Override': 'DELETE'
                                },
                                success: () => {
                                    location.reload();
                                },
                                error: function (response) {
                                    getAAM().notification('danger', null, {
                                        request: 'aam/v2/service/settings',
                                        response
                                    });
                                },
                                complete: function () {
                                    $('#clear-settings').prop('disabled', false);
                                    $('#clear-settings').text(getAAM().__('Clear'));
                                    $('#clear-settings-modal').modal('hide');
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

                            $.ajax(`${getLocal().rest_base}aam/v2/service/configpress`, {
                                type: 'POST',
                                dataType: 'json',
                                data: payload,
                                headers: {
                                    'X-WP-Nonce': getLocal().rest_nonce
                                },
                                error: function (response) {
                                    getAAM().notification('danger', null, {
                                        request: 'aam/v2/service/configpress',
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

                getAAM().queueRequest(function () {
                    $.ajax(`${getLocal().rest_base}aam/v2/service/settings`, {
                        type: 'POST',
                        dataType: 'json',
                        data: getAAM().prepareRequestSubjectData(),
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
                            getAAM().notification('danger', null, {
                                request: 'aam/v2/service/settings',
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
        } else if (getLocal().subject.type) {
            this.setSubject(
                getLocal().subject.type,
                getLocal().subject.id,
                getLocal().subject.name
            );
        } else {
            $('#aam-subject-banner').addClass('hidden');
        }

        // Load the UI javascript support
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

        // Making sure that when modal is presented, we scroll to the top
        $('body').delegate('.modal', 'show.bs.modal', function() {
            parent.window.scrollTo(0, 0);
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
            $('.aam-area[data-type="' + query.get('aam_page') + '"]').addClass('text-danger');
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
     * Show notification
     *
     * @param {String} status
     * @param {String} message
     * @param {Object} metadata
     *
     * @returns {Void}
     */
    AAM.prototype.notification = function (status, message, metadata = null) {
        let notification_header;
        let notification_message;

        switch (status) {
            case 'success':
                notification_header  = 'Success';
                notification_message = getAAM().__(
                    message || 'Operation completed successfully'
                );
                break;

            case 'danger':
                notification_header = 'Unexpected Issue';

                if (metadata
                    && metadata.response
                    && metadata.response.status !== 500
                    && metadata.response.responseJSON.errors
                ) {
                    const http_error = Object.keys(
                        metadata.response.responseJSON.errors
                    ).shift();

                    if (http_error === 'rest_invalid_argument') {
                        notification_header  = getAAM().__('Invalid Arguments');
                    }

                    notification_message = message || metadata.response.responseJSON.errors[http_error][0];
                } else {
                    if (metadata !== null) {
                        metadata.response = metadata.response.responseJSON;
                    }
                    notification_message = getAAM().__(
                        message || 'An unexpected application issue has arisen. Please feel free to report this issue to us, and we will promptly provide you with a solution.'
                    );
                }
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

        parent.window.scrollTo(0, 0);
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
     * @param {*} mergeWith
     * @returns
     */
    AAM.prototype.prepareRequestSubjectData = function(mergeWith = {}) {
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