<?php
/**
 * Staffing Engine - Chatbot - Embed class
 *
 * @link            https://staffingengine.ai/
 * @since           0.1.0
 *
 * @package         Staffing Engine Chatbot
 * @subpackage      Core
 * @author          Staffing Engine
 * @copyright       (c) 2022 Staffing Engine
 *
 * @license         https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 */

namespace StaffingEngine\Chatbot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render chatbot embed
 *
 * @since 0.1.0
 */
final class Embed {

  /**
	 * Instance of this class.
	 *
	 * @since    0.2.0
	 * @var      object
	 */
	protected static $instance = null;

  /**
	 * Configuration options for the plugin
	 *
	 * @since    0.2.0
	 * @var      array
	 */
  private static $options = array();

  /**
	 * Configuration options for the chatbot
	 *
	 * @since    0.2.0
	 * @var      array
	 */
  private static $final_options = array();

  /**
   * Our custom settings for the chatbot
   *
   * @since    0.9.0
   * @var      array
   */
  private static $settings = array();

  /**
	 * Whether the chatbot is enabled for the page
	 *
	 * @since    0.3.0
	 * @var      boolean
	 */
  private static $enable = false;

  /**
   * The page id the embed is running on
   *
   * @since    0.5.0
   */
  private static $this_page = null;

  /**
   * The RWC version
   *
   * @since 0.10.0
   * @var   string
   */
  private static $rwc_version = '4';

