<?php
/**
 * Staffing Engine - Chatbot - Admin class
 *
 * @link        https://staffing-engine-chatbot.ai/
 * @since       0.2.0
 *
 * @package     Staffing Engine - Chatbot
 * @author      Staffing Engine
 * @copyright   (c) 2022 staffing Engine
 *
 * @license     https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 */

namespace StaffingEngine\Chatbot;

use StaffingEngine\Chatbot\Bin\RationalOptionPages;
use \WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configure plugin in wp admin
 *
 * @since 0.2.0
 */
final class Admin {

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
	 * @since    0.3.0
	 * @var      array
	 */
  private static $options = array();

  /**
   * Pages registered to the site
   *
   * @since 0.3.0
   * @var array
   */
  private static $site_pages = array();

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
	 * @since 0.2.0
	 */
	public function __construct() {
    $this->register_hooks();

    self::$options = get_option( Plugin::SLUG . '_settings', array() );

    // Utils\debug_log('Chatbot Admin loaded with config:');
    // Utils\debug_log(print_r( self::$options, true ) );

    self::$site_pages = $this->get_site_pages();

    $this->build_settings_page(self::$site_pages);
  }

  /**
   * Register hooks
   *
   * Tell wordpress to enqueue scripts, set up admin pages, and modify the UI
   *
   * @since 0.2.0
   */
  private function register_hooks() {
    $should_notify = $this->should_notify_unsupported_php();
    if ( $should_notify ) {
      add_action( 'admin_notices', array( $this, 'notify_unsupported_php' ) );
    }

    add_action( 'add_option_' . Plugin::SLUG . '_settings', array( $this, 'set_default_options' ), 10, 2 );

    add_action( 'admin_enqueue_scripts', array( $this, 'load_setting_page_styles' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'load_setting_page_scripts' ) );

    add_filter( 'plugin_action_links_' . SE_CHAT_BASENAME , array( $this, 'add_plugin_links' ), 10, 1 );
  }

  /**
   * Load settings page styles
   *
   * @since 0.2.0
   */
  public function load_setting_page_styles($hook) {
    if ( 'settings_page_staffing_engine_chatbot' != $hook ) {
      return;
    }

    wp_enqueue_style( Plugin::SLUG . '-settings-styles' , path_join( SE_CHAT_PUBLIC_ASSET_PATH, 'css/se-chatbot-admin.css'), array(), null );
  }

  /**
   * Load settings page scripts
   *
   * @since 0.2.0
   */
  public function load_setting_page_scripts($hook) {
    if ( 'settings_page_staffing_engine_chatbot' != $hook ) {
      return;
    }

    wp_enqueue_script( Plugin::SLUG . '-settings-scripts' , path_join( SE_CHAT_PUBLIC_ASSET_PATH, 'js/se-chatbot-admin.js' ), array(), null, true );
  }

  /**
   * Add custom links on the plugins page
   *
   * @param  array  $links
   * @since  0.2.0
   * @return array
   */
  public function add_plugin_links( $links ) {
    $new_links = array(
      'settings' => sprintf( __( '<a href="%s">Settings</a>', Plugin::SLUG ), esc_url( admin_url( 'options-general.php?page=staffing_engine_chatbot' ) ) ),
    );

    return array_merge( $new_links, $links );
  }

  /**
   * When the plugin options are first saved we can add some default config values that can't be set via RationalOptionPages
   *
   * @since 0.3.0
   */
  public function set_default_options(string $option, array $value) {
    // Default values to pre-load on initial plugin install.
    $defaults = array(
      'invite_message' => 'Hey! I\'m the chatbot. 👋 I can help you find the right job 😊',
    );

    $updatedOptions = array_merge( $value, $defaults );

    update_option( Plugin::SLUG . '_settings', $updatedOptions );
  }

