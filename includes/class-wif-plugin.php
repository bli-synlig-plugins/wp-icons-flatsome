<?php

if (!defined('ABSPATH')) {
    exit;
}

class WIF_Plugin
{
    public static function init(): void
    {
        add_action('admin_notices', array(__CLASS__, 'admin_notices'));

        if (!self::has_icons()) {
            return;
        }

        WIF_Builder::init();
        WIF_Icons::init();
        WIF_Featured_Box::init();
        WIF_Button::init();
        WIF_Menu_Link::init();

        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_front'), 20);
    }

    public static function has_icons(): bool
    {
        return function_exists('wpicons_get_icon');
    }

    public static function has_flatsome(): bool
    {
        return class_exists('Flatsome_Default', false) || function_exists('flatsome_option');
    }

    public static function admin_notices(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        if (!self::has_icons()) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('WP Icons for Flatsome requires the WP Icons plugin to be active.', 'wpicons-flatsome');
            echo '</p></div>';
            return;
        }

        if (!self::has_flatsome()) {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('WP Icons for Flatsome is active, but Flatsome was not detected. Icon Box, Button and Menu integration stays disabled until Flatsome is used.', 'wpicons-flatsome');
            echo '</p></div>';
        }
    }

    public static function enqueue_front(): void
    {
        wp_enqueue_style(
            'wpicons-flatsome',
            WIF_PLUGIN_URL . 'assets/css/frontend.css',
            array('wpicons'),
            WIF_VERSION
        );
    }
}