  /**
	 * Instance Builder
	 *
	 * Singleton pattern means we create only one instance of the class.
	 *
   * @since 0.2.0
   * @return object
   */
  public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
    }

    return self::$instance;
  }

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
    self::$options = get_option( Plugin::SLUG . '_settings', array() );
    self::$rwc_version = $this->get_rwc_version();
    self::$final_options = $this->get_options_with_defaults();
    self::$settings = $this->get_settings_with_defaults();

    $this->register_lifecycle_hooks();
    $this->register_script_hooks();
  }

  /**
   * Register lifecycle hooks
   *
   * @since 0.6.0
   */
  private function register_lifecycle_hooks() {
    add_action( 'wp', array( $this, 'get_page_info' ) );
    add_action( 'wp', array( $this, 'get_is_enabled' ) );
  }

  /**
   * Register hooks for loading scripts
   *
   * @since 0.2.0
   */
  private function register_script_hooks() {
    add_filter( 'script_loader_tag', array( $this, 'add_async_attribute_to_script' ), 10, 2 );
    add_filter( 'style_loader_tag', array( $this, 'defer_style_tag' ), 10, 4 );

    add_action( 'wp_footer', array( $this, 'embed_template' ) );
    add_action( 'wp_print_footer_scripts', array( $this, 'print_scripts' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
  }

	/**
	 * Insert the html template to embed the chatbot
	 *
	 * @since 0.1.0
	 */
	public function embed_template() {
    if (! self::$enable) {
      return;
    }
		echo'<div id="staffing-engine-chatbot"></div>';
	}

	/**
	 * Print Scripts
	 *
	 * @since 0.1.0
	 */
	public function print_scripts() {
    if (! self::$enable) {
      return;
    }
?>
<script>
  /**
   * Staffing Engine Chatbot - <?php echo SE_CHAT_VERSION ?>
   */

  const SE_RWC_VERSION = <?php echo json_encode( self::$rwc_version ); ?>;
  const SE_RWC_SCRIPT_LOCATION = "<?php echo $this->get_rwc_script_location() ?>";
  const SE_RWC_OPTIONS = <?php echo json_encode( self::$final_options ); ?>;
  const SE_RWC_SETTINGS = <?php echo json_encode( self::$settings ); ?>;
</script>
<?php
	}

	/**
	 * Enqueue Scripts
	 *
	 * @since 0.1.0
	 */
	public function enqueue_scripts() {
    if (! self::$enable) {
      return;
    }

    wp_enqueue_script(
      Plugin::SLUG . '-chatbot-async',
      path_join( SE_CHAT_PUBLIC_ASSET_PATH, 'js/se-chatbot.js' ),
      array(),
      $this->get_cache_version( path_join( SE_CHAT_DIR_PUBLIC, 'js/se-chatbot.js') ),
      true
    );
	}
	/**
	 * Enqueue Styles
	 *
	 * @since 0.1.0
	 */
	public function enqueue_styles() {
    if (! self::$enable) {
      return;
    }

    if (self::$rwc_version == 'new') {
		  wp_enqueue_style(
        Plugin::SLUG . '-rwc-defer',
        'https://chat.staffingengine.onereach.ai/lib/richWebChat.css',
        array(),
        null
      );
    } else if (self::$options['advanced_override_rwc_css']) {
      wp_enqueue_style(
        Plugin::SLUG . '-rwc-override',
        esc_attr( self::$options['advanced_override_rwc_css'] ),
        array(),
        null
      );
    } else {
		  wp_enqueue_style(
        Plugin::SLUG . '-rwc-defer',
        path_join( SE_CHAT_PUBLIC_ASSET_PATH, 'css/richWebChat.css'),
        array(),
        $this->get_cache_version( path_join( SE_CHAT_DIR_PUBLIC, 'css/richWebChat.css') )
      );
    }

    wp_enqueue_style(
      Plugin::SLUG . '-chatbot-defer',
      path_join( SE_CHAT_PUBLIC_ASSET_PATH, 'css/se-chatbot.css'),
      array(),
      $this->get_cache_version( path_join( SE_CHAT_DIR_PUBLIC, 'css/se-chatbot.css') )
    );

    if (self::$options['advanced_additional_styles']) {
      wp_enqueue_style( Plugin::SLUG . '-additional-styles', esc_attr( self::$options['advanced_additional_styles'] ), array(), null );
    }
	}

  /**
   * Get which RWC version to use
   *
   * @since 0.10.0
   * @return string
   */
  private function get_rwc_version() {
    return array_key_exists( 'rwc_version', self::$options ) ? esc_attr( self::$options['rwc_version'] ) : 'old';
  }

  /**
   * Parse user-defined settings and merge with sensible defaults
   *
   * @since 0.2.0
   * @return array
   */
  private function get_options_with_defaults() {
    if (self::$rwc_version == 'new') {
      return [
        "chatUrl" => array_key_exists( 'chat_url', self::$options ) ? esc_attr( self::$options['chat_url'] ) : '',
        "appearance" => array(
          "blurredBackground" => false,
          "resizeable" => true,
          "draggable" => true,
          "saveChatSize" => true,
          "preloadInBackground" => true, // TODO: turn back on or make a setting
        ),
        "widget" => array(
          "position" => array_key_exists( 'position', self::$options ) ? self::$options['position'] : 'bottom-right',
          "animation" => array_key_exists( 'animation', self::$options ) ? esc_attr( self::$options['animation'] ) : 'pulse',
          "color" => array_key_exists( 'widget_color', self::$options ) ? esc_attr( self::$options['widget_color'] ) : '#ffffff',
          "logoUrl" => $this->get_thumb_logo(),
          "revealDelay" => 0,
          "openDelay" => 0,
        ),
        "invitation" => array(
          "closeable" => array_key_exists( 'invite_show_close_icon', self::$options ) ? $this->parse_radio_setting( esc_attr( self::$options['invite_show_close_icon'] ), true ) : true,
          "imageUrl" => array_key_exists( 'invite_image', self::$options ) ? esc_attr( self::$options['invite_image'] ) : '',
          "message" => $this->get_invite_message(),
          "buttonLabel" => array_key_exists( 'invite_button_text', self::$options ) ? esc_attr( self::$options['invite_button_text'] )  : '',
          "appearDelay" => array_key_exists( 'invite_timeout', self::$options ) ? $this->parse_ms_setting( esc_attr( self::$options['invite_timeout'] ) . '000' ) : 5000,
        ),
      ];
    }

    return [
      "chatUrl" => 'https://v1.chat.staffingengine.onereach.ai/' . ( array_key_exists( 'key', self::$options ) ? esc_attr( self::$options['key'] ) : '' ) . '?loader=auto',
      "position" => array_key_exists( 'position', self::$options ) ? self::$options['position'] : 'bottom-right',
      "thumbLogo" => $this->get_thumb_logo(),
      "widgetColor" => array_key_exists( 'widget_color', self::$options ) ? esc_attr( self::$options['widget_color'] ) : '#c4c4c4',
      "animation" => array_key_exists( 'animation', self::$options ) ? esc_attr( self::$options['animation'] ) : 'pulse',
      "inviteMessage" => $this->get_invite_message(),
      "inviteButton" => array_key_exists( 'invite_button_text', self::$options ) ? esc_attr( self::$options['invite_button_text'] )  : '',
      "inviteImage" => array_key_exists( 'invite_image', self::$options ) ? esc_attr( self::$options['invite_image'] ) : '',
      "inviteTimeout" => array_key_exists( 'invite_timeout', self::$options ) ? $this->parse_ms_setting( esc_attr( self::$options['invite_timeout'] ) . '000' ) : 5000,
      "showCloseIcon" => array_key_exists( 'invite_show_close_icon', self::$options ) ? $this->parse_radio_setting( esc_attr( self::$options['invite_show_close_icon'] ), true ) : true,
      "autoExpandDelay" => 0,
      "allowStartNewConversation" => array_key_exists( 'allow_start_new_conversation', self::$options ) ? $this->parse_radio_setting( esc_attr( self::$options['allow_start_new_conversation'] ), true ) : true,
      "allowChangeChatWindowSize" => array_key_exists( 'allow_change_window_size', self::$options ) ? $this->parse_radio_setting( esc_attr( self::$options['allow_change_window_size'] ), true ) : true,
      "allowDrag" => array_key_exists( 'allow_window_drag', self::$options ) ? $this->parse_radio_setting( esc_attr( self::$options['allow_window_drag'] ), true ) : true,
      "appearance" => array(
        "blurredBackground" => false,
        "preloadInBackground" => true,
      ),
    ];
  }

  /**
   * Versioning for static assets based on extension and wordpress versions as well as last file change time if a file path is provided.
   *
   * @since 0.9.2
   */
  private function get_cache_version(string $file = '') {
    $ext_version = ( defined( 'SE_CHAT_VERSION' ) ) ? SE_CHAT_VERSION : '';
    $current_wp_version = ( defined ( 'CURRENT_WP_VERSION' ) ) ? CURRENT_WP_VERSION : '';

    $result = $ext_version . '.' . $current_wp_version;

    if ( empty( $file )) {
      return $result;
    }

    return $result . '.' . filemtime( $file );
  }

  /**
   * Parse custom settings and merge with sensible defaults
   *
   * @since 0.9.0
   * @return array
   */
  private function get_settings_with_defaults() {
    return [
      "autoExpandEnable" => array_key_exists( 'auto_expand_enable', self::$options ) ? self::$options['auto_expand_enable'] : 'disable',
      "autoExpandDelay" => array_key_exists( 'auto_expand_delay', self::$options ) ? $this->parse_ms_setting( esc_attr( self::$options['auto_expand_delay'] ) . '000' ) : 0,
      "thumbIconSize" => array_key_exists( 'thumb_icon_size', self::$options ) ? self::$options['thumb_icon_size'] : 'default',
    ];
  }

  /**
   *
   * @since 0.9.0
   * @return string
   */
  private function get_rwc_script_location() {
    if ( self::$rwc_version == 'new') {
      return 'https://chat.staffingengine.onereach.ai/lib/richWebChat.umd.min.js';
    }

    if ( self::$options['advanced_override_rwc_js']) {
      return esc_attr( self::$options['advanced_override_rwc_js'] );
    }

    return path_join( SE_CHAT_PUBLIC_ASSET_PATH, 'js/richWebChat.umd.min.js' );
  }

  /**
   * Check whether the invite message is turned on, and return it if so
   *
   * returning an empty string tells the chatbot to disable the invite
   *
   * @since 0.3.0
   * @return string
   */
  private function get_invite_message() {
    $enabled = array_key_exists( 'invite_enable', self::$options ) ? esc_attr( self::$options['invite_enable'] ) : true;
    $message = array_key_exists( 'invite_message', self::$options ) ? esc_attr( self::$options['invite_message'] ) : '';

    return $this->parse_checkbox_setting( $enabled, true ) ? $message : '';
  }

  /**
   * Return the user defined logo if configured, otherwise use the default
   *
   * @since 0.3.0
   * @return string
   */
  private function get_thumb_logo() {
    $userDefinedLogo = array_key_exists( 'thumb_logo', self::$options ) ? esc_attr( self::$options['thumb_logo'] ) : '';

    if ($userDefinedLogo == '') {
      return path_join( SE_CHAT_PUBLIC_ASSET_PATH, 'assets/staffing-engine-chatbot_default.png' );
    }

    return $userDefinedLogo;
  }

  /**
   * Parse a setting as ms
   *
   * @since 0.1.0
   */
  private function parse_ms_setting($val) {
    if (is_numeric($val)) {
      return $val + 0;
    }
    return 0;
  }

  /**
   * Parse a boolean value. Usually these are radios configured to store 'yes' or 'no'
   *
   * @since 0.1.0
   */
  private function parse_radio_setting(string $val, bool $default) {
    if ($val == 'yes') {
      return true;
    } else if ($val == 'no') {
      return false;
    }

    return $default;
  }

  /**
   * Parse a checkbox value. Usually these are `'on'` for true, and `false` for... false
   *
   * @since 0.3.0
   */
  private function parse_checkbox_setting($val, bool $default) {
    if ($val == 'on') {
      return true;
    } else {
      return false;
    }

    return $default;
  }

  /**
   * Get the current page
   *
   * Sorta funky, but returns either a string for special pages, or an id for a page with an id
   *
   * @since 0.5.0
   */
  public function get_page_info() {
    global $post;
    if ( $post ) {
      self::$this_page = $post->ID;
    }
  }

  /**
   * Check if the chatbot is enabled for this page
   *
   * @since 0.3.0
   * @return boolean
   */
  public function get_is_enabled() {
    $enabled_globally = array_key_exists( 'enable', self::$options ) ? $this->parse_checkbox_setting( self::$options['enable'], false ) : false;

    if (! $enabled_globally) {
      self::$enable = false;
      return;
    }

    $rendering = array_key_exists( 'rendering', self::$options ) ? self::$options['rendering'] : 'all';

    if ( $rendering == 'except' ) {
      self::$enable = $this->render_if_not_exception();
      return;
    }

    if ( $rendering == 'only' ) {
      self::$enable = $this->render_if_is_only();
      return;
    }

    self::$enable = true;
  }

  /**
   * If the page is in the except list, return false
   *
   * @since 0.5.0
   * @return boolean
   */
  private function render_if_not_exception() {
    $except_list = array_key_exists( 'rendering_except', self::$options ) ? self::$options['rendering_except'] : null;

    // If no list, lets render
    if ( $except_list == null) {
      return true;
    }

    $in_exceptions = in_array( self::$this_page, $except_list );

    if ( $in_exceptions ) {
      return false;
    }

    return true;
  }

  /**
   * If the page is in the only list, return true.
   *
   * @since 0.5.0
   * @return boolean
   */
  private function render_if_is_only() {
    $only_list = array_key_exists( 'rendering_only', self::$options ) ? self::$options['rendering_only'] : null;

    // If no list, lets not render
    if ( $only_list == null) {
      return false;
    }

    $in_only = in_array( self::$this_page, $only_list );

    if ( $in_only ) {
      return true;
    }

    return false;
  }

  /**
   * Add async attributes to script enqueues
   *
   * Largely derived from: https://stackoverflow.com/a/40553706
   *
   * @since 0.6.0
   * @param  String  $tag     The original enqueued <script src="...> tag
   * @param  String  $handle  The registered unique name of the script
   * @return String
  */
  public function add_async_attribute_to_script($tag, $handle) {
    // if the unique handle/name of the registered script has 'async' in it
    if (strpos($handle, 'async') !== false) {
      return str_replace( '<script ', '<script async ', $tag );
    }

    return $tag;
  }

  /**
   * Change style tags with `-defer` to be deferred
   *
   * Why? https://web.dev/defer-non-critical-css/#optimize
   *
   * @since 0.6.0
   * @param  String  $tag     The original enqueued <style src="...> tag
   * @param  String  $handle  The registered unique name of the style
   * @param  String  $href    The href="" value
   * @param  String  $media   The media="" value
   * @return String
  */
  public function defer_style_tag( $tag, $handle, $href, $media ) {
    if ( strpos($handle, 'defer') !== false ) {
      return '<link rel="preload" href="' . $href . '" as="style" id="' . $handle . '" onload="this.onload=null;this.rel=\'stylesheet\'">'
             . '<noscript><link rel="stylesheet" href="' . $href . '" id="' . $handle . '-css"></noscript>';
    }

    return $tag;
  }
}
