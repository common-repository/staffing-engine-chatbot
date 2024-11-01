<?php
/**
 * @package     Staffing Engine - Chatbot
 * @author      Staffing Engine
 * @copyright   (c) 2024 staffing Engine
 * @license     GPL-3.0-or-later
 * @version     0.9.7
 *
 * @wordpress-plugin
 * Plugin Name: Staffing Engine - Chatbot
 * Plugin URI: https://staffingengine.ai/
 * Description: Provides the setting page and inserts the chatbot code into the site.
 * Author: staffingengine
 * Author URI: https://staffingengine.ai/
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 * Version: 0.9.7
 * Requires at least: 5.5
 * Requires PHP: 7.1
 * Domain Path: /languages
 * Text Domain: staffing-engine-chatbot
 *
 * Staffing Engine - Chatbot is free software: you can
 * redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of
 * the License, or any later version.
 *
 * Staffing Engine - Chatbot is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Staffing Engine - Chatbot. If not, see <http://www.gnu.org/licenses/>.
 */


namespace StaffingEngine\Chatbot;

global $wp_version;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SE_CHAT_VERSION', '0.9.7' );
define( 'CURRENT_WP_VERSION', $wp_version );

define( 'SE_CHAT_MIN_PHP', '7.1.0' );

// Set up a dev logging mode
define( 'SE_CHAT_DEV_MODE', getenv('SE_CHAT_DEV_MODE') );

// Basename
define( 'SE_CHAT_BASENAME', plugin_basename( __FILE__ ) );

// Dir globals
define( 'SE_CHAT_DIR', plugin_dir_path( __FILE__ ) );
define( 'SE_CHAT_DIR_INCLUDES', trailingslashit( path_join( SE_CHAT_DIR, 'includes' ) ) );
define( 'SE_CHAT_DIR_BIN', trailingslashit( path_join( SE_CHAT_DIR, 'bin' ) ) );
define( 'SE_CHAT_DIR_PUBLIC', trailingslashit( path_join( SE_CHAT_DIR, 'public' ) ) );
define( 'SE_CHAT_DIR_LANGUAGES', trailingslashit( path_join( SE_CHAT_DIR, 'languages' ) ) );
define( 'SE_CHAT_PUBLIC_ASSET_PATH', trailingslashit( path_join( plugin_dir_url( __FILE__ ), 'public' ) ) );

load_dependencies();

/**
 * Register hooks
 */
add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );
register_activation_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'on_activate' ) );
register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'on_deactivate' ) );
register_uninstall_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'on_uninstall' ) );


/**
 * Load Dependencies
 *
 * Replaces the old autorequire setup to be more explicit and top-level. Import everything here
 * and then `use` the classes when needed.
 *
 * Ideally we do this better in the future.
 *
 * @since 0.1.0
 *
 * @return void
 */
function load_dependencies() {
  // Includes
  include_once SE_CHAT_DIR_INCLUDES . 'plugin.php';
  include_once SE_CHAT_DIR_INCLUDES . 'embed.php';
  include_once SE_CHAT_DIR_INCLUDES . 'admin.php';
  include_once SE_CHAT_DIR_INCLUDES . 'utils.php';

  // Bin
  include_once SE_CHAT_DIR_BIN . 'rational-option-pages.php';
}

/**
 * Starts the Plugin
 *
 * @since 0.1.0
 */
function init() {
  Plugin::get_instance();
}
