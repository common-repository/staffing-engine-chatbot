<?php
/**
 * Staffing Engine - Chatbot - Utils class
 *
 * @link            https://staffingengine.ai/
 * @since           0.2.0
 *
 * @package         Staffing Engine Chatbot
 * @subpackage      Core
 * @author          Staffing Engine
 * @copyright       (c) 2022 Staffing Engine
 *
 * @license         https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 */

namespace StaffingEngine\Chatbot\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if the plugin is running in dev mode, for logging
 *
 * @since 0.1.0
 */
function is_dev() {
  return ( SE_CHAT_DEV_MODE == true );
}

/**
 * Print to debug log when in dev mode
 *
 * @since 0.1.0
 */
function debug_log($message) {
  if ( is_dev() ) {
    error_log('[SE Chat] ' . $message);
  }
}

// /**
//  * Start the debug log on init when in dev mode
//  *
//  * @since 0.1.0
//  */
// function start_debug_log() {
//   if ( is_dev() ) {
//     debug_log(' --- Dev Mode Enabled ---');
//     // debug_log(' --- Globals ---');
//     // debug_log('SE_CHAT_DIR' . SE_CHAT_DIR );
//     // debug_log('SE_CHAT_DIR_INCLUDES' . SE_CHAT_DIR_INCLUDES );
//     // debug_log('SE_CHAT_DIR_BIN' . SE_CHAT_DIR_BIN );
//     // debug_log('SE_CHAT_DIR_PUBLIC' . SE_CHAT_DIR_PUBLIC );
//     // debug_log('SE_CHAT_DIR_LANGUAGES' . SE_CHAT_DIR_LANGUAGES );
//     // debug_log('SE_CHAT_PUBLIC_ASSET_PATH' . SE_CHAT_PUBLIC_ASSET_PATH );
//   }
// }
