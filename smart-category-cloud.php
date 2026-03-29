<?php
/**
 * Plugin Name: Smart Category Cloud
 * Description: WordPress plugin that generates a weighted category word cloud where topic size reflects both post count and recent activity.
 * Version: 1.1.0
 * Author: Eric Burns
 * Text Domain: wordpress-smart-category-cloud
 * Requires at least: 5.5
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SCC_VERSION', '1.1.0');
define('SCC_FILE', __FILE__);
define('SCC_DIR', plugin_dir_path(__FILE__));
define('SCC_URL', plugin_dir_url(__FILE__));
define('SCC_BASENAME', plugin_basename(__FILE__));

require_once SCC_DIR . 'includes/class-scc-plugin.php';

if (file_exists(SCC_DIR . 'private/private.php')) {
    require_once SCC_DIR . 'private/private.php';
}

SCC_Plugin::instance();
