<?php

if (!defined('ABSPATH')) {
    exit;
}

class WIF_Menu_Link
{
    public const TAG = 'ux_menu_link';
    public const SOURCE = 'icon_source';
    public const NAME = 'icon_name';
    public const SIZE = 'icon_size';
    public const COLOR = 'icon_color';
    public const STROKE = 'icon_stroke';
    public const POS = 'icon_pos';
    public const ALIGN = 'icon_align';
    public const GAP = 'icon_gap';
    public const GROUP = 'icon_options';
    public const FLATSOME_ICON = 'icon';

    public static function init(): void
    {
        add_filter('ux_builder_shortcode_data_' . self::TAG, array(__CLASS__, 'extend_builder'), 20);
        add_action('wp_loaded', array(__CLASS__, 'wrap_shortcode'), 20);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function extend_builder(array $data): array
    {
        $options = WIF_Builder::group_options($data);
        if ($options === []) {
            return $data;
        }

        $flatsomeIcon = $options[self::FLATSOME_ICON] ?? null;
        unset($options[self::FLATSOME_ICON]);

        $whenWpicons = self::SOURCE . ' === "wpicons"';
        $hasIcon = '(icon && icon_source != "wpicons") || (icon_source === "wpicons" && icon_name)';

        $iconOptions = [
            self::SOURCE => [
                'type' => 'radio-buttons',
                'heading' => __('Icon source', 'wpicons-flatsome'),
                'default' => 'flatsome',
                'full_width' => true,
                'options' => [
                    'flatsome' => ['title' => __('Flatsome', 'wpicons-flatsome')],
                    'wpicons' => ['title' => __('WP Icons', 'wpicons-flatsome')],
                ],
            ],
        ];

        if (is_array($flatsomeIcon)) {
            $flatsomeIcon['conditions'] = WIF_Builder::and_condition(
                $flatsomeIcon['conditions'] ?? '',
                self::SOURCE . ' != "wpicons"'
            );
            $iconOptions[self::FLATSOME_ICON] = $flatsomeIcon;
        }

        $iconOptions += [
            self::NAME => [
                'type' => 'wpicons',
                'heading' => __('Icon', 'wpicons-flatsome'),
                'default' => '',
                'conditions' => $whenWpicons,
            ],
            self::SIZE => [
                'type' => 'slider',
                'heading' => __('Size', 'wpicons-flatsome'),
                'default' => '0',
                'min' => '0',
                'max' => '96',
                'step' => '1',
                'conditions' => $whenWpicons,
            ],
            self::COLOR => [
                'type' => 'colorpicker',
                'heading' => __('Color', 'wpicons-flatsome'),
                'default' => '',
                'conditions' => $whenWpicons,
            ],
            self::STROKE => [
                'type' => 'slider',
                'heading' => __('Stroke', 'wpicons-flatsome'),
                'default' => '2',
                'min' => '0.5',
                'max' => '4',
                'step' => '0.25',
                'conditions' => $whenWpicons,
            ],
            self::POS => [
                'type' => 'select',
                'heading' => __('Position', 'wpicons-flatsome'),
                'default' => '',
                'conditions' => $hasIcon,
                'options' => [
                    '' => __('Left', 'wpicons-flatsome'),
                    'right' => __('Right', 'wpicons-flatsome'),
                ],
            ],
            self::ALIGN => [
                'type' => 'radio-buttons',
                'heading' => __('Align', 'wpicons-flatsome'),
                'default' => 'center',
                'full_width' => true,
                'conditions' => $hasIcon,
                'options' => [
                    'start' => ['title' => __('Start', 'wpicons-flatsome')],
                    'center' => ['title' => __('Center', 'wpicons-flatsome')],
                    'end' => ['title' => __('End', 'wpicons-flatsome')],
                ],
            ],
            self::GAP => [
                'type' => 'slider',
                'heading' => __('Gap', 'wpicons-flatsome'),
                'default' => '8',
                'min' => '0',
                'max' => '48',
                'step' => '1',
                'unit' => 'px',
                'conditions' => $hasIcon,
            ],
        ];

        $before = array_key_exists('label', $options) ? 'label' : 'link_options';
        $options = WIF_Builder::insert_options($options, $before, [
            self::GROUP => [
                'type' => 'group',
                'heading' => __('Icon', 'wpicons-flatsome'),
                'options' => $iconOptions,
            ],
        ]);

        $data = WIF_Builder::set_group_options($data, $options);

        $template = WIF_PLUGIN_DIR . 'templates/ux-builder/ux_menu_link.html';
        if (is_readable($template)) {
            $data['template'] = file_get_contents($template) ?: ($data['template'] ?? '');
        }

        return $data;
    }

    public static function wrap_shortcode(): void
    {
        global $shortcode_tags;

        if (empty($shortcode_tags[self::TAG]) || !is_callable($shortcode_tags[self::TAG])) {
            return;
        }

        $original = $shortcode_tags[self::TAG];
        add_shortcode(self::TAG, static function ($atts, $content, $tag) use ($original) {
            return self::render(is_array($atts) ? $atts : [], (string) $content, $original, (string) $tag);
        });
    }

    /**
     * @param array<string, mixed> $atts
     * @param callable $original
     */
    public static function render(array $atts, string $content, callable $original, string $tag = self::TAG): string
    {
        $runOriginal = static function () use ($original, &$atts, $content, $tag): string {
            return (string) call_user_func($original, $atts, $content, $tag);
        };

        $source = (string) ($atts[self::SOURCE] ?? 'flatsome');
        $icon = (string) ($atts[self::NAME] ?? '');
        $isWpicons = $source === 'wpicons' && $icon !== '';

        if ($isWpicons) {
            $parsed = WIF_Icons::parse($icon);
            if ($parsed['name'] === '') {
                return self::apply_layout($runOriginal(), $atts, false);
            }

            $style = WIF_Icons::style_from_atts($atts);
            $svg = WIF_Icons::svg($parsed, [
                'size' => $style['match_text'] ? 24 : $style['size'],
                'color' => $style['color'],
                'stroke' => $style['stroke'],
                'class' => '',
            ]);
            if ($svg === '') {
                return self::apply_layout($runOriginal(), $atts, false);
            }

            $atts[self::FLATSOME_ICON] = WIF_Icons::sentinel(
                $parsed['library'],
                $parsed['name'],
                $parsed['variant'] ?? ''
            );

            return self::apply_layout(
                WIF_Icons::with_style($style, $runOriginal),
                $atts,
                true
            );
        }

        $hasFlatsomeIcon = $source !== 'wpicons' && (string) ($atts[self::FLATSOME_ICON] ?? '') !== '';

        return self::apply_layout($runOriginal(), $atts, $hasFlatsomeIcon);
    }

    /**
     * @param array<string, mixed> $atts
     */
    private static function apply_layout(string $html, array $atts, bool $hasIcon): string
    {
        if ($html === '' || !$hasIcon) {
            return $html;
        }

        $align = sanitize_key((string) ($atts[self::ALIGN] ?? ''));
        $gapRaw = $atts[self::GAP] ?? '';
        $pos = sanitize_key((string) ($atts[self::POS] ?? ''));
        $source = (string) ($atts[self::SOURCE] ?? 'flatsome');
        $isWpicons = $source === 'wpicons';

        $explicit = $align !== '' || $pos === 'right' || ($gapRaw !== '' && $gapRaw !== null);
        if (!$isWpicons && !$explicit) {
            return $html;
        }

        $alignCss = [
            'start' => 'flex-start',
            'center' => 'center',
            'end' => 'flex-end',
        ];
        if ($align === '' || !isset($alignCss[$align])) {
            $align = 'center';
        }

        $gap = is_numeric($gapRaw) ? max(0, (float) $gapRaw) : 8.0;

        if (!class_exists(\WP_HTML_Tag_Processor::class)) {
            return $html;
        }

        $processor = new \WP_HTML_Tag_Processor($html);
        if (!$processor->next_tag(['tag_name' => 'DIV', 'class_name' => 'ux-menu-link'])) {
            return $html;
        }

        $processor->add_class('wif-menu-link');
        if ($pos === 'right') {
            $processor->add_class('wif-menu-link--icon-right');
        }

        $styles = [
            '--wif-icon-align:' . $alignCss[$align],
            '--wif-icon-gap:' . $gap . 'px',
        ];
        $existing = trim((string) $processor->get_attribute('style'));
        $next = $existing === ''
            ? implode(';', $styles)
            : rtrim($existing, ';') . ';' . implode(';', $styles);
        $processor->set_attribute('style', $next);

        return $processor->get_updated_html();
    }
}