  /**
   * Build the settings page UI
   *
   * @since 0.2.0
   */
  private function build_settings_page(array $pages) {
    $page_choices = array();
    foreach ($pages as $page) {
      $value = $page['title'];
      $page_choices[$page['id']] = $value;
    }

    $pageConfig = array(
			Plugin::SLUG . '_settings'	=> array(
				'parent_slug' => 'options-general.php',
				'page_title'	=> __( Plugin::NAME, Plugin::SLUG ),
        'menu_title' => __( 'Staffing Engine', Plugin::SLUG ),
				'sections' => array(
					'config' => array(
						'title'	 => __( 'Configuration', Plugin::SLUG ),
						'fields' => array(
              'enable' => array(
                'id'      => 'enable',
                'type'    => 'checkbox',
                'title'   => __( 'Enable', Plugin::SLUG ),
                'checked' => $this->get_checkbox_value('enable'),
              ),
              'rendering' => array(
                'id'		=> 'rendering',
                'title'	=> __( 'Display on', Plugin::SLUG ),
                'text'  => __( 'Specify which pages the chatbot renders on', Plugin::SLUG ),
                'type'  => 'select',
                'value' => 'all',
                'choices'		=> array(
                  'all'      => __( 'All pages', Plugin::SLUG ),
                  'except'   => __( 'All pages except', Plugin::SLUG ),
                  'only'     => __( 'Only on selected pages', Plugin::SLUG ),
                ),
              ),
              // This field will be hidden by default, and js will render it when the appropriate 'rendering' setting is set
              'rendering_except' => array(
                'id'      => 'rendering_except',
                'type'		=> 'select',
								'title'		=> __( 'Exception List', Plugin::SLUG ),
                'text'    => __( 'FYI: Cmd/Ctrl + Click to select individual items, Shift + Click to select multiple items at once', Plugin::SLUG ),
								'value' 	=> array(),
                'choices' => $page_choices,
                'attributes' => array(
                  'multiple' => 'true',
                  'size' => '8'
                ),
              ),
              // This field will be hidden by default, and js will render it when the appropriate 'rendering' setting is set
              'rendering_only' => array(
                'id'      => 'rendering_only',
                'type'		=> 'select',
                'title'		=> __( 'Only List', Plugin::SLUG ),
                'text'    => __( 'FYI: Cmd/Ctrl + Click to select individual items, Shift + Click to select multiple items at once', Plugin::SLUG ),
                'value' 	=> array(),
                'choices' => $page_choices,
                'attributes' => array(
                  'multiple' => 'true',
                  'size' => '8'
                ),
              ),
              // 'rwcVersion' => array(
              //   'id'      => 'rwc_version',
              //   'title'   => __( 'RWC Version', Plugin::SLUG ),
              //   'text'    => __( 'Set the RWC version', Plugin::SLUG ),
              //   'type'    => 'radio',
              //   'value'   => 'old',
              //   'choices' => array(
              //     'old'	=> __( 'v4', Plugin::SLUG),
              //     'new'	=> __( 'v5 (Experimental)', Plugin::SLUG ),
              //   ),
              // ),
							'key' => array(
								'id'		  => 'key',
								'type'		=> 'text',
								'title'		=> __( 'Bot Key', Plugin::SLUG ),
                'text'    => __( 'Your unique chat instance key. Contact Staffing Engine support if you haven\'t received one.', Plugin::SLUG ),
								'default'	=> '',
							),
              // 'chatUrl' => array(
							// 	'id'		  => 'chat_url',
							// 	'type'		=> 'text',
							// 	'title'		=> __( 'Chat URL', Plugin::SLUG ),
              //   'text'    => __( 'Your unique chat URL. Contact Staffing Engine support if you haven\'t received one.', Plugin::SLUG ),
							// 	'default'	=> '',
							// ),
            ),
          ),
          'appearance' => array(
            'title'  => __( 'Widget', Plugin::SLUG),
            'text'   => __( 'Customize the chat widget\'s appearance', Plugin::SLUG ),
            'fields' => array(
              'position' => array(
                'id'		  => 'position',
                'title'	  => __( 'Position', Plugin::SLUG ),
                'text'    => __( 'Set the location of the bot on your site.', Plugin::SLUG ),
                'type'    => 'select',
                'value'   => 'bottom-right',
                'choices'	=> array(
                  'bottom-right' => __( 'Bottom Right', Plugin::SLUG ),
                  'bottom-left'  => __( 'Bottom Left', Plugin::SLUG ),
                  'top-right'    => __( 'Top Right', Plugin::SLUG ),
                  'top-left'     => __( 'Top Left', Plugin::SLUG ),
                ),
              ),
              'widgetColor'	=> array(
                'id'    => 'widget_color',
                'title'	=> __( 'Color', Plugin::SLUG ),
                'text'  => __( 'Set the background color of the chatbot embed.', Plugin::SLUG ),
                'type'	=> 'color',
                'value'	=> '#ffffff',
              ),
              'thumbLogo'	=> array(
                'id'    => 'thumb_logo',
                'title'	=> __( 'Logo', Plugin::SLUG ),
                'text'  => __( 'Upload a custom logo for your chatbot.', Plugin::SLUG ),
                'type'	=> 'media',
                'value'	=> '',
              ),
              'animation' => array(
                'id'      => 'animation',
                'title'	  => __( 'Animation', Plugin::SLUG ),
                'text'    => __( 'Choose an animation style to get the attention of site visitors.', Plugin::SLUG ),
                'type'	  => 'select',
                'value'	  => 'pulse',
                'choices'	=> array(
                  'pulse' => __( 'Pulse', Plugin::SLUG ),
                  'ring'  => __( 'Ring', Plugin::SLUG ),
                  'zoom'  => __( 'Zoom', Plugin::SLUG ),
                ),
              ),
              'thumbIconSize' => array(
                'id'      => 'thumb_icon_size',
                'title'	  => __( 'Thumb Icon Size', Plugin::SLUG ),
                'text'    => __( 'Adjust the size of the thumb icon within the chat activation button.', Plugin::SLUG ),
                'type'	  => 'select',
                'value'	  => 'default',
                'choices'	=> array(
                  'default' => __( 'Default', Plugin::SLUG ),
                  'large'   => __( 'Large', Plugin::SLUG ),
                  'max'     => __( 'Max', Plugin::SLUG ),
                ),
              )
            ),
          ),
          'invite_popup' => array(
            'title'  => __( ' Invitation', Plugin::SLUG ),
            'text'   => __( 'Show an informational popup next to the widget after a set duration', Plugin::SLUG ),
            'fields' => array(
              'inviteEnable' => array(
                'id'      => 'invite_enable',
                'type'    => 'checkbox',
                'title'   => __( 'Enable Invitation Popup', Plugin::SLUG ),
                'checked' => $this->get_checkbox_value('invite_enable'),
              ),
              'inviteMessage'	=> array(
                'id'    => 'invite_message',
                'title'	=> __( 'Message', Plugin::SLUG ),
                'type'	=> 'textarea',
                'class' => 'inviteMessage',
                'rows'  => 5,
                'text'	=> __( 'Text shown to promote your chatbot in the popup.', Plugin::SLUG ),
                'value'	=> '',
              ),
              'inviteButtonText' => array(
                'id'    => 'invite_button_text',
                'title'	=> __( 'Button text', Plugin::SLUG ),
                'text'  => __( 'Display a button with this text in the popup (optional).', Plugin::SLUG ),
                'type'  => 'text',
                'value' => '',
              ),
              'inviteImage' => array(
                'id'    => 'invite_image',
                'title'	=> __( 'Image', Plugin::SLUG ),
                'text'  => __( 'Display an image in the popup (optional).', Plugin::SLUG ),
                'type'  => 'media',
                'value' => '',
              ),
              'inviteShowCloseIcon' => array(
                'id'      => 'invite_show_close_icon',
                'title'	  => __( 'Closeable', Plugin::SLUG ),
                'text'    => __( 'Show the close icon on the popup. If not shown, the popup cannot be dismissed.', Plugin::SLUG  ),
                'type'    => 'radio',
                'value'   => 'yes',
                'choices'		=> array(
                  'yes'	=> __( 'Show', Plugin::SLUG),
                  'no'	=> __( 'Don\'t Show', Plugin::SLUG ),
                ),
              ),
              'inviteTimeout'	=> array(
                'id'         => 'invite_timeout',
                'title'	     => __( 'Appear Delay', Plugin::SLUG ),
                'text'       => __( 'Set how long until the popup appears (in seconds)', Plugin::SLUG ),
                'type'	     => 'number',
                'value'	     => 6,
                'attributes' => array(
                  'min' => 1,
                  'max' => 60,
                )
              ),
            ),
          ),
          'autoExpand' => array(
            'title'  => __( 'Auto Expand', Plugin::SLUG),
            'text'   => __( 'Automatically expand the chatbot sometime after the site has loaded', Plugin::SLUG ),
            'fields' => array(
              'autoExpandEnable' => array(
                'id'      => 'auto_expand_enable',
                'title'   => __( 'Enable Auto Expand', Plugin::SLUG ),
                'text'    => __( 'Auto Expand will automatically open the chatbot window after a set amount of time. Use "Enable, excluding small screens" to prevent auto expand from taking over the entire site on mobile devices.', Plugin::SLUG ),
                'type'    => 'select',
                'value'   => 'disable',
                'choices' => array(
                  'disable'           => __( 'Disable', Plugin::SLUG ),
                  'enable-not-mobile' => __( 'Enable, excluding small screens', Plugin::SLUG ),
                  'enable'            => __( 'Enable', Plugin::SLUG ),
                ),
              ),
              'autoExpandDelay'	=> array(
                'id'	       => 'auto_expand_delay',
                'title'	     => __( 'Auto Expand Delay', Plugin::SLUG ),
                'text'       => __( 'How long to wait (in seconds) before the chatbot window automatically opens.', Plugin::SLUG ),
                'type'	     => 'number',
                'value'	     => 5,
                'attributes' => array(
                  'min' => 1,
                  'max' => 60,
                )
              ),
            ),
          ),
          'behavior' => array(
            'title'  => __( 'Behavior', Plugin::SLUG),
            'text'   => __( 'Customize how the chatbot acts under certain conditions', Plugin::SLUG ),
            'fields' => array(
              'allowStartNewConversation' => array(
                'id'      => 'allow_start_new_conversation',
                'title'   => __( 'Allow starting a new conversation', Plugin::SLUG ),
                'text'    => __( 'Set whether site visitors can start a new conversation or are required to pick up from a previous conversation, if one exists.', Plugin::SLUG ),
                'type'    => 'radio',
                'value'   => 'yes',
                'choices' => array(
                  'yes'	=> __( 'Allow', Plugin::SLUG),
                  'no'	=> __( 'Don\'t Allow', Plugin::SLUG ),
                ),
              ),
              'allowWindowDrag' => array(
                'id'      => 'allow_window_drag',
                'title'   => __( 'Draggable', Plugin::SLUG ),
                'text'    => __( 'Set whether site visitors can move the chat window around the site.', Plugin::SLUG ),
                'type'    => 'radio',
                'value'   => 'no',
                'choices' => array(
                  'yes'	=> __( 'Allow', Plugin::SLUG),
                  'no'	=> __( 'Don\'t Allow', Plugin::SLUG ),
                ),
              ),
              'allowChangeWindowSize'	=> array(
                'id'      => 'allow_change_window_size',
                'title'   => __( 'Allow adjusting chat window size', Plugin::SLUG ),
                'text'    => __( 'Set whether site visitors can adjust the size of the chat window.', Plugin::SLUG ),
                'type'    => 'radio',
                'value'   => 'no',
                'choices' => array(
                  'yes'	=> __( 'Allow', Plugin::SLUG),
                  'no'	=> __( 'Don\'t Allow', Plugin::SLUG ),
                ),
              ),
            ),
          ),
          'advanced' => array(
            'title'  => __( 'Advanced', Plugin::SLUG),
            'text'   => __( '!!! These settings should be changed with caution and may break the plugin! Please reach out to staffing engine support before making changes to advanced settings.', Plugin::SLUG ),
            'fields' => array(
              'additionalStyles' => array(
                'id'      => 'advanced_additional_styles',
                'title'   => __( 'Additional Styles', Plugin::SLUG ),
                'text'    => __( 'Supply a url to a css file which will be included alongside RWC styles. Styles included this way do not apply to anything inside of the chat window <code>' . esc_html( '<iframe>' ) . '</code>.', Plugin::SLUG ),
                'type'  => 'text',
                'value' => '',
              ),
              'overrideRwcCss' => array(
                'id'      => 'advanced_override_rwc_css',
                'title'   => __( 'Override CSS', Plugin::SLUG ),
                'text'    => __( 'Supply a url to a css file which will override <code>richWebChat.css</code>. An incompatible file can break the chatbot.', Plugin::SLUG ),
                'type'  => 'text',
                'value' => '',
              ),
              'overrideRwcJs' => array(
                'id'      => 'advanced_override_rwc_js',
                'title'   => __( 'Override JS', Plugin::SLUG ),
                'text'    => __( 'Supply a url to a js file which will override <code>richWebChat.umd.min.js</code>. An incompatible file can break the chatbot.', Plugin::SLUG ),
                'type'  => 'text',
                'value' => '',
              ),
            ),
          ),
				),
			),
		);

    new RationalOptionPages( $pageConfig );
  }

