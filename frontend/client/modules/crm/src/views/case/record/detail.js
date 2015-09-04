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

Espo.define('crm:views/case/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            if (this.getAcl().checkModel(this.model, 'edit')) {
                if (['Closed', 'Rejected', 'Duplicate'].indexOf(this.model.get('status')) == -1) {
                    this.dropdownItemList.push({
                        'label': 'Close',
                        'name': 'close'
                    });
                    this.dropdownItemList.push({
                        'label': 'Reject',
                        'name': 'reject'
                    });
                }
            }
        },

        actionClose: function () {
                this.model.save({
                    status: 'Closed'
                }, {
                    patch: true,
                    success: function () {
                        Espo.Ui.success(this.translate('Closed', 'labels', 'Case'));
                        this.removeButton('close');
                        this.removeButton('reject');
                    }.bind(this),
                });
        },

        actionReject: function () {
                this.model.save({
                    status: 'Rejected'
                }, {
                    patch: true,
                    success: function () {
                        Espo.Ui.success(this.translate('Rejected', 'labels', 'Case'));
                        this.removeButton('close');
                        this.removeButton('reject');
                    }.bind(this),
                });
        },

    });
});

