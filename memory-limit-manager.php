<?php
/**
 * Plugin Name: Memory Limit Manager
 * Plugin URI: https://muhammadshakeel.com/memory-limit-manager/
 * Description: Easily manage memory limits (WP_MEMORY_LIMIT and WP_MAX_MEMORY_LIMIT) through a beautiful admin interface with advanced conflict detection.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.9
 * Author: Muhammad Shakeel
 * Author URI: https://muhammadshakeel.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: memory-limit-manager
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check minimum requirements
function memory_limit_manager_check_requirements() {
	$wp_version = get_bloginfo( 'version' );
	$php_version = phpversion();
	
	$errors = array();
	
	// Check WordPress version
	if ( version_compare( $wp_version, '6.0', '<' ) ) {
		$errors[] = sprintf(
			/* translators: %s: Current version */
			__( 'Memory Limit Manager requires version 6.0 or higher. You are running version %s.', 'memory-limit-manager' ),
			$wp_version
		);
	}
	
	// Check PHP version
	if ( version_compare( $php_version, '7.4', '<' ) ) {
		$errors[] = sprintf(
			/* translators: %s: Current PHP version */
			__( 'Memory Limit Manager requires PHP 7.4 or higher. You are running PHP %s.', 'memory-limit-manager' ),
			$php_version
		);
	}
	
	if ( ! empty( $errors ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			'<h1>' . esc_html__( 'Plugin Activation Error', 'memory-limit-manager' ) . '</h1>' .
			'<p>' . implode( '</p><p>', array_map( 'esc_html', $errors ) ) . '</p>' .
			'<p><a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( '&larr; Back to Plugins', 'memory-limit-manager' ) . '</a></p>',
			esc_html__( 'Plugin Activation Error', 'memory-limit-manager' ),
			array( 'back_link' => true )
		);
	}
}
register_activation_hook( __FILE__, 'memory_limit_manager_check_requirements' );

// Define plugin constants
define( 'MEMORY_MANAGER_WP_VERSION', '1.0.0' );
define( 'MEMORY_MANAGER_WP_PLUGIN_FILE', __FILE__ );
define( 'MEMORY_MANAGER_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEMORY_MANAGER_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class
 */
class Memory_Manager_WP {
	
	/**
	 * Instance of this class
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}
	
	/**
	 * Load required files
	 */
	private function load_dependencies() {
		require_once MEMORY_MANAGER_WP_PLUGIN_DIR . 'includes/class-config-handler.php';
		require_once MEMORY_MANAGER_WP_PLUGIN_DIR . 'includes/class-admin.php';
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		
		// Initialize admin
		if ( is_admin() ) {
			Memory_Manager_WP_Admin::get_instance();
		}
	}
	
	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin page
		if ( 'settings_page_memory-limit-manager' !== $hook ) {
			return;
		}
		
		wp_enqueue_style(
			'memory-limit-manager-admin',
			MEMORY_MANAGER_WP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			MEMORY_MANAGER_WP_VERSION
		);
		
		wp_enqueue_script(
			'memory-limit-manager-admin',
			MEMORY_MANAGER_WP_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			MEMORY_MANAGER_WP_VERSION,
			true
		);
		
		wp_localize_script(
			'memory-limit-manager-admin',
			'memoryManagerWP',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'memory_limit_manager_nonce' ),
			)
		);
	}
}

/**
 * Initialize the plugin
 */
function memory_limit_manager_init() {
	return Memory_Manager_WP::get_instance();
}

// Start the plugin
memory_limit_manager_init();
