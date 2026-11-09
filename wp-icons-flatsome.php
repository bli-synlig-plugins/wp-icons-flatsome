<?php
/**
 * Plugin Name:         WP Icons for Flatsome
 * Plugin URI:          https://kodekassa.no/plugins/wp-icons-flatsome
 * Description:         Use WP Icons in Flatsome Icon Box, Button and Menu instead of the built-in image upload or icon set.
 * Version:             1.2.0
 * Requires at least:   6.6
 * Requires PHP:        8.1
 * Author:              Benjamin
 * License:             GPLv2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         wpicons-flatsome
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WIF_VERSION', '1.2.0');
define('WIF_PLUGIN_FILE', __FILE__);
define('WIF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WIF_PLUGIN_URL', plugin_dir_url(__FILE__));

define('WPICONS_FLATSOME_VERSION', WIF_VERSION);
define('WPICONS_FLATSOME_FILE', WIF_PLUGIN_FILE);
define('WPICONS_FLATSOME_PATH', WIF_PLUGIN_DIR);
define('WPICONS_FLATSOME_URL', WIF_PLUGIN_URL);

require_once WIF_PLUGIN_DIR . 'includes/class-wif-plugin.php';
require_once WIF_PLUGIN_DIR . 'includes/class-wif-icons.php';
require_once WIF_PLUGIN_DIR . 'includes/class-wif-builder.php';
require_once WIF_PLUGIN_DIR . 'includes/elements/class-wif-featured-box.php';
require_once WIF_PLUGIN_DIR . 'includes/elements/class-wif-button.php';
require_once WIF_PLUGIN_DIR . 'includes/elements/class-wif-menu-link.php';

add_action('plugins_loaded', array('WIF_Plugin', 'init'), 20);
