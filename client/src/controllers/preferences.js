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

import RecordController from 'controllers/record';
import Preferences from 'models/preferences';

class PreferencesController extends RecordController {

    defaultAction = 'own'

    getModel(callback, context) {
        const model = new Preferences();

        model.settings = this.getConfig();
        model.defs = this.getMetadata().get('entityDefs.Preferences');

        if (callback) {
            callback.call(this, model);
        }

        return new Promise(resolve => {
            resolve(model);
        });
    }

    checkAccess(action) {
        return true;
    }

    // noinspection JSUnusedGlobalSymbols
    actionOwn() {
        this.actionEdit({id: this.getUser().id});
    }

    actionList() {}
}

export default PreferencesController;
