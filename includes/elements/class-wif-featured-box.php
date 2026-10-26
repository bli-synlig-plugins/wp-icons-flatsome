<?php

if (!defined('ABSPATH')) {
    exit;
}

class WIF_Featured_Box
{
    public const TAG = 'featured_box';
    public const SOURCE = 'icon_source';
    public const NAME = 'icon_name';
    public const STROKE = 'icon_stroke';
    public const IMAGE = 'img';
    public const INLINE_SVG = 'inline_svg';

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

        if (isset($options[self::IMAGE]) && is_array($options[self::IMAGE])) {
            $options[self::IMAGE]['conditions'] = WIF_Builder::and_condition(
                $options[self::IMAGE]['conditions'] ?? '',
                self::SOURCE . ' != "wpicons"'
            );
        }

        if (isset($options[self::INLINE_SVG]) && is_array($options[self::INLINE_SVG])) {
            $options[self::INLINE_SVG]['conditions'] = WIF_Builder::and_condition(
                $options[self::INLINE_SVG]['conditions'] ?? '',
                self::SOURCE . ' != "wpicons"'
            );
        }

        $insert = [
            self::SOURCE => [
                'type' => 'radio-buttons',
                'heading' => __('Icon source', 'wpicons-flatsome'),
                'default' => 'image',
                'full_width' => true,
                'options' => [
                    'image' => ['title' => __('Image', 'wpicons-flatsome')],
                    'wpicons' => ['title' => __('WP Icons', 'wpicons-flatsome')],
                ],
            ],
            self::NAME => [
                'type' => 'wpicons',
                'heading' => __('Icon', 'wpicons-flatsome'),
                'default' => '',
                'conditions' => self::SOURCE . ' === "wpicons"',
            ],
        ];

        $options = WIF_Builder::insert_options($options, self::IMAGE, $insert);
        $options = WIF_Builder::insert_options($options, 'pos', [
            self::STROKE => [
                'type' => 'slider',
                'heading' => __('Stroke', 'wpicons-flatsome'),
                'default' => '2',
                'min' => '0.5',
                'max' => '4',
                'step' => '0.25',
                'conditions' => self::SOURCE . ' === "wpicons"',
                'on_change' => [
                    'selector' => '.icon-box-img svg',
                    'style' => 'stroke-width: {{ value }}',
                ],
            ],
        ]);

        return WIF_Builder::set_group_options($data, $options);
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
        $source = (string) ($atts[self::SOURCE] ?? 'image');
        $icon = (string) ($atts[self::NAME] ?? '');
        if ($source !== 'wpicons' || $icon === '') {
            return (string) call_user_func($original, $atts, $content);
        }

        $parsed = WIF_Icons::parse($icon);
        if ($parsed['name'] === '') {
            return (string) call_user_func($original, $atts, $content);
        }

        $defaults = [
            'title' => '',
            'title_small' => '',
            'font_size' => '',
            'class' => '',
            'visibility' => '',
            'img' => '',
            'inline_svg' => 'true',
            'img_width' => '60',
            'pos' => 'top',
            'link' => '',
            'target' => '_self',
            'rel' => '',
            'tooltip' => '',
            'margin' => '',
            'icon_border' => '',
            'icon_color' => '',
            self::SOURCE => 'image',
            self::NAME => '',
            self::STROKE => '2',
        ];
        $atts = shortcode_atts($defaults, $atts, self::TAG);

        if ($atts['visibility'] === 'hidden') {
            return '';
        }

        $width = max(20, (int) $atts['img_width']);
        $style = WIF_Icons::style_from_atts($atts);
        $svg = WIF_Icons::svg($parsed, [
            'size' => $width,
            'color' => 'currentColor',
            'stroke' => $style['stroke'],
            'title' => (string) $atts['title'],
            'class' => 'icon-box-wpicons',
        ]);

        if ($svg === '') {
            return (string) call_user_func($original, $atts, $content);
        }

        $classes = ['featured-box', 'icon-box-' . $atts['pos']];
        $classes_img = ['icon-box-img'];
        if ($atts['class']) {
            $classes[] = $atts['class'];
        }
        if ($atts['visibility']) {
            $classes[] = $atts['visibility'];
        }
        if ($atts['tooltip']) {
            $classes[] = 'tooltip';
        }
        if ($atts['pos'] === 'center') {
            $classes[] = 'text-center';
        }
        if ($atts['pos'] === 'left' || $atts['pos'] === 'top') {
            $classes[] = 'text-left';
        }
        if ($atts['pos'] === 'right') {
            $classes[] = 'text-right';
        }
        if ($atts['font_size']) {
            $classes[] = 'is-' . $atts['font_size'];
        }
        if ($atts['icon_border']) {
            $classes_img[] = 'has-icon-bg';
        }

        $css_args_out = [
            'margin' => [
                'attribute' => 'margin',
                'value' => $atts['margin'],
            ],
        ];
        $css_args = [
            'icon_border' => [
                'attribute' => 'border-width',
                'unit' => 'px',
                'value' => $atts['icon_border'],
            ],
            'icon_color' => [
                'attribute' => 'color',
                'value' => $atts['icon_color'],
            ],
        ];

        $link_atts = [
            'target' => $atts['target'],
            'rel' => [$atts['rel']],
        ];

        ob_start();
        if ($atts['link'] && function_exists('flatsome_parse_target_rel')) {
            echo '<a class="plain" href="' . esc_url($atts['link']) . '"' . flatsome_parse_target_rel($link_atts) . '>';
        }
        ?>
        <div class="icon-box <?php echo esc_attr(implode(' ', $classes)); ?>"<?php
            echo $atts['tooltip'] ? ' title="' . esc_attr($atts['tooltip']) . '"' : '';
            echo function_exists('get_shortcode_inline_css') ? get_shortcode_inline_css($css_args_out) : '';
        ?>>
            <div class="<?php echo esc_attr(implode(' ', $classes_img)); ?>" style="width: <?php echo (int) $width; ?>px">
                <div class="icon">
                    <div class="icon-inner"<?php echo function_exists('get_shortcode_inline_css') ? get_shortcode_inline_css($css_args) : ''; ?>>
                        <?php echo $svg; ?>
                    </div>
                </div>
            </div>
            <div class="icon-box-text last-reset">
                <?php if ($atts['title']) : ?>
                    <h5 class="uppercase"><?php echo wp_kses_post($atts['title']); ?></h5>
                <?php endif; ?>
                <?php if ($atts['title_small']) : ?>
                    <h6><?php echo wp_kses_post($atts['title_small']); ?></h6>
                <?php endif; ?>
                <?php echo do_shortcode($content); ?>
            </div>
        </div>
        <?php
        if ($atts['link']) {
            echo '</a>';
        }

        return (string) ob_get_clean();
    }
}
