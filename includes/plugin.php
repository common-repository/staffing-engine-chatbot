<?php
/**
 * Staffing Engine - Chatbot - Plugin class
 *
 * @link        https://staffingengine.ai/
 * @since       0.1.0
 *
 * @package     Staffing Engine - Chatbot
 * @author      Staffing Enginee
 * @copyright   (c) 2022 staffing Engine
 *
 * @license     https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 */

namespace StaffingEngine\Chatbot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin instance
 *
 * @final
 * @since 0.1.0
 */
final class Plugin {
	/**
	 * Slug
   *
   * The plugin's slug
	 * @since 0.2.0
	 *
	 * @var string
	 */
	const SLUG = 'staffing-engine-chatbot';

	/**
	 * Name
   *
   * The plugin's name, for rendering
	 *
	 * @since 0.2.0
	 * @var string
	 */
	const NAME = 'Staffing Engine - Chatbot';

  /**
   * Plugin version
   *
   * @since 0.2.0
   * @var string
   */
  public static $version = '';

  /**
   * Minimum supported php version
   *
   * @since 0.2.0
   * @var string
   */
  public static $min_supported_php = '';


	/**
	 * Directory
	 *
	 * @since 0.1.0
	 * @var string
	 */
	public static $directory = '';

	/**
	 * Variable Instance
	 *
	 * @since 0.1.0
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Class Constructor
	 *
	 * @since  0.1.0
	 */
	function __construct() {
    self::$version = SE_CHAT_VERSION;
    self::$min_supported_php = SE_CHAT_MIN_PHP;
    self::$directory = SE_CHAT_DIR;

    $this->load_textdomain();

    if ( is_admin() ) {
      Admin::get_instance();
    } else {
      Embed::get_instance();
    }

    /**
     * Staffing Engine Extension [Extension] Initialized
     *
     * Action to run immediately after a Staffing Engine Extension's Initialization Code Runs
     *
     * @since 0.1.0
     */
    do_action( self::SLUG . '-initialized' );

    /**
     * Staffing Engine Extension [Extension] Loaded
     *
     * Action to run immediately after a Staffing Engine Extension's Loading Code Runs (during WordPress's
     * plugins_loaded action).
     *
     * @since 0.1.0
     */
    do_action( self::SLUG . '-loaded' );
	}

	/**
	 * Instance Builder
	 *
	 * Singleton pattern means we create only one instance of the class.
	 *
	 * @since  0.1.0
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

  /**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.1.0
	 */
	public static function on_activate() {
    // Check that we actually want to run the activation hook
    if ( ! current_user_can( 'activate_plugins' ) ) {
      return;
    }

    // Activation tasks
  }

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.1.0
	 */
	public static function on_deactivate() {
    // Check that we actually want to run the deactivation hook
    if ( ! current_user_can( 'activate_plugins' ) ) {
      return;
    }

    // Deactivation tasks
  }

  /**
   * Fired when the plugin is uninstalled.
   *
   * see: https://developer.wordpress.org/plugins/the-basics/uninstall-methods/
   *
   * @since 0.3.0
   */
  public static function on_uninstall() {
    // Check that we actually want to run the uninstall hook
    if ( ! current_user_can( 'activate_plugins' ) ) {
      return;
    }

    // Uninstall tasks
  }

	/**
	 * Load the i18n text domain for the plugin
	 *
	 * @since  0.1.0
	 * @return void
	 */
	private function load_textdomain() {
		load_plugin_textdomain( self::SLUG, false, SE_CHAT_DIR_LANGUAGES );
	}

  /**
	 * Throw error on object clone.
	 *
	 * Singleton design pattern means is that there is a single object,
	 * and therefore, we don't want or allow the object to be cloned.
	 *
	 * @since  0.1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'No can do! You may not clone an instance of the plugin.', self::SLUG ), esc_attr( self::$version ) );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * Unserializing of the class is also forbidden in the singleton pattern.
	 *
	 * @since  0.1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'No can do! You may not unserialize an instance of the plugin.', self::SLUG ), esc_attr( self::$version ) );
	}
}
