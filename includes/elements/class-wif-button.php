<?php

if (!defined('ABSPATH')) {
    exit;
}

class WIF_Button
{
    public const TAG = 'button';
    public const SOURCE = 'icon_source';
    public const NAME = 'icon_name';
    public const SIZE = 'icon_size';
    public const COLOR = 'icon_color';
    public const STROKE = 'icon_stroke';
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
        $options = WIF_Builder::group_options($data, self::GROUP);
        if ($options === []) {
            return $data;
        }

        if (isset($options[self::FLATSOME_ICON]) && is_array($options[self::FLATSOME_ICON])) {
            $options[self::FLATSOME_ICON]['conditions'] = WIF_Builder::and_condition(
                $options[self::FLATSOME_ICON]['conditions'] ?? '',
                self::SOURCE . ' != "wpicons"'
            );
        }

        $has_icon = '(icon && icon_source != "wpicons") || (icon_source === "wpicons" && icon_name)';
        foreach (array('icon_pos', 'icon_reveal') as $key) {
            if (!isset($options[$key]) || !is_array($options[$key])) {
                continue;
            }
            $options[$key]['conditions'] = $has_icon;
        }

        $whenWpicons = self::SOURCE . ' === "wpicons"';
        $insert = [
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
        ];

        $data = WIF_Builder::set_group_options(
            $data,
            WIF_Builder::insert_options($options, self::FLATSOME_ICON, $insert),
            self::GROUP
        );

        $template = WIF_PLUGIN_DIR . 'templates/ux-builder/button.html';
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
        add_shortcode(self::TAG, static function ($atts, $content) use ($original) {
            return self::render(is_array($atts) ? $atts : [], (string) $content, $original);
        });
    }

    /**
     * @param array<string, mixed> $atts
     * @param callable $original
     */
    public static function render(array $atts, string $content, callable $original): string
    {
        $source = (string) ($atts[self::SOURCE] ?? 'flatsome');
        $icon = (string) ($atts[self::NAME] ?? '');
        if ($source !== 'wpicons' || $icon === '') {
            return (string) call_user_func($original, $atts, $content);
        }

        $parsed = WIF_Icons::parse($icon);
        if ($parsed['name'] === '') {
            return (string) call_user_func($original, $atts, $content);
        }

        $style = WIF_Icons::style_from_atts($atts);
        $svg = WIF_Icons::svg($parsed, [
            'size' => $style['match_text'] ? 24 : $style['size'],
            'color' => $style['color'],
            'stroke' => $style['stroke'],
            'class' => '',
        ]);
        if ($svg === '') {
            return (string) call_user_func($original, $atts, $content);
        }

        $atts[self::FLATSOME_ICON] = WIF_Icons::sentinel(
            $parsed['library'],
            $parsed['name'],
            $parsed['variant'] ?? ''
        );

        return WIF_Icons::with_style($style, static function () use ($original, $atts, $content) {
            return (string) call_user_func($original, $atts, $content);
        });
    }
}
