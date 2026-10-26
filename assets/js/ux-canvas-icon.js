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

                        var config = window.WIFCanvas || {};
                        var base = String(config.restUrl || '');
                        if (!base) {
                            element.empty();
                            return;
                        }

                        var size = Number(scope.wifCanvasSize);
                        var matchText = !size;
                        var color = String(scope.wifCanvasColor || 'currentColor').trim() || 'currentColor';
                        var stroke = scope.wifCanvasStroke == null || scope.wifCanvasStroke === ''
                            ? '2'
                            : String(scope.wifCanvasStroke);

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

                        var params = new URLSearchParams({
                            library: parsed.library,
                            name: parsed.name,
                            size: String(matchText ? 24 : size),
                            color: color,
                            stroke: stroke,
                        });

                        fetch(base + 'icon?' + params.toString(), {
                            credentials: 'same-origin',
                            headers: {
                                'X-WP-Nonce': config.nonce || '',
                            },
                        })
                            .then(function (response) {
                                if (!response.ok) {
                                    throw new Error('Icon request failed');
                                }
                                return response.json();
                            })
                            .then(function (data) {
                                if (id !== requestId) {
                                    return;
                                }
                                element.html(data && data.svg ? data.svg : '');
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
        if (!raw) {
            return { library: 'lucide', name: '' };
        }
        var parts = raw.split('/');
        if (parts.length < 2) {
            return { library: 'lucide', name: raw };
        }
        return {
            library: parts[0] || 'lucide',
            name: parts.slice(1).join('/'),
        };
    }
})();
