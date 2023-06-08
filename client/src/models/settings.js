/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module models/settings */

import Dep from 'model';

/**
 * A config.
 */
export default class extends Dep {

    name = 'Settings'
    entityType = 'Settings'
    urlRoot = 'Settings'

    /**
     * Load.
     *
     * @returns {Promise}
     */
    load() {
        return new Promise(resolve => {
            this.fetch()
                .then(() => resolve());
        });
    }

    /**
     * Get a value by a path.
     *
     * @param {string[]} path A path.
     * @returns {*} Null if not set.
     */
    getByPath(path) {
        if (!path.length) {
            return null;
        }

        let p;

        for (let i = 0; i < path.length; i++) {
            var item = path[i];

            if (i === 0) {
                p = this.get(item);
            }
            else {
                if (item in p) {
                    p = p[item];
                }
                else {
                    return null;
                }
            }

            if (i === path.length - 1) {
                return p;
            }

            if (p === null || typeof p !== 'object') {
                return null;
            }
        }
    }
}