  /**
   * Check if we should notify about an unsupported PHP version
   *
   * @since 0.2.0
   * @return boolean
   */
  private function should_notify_unsupported_php() {
    return version_compare( PHP_VERSION, '7.1.0', '<=' );
  }

  /**
   * Admin Notice if Unsupported PHP Version. PHP Versions below this might have issues running the plugin.
   *
   * @since 0.2.0
   */
  public function notify_unsupported_php() {
    $html = '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>';

    $message = __( '%s requires PHP Version %s or better. Contact your web host to upgrade so you can use the features offered by this plugin.', Plugin::SLUG );

    printf( $html, esc_html( sprintf( $message, Plugin::NAME, '7.1.0' ) ) );
  }

  /**
   * Get the value of a checkbox option, returning false if no value is stored
   *
   * Checkboxes return `'on'` when checked, and `false` when unchecked.
   *
   * @since 0.3.0
   * @return boolean
   */
  private function get_checkbox_value($key) {
    $stored_value = array_key_exists( $key, self::$options ) ? self::$options[$key] : false;

    if ( $stored_value == 'on' ) {
      return true;
    }

    return false;
  }

  /**
   * Get the parent of a page
   *
   * @since 0.9.0
   * @return array|null
   */
  private function get_parent_page($pages, $parentId) {
    $parent_page = null;
    foreach ($pages as $page) {
      if ($page['id'] === $parentId) {
        $parent_page = $page;
        break;
      }
    }
    unset($page);
    return $parent_page;
  }

  /**
   * Format page titles to show pages that are children and grandchildren
   *
   * @since 0.9.0
   * @return array
   */
  private function format_page_title(&$page, int $index, array $pages) {
    $parent_page = $this->get_parent_page($pages, $page['parent']);

    if ($parent_page == null) {
      return $page;
    }

    if ($parent_page['parent']) {
      $page['title'] = ' - - ' . $page['title'];
      return $page;
    }

    $page['title'] = ' - ' . $page['title'];
    return $page;
  }

  /**
   * Get pages from the site
   *
   * @since 0.3.0
   * @return array
   */
  private function get_site_pages() {
    $pages = get_pages( array(
      'child_of' => 0,
      'post_status' => array( 'publish', 'draft' ),
    ) );

    // Build simpler array representations
    $map_page_data = function(WP_Post $page) {
      return array(
        'id' => $page->ID,
        'title' => $page->post_title,
        'status' => $page->post_status,
        'guid' => $page->guid,
        'parent' => $page->post_parent
      );
    };
    $mapped_pages = array_map($map_page_data, $pages );

    // Add indication of parents and grandparents to the page title
    array_walk($mapped_pages, array( $this, 'format_page_title' ), $mapped_pages );

    return $mapped_pages;
  }
}
