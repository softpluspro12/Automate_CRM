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

define('views/fields/multi-enum', ['views/fields/array', 'helpers/reg-exp-pattern', 'lib!Selectize'],
function (Dep, RegExpPattern, Selectize) {

    /**
     * A multi-enum field.
     *
     * @class
     * @name Class
     * @extends module:views/fields/array.Class
     * @memberOf module:views/fields/multi-enum
     */
    return Dep.extend(/** @lends module:views/fields/multi-enum.Class# */{

        type: 'multiEnum',

        listTemplate: 'fields/array/list',

        detailTemplate: 'fields/array/detail',

        editTemplate: 'fields/multi-enum/edit',

        /**
         * @const
         */
        MAX_ITEM_LENGTH: 100,

        /**
         * @protected
         * @type {boolean}
         */
        restoreOnBackspace: false,

        events: {},

        /**
         * @inheritDoc
         */
        data: function () {
            return _.extend({
                optionList: this.params.options || []
            }, Dep.prototype.data.call(this));
        },

        getTranslatedOptions: function () {
            return (this.params.options || []).map((item) => {
                if (this.translatedOptions !== null) {
                    if (item in this.translatedOptions) {
                        return this.translatedOptions[item];
                    }
                }

                return item;
            });
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.restoreOnBackspace && !('restore_on_backspace_espo' in Selectize.plugins)) {
                this.loadRestoreOnBackspavePlugin();
            }
        },

        loadRestoreOnBackspavePlugin: function () {
            Selectize.define('restore_on_backspace_espo', function (options) {
                Selectize.restoreOnBackspacePluginLoaded = true;

                options.text = options.text || function (option) {
                    return option[this.settings.labelField];
                };

                let self = this;

                this.onKeyDown = (function() {
                    let original = self.onKeyDown;

                    return function (e) {
                        let index, option;

                        if (
                            e.keyCode === 8 &&
                            this.$control_input.val() === '' &&
                            !this.$activeItems.length
                        ) {
                            index = this.caretPos - 1;

                            if (index >= 0 && index < this.items.length) {
                                option = this.options[this.items[index]];

                                option = {
                                    value: option.value,
                                    $order: option.$order,
                                    label: option.value,
                                };

                                if (this.deleteSelection(e)) {
                                    this.setTextboxValue(options.text.apply(this, [option]));
                                    this.refreshOptions(true);
                                }

                                e.preventDefault();

                                return;
                            }
                        }

                        return original.apply(this, arguments);
                    };
                })();
            });
        },

        translateValueToEditLabel: function (value) {
            var label = value;

            if (~(this.params.options || []).indexOf(value)) {
                label = this.getLanguage().translateOption(value, this.name, this.scope);
            }

            if (this.translatedOptions) {
                if (value in this.translatedOptions) {
                    label = this.translatedOptions[value];
                }
            }

            if (label === '') {
                label = this.translate('None');
            }

            return label;
        },

        afterRender: function () {
            if (this.mode === 'edit') {
                this.$element = this.$el.find('[data-name="' + this.name + '"]');

                var data = [];

                var valueList = Espo.Utils.clone(this.selected);

                for (var i in valueList) {
                    var value = valueList[i];
                    var originalValue = value;

                    if (value === '') {
                        value = valueList[i] = '__emptystring__';
                    }

                    if (!~(this.params.options || []).indexOf(value)) {
                        data.push({
                            value: value,
                            label: this.translateValueToEditLabel(originalValue),
                        });
                    }
                }

                this.$element.val(valueList.join(this.itemDelimiter));

                (this.params.options || []).forEach(value => {
                    var originalValue = value;

                    if (value === '') {
                        value = '__emptystring__';
                    }

                    data.push({
                        value: value,
                        label: this.translateValueToEditLabel(originalValue),
                    });
                });

                var pluginList = ['remove_button', 'drag_drop'];

                if (this.restoreOnBackspace) {
                    pluginList.push('restore_on_backspace_espo');
                }

                let selectizeOptions = {
                    options: data,
                    delimiter: this.itemDelimiter,
                    labelField: 'label',
                    valueField: 'value',
                    highlight: false,
                    searchField: ['label'],
                    plugins: pluginList,
                    copyClassesToDropdown: true,
                };

                if (!this.matchAnyWord) {
                    selectizeOptions.score = function (search) {
                        var score = this.getScoreFunction(search);

                        search = search.toLowerCase();

                        return function (item) {
                            if (item.label.toLowerCase().indexOf(search) === 0) {
                                return score(item);
                            }

                            return 0;
                        };
                    };
                }

                if (this.allowCustomOptions) {
                    selectizeOptions.persist = false;

                    selectizeOptions.create = (input) => {
                        if (input.length > this.MAX_ITEM_LENGTH) {
                            let message = this.translate('arrayItemMaxLength', 'messages')
                                .replace('{max}', this.MAX_ITEM_LENGTH.toString())

                            this.showValidationMessage(message, '.selectize-control')

                            return null;
                        }

                        if (this.params.pattern) {
                            /** @type module:helpers/reg-exp-pattern.Class */
                            let helper = new RegExpPattern(this.getMetadata(), this.getLanguage());

                            let result = helper.validate(this.params.pattern, input, this.name, this.entityType);

                            if (result) {
                                this.showValidationMessage(result.message, '.selectize-control')

                                return null;
                            }
                        }

                        return {
                            value: input,
                            label: input,
                        };
                    };

                    selectizeOptions.render = {
                        option_create: function (data, escape) {
                            return '<div class="create"><strong>' + escape(data.input) +
                                '</strong>&hellip;</div>';
                        }
                    };
                }

                this.$element.selectize(selectizeOptions);

                this.$element.on('change', () => {
                    this.trigger('change');
                });
            }

            if (this.mode === 'search') {
                this.renderSearch();
            }
        },

        fetch: function () {
            let list = this.$element.val().split(this.itemDelimiter);

            if (list.length === 1 && list[0] === '') {
                list = [];
            }

            for (let i in list) {
                if (list[i] === '__emptystring__') {
                    list[i] = '';
                }
            }

            if (this.params.isSorted && this.translatedOptions) {
                list = list.sort((v1, v2) => {
                     return (this.translatedOptions[v1] || v1)
                         .localeCompare(this.translatedOptions[v2] || v2);
                });
            }

            let data = {};

            data[this.name] = list;

            return data;
        },

        validateRequired: function () {
            if (this.isRequired()) {
                let value = this.model.get(this.name);

                if (!value || value.length === 0) {
                    let msg = this.translate('fieldIsRequired', 'messages')
                        .replace('{field}', this.getLabelText());

                    this.showValidationMessage(msg, '.selectize-control');

                    return true;
                }
            }
        },

        validateMaxCount: function () {
            if (this.params.maxCount) {
                let itemList = this.model.get(this.name) || [];

                if (itemList.length > this.params.maxCount) {
                    let msg =
                        this.translate('fieldExceedsMaxCount', 'messages')
                            .replace('{field}', this.getLabelText())
                            .replace('{maxCount}', this.params.maxCount.toString());

                    this.showValidationMessage(msg, '.selectize-control');

                    return true;
                }
            }
        },
    });
});
