(function () {
    if (typeof angular === 'undefined') {
        return;
    }

    var module;
    try {
        module = angular.module('uxBuilder');
    } catch (error) {
        return;
    }

    module.run([
        '$templateCache',
        function ($templateCache) {
            $templateCache.put(
                'components/ux-option/types/wpicons.html',
                '<ux-option-wpicons option="$ctrl.option" model="$ctrl.model"></ux-option-wpicons>'
            );
        },
    ]);

    module.component('uxOptionWpicons', {
        require: {
            $optionsCtrl: '^uxOptions',
        },
        bindings: {
            option: '<',
            model: '=',
        },
        template:
            '<div class="wpicons-ux-option">' +
                '<div class="wpicons-ux-option__preview" ng-if="$ctrl.label()">' +
                    '<strong>{{ $ctrl.label() }}</strong>' +
                    '<span>{{ $ctrl.meta() }}</span>' +
                '</div>' +
                '<div class="wpicons-ux-option__empty" ng-if="!$ctrl.label()">' +
                    '{{ $ctrl.emptyLabel }}' +
                '</div>' +
                '<div class="wpicons-ux-option__actions option-actions">' +
                    '<button type="button" class="wp-style outline" ng-click="$ctrl.open()">' +
                        '{{ $ctrl.model ? $ctrl.changeLabel : $ctrl.chooseLabel }}' +
                    '</button>' +
                    '<button type="button" class="wp-style outline" ng-if="$ctrl.model" ng-click="$ctrl.clear()">' +
                        '{{ $ctrl.clearLabel }}' +
                    '</button>' +
                '</div>' +
            '</div>',
        controller: [
            '$scope',
            function ($scope) {
                var ctrl = this;

                ctrl.chooseLabel = 'Choose icon';
                ctrl.changeLabel = 'Change icon';
                ctrl.clearLabel = 'Remove';
                ctrl.emptyLabel = 'No icon selected';

                ctrl.meta = function () {
                    var parsed = parse(ctrl.model);
                    var parts = [parsed.library];
                    if (parsed.variant) {
                        parts.push(parsed.variant);
                    }
                    return parts.filter(Boolean).join(' · ');
                };

                ctrl.label = function () {
                    var name = parse(ctrl.model).name;
                    if (!name) {
                        return '';
                    }
                    return name.replace(/[-_]+/g, ' ').replace(/\b\w/g, function (letter) {
                        return letter.toUpperCase();
                    });
                };

                ctrl.open = function () {
                    if (!window.wpIcons || typeof window.wpIcons.openPicker !== 'function') {
                        window.alert('WP Icons picker is not available.');
                        return;
                    }

                    var parsed = parse(ctrl.model);
                    var values = (ctrl.$optionsCtrl && ctrl.$optionsCtrl.model) || {};
                    var isIconBox = Object.prototype.hasOwnProperty.call(values, 'imgWidth');
                    var size = isIconBox
                        ? Number(values.imgWidth) || 60
                        : Number(values.iconSize) || undefined;

                    window.wpIcons
                        .openPicker({
                            value: {
                                library: parsed.library,
                                name: parsed.name,
                                variant: parsed.variant,
                                color: values.iconColor || 'currentColor',
                                stroke: values.iconStroke || 2,
                                size: size,
                            },
                        })
                        .then(function (icon) {
                            if (!icon || !icon.name) {
                                return;
                            }
                            $scope.$applyAsync(function () {
                                var model = ctrl.$optionsCtrl && ctrl.$optionsCtrl.model;
                                ctrl.model = toRef(icon);
                                if (!model) {
                                    return;
                                }
                                model.iconColor = icon.color || 'currentColor';
                                model.iconStroke =
                                    icon.stroke == null || icon.stroke === '' ? 2 : Number(icon.stroke);
                                if (isIconBox) {
                                    var nextSize = Number(icon.size) || Number(values.imgWidth) || 60;
                                    model.imgWidth = Math.max(20, Math.min(600, nextSize));
                                } else if (icon.size) {
                                    model.iconSize = Number(icon.size) || 0;
                                }
                            });
                        });
                };

                ctrl.clear = function () {
                    ctrl.model = '';
                };

                function parse(value) {
                    var raw = String(value || '').trim();
                    if (raw.indexOf('wpicons:') === 0) {
                        raw = raw.slice('wpicons:'.length);
                    }
                    if (!raw) {
                        return { library: 'lucide', name: '', variant: '' };
                    }
                    if (raw.indexOf(':') !== -1) {
                        var colon = raw.split(':');
                        return {
                            library: colon[0] || 'lucide',
                            name: colon[1] || '',
                            variant: colon[2] || '',
                        };
                    }
                    if (raw.indexOf('/') !== -1) {
                        var slash = raw.split('/');
                        return {
                            library: slash[0] || 'lucide',
                            name: slash.slice(1).join('/'),
                            variant: '',
                        };
                    }
                    return { library: 'lucide', name: raw, variant: '' };
                }

                function toRef(icon) {
                    var library = icon.library || 'lucide';
                    var name = icon.name || '';
                    if (!name) {
                        return '';
                    }
                    if (icon.variant) {
                        return library + ':' + name + ':' + icon.variant;
                    }
                    return library + ':' + name;
                }
            },
        ],
    });
})();
