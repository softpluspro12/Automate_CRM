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

import DatetimeOptionalFieldView from 'views/fields/datetime-optional';
import moment from 'moment';

class TaskDateEndFieldView extends DatetimeOptionalFieldView {

    detailTemplate = 'crm:task/fields/date-end/detail'
    listTemplate = 'crm:task/fields/date-end/detail'

    isEnd = true

    data() {
        const data = super.data();

        const status = this.model.get('status');

        if (!status || this.notActualStatusList.includes(status)) {
            return data;
        }

        if (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST) {
            if (this.isDate()) {
                const value = this.model.get(this.nameDate);

                if (value) {
                    const d = moment.utc(value + ' 23:59', this.getDateTime().internalDateTimeFormat);
                    const now = this.getDateTime().getNowMoment();

                    if (d.unix() < now.unix()) {
                        data.isOverdue = true;
                    }
                }
            } else {
                const value = this.model.get(this.name);

                if (value) {
                    const d = this.getDateTime().toMoment(value);
                    const now = moment().tz(this.getDateTime().timeZone || 'UTC');

                    if (d.unix() < now.unix()) {
                        data.isOverdue = true;
                    }
                }
            }
        }

        return data;
    }

    setup() {
        super.setup();

        this.notActualStatusList = [
            ...(this.getMetadata().get(`scopes.${this.entityType}.completedStatusList`) || []),
            ...(this.getMetadata().get(`scopes.${this.entityType}.canceledStatusList`) || []),
        ];

        if (this.isEditMode() || this.isDetailMode()) {
            this.on('change', () => {
                if (!this.model.get('dateEnd') && this.model.get('reminders')) {
                    this.model.set('reminders', []);
                }
            });
        }
    }
}

export default TaskDateEndFieldView;
