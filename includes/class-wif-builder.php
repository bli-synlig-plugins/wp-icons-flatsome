<?php

if (!defined('ABSPATH')) {
    exit;
}

class WIF_Builder
{
    public static function init(): void
    {
        add_action('ux_builder_setup', array(__CLASS__, 'register_option_type'));
        add_action('ux_builder_enqueue_scripts', array(__CLASS__, 'enqueue'), 20);
    }

    public static function register_option_type(): void
    {
        if (!function_exists('add_ux_builder_option_type')) {
            return;
        }

        add_ux_builder_option_type('wpicons');
    }

    public static function enqueue(string $context): void
    {
        if ($context === 'editor') {
            wp_enqueue_script(
                'wpicons-flatsome-ux-option',
                WIF_PLUGIN_URL . 'assets/js/ux-option-wpicons.js',
                array('ux-builder-core', 'wpicons-tinymce-picker'),
                WIF_VERSION,
                true
            );

            wp_enqueue_style(
                'wpicons-flatsome-ux-option',
                WIF_PLUGIN_URL . 'assets/css/ux-option-wpicons.css',
                array('ux-builder-core', 'wp-components', 'wpicons_tinymce_picker_style'),
                WIF_VERSION
            );

            return;
        }

        if ($context !== 'content') {
            return;
        }

        wp_enqueue_style(
            'wpicons-flatsome',
            WIF_PLUGIN_URL . 'assets/css/frontend.css',
            array('ux-builder-core', 'wpicons'),
            WIF_VERSION
        );

        wp_enqueue_script(
            'wpicons-flatsome-ux-canvas',
            WIF_PLUGIN_URL . 'assets/js/ux-canvas-icon.js',
            array('ux-builder-core'),
            WIF_VERSION,
            true
        );

        wp_localize_script(
            'wpicons-flatsome-ux-canvas',
            'WIFCanvas',
            array(
                'restUrl' => rest_url('wpicons/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
            )
        );
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $insert
     * @return array<string, mixed>
     */
    public static function insert_options(array $options, string $before, array $insert): array
    {
        if (!array_key_exists($before, $options)) {
            return $insert + $options;
        }

        $next = [];
        foreach ($options as $key => $option) {
            if ($key === $before) {
                foreach ($insert as $insert_key => $insert_option) {
                    $next[$insert_key] = $insert_option;
                }
            }
            $next[$key] = $option;
        }

        return $next;
    }

    public static function and_condition(string $existing, string $extra): string
    {
        $existing = trim($existing);
        if ($existing === '') {
            return $extra;
        }

        return $existing . ' && ' . $extra;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function group_options(array $data, string $group = ''): array
    {
        if ($group === '') {
            return isset($data['options']) && is_array($data['options']) ? $data['options'] : [];
        }

        if (
            !isset($data['options'][$group]['options'])
            || !is_array($data['options'][$group]['options'])
        ) {
            return [];
        }

        return $data['options'][$group]['options'];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function set_group_options(array $data, array $options, string $group = ''): array
    {
        if ($group === '') {
            $data['options'] = $options;

            return $data;
        }

        $data['options'][$group]['options'] = $options;

        return $data;
    }
}
