/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

Espo.define('Views.Admin.EntityManager.Index', 'View', function (Dep) {

    return Dep.extend({

        template: 'admin.entity-manager.index',

        scopeDataList: null,

        scope: null,

        type: null,

        data: function () {
            return {
                scopeDataList: this.scopeDataList,
                scope: this.scope,
            };
        },

        events: {
            'click a[data-action="editEntity"]': function (e) {
                var scope = $(e.currentTarget).data('scope');
                this.editEntity(scope);
            },
            'click button[data-action="createEntity"]': function (e) {
                this.editEntity();
            },
            'click [data-action="removeEntity"]': function (e) {
                var scope = $(e.currentTarget).data('scope');
                if (confirm(this.translate('confirmation', 'messages'))) {
                    this.removeEntity(scope);
                }
            }
        },

        setup: function () {
            this.scopeDataList = [];

            var scopeList = Object.keys(this.getMetadata().get('scopes')).sort(function (v1, v2) {
                return this.translate(v1, 'scopeNames').localeCompare(this.translate(v2, 'scopeNames'));
            }.bind(this));

            scopeList.forEach(function (scope) {
                var d = this.getMetadata().get('scopes.' + scope);
                if (d.entity) {
                    this.scopeDataList.push({
                        name: scope,
                        isCustom: d.isCustom,
                        customizable: d.customizable,
                        type: d.type
                    });

                }
            }, this);

            this.scope = this.options.scope || null;

            this.on('after:render', function () {
                this.renderHeader();
                if (!this.scope) {
                    this.renderDefaultPage();
                } else {
                    if (!this.field) {
                        this.openScope(this.scope);
                    } else {
                        this.openField(this.scope, this.field);
                    }
                }
            });
        },

        createEntity: function () {
            this.createView('edit', 'Admin.EntityManager.Modals.EditEntity', {}, function (view) {
                view.render();
            });
        },

        editEntity: function (scope) {
            this.createView('edit', 'Admin.EntityManager.Modals.EditEntity', {
                scope: scope
            }, function (view) {
                view.render();
            });
        },

        removeEntity: function (scope) {
            $.ajax({
                url: 'EntityManager/action/removeEntity',
                type: 'POST',
                data: JSON.stringify({
                    name: scope
                })
            }).done(function () {
                this.$el.find('table tr[data-scope="'+scope+'"]').remove();
            }.bind(this));
        },

        renderDefaultPage: function () {
            $('#fields-header').html('').hide();
            $('#fields-content').html(this.translate('selectEntityType', 'messages', 'Admin'));
        },

        renderHeader: function () {
            if (!this.scope) {
                $('#scope-header').html('');
                return;
            }

            $('#scope-header').show().html(this.getLanguage().translate(this.scope, 'scopeNames'));
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Entity Manager', 'labels', 'Admin'));
        },
    });
});


