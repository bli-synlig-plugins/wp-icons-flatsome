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

    module.directive('wifCanvasIcon', function () {
        return {
            restrict: 'A',
            scope: {
                wifCanvasIcon: '=',
                wifCanvasSize: '=',
                wifCanvasColor: '=',
                wifCanvasStroke: '=',
            },
            link: function (scope, element) {
                var requestId = 0;

                scope.$watchGroup(
                    ['wifCanvasIcon', 'wifCanvasSize', 'wifCanvasColor', 'wifCanvasStroke'],
                    function () {
                        var id = ++requestId;
                        var parsed = parse(scope.wifCanvasIcon);
                        if (!parsed.name) {
                            element.empty();
                            return;
                        }

                        var size = Number(scope.wifCanvasSize);
                        var matchText = !size;
                        var color = String(scope.wifCanvasColor || 'currentColor').trim() || 'currentColor';
                        var stroke = scope.wifCanvasStroke == null || scope.wifCanvasStroke === ''
                            ? 2
                            : Number(scope.wifCanvasStroke);

                        if (matchText) {
                            element.removeClass('icon-wpicons--sized');
                            element.css({ width: '', height: '' });
                        } else {
                            element.addClass('icon-wpicons--sized');
                            element.css({
                                width: size + 'px',
                                height: size + 'px',
                            });
                        }

                        if (color.toLowerCase() === 'currentcolor') {
                            element.css('color', '');
                        } else {
                            element.css('color', color);
                        }

                        if (!window.wpIcons || typeof window.wpIcons.getIcon !== 'function') {
                            element.empty();
                            return;
                        }

                        window.wpIcons
                            .getIcon(toRef(parsed), {
                                size: matchText ? 24 : size,
                                color: color,
                                stroke: stroke,
                            })
                            .then(function (svg) {
                                if (id !== requestId) {
                                    return;
                                }
                                element.html(svg || '');
                            })
                            .catch(function () {
                                if (id !== requestId) {
                                    return;
                                }
                                element.empty();
                            });
                    }
                );
            },
        };
    });

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

    function toRef(parsed) {
        var library = parsed.library || 'lucide';
        var name = parsed.name || '';
        if (!name) {
            return '';
        }
        if (parsed.variant) {
            return library + ':' + name + ':' + parsed.variant;
        }
        return library + ':' + name;
    }
})();
