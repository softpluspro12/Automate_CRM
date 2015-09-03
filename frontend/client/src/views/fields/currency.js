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

Espo.define('Views.Fields.Currency', 'Views.Fields.Float', function (Dep) {

    return Dep.extend({

        type: 'currency',

        editTemplate: 'fields.currency.edit',

        detailTemplate: 'fields.currency.detail',

        listTemplate: 'fields.currency.detail',

        data: function () {
            return _.extend({
                currencyFieldName: this.currencyFieldName,
                currencyValue: this.model.get(this.currencyFieldName) || this.getPreferences().get('defaultCurrency') || this.getConfig().get('defaultCurrency'),
                currencyOptions: this.currencyOptions,
                currencyList: this.currencyList
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            this.currencyFieldName = this.name + 'Currency';
            this.currencyList = this.getConfig().get('currencyList') || ['USD', 'EUR'];
            var currencyValue = this.currencyValue = this.model.get(this.currencyFieldName) || this.getConfig().get('defaultCurrency');

        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.mode == 'edit') {
                this.$currency = this.$el.find('[name="' + this.currencyFieldName + '"]');
            }
        },

        fetch: function () {
            var value = this.$element.val();
            value = this.parse(value);

            var data = {};

            var currencyValue = this.$currency.val();
            if (value === null) {
                currencyValue = null;
            }

            data[this.name] = value;
            data[this.currencyFieldName] = currencyValue
            return data;
        },
    });
});

