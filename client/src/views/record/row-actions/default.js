/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('views/record/row-actions/default', ['view'], function (Dep) {

    /**
     * A detail-side record view.
     *
     * @class
     * @name Class
     * @extends module:view.Class
     * @memberOf views/record/row-actions/default
     */
    return Dep.extend(/** @lends views/record/row-actions/default.Class# */{

        template: 'record/row-actions/default',

        setup: function () {
            this.options.acl = this.options.acl || {};
        },

        afterRender: function () {
            let $dd = this.$el.find('button[data-toggle="dropdown"]').parent();

            let isChecked = false;

            $dd.on('show.bs.dropdown', () => {
                let $el = this.$el.closest('.list-row');

                isChecked = false;

                if ($el.hasClass('active')) {
                    isChecked = true;
                }

                $el.addClass('active');
            });

            $dd.on('hide.bs.dropdown', () => {
                if (!isChecked) {
                    this.$el.closest('.list-row').removeClass('active');
                }
            });
        },

        /**
         * Get an action list.
         *
         * @return {module:views/record/list~rowAction[]}
         */
        getActionList: function () {
            var list = [{
                action: 'quickView',
                label: 'View',
                data: {
                    id: this.model.id
                },
                link: '#' + this.model.name + '/view/' + this.model.id,
            }];

            if (this.options.acl.edit) {
                list.push({
                    action: 'quickEdit',
                    label: 'Edit',
                    data: {
                        id: this.model.id
                    },
                    link: '#' + this.model.name + '/edit/' + this.model.id,
                });
            }

            if (this.options.acl.delete) {
                list.push({
                    action: 'quickRemove',
                    label: 'Remove',
                    data: {
                        id: this.model.id,
                    }
                });
            }

            return list;
        },

        data: function () {
            return {
                acl: this.options.acl,
                actionList: this.getActionList(),
                scope: this.model.name,
            };
        },
    });
});
