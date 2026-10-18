(function (blocks, blockEditor, components, element, i18n) {
    'use strict';

    var createElement = element.createElement;
    var __ = i18n.__;

    blocks.registerBlockType('aam/secure-login', {
        apiVersion: 2,
        title: __('AAM Secure Login', 'advanced-access-manager'),
        category: 'widgets',
        icon: 'lock',
        description: __('Display the AAM Secure Login form and signed-in account links.', 'advanced-access-manager'),
        attributes: {
            loginTitle: { type: 'string' },
            userTitle: { type: 'string' },
            redirect: { type: 'string', default: '' }
        },
        supports: { html: false },
        edit: function (props) {
            var attributes = props.attributes;
            var blockProps = blockEditor.useBlockProps();

            return createElement(
                element.Fragment,
                null,
                createElement(
                    blockEditor.InspectorControls,
                    null,
                    createElement(
                        components.PanelBody,
                        { title: __('Secure Login settings', 'advanced-access-manager') },
                        createElement(components.TextControl, {
                            label: __('Login Title', 'advanced-access-manager'),
                            value: attributes.loginTitle || __('Login', 'advanced-access-manager'),
                            onChange: function (value) {
                                props.setAttributes({ loginTitle: value });
                            }
                        }),
                        createElement(components.TextControl, {
                            label: __('Logged In Title', 'advanced-access-manager'),
                            help: __('Use %username% to display the user’s name.', 'advanced-access-manager'),
                            value: attributes.userTitle || __('Howdy, %username%', 'advanced-access-manager'),
                            onChange: function (value) {
                                props.setAttributes({ userTitle: value });
                            }
                        }),
                        createElement(components.TextControl, {
                            label: __('Redirect after login', 'advanced-access-manager'),
                            help: __('Leave empty to use redirect_to or the configured login redirect.', 'advanced-access-manager'),
                            value: attributes.redirect,
                            onChange: function (value) {
                                props.setAttributes({ redirect: value });
                            }
                        })
                    )
                ),
                createElement(
                    'div',
                    blockProps,
                    createElement('h3', null,
                        attributes.loginTitle || __('Login', 'advanced-access-manager')
                    ),
                    createElement('p', null, __('Username or Email Address', 'advanced-access-manager')),
                    createElement('input', {
                        type: 'text',
                        disabled: true,
                        'aria-label': __('Username or Email Address', 'advanced-access-manager')
                    }),
                    createElement('p', null, __('Password', 'advanced-access-manager')),
                    createElement('input', {
                        type: 'password',
                        disabled: true,
                        'aria-label': __('Password', 'advanced-access-manager')
                    }),
                    createElement('p', null,
                        createElement('button', { type: 'button', disabled: true },
                            __('Log In', 'advanced-access-manager')
                        )
                    )
                )
            );
        },
        save: function () {
            return null;
        }
    });
})(wp.blocks, wp.blockEditor, wp.components, wp.element, wp.i18n);
