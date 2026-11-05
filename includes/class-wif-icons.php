<?php

if (!defined('ABSPATH')) {
    exit;
}

class WIF_Icons
{
    public const SENTINEL_PREFIX = 'wpicons:';

    /**
     * @return array{library: string, name: string, variant: string}
     */
    public static function parse(string $value): array
    {
        $value = trim($value);
        if (str_starts_with($value, self::SENTINEL_PREFIX)) {
            $value = substr($value, strlen(self::SENTINEL_PREFIX));
        }

        $empty = [
            'library' => 'lucide',
            'name' => '',
            'variant' => '',
        ];

        if ($value === '') {
            return $empty;
        }

        if (str_contains($value, ':')) {
            $parts = explode(':', $value);

            return [
                'library' => sanitize_key($parts[0] ?? '') ?: 'lucide',
                'name' => self::sanitize_name($parts[1] ?? ''),
                'variant' => self::sanitize_variant($parts[2] ?? ''),
            ];
        }

        if (str_contains($value, '/')) {
            [$library, $name] = explode('/', $value, 2);

            return [
                'library' => sanitize_key($library) ?: 'lucide',
                'name' => self::sanitize_name($name),
                'variant' => '',
            ];
        }

        return [
            'library' => 'lucide',
            'name' => self::sanitize_name($value),
            'variant' => '',
        ];
    }

    public static function sanitize_name(string $name): string
    {
        $name = strtolower(trim($name));

        return preg_replace('/[^a-z0-9_-]/', '', $name) ?? '';
    }

    public static function sanitize_variant(string $variant): string
    {
        $variant = strtolower(trim($variant));

        return preg_match('/^[a-z0-9-]{1,20}$/', $variant) === 1 ? $variant : '';
    }

    /**
     * @param array{library?: string, name?: string, variant?: string} $parsed
     */
    public static function ref(array $parsed): string
    {
        $library = sanitize_key((string) ($parsed['library'] ?? '')) ?: 'lucide';
        $name = self::sanitize_name((string) ($parsed['name'] ?? ''));
        $variant = self::sanitize_variant((string) ($parsed['variant'] ?? ''));

        if ($name === '') {
            return '';
        }

        if ($variant !== '') {
            return $library . ':' . $name . ':' . $variant;
        }

        return $library . ':' . $name;
    }

    public static function sentinel(string $library, string $name, string $variant = ''): string
    {
        $ref = self::ref([
            'library' => $library,
            'name' => $name,
            'variant' => $variant,
        ]);

        return $ref === '' ? '' : self::SENTINEL_PREFIX . $ref;
    }

    public static function is_sentinel(string $value): bool
    {
        return str_starts_with($value, self::SENTINEL_PREFIX);
    }

    /**
     * @param array<string, mixed> $atts
     * @return array{size: int, color: string, stroke: float, match_text: bool}
     */
    public static function style_from_atts(array $atts): array
    {
        $sizeRaw = $atts['icon_size'] ?? '';
        $matchText = $sizeRaw === '' || $sizeRaw === null || (int) $sizeRaw <= 0;
        $size = $matchText ? 0 : max(8, min(256, (int) $sizeRaw));
        $stroke = isset($atts['icon_stroke']) && is_numeric($atts['icon_stroke'])
            ? max(0.25, min(4, (float) $atts['icon_stroke']))
            : 2.0;

        return [
            'size' => $size,
            'color' => self::color((string) ($atts['icon_color'] ?? 'currentColor')),
            'stroke' => $stroke,
            'match_text' => $matchText,
        ];
    }

    public static function color(string $value): string
    {
        if (class_exists(\WPIcons\Icons\Svg::class)) {
            return \WPIcons\Icons\Svg::color($value);
        }

        $value = trim($value);

        return $value === '' ? 'currentColor' : $value;
    }

    /**
     * @param array{library: string, name: string, variant?: string} $parsed
     * @param array<string, mixed> $attributes
     */
    public static function svg(array $parsed, array $attributes = []): string
    {
        if ($parsed['name'] === '' || !function_exists('wpicons_get_icon')) {
            return '';
        }

        $ref = self::ref($parsed);
        if ($ref === '') {
            return '';
        }

        $args = $attributes;
        $variant = self::sanitize_variant((string) ($parsed['variant'] ?? ''));
        if ($variant !== '' && (!isset($args['variant']) || !is_string($args['variant']) || $args['variant'] === '')) {
            $args['variant'] = $variant;
        }

        return wpicons_get_icon($ref, $args);
    }
}
