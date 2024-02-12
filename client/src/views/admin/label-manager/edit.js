/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import View from 'view';

class LabelManagerEditView extends View {

    template = 'admin/label-manager/edit'

    events = {
        /** @this LabelManagerEditView */
        'click [data-action="showCategory"]': function (e) {
            const name = $(e.currentTarget).data('name');

            this.showCategory(name);
        },
        /** @this LabelManagerEditView */
        'click [data-action="hideCategory"]': function (e) {
            const name = $(e.currentTarget).data('name');

            this.hideCategory(name);
        },
        /** @this LabelManagerEditView */
        'click [data-action="cancel"]': function () {
            this.actionCancel();
        },
        /** @this LabelManagerEditView */
        'click [data-action="save"]': function () {
            this.actionSave();
        },
        /** @this LabelManagerEditView */
        'change input.label-value': function (e) {
            const name = $(e.currentTarget).data('name');
            const value = $(e.currentTarget).val();

            this.setLabelValue(name, value);
        },
    }

    data() {
        return {
            categoryList: this.getCategoryList(),
            scope: this.scope,
        };
    }

    setup() {
        this.scope = this.options.scope;
        this.language = this.options.language;

        this.dirtyLabelList = [];

        this.wait(true);

        Espo.Ajax.postRequest('LabelManager/action/getScopeData', {
            scope: this.scope,
            language: this.language,
        }).then(data => {
            this.scopeData = data;

            this.scopeDataInitial = Espo.Utils.cloneDeep(this.scopeData);
            this.wait(false);
        });
    }

    getCategoryList() {
        return Object.keys(this.scopeData).sort((v1, v2) => {
            return v1.localeCompare(v2);
        });
    }

    setLabelValue(name, value) {
        const category = name.split('[.]')[0];

        value = value.replace(/\\\\n/i, '\n');
        value = value.trim();

        this.scopeData[category][name] = value;

        this.dirtyLabelList.push(name);
        this.setConfirmLeaveOut(true);

        if (!this.getCategoryView(category)) {
            return;
        }

        this.getCategoryView(category).categoryData[name] = value;
    }

    /**
     * @param {string} category
     * @return {import('./category').default}
     */
    getCategoryView(category) {
        return this.getView(category);
    }

    setConfirmLeaveOut(value) {
        this.getRouter().confirmLeaveOut = value;
    }

    afterRender() {
        this.$save = this.$el.find('button[data-action="save"]');
        this.$cancel = this.$el.find('button[data-action="cancel"]');
    }

    actionSave() {
        this.$save.addClass('disabled').attr('disabled');
        this.$cancel.addClass('disabled').attr('disabled');

        const data = {};

        this.dirtyLabelList.forEach(name => {
            const category = name.split('[.]')[0];

            data[name] = this.scopeData[category][name];
        });

        Espo.Ui.notify(this.translate('saving', 'messages'));

        Espo.Ajax.postRequest('LabelManager/action/saveLabels', {
            scope: this.scope,
            language: this.language,
            labels: data,
        })
        .then(returnData => {
            this.scopeDataInitial = Espo.Utils.cloneDeep(this.scopeData);
            this.dirtyLabelList = [];
            this.setConfirmLeaveOut(false);

            this.$save.removeClass('disabled').removeAttr('disabled');
            this.$cancel.removeClass('disabled').removeAttr('disabled');

            for (const key in returnData) {
                const name = key.split('[.]').splice(1).join('[.]');

                this.$el.find(`input.label-value[data-name="${name}"]`).val(returnData[key]);
            }

            Espo.Ui.success(this.translate('Saved'));

            this.getHelper().broadcastChannel.postMessage('update:language');
            this.getLanguage().loadSkipCache();
        })
        .catch(() => {
            this.$save.removeClass('disabled').removeAttr('disabled');
            this.$cancel.removeClass('disabled').removeAttr('disabled');
        });
    }

    actionCancel() {
        this.scopeData = Espo.Utils.cloneDeep(this.scopeDataInitial);
        this.dirtyLabelList = [];

        this.setConfirmLeaveOut(false);

        this.getCategoryList().forEach(category => {
            if (!this.getCategoryView(category)) {
                return;
            }

            this.getCategoryView(category).categoryData = this.scopeData[category];
            this.getCategoryView(category).reRender();
        });
    }

    showCategory(category) {
        this.$el.find(`a[data-action="showCategory"][data-name="${category}"]`).addClass('hidden');

        if (this.hasView(category)) {
            this.$el.find(`a[data-action="hideCategory"][data-name="${category}"]`).removeClass('hidden');
            this.$el.find(`.panel-body[data-name="${category}"]`).removeClass('hidden');

            return;
        }

        this.createView(category, 'views/admin/label-manager/category', {
            selector: `.panel-body[data-name="${category}"]`,
            categoryData: this.getCategoryData(category),
            scope: this.scope,
            language: this.language,
        }, view => {
            this.$el.find(`.panel-body[data-name="${category}"]`).removeClass('hidden');
            this.$el.find(`a[data-action="hideCategory"][data-name="${category}"]`).removeClass('hidden');

            view.render();
        });
    }

    hideCategory(category) {
        this.clearView(category);

        this.$el.find(`.panel-body[data-name="${category}"]`).addClass('hidden');
        this.$el.find(`a[data-action="showCategory"][data-name="${category}"]`).removeClass('hidden');
        this.$el.find(`a[data-action="hideCategory"][data-name="${category}"]`).addClass('hidden');
    }

    getCategoryData(category) {
        return this.scopeData[category] || {};
    }
}

export default LabelManagerEditView;


