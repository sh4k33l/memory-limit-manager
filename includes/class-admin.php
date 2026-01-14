<?php
/**
 * Admin functionality
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class
 */
class Memory_Manager_WP_Admin {
	
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_post_memory_limit_manager_update', array( $this, 'handle_form_submission' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MEMORY_MANAGER_WP_PLUGIN_FILE ), array( $this, 'add_plugin_action_links' ) );
	}
	
	/**
	 * Add Settings link on plugins page
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=memory-limit-manager' ) ) . '">' . esc_html__( 'Settings', 'memory-limit-manager' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
	
	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Memory Limit Manager', 'memory-limit-manager' ),
			__( 'Memory Manager', 'memory-limit-manager' ),
			'manage_options',
			'memory-limit-manager',
			array( $this, 'render_admin_page' )
		);
	}
	
	/**
	 * Handle form submission via admin-post.php
	 */
	public function handle_form_submission() {
		// Check nonce
		if ( ! isset( $_POST['memory_limit_manager_nonce'] ) || 
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['memory_limit_manager_nonce'] ) ), 'memory_limit_manager_save' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'memory-limit-manager' ), esc_html__( 'Security Error', 'memory-limit-manager' ), array( 'back_link' => true ) );
		}
		
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'memory-limit-manager' ), esc_html__( 'Permission Error', 'memory-limit-manager' ), array( 'back_link' => true ) );
		}
		
		// Get and validate input
		$wp_memory_limit = isset( $_POST['wp_memory_limit'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_memory_limit'] ) ) : '';
		$wp_max_memory_limit = isset( $_POST['wp_max_memory_limit'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_max_memory_limit'] ) ) : '';
		
		// Validate memory format
		$errors = array();
		
		if ( ! $this->validate_memory_value( $wp_memory_limit ) ) {
			$errors[] = __( 'WP Memory Limit format is invalid. Use format like: 256M, 512M, 1G', 'memory-limit-manager' );
		}
		
		if ( ! $this->validate_memory_value( $wp_max_memory_limit ) ) {
			$errors[] = __( 'WP Max Memory Limit format is invalid. Use format like: 256M, 512M, 1G', 'memory-limit-manager' );
		}
		
		// Check if max is greater than regular limit
		if ( empty( $errors ) && $this->parse_memory_value( $wp_max_memory_limit ) < $this->parse_memory_value( $wp_memory_limit ) ) {
			$errors[] = __( 'WP Max Memory Limit must be greater than or equal to WP Memory Limit', 'memory-limit-manager' );
		}
		
		if ( ! empty( $errors ) ) {
			$user_id = get_current_user_id();
			set_transient( 'memory_limit_manager_errors_' . $user_id, $errors, 300 );
			wp_safe_redirect( add_query_arg( 'status', 'validation_error', admin_url( 'options-general.php?page=memory-limit-manager' ) ) );
			exit;
		}
		
		// Update wp-config.php
		$config_handler = new Memory_Manager_WP_Config_Handler();
		$result = $config_handler->update_memory_limits( $wp_memory_limit, $wp_max_memory_limit );
		
		$user_id = get_current_user_id();
		
		if ( is_wp_error( $result ) ) {
			// Store attempted values for manual config
			set_transient( 'memory_limit_manager_attempted_values_' . $user_id, array(
				'wp_memory_limit' => $wp_memory_limit,
				'wp_max_memory_limit' => $wp_max_memory_limit,
			), 300 );
			
			set_transient( 'memory_limit_manager_errors_' . $user_id, array( $result->get_error_message() ), 300 );
			wp_safe_redirect( add_query_arg( 'status', 'error', admin_url( 'options-general.php?page=memory-limit-manager' ) ) );
			exit;
		}
		
		// Verify the changes were actually written
		$config_file_values = $config_handler->get_config_file_values();
		
		if ( $config_file_values['wp_memory_limit'] === $wp_memory_limit && 
		     $config_file_values['wp_max_memory_limit'] === $wp_max_memory_limit ) {
			// Clear attempted values on success
			delete_transient( 'memory_limit_manager_attempted_values_' . $user_id );
			
			set_transient( 'memory_limit_manager_success_' . $user_id, __( 'Memory limits updated successfully in wp-config.php! The new values are now active sitewide and displayed in the "Current Memory Status" section below.', 'memory-limit-manager' ), 300 );
			wp_safe_redirect( add_query_arg( array( 'status' => 'success', 'updated' => time() ), admin_url( 'options-general.php?page=memory-limit-manager' ) ) );
			exit;
		} else {
			$errors = array();
			$errors[] = __( 'The plugin attempted to write to wp-config.php, but the values were not saved correctly.', 'memory-limit-manager' );
			
			if ( empty( $config_file_values['wp_memory_limit'] ) ) {
				$errors[] = sprintf( 
					/* translators: %s: Attempted memory limit value */
					__( 'WP_MEMORY_LIMIT was not added to wp-config.php. Attempted to set: %s', 'memory-limit-manager' ),
					$wp_memory_limit 
				);
			}
			
			if ( empty( $config_file_values['wp_max_memory_limit'] ) ) {
				$errors[] = sprintf( 
					/* translators: %s: Attempted max memory limit value */
					__( 'WP_MAX_MEMORY_LIMIT was not added to wp-config.php. Attempted to set: %s', 'memory-limit-manager' ),
					$wp_max_memory_limit 
				);
			}
			
			$errors[] = __( 'Please scroll down and use the Manual Configuration section below.', 'memory-limit-manager' );
			
			// Store the attempted values so we can display them in manual config
			set_transient( 'memory_limit_manager_attempted_values_' . $user_id, array(
				'wp_memory_limit' => $wp_memory_limit,
				'wp_max_memory_limit' => $wp_max_memory_limit,
			), 300 ); // 5 minutes
			
			set_transient( 'memory_limit_manager_errors_' . $user_id, $errors, 300 );
			wp_safe_redirect( add_query_arg( 'status', 'write_failed', admin_url( 'options-general.php?page=memory-limit-manager' ) ) );
			exit;
		}
	}
	
	/**
	 * Validate memory value format
	 */
	private function validate_memory_value( $value ) {
		return preg_match( '/^\d+[MG]$/i', $value );
	}
	
	/**
	 * Parse memory value to bytes
	 */
	private function parse_memory_value( $value ) {
		$unit = strtoupper( substr( $value, -1 ) );
		$number = (int) substr( $value, 0, -1 );
		
		if ( $unit === 'G' ) {
			return $number * 1024 * 1024 * 1024;
		} elseif ( $unit === 'M' ) {
			return $number * 1024 * 1024;
		}
		
		return 0;
	}
	
	/**
	 * Display admin notices
	 */
	public function display_admin_notices() {
		// Only show on our plugin page
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'settings_page_memory-limit-manager' ) {
			return;
		}
		
		// Check if we have a status parameter
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		
		// Always check for transients even without status parameter (in case redirect failed)
		$user_id = get_current_user_id();
		$success = get_transient( 'memory_limit_manager_success_' . $user_id );
		$errors = get_transient( 'memory_limit_manager_errors_' . $user_id );
		
		// Show success message
		if ( $status === 'success' || $success ) {
			$message = $success ? $success : __( 'Memory limits updated successfully! Please clear your site cache and refresh this page to see the changes.', 'memory-limit-manager' );
			echo '<div class="notice notice-success is-dismissible mlm-notice-success" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-left: 4px solid #28a745; padding: 15px;">';
			echo '<p style="margin: 0; font-size: 14px;"><strong style="font-size: 16px;">✅ ' . esc_html__( 'Success!', 'memory-limit-manager' ) . '</strong><br>' . esc_html( $message ) . '</p>';
			echo '</div>';
			delete_transient( 'memory_limit_manager_success_' . $user_id );
		}
		
		// Show error messages
		if ( in_array( $status, array( 'error', 'write_failed', 'validation_error' ), true ) || $errors ) {
			if ( $errors && is_array( $errors ) ) {
				echo '<div class="notice notice-error is-dismissible mlm-notice-error" id="mlm-error-notice" style="background: linear-gradient(135deg, #f8d7da 0%, #f5c2c7 100%); border-left: 4px solid #dc3545; padding: 15px;">';
				echo '<p style="margin: 0 0 10px 0; font-size: 16px;"><strong>❌ ' . esc_html__( 'Error:', 'memory-limit-manager' ) . '</strong></p>';
				echo '<ul style="margin: 8px 0 8px 20px; list-style: disc; font-size: 14px;">';
				foreach ( $errors as $error ) {
					echo '<li style="margin: 4px 0;">' . esc_html( $error ) . '</li>';
				}
				echo '</ul>';
				echo '</div>';
				delete_transient( 'memory_limit_manager_errors_' . $user_id );
			} else {
				// Fallback error message
				echo '<div class="notice notice-error is-dismissible mlm-notice-error" id="mlm-error-notice" style="background: linear-gradient(135deg, #f8d7da 0%, #f5c2c7 100%); border-left: 4px solid #dc3545; padding: 15px;">';
				echo '<p style="margin: 0; font-size: 14px;"><strong>❌ ' . esc_html__( 'Error:', 'memory-limit-manager' ) . '</strong> ' . esc_html__( 'An error occurred while updating memory limits.', 'memory-limit-manager' ) . '</p>';
				echo '</div>';
			}
		}
	}
	
	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'memory-limit-manager' ) );
		}
		
		// Get current values
		$config_handler = new Memory_Manager_WP_Config_Handler();
		$current_values = $config_handler->get_current_memory_limits();
		
		$current_wp_memory = defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : '40M';
		$current_wp_max_memory = defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : '256M';
		
		$actual_memory = ini_get( 'memory_limit' );
		
		// Diagnostic information
		$config_path = $config_handler->get_config_path();
		$is_writable = $config_handler->is_config_writable();
		$config_perms = $config_path ? substr( sprintf( '%o', fileperms( $config_path ) ), -4 ) : 'N/A';
		
		// Check for conflicts
		$conflicts = $config_handler->check_for_conflicts();
		$config_file_values = $config_handler->get_config_file_values();
		
		// Get attempted values if write failed (for manual config display)
		$user_id = get_current_user_id();
		$attempted_values = get_transient( 'memory_limit_manager_attempted_values_' . $user_id );
		if ( ! $attempted_values ) {
			$attempted_values = $current_values; // Fallback to current values
		}
		
		?>
		<div class="wrap memory-limit-manager-wrap">
			<h1>
				<span class="dashicons dashicons-performance"></span>
				<?php esc_html_e( 'Memory Limit Manager', 'memory-limit-manager' ); ?>
			</h1>
			
			<div class="memory-limit-manager-container">
				<!-- Current Status Card -->
				<div class="mlm-card mlm-status-card">
					<div class="mlm-card-header">
						<h2><?php esc_html_e( 'Current Memory Status', 'memory-limit-manager' ); ?></h2>
					</div>
					<div class="mlm-card-body">
						<div class="mlm-status-grid">
							<div class="mlm-status-item">
								<div class="mlm-status-icon mlm-icon-blue">
									<span class="dashicons dashicons-chart-area"></span>
								</div>
								<div class="mlm-status-content">
									<span class="mlm-status-label"><?php esc_html_e( 'WP Memory Limit', 'memory-limit-manager' ); ?></span>
									<span class="mlm-status-value"><?php echo esc_html( $current_wp_memory ); ?></span>
								</div>
							</div>
							
							<div class="mlm-status-item">
								<div class="mlm-status-icon mlm-icon-purple">
									<span class="dashicons dashicons-admin-settings"></span>
								</div>
								<div class="mlm-status-content">
									<span class="mlm-status-label"><?php esc_html_e( 'WP Max Memory Limit', 'memory-limit-manager' ); ?></span>
									<span class="mlm-status-value"><?php echo esc_html( $current_wp_max_memory ); ?></span>
								</div>
							</div>
							
							<div class="mlm-status-item">
								<div class="mlm-status-icon mlm-icon-green">
									<span class="dashicons dashicons-dashboard"></span>
								</div>
								<div class="mlm-status-content">
									<span class="mlm-status-label"><?php esc_html_e( 'PHP Memory Limit', 'memory-limit-manager' ); ?></span>
									<span class="mlm-status-value"><?php echo esc_html( $actual_memory ); ?></span>
								</div>
							</div>
						</div>
						
						<div class="mlm-info-box">
							<span class="dashicons dashicons-info"></span>
							<div>
								<strong><?php esc_html_e( 'Note:', 'memory-limit-manager' ); ?></strong>
								<?php esc_html_e( 'WP_MEMORY_LIMIT controls WordPress frontend memory. WP_MAX_MEMORY_LIMIT applies to the admin area. Both should not exceed your PHP memory limit.', 'memory-limit-manager' ); ?>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Settings Form Card -->
				<div class="mlm-card mlm-settings-card">
					<div class="mlm-card-header">
						<h2><?php esc_html_e( 'Update Memory Limits', 'memory-limit-manager' ); ?></h2>
					</div>
					<div class="mlm-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="memory-limit-manager-form">
						<input type="hidden" name="action" value="memory_limit_manager_update" />
						<?php wp_nonce_field( 'memory_limit_manager_save', 'memory_limit_manager_nonce' ); ?>
							
							<div class="mlm-form-group">
								<label for="wp_memory_limit" class="mlm-label">
									<?php esc_html_e( 'WP Memory Limit', 'memory-limit-manager' ); ?>
									<span class="mlm-required">*</span>
								</label>
								<div class="mlm-input-wrapper">
									<input 
										type="text" 
										name="wp_memory_limit" 
										id="wp_memory_limit" 
										class="mlm-input" 
										value="<?php echo esc_attr( $current_values['wp_memory_limit'] ); ?>"
										placeholder="256M"
										required
									>
									<span class="mlm-input-hint"><?php esc_html_e( 'Format: 64M, 128M, 256M, 512M, 1G, etc.', 'memory-limit-manager' ); ?></span>
								</div>
							</div>
							
							<div class="mlm-form-group">
								<label for="wp_max_memory_limit" class="mlm-label">
									<?php esc_html_e( 'WP Max Memory Limit', 'memory-limit-manager' ); ?>
									<span class="mlm-required">*</span>
								</label>
								<div class="mlm-input-wrapper">
									<input 
										type="text" 
										name="wp_max_memory_limit" 
										id="wp_max_memory_limit" 
										class="mlm-input" 
										value="<?php echo esc_attr( $current_values['wp_max_memory_limit'] ); ?>"
										placeholder="512M"
										required
									>
									<span class="mlm-input-hint"><?php esc_html_e( 'Should be equal to or greater than WP Memory Limit', 'memory-limit-manager' ); ?></span>
								</div>
							</div>
							
							<div class="mlm-quick-presets">
								<label class="mlm-label"><?php esc_html_e( 'Quick Presets:', 'memory-limit-manager' ); ?></label>
								<div class="mlm-preset-buttons">
									<button type="button" class="mlm-preset-btn" data-memory="128M" data-max="256M">
										<span class="dashicons dashicons-arrow-up-alt"></span> <?php esc_html_e( 'Low', 'memory-limit-manager' ); ?> (128M / 256M)
									</button>
									<button type="button" class="mlm-preset-btn" data-memory="256M" data-max="512M">
										<span class="dashicons dashicons-arrow-up-alt"></span> <?php esc_html_e( 'Medium', 'memory-limit-manager' ); ?> (256M / 512M)
									</button>
									<button type="button" class="mlm-preset-btn" data-memory="512M" data-max="1G">
										<span class="dashicons dashicons-arrow-up-alt"></span> <?php esc_html_e( 'High', 'memory-limit-manager' ); ?> (512M / 1G)
									</button>
									<button type="button" class="mlm-preset-btn" data-memory="1G" data-max="2G">
										<span class="dashicons dashicons-arrow-up-alt"></span> <?php esc_html_e( 'Very High', 'memory-limit-manager' ); ?> (1G / 2G)
									</button>
								</div>
							</div>
							
							<div class="mlm-warning-box">
								<span class="dashicons dashicons-warning"></span>
								<div>
									<strong><?php esc_html_e( 'Warning:', 'memory-limit-manager' ); ?></strong>
									<?php esc_html_e( 'This will modify your wp-config.php file. Make sure you have a backup before proceeding.', 'memory-limit-manager' ); ?>
								</div>
							</div>
							
							<div class="mlm-info-box" style="background: #e3f2fd; border-left: 4px solid #2196f3; margin-top: 15px; margin-bottom: 25px;">
								<span class="dashicons dashicons-lightbulb" style="color: #2196f3;"></span>
								<div>
									<strong><?php esc_html_e( 'How it works:', 'memory-limit-manager' ); ?></strong>
									<p style="margin: 8px 0 0 0; font-size: 13px;"><?php esc_html_e( 'When you click "Update Memory Limits", the plugin will modify your wp-config.php file. After a successful update:', 'memory-limit-manager' ); ?></p>
									<ol style="margin: 8px 0 0 20px; padding-left: 0; font-size: 13px;">
										<li><?php esc_html_e( 'You will see a success message at the top of this page', 'memory-limit-manager' ); ?></li>
										<li><?php esc_html_e( 'The values in wp-config.php have been changed and the "Current Memory Status" section above will display them', 'memory-limit-manager' ); ?></li>
									</ol>
								</div>
							</div>
							
							<div class="mlm-form-actions">
								<button type="submit" name="memory_limit_manager_submit" class="button button-primary button-hero mlm-submit-btn">
									<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Update Memory Limits', 'memory-limit-manager' ); ?>
								</button>
							</div>
						</form>
					</div>
				</div>
				
				<!-- Diagnostics Card -->
				<div class="mlm-card mlm-diagnostics-card">
					<div class="mlm-card-header">
						<h2><?php esc_html_e( 'System Diagnostics', 'memory-limit-manager' ); ?></h2>
					</div>
					<div class="mlm-card-body">
						<table class="mlm-diagnostics-table">
							<tbody>
								<tr>
									<td class="mlm-diag-label"><?php esc_html_e( 'wp-config.php Location:', 'memory-limit-manager' ); ?></td>
									<td class="mlm-diag-value">
										<code><?php echo esc_html( $config_path ? $config_path : __( 'Not found', 'memory-limit-manager' ) ); ?></code>
									</td>
								</tr>
								<tr>
									<td class="mlm-diag-label"><?php esc_html_e( 'File Permissions:', 'memory-limit-manager' ); ?></td>
									<td class="mlm-diag-value">
										<code><?php echo esc_html( $config_perms ); ?></code>
										<?php if ( $config_perms !== 'N/A' && ! in_array( $config_perms, array( '0644', '0664', '0666' ), true ) ) : ?>
											<span class="mlm-diag-warning"><?php esc_html_e( '(Unusual permissions)', 'memory-limit-manager' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<td class="mlm-diag-label"><?php esc_html_e( 'Writable:', 'memory-limit-manager' ); ?></td>
									<td class="mlm-diag-value">
										<?php if ( $is_writable ) : ?>
											<span class="mlm-diag-success">✓ <?php esc_html_e( 'Yes', 'memory-limit-manager' ); ?></span>
										<?php else : ?>
											<span class="mlm-diag-error">✗ <?php esc_html_e( 'No', 'memory-limit-manager' ); ?></span>
											<div class="mlm-diag-help">
												<?php esc_html_e( 'Try running: chmod 644 wp-config.php', 'memory-limit-manager' ); ?>
											</div>
										<?php endif; ?>
									</td>
								</tr>
							</tbody>
						</table>
						
						<!-- System Requirements Check -->
						<h3 class="mlm-diag-section-title"><?php esc_html_e( 'System Requirements', 'memory-limit-manager' ); ?></h3>
						<table class="mlm-diagnostics-table">
							<tbody>
								<tr>
									<td class="mlm-diag-label"><?php esc_html_e( 'WordPress Version:', 'memory-limit-manager' ); ?></td>
									<td class="mlm-diag-value">
										<code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code>
										<?php if ( version_compare( get_bloginfo( 'version' ), '6.0', '>=' ) ) : ?>
											<span class="mlm-diag-success"> ✓ <?php esc_html_e( 'Compatible (Requires 6.0+)', 'memory-limit-manager' ); ?></span>
										<?php else : ?>
											<span class="mlm-diag-error"> ✗ <?php esc_html_e( 'Incompatible (Requires 6.0+)', 'memory-limit-manager' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<td class="mlm-diag-label"><?php esc_html_e( 'PHP Version:', 'memory-limit-manager' ); ?></td>
									<td class="mlm-diag-value">
										<code><?php echo esc_html( PHP_VERSION ); ?></code>
										<?php if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) : ?>
											<span class="mlm-diag-success"> ✓ <?php esc_html_e( 'Compatible (Requires 7.4+)', 'memory-limit-manager' ); ?></span>
										<?php else : ?>
											<span class="mlm-diag-error"> ✗ <?php esc_html_e( 'Incompatible (Requires 7.4+)', 'memory-limit-manager' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
							</tbody>
						</table>
						
						<h3 class="mlm-diag-section-title"><?php esc_html_e( 'Values Comparison', 'memory-limit-manager' ); ?></h3>
						<table class="mlm-diagnostics-table mlm-comparison-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Constant', 'memory-limit-manager' ); ?></th>
									<th><?php esc_html_e( 'In wp-config.php', 'memory-limit-manager' ); ?></th>
									<th><?php esc_html_e( 'WordPress Using', 'memory-limit-manager' ); ?></th>
									<th><?php esc_html_e( 'Status', 'memory-limit-manager' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="mlm-diag-label"><code>WP_MEMORY_LIMIT</code></td>
									<td class="mlm-diag-value">
										<?php if ( $config_file_values['wp_memory_limit'] ) : ?>
											<code><?php echo esc_html( $config_file_values['wp_memory_limit'] ); ?></code>
										<?php else : ?>
											<span class="mlm-diag-warning"><?php esc_html_e( 'Not defined', 'memory-limit-manager' ); ?></span>
										<?php endif; ?>
									</td>
									<td class="mlm-diag-value">
										<code><?php echo esc_html( defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'Not defined' ); ?></code>
									</td>
									<td class="mlm-diag-value">
										<?php if ( $config_file_values['wp_memory_limit'] && defined( 'WP_MEMORY_LIMIT' ) && $config_file_values['wp_memory_limit'] === WP_MEMORY_LIMIT ) : ?>
											<span class="mlm-diag-success">✓ <?php esc_html_e( 'Match', 'memory-limit-manager' ); ?></span>
										<?php elseif ( $config_file_values['wp_memory_limit'] && defined( 'WP_MEMORY_LIMIT' ) ) : ?>
											<span class="mlm-diag-error">✗ <?php esc_html_e( 'Mismatch!', 'memory-limit-manager' ); ?></span>
										<?php else : ?>
											<span class="mlm-diag-warning">—</span>
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<td class="mlm-diag-label"><code>WP_MAX_MEMORY_LIMIT</code></td>
									<td class="mlm-diag-value">
										<?php if ( $config_file_values['wp_max_memory_limit'] ) : ?>
											<code><?php echo esc_html( $config_file_values['wp_max_memory_limit'] ); ?></code>
										<?php else : ?>
											<span class="mlm-diag-warning"><?php esc_html_e( 'Not defined', 'memory-limit-manager' ); ?></span>
										<?php endif; ?>
									</td>
									<td class="mlm-diag-value">
										<code><?php echo esc_html( defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : 'Not defined' ); ?></code>
									</td>
									<td class="mlm-diag-value">
										<?php if ( $config_file_values['wp_max_memory_limit'] && defined( 'WP_MAX_MEMORY_LIMIT' ) && $config_file_values['wp_max_memory_limit'] === WP_MAX_MEMORY_LIMIT ) : ?>
											<span class="mlm-diag-success">✓ <?php esc_html_e( 'Match', 'memory-limit-manager' ); ?></span>
										<?php elseif ( $config_file_values['wp_max_memory_limit'] && defined( 'WP_MAX_MEMORY_LIMIT' ) ) : ?>
											<span class="mlm-diag-error">✗ <?php esc_html_e( 'Mismatch!', 'memory-limit-manager' ); ?></span>
										<?php else : ?>
											<span class="mlm-diag-warning">—</span>
										<?php endif; ?>
									</td>
								</tr>
							</tbody>
						</table>
						
					<?php 
					// Only show manual configuration if the user tried to update and it failed
					$attempted_values_exist = get_transient( 'memory_limit_manager_attempted_values_' . get_current_user_id() );
					$show_manual_config = ( $attempted_values_exist !== false );
					?>
					
					<?php if ( $show_manual_config ) : ?>
						<!-- Manual Instructions -->
						<div class="mlm-warning-box" id="mlm-manual-config" style="background: linear-gradient(135deg, #fff3cd 0%, #ffe9a6 100%); border-left: 4px solid #ff9800;">
							<span class="dashicons dashicons-admin-tools"></span>
							<div>
								<strong><?php esc_html_e( 'Manual Configuration Required', 'memory-limit-manager' ); ?></strong>
								<p><?php esc_html_e( 'The plugin cannot automatically update your wp-config.php file. Please add the following lines manually:', 'memory-limit-manager' ); ?></p>
								<?php if ( $attempted_values !== $current_values ) : ?>
								<p style="background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0;">
								<strong><?php esc_html_e( 'Note:', 'memory-limit-manager' ); ?></strong>
								<?php 
								printf( 
									/* translators: 1: Memory limit value, 2: Max memory limit value */
									esc_html__( 'You tried to set WP_MEMORY_LIMIT to %1$s and WP_MAX_MEMORY_LIMIT to %2$s. The code below reflects these values.', 'memory-limit-manager' ),
									'<code>' . esc_html( $attempted_values['wp_memory_limit'] ) . '</code>',
									'<code>' . esc_html( $attempted_values['wp_max_memory_limit'] ) . '</code>'
								); 
								?>
								</p>
								<?php endif; ?>
								
								<div style="display: flex; align-items: center; justify-content: space-between; margin: 12px 0 8px 0;">
									<p style="margin: 0;"><strong><?php esc_html_e( 'Add these lines to your wp-config.php file:', 'memory-limit-manager' ); ?></strong></p>
									<button type="button" class="button mlm-copy-config-btn" style="margin-left: 10px;">
										<span class="dashicons dashicons-clipboard" style="margin-top: 3px;"></span>
										<?php esc_html_e( 'Copy Code', 'memory-limit-manager' ); ?>
									</button>
								</div>
								<pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px;"><code><?php
								$manual_code = "// WordPress Memory Limits\n";
								$manual_code .= "define( 'WP_MEMORY_LIMIT', '" . esc_html( $attempted_values['wp_memory_limit'] ) . "' );\n";
								$manual_code .= "define( 'WP_MAX_MEMORY_LIMIT', '" . esc_html( $attempted_values['wp_max_memory_limit'] ) . "' );\n";
								echo esc_html( $manual_code );
								?></code></pre>
								
								<p><strong><?php esc_html_e( 'Where to add them:', 'memory-limit-manager' ); ?></strong></p>
								<ol style="margin: 8px 0 8px 20px;">
									<li><?php esc_html_e( 'Connect to your site via FTP or cPanel File Manager', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( 'Open wp-config.php for editing', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( 'Find this line: /* That\'s all, stop editing! Happy publishing. */', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( 'Add the code ABOVE that line', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( 'Save the file', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( 'Refresh this page to see if values update', 'memory-limit-manager' ); ?></li>
								</ol>
							</div>
						</div>
						<?php endif; ?>
						
						<?php if ( ! empty( $conflicts ) ) : ?>
						<!-- Conflicts Warning -->
						<div class="mlm-error-box">
							<span class="dashicons dashicons-warning"></span>
							<div>
								<strong><?php esc_html_e( 'Conflicts Detected!', 'memory-limit-manager' ); ?></strong>
								<p><?php esc_html_e( 'The memory limits in wp-config.php are being overridden by another source:', 'memory-limit-manager' ); ?></p>
								<ul>
									<?php foreach ( $conflicts as $conflict ) : ?>
										<li><?php echo esc_html( $conflict ); ?></li>
									<?php endforeach; ?>
								</ul>
								<p><strong><?php esc_html_e( 'Possible causes:', 'memory-limit-manager' ); ?></strong></p>
								<ul>
									<li><?php esc_html_e( '✗ Another plugin is defining these constants', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( '✗ Your theme\'s functions.php is defining these constants', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( '✗ A must-use plugin (mu-plugin) is defining these constants', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( '✗ Multiple definitions exist in wp-config.php', 'memory-limit-manager' ); ?></li>
								</ul>
								<p><strong><?php esc_html_e( 'How to fix:', 'memory-limit-manager' ); ?></strong></p>
								<ol>
									<li><?php esc_html_e( 'Search your theme\'s functions.php for WP_MEMORY_LIMIT or WP_MAX_MEMORY_LIMIT', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( 'Check wp-content/mu-plugins/ for any files defining these constants', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( 'Deactivate plugins one by one to find which one is setting these values', 'memory-limit-manager' ); ?></li>
									<li><?php esc_html_e( 'Open wp-config.php and search for duplicate definitions', 'memory-limit-manager' ); ?></li>
								</ol>
							</div>
						</div>
						<?php endif; ?>
						
						<?php if ( ! $is_writable ) : ?>
						<div class="mlm-error-box">
							<span class="dashicons dashicons-warning"></span>
							<div>
								<strong><?php esc_html_e( 'Action Required:', 'memory-limit-manager' ); ?></strong>
								<p><?php esc_html_e( 'The wp-config.php file is not writable. Please fix file permissions before updating memory limits.', 'memory-limit-manager' ); ?></p>
								<p><strong><?php esc_html_e( 'Fix via FTP/SSH:', 'memory-limit-manager' ); ?></strong></p>
								<pre><code>chmod 644 <?php echo esc_html( basename( $config_path ) ); ?></code></pre>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>
				
				<!-- Help & Documentation Card -->
				<div class="mlm-card mlm-help-card">
					<div class="mlm-card-header">
						<h2><?php esc_html_e( 'Help & Documentation', 'memory-limit-manager' ); ?></h2>
					</div>
					<div class="mlm-card-body">
						<div class="mlm-help-item">
							<h3><?php esc_html_e( 'What is WP_MEMORY_LIMIT?', 'memory-limit-manager' ); ?></h3>
							<p><?php esc_html_e( 'Controls the maximum amount of memory WordPress can use on the frontend of your site. Default is 40MB, but 64MB or higher is recommended for modern WordPress sites.', 'memory-limit-manager' ); ?></p>
						</div>
						
						<div class="mlm-help-item">
							<h3><?php esc_html_e( 'What is WP_MAX_MEMORY_LIMIT?', 'memory-limit-manager' ); ?></h3>
							<p><?php esc_html_e( 'Controls the maximum memory limit for WordPress admin area operations. This should be higher than WP_MEMORY_LIMIT as admin operations typically require more memory.', 'memory-limit-manager' ); ?></p>
						</div>
						
						<div class="mlm-help-item">
							<h3><?php esc_html_e( 'Recommended Values', 'memory-limit-manager' ); ?></h3>
							<ul>
								<li><strong><?php esc_html_e( 'Small sites:', 'memory-limit-manager' ); ?></strong> <?php esc_html_e( '128M / 256M', 'memory-limit-manager' ); ?></li>
								<li><strong><?php esc_html_e( 'Medium sites:', 'memory-limit-manager' ); ?></strong> <?php esc_html_e( '256M / 512M', 'memory-limit-manager' ); ?></li>
								<li><strong><?php esc_html_e( 'Large sites:', 'memory-limit-manager' ); ?></strong> <?php esc_html_e( '512M / 1G', 'memory-limit-manager' ); ?></li>
								<li><strong><?php esc_html_e( 'Enterprise:', 'memory-limit-manager' ); ?></strong> <?php esc_html_e( '1G / 2G or higher', 'memory-limit-manager' ); ?></li>
							</ul>
						</div>
					</div>
				</div>
				
				<!-- Footer Credits -->
				<div style="margin-top: 40px; padding: 20px; text-align: center; border-top: 2px solid #e8eaed;">
					<p style="margin: 0; color: #5f6368; font-size: 14px;">
						<strong><?php esc_html_e( 'Memory Limit Manager', 'memory-limit-manager' ); ?></strong> 
						<?php esc_html_e( 'by', 'memory-limit-manager' ); ?> 
						<a href="https://muhammadshakeel.com/" target="_blank" rel="noopener" style="color: #2271b1; text-decoration: none; font-weight: 600;">Muhammad Shakeel</a>
					</p>
					<p style="margin: 8px 0 0 0; font-size: 13px; color: #999;">
						<a href="https://muhammadshakeel.com/memory-limit-manager/" target="_blank" rel="noopener" style="color: #999; text-decoration: none;"><?php esc_html_e( 'Plugin Page', 'memory-limit-manager' ); ?></a>
						<span style="margin: 0 8px;">•</span>
						<a href="https://muhammadshakeel.com/" target="_blank" rel="noopener" style="color: #999; text-decoration: none;"><?php esc_html_e( 'Get Support', 'memory-limit-manager' ); ?></a>
						<span style="margin: 0 8px;">•</span>
						<?php 
						/* translators: %s: Plugin version number */
						printf( esc_html__( 'Version %s', 'memory-limit-manager' ), esc_html( MEMORY_MANAGER_WP_VERSION ) ); 
						?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}
}
