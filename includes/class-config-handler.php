<?php
/**
 * wp-config.php handler
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Config Handler class
 */
class Memory_Manager_WP_Config_Handler {
	
	/**
	 * Path to wp-config.php
	 */
	private $config_path;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->config_path = $this->locate_config_file();
	}
	
	/**
	 * Locate wp-config.php file
	 */
	private function locate_config_file() {
		// Try standard location first
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			return ABSPATH . 'wp-config.php';
		}
		
		// Try one level up
		if ( file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) {
			return dirname( ABSPATH ) . '/wp-config.php';
		}
		
		return false;
	}
	
	/**
	 * Get current memory limits from wp-config.php
	 */
	public function get_current_memory_limits() {
		$limits = array(
			'wp_memory_limit'     => '256M',
			'wp_max_memory_limit' => '512M',
		);
		
		if ( ! $this->config_path || ! is_readable( $this->config_path ) ) {
			return $limits;
		}
		
		$config_content = file_get_contents( $this->config_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		
		// Extract WP_MEMORY_LIMIT
		if ( preg_match( "/define\s*\(\s*['\"]WP_MEMORY_LIMIT['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $config_content, $matches ) ) {
			$limits['wp_memory_limit'] = $matches[1];
		} elseif ( defined( 'WP_MEMORY_LIMIT' ) ) {
			$limits['wp_memory_limit'] = WP_MEMORY_LIMIT;
		}
		
		// Extract WP_MAX_MEMORY_LIMIT
		if ( preg_match( "/define\s*\(\s*['\"]WP_MAX_MEMORY_LIMIT['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $config_content, $matches ) ) {
			$limits['wp_max_memory_limit'] = $matches[1];
		} elseif ( defined( 'WP_MAX_MEMORY_LIMIT' ) ) {
			$limits['wp_max_memory_limit'] = WP_MAX_MEMORY_LIMIT;
		}
		
		return $limits;
	}
	
	/**
	 * Update memory limits in wp-config.php
	 */
	public function update_memory_limits( $wp_memory_limit, $wp_max_memory_limit ) {
		// Check if wp-config.php exists and is writable
		if ( ! $this->config_path ) {
			return new WP_Error(
				'config_not_found',
				__( 'Could not locate wp-config.php file.', 'memory-limit-manager' )
			);
		}
		
		// Direct filesystem check is required for wp-config.php
		if ( ! $this->is_file_writable( $this->config_path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
			return new WP_Error(
				'config_not_writable',
				sprintf(
					/* translators: %s: Path to wp-config.php file */
					__( 'wp-config.php file is not writable. Please check file permissions for: %s', 'memory-limit-manager' ),
					$this->config_path
				)
			);
		}
		
		// Read current config
		$config_content = file_get_contents( $this->config_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		
		if ( $config_content === false ) {
			return new WP_Error(
				'config_read_error',
				__( 'Could not read wp-config.php file.', 'memory-limit-manager' )
			);
		}
		
		// Create backup
		$backup_path = $this->config_path . '.backup-' . gmdate( 'Y-m-d-His' );
		if ( ! copy( $this->config_path, $backup_path ) ) {
			return new WP_Error(
				'backup_failed',
				__( 'Could not create backup of wp-config.php file.', 'memory-limit-manager' )
			);
		}
		
		// Process WP_MEMORY_LIMIT
		$config_content = $this->update_or_add_define( $config_content, 'WP_MEMORY_LIMIT', $wp_memory_limit );
		
		// Process WP_MAX_MEMORY_LIMIT
		$config_content = $this->update_or_add_define( $config_content, 'WP_MAX_MEMORY_LIMIT', $wp_max_memory_limit );
		
		// Write updated config
		$result = file_put_contents( $this->config_path, $config_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		
		if ( $result === false ) {
			// Restore backup
			copy( $backup_path, $this->config_path );
			$this->delete_backup_file( $backup_path );
			
			return new WP_Error(
				'config_write_error',
				__( 'Could not write to wp-config.php file. Backup has been restored.', 'memory-limit-manager' )
			);
		}
		
		// Verify the write by reading back
		clearstatcache();
		$verify_content = file_get_contents( $this->config_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		
		// Check if our defines are actually in the file now
		$wp_memory_found = ( stripos( $verify_content, "define( 'WP_MEMORY_LIMIT', '{$wp_memory_limit}' )" ) !== false );
		$wp_max_memory_found = ( stripos( $verify_content, "define( 'WP_MAX_MEMORY_LIMIT', '{$wp_max_memory_limit}' )" ) !== false );
		
		if ( ! $wp_memory_found || ! $wp_max_memory_found ) {
			// The defines weren't added properly
			return new WP_Error(
				'defines_not_added',
				__( 'File was written but defines were not added correctly. The file may have an unusual format. Check the backup file and try manual configuration.', 'memory-limit-manager' )
			);
		}
		
		// Keep the most recent backup, delete older ones
		$this->cleanup_old_backups();
		
		// Clear opcode cache for wp-config.php so changes take effect immediately
		$this->clear_opcode_cache();
		
		return true;
	}
	
	/**
	 * Check if file is writable (direct check needed for wp-config.php)
	 */
	private function is_file_writable( $file ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		return is_writable( $file );
	}
	
	/**
	 * Delete backup file
	 */
	private function delete_backup_file( $file ) {
		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Backup files are not WordPress uploads
			@unlink( $file );
		}
	}
	
	/**
	 * Clear opcode cache for wp-config.php
	 */
	private function clear_opcode_cache() {
		// Clear PHP opcache if available
		if ( function_exists( 'opcache_invalidate' ) ) {
			opcache_invalidate( $this->config_path, true );
		}
		
		// Clear APC cache if available
		if ( function_exists( 'apc_delete_file' ) ) {
			apc_delete_file( $this->config_path );
		}
		
		// Clear WinCache if available
		if ( function_exists( 'wincache_refresh_if_changed' ) ) {
			wincache_refresh_if_changed( array( $this->config_path ) );
		}
		
		// Clear file status cache
		clearstatcache( true, $this->config_path );
	}
	
	/**
	 * Update or add a define statement in wp-config.php content
	 */
	private function update_or_add_define( $content, $constant_name, $value ) {
		$define_line = "define( '{$constant_name}', '{$value}' );";
		
		// Split content into lines for line-by-line processing
		$lines = explode( "\n", $content );
		$total_lines = count( $lines );
		
		// Step 1: Check if constant already exists and update it
		$found_existing = false;
		for ( $i = 0; $i < $total_lines; $i++ ) {
			// Match any define for this constant
			if ( preg_match( "/define\s*\(\s*['\"]" . preg_quote( $constant_name, '/' ) . "['\"]/i", $lines[ $i ] ) ) {
				// Replace the entire line
				$lines[ $i ] = $define_line;
				$found_existing = true;
				break;
			}
		}
		
		// Step 2: If not found, add it before the appropriate line
		if ( ! $found_existing ) {
			$insertion_index = false;
			
			// Try to find "That's all" comment
			for ( $i = 0; $i < $total_lines; $i++ ) {
				if ( stripos( $lines[ $i ], "That's all" ) !== false || 
				     stripos( $lines[ $i ], 'stop editing' ) !== false ) {
					$insertion_index = $i;
					break;
				}
			}
			
			// If not found, try to find require wp-settings.php
			if ( $insertion_index === false ) {
				for ( $i = 0; $i < $total_lines; $i++ ) {
					if ( stripos( $lines[ $i ], 'wp-settings.php' ) !== false ) {
						$insertion_index = $i;
						break;
					}
				}
			}
			
			// If still not found, find the last define() and add after it
			if ( $insertion_index === false ) {
				for ( $i = $total_lines - 1; $i >= 0; $i-- ) {
					if ( stripos( $lines[ $i ], 'define(' ) !== false || 
					     stripos( $lines[ $i ], 'define (' ) !== false ) {
						$insertion_index = $i + 1;
						break;
					}
				}
			}
			
			// If STILL not found, try before closing PHP tag
			if ( $insertion_index === false ) {
				for ( $i = $total_lines - 1; $i >= 0; $i-- ) {
					if ( strpos( $lines[ $i ], '?>' ) !== false ) {
						$insertion_index = $i;
						break;
					}
				}
			}
			
			// Last resort: add at the end
			if ( $insertion_index === false ) {
				$insertion_index = $total_lines;
			}
			
			// Insert the define line
			array_splice( $lines, $insertion_index, 0, array( '', $define_line ) );
		}
		
		// Rejoin lines
		return implode( "\n", $lines );
	}
	
	/**
	 * Cleanup old backup files (keep only the 5 most recent)
	 */
	private function cleanup_old_backups() {
		if ( ! $this->config_path ) {
			return;
		}
		
		$dir = dirname( $this->config_path );
		$pattern = basename( $this->config_path ) . '.backup-*';
		$backups = glob( $dir . '/' . $pattern );
		
		if ( ! $backups || count( $backups ) <= 5 ) {
			return;
		}
		
		// Sort by modification time (newest first)
		usort(
			$backups,
			function ( $a, $b ) {
				return filemtime( $b ) - filemtime( $a );
			}
		);
		
		// Delete old backups (keep only 5 most recent)
		$backups_to_delete = array_slice( $backups, 5 );
		foreach ( $backups_to_delete as $backup ) {
			$this->delete_backup_file( $backup );
		}
	}
	
	/**
	 * Get config file path
	 */
	public function get_config_path() {
		return $this->config_path;
	}
	
	/**
	 * Check if config file is writable
	 */
	public function is_config_writable() {
		return $this->config_path && $this->is_file_writable( $this->config_path );
	}
	
	/**
	 * Check for multiple or conflicting definitions
	 */
	public function check_for_conflicts() {
		$conflicts = array();
		
		if ( ! $this->config_path || ! is_readable( $this->config_path ) ) {
			return $conflicts;
		}
		
		$config_content = file_get_contents( $this->config_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		
		// Check for multiple WP_MEMORY_LIMIT definitions
		$wp_memory_count = preg_match_all( "/define\s*\(\s*['\"]WP_MEMORY_LIMIT['\"]/i", $config_content );
		if ( $wp_memory_count > 1 ) {
			$conflicts['wp_memory_limit_multiple'] = sprintf(
				/* translators: %d: Number of times WP_MEMORY_LIMIT is defined */
				__( 'WP_MEMORY_LIMIT is defined %d times in wp-config.php. Only the first one takes effect.', 'memory-limit-manager' ),
				$wp_memory_count
			);
		}
		
		// Check for multiple WP_MAX_MEMORY_LIMIT definitions
		$wp_max_memory_count = preg_match_all( "/define\s*\(\s*['\"]WP_MAX_MEMORY_LIMIT['\"]/i", $config_content );
		if ( $wp_max_memory_count > 1 ) {
			$conflicts['wp_max_memory_limit_multiple'] = sprintf(
				/* translators: %d: Number of times WP_MAX_MEMORY_LIMIT is defined */
				__( 'WP_MAX_MEMORY_LIMIT is defined %d times in wp-config.php. Only the first one takes effect.', 'memory-limit-manager' ),
				$wp_max_memory_count
			);
		}
		
		// Check if constants are defined in wp-config but values don't match what WordPress is using
		if ( defined( 'WP_MEMORY_LIMIT' ) ) {
			if ( preg_match( "/define\s*\(\s*['\"]WP_MEMORY_LIMIT['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $config_content, $matches ) ) {
				$config_value = $matches[1];
				$wp_value = WP_MEMORY_LIMIT;
				if ( $config_value !== $wp_value ) {
					$conflicts['wp_memory_limit_mismatch'] = sprintf(
						/* translators: 1: Value in wp-config.php, 2: Value WordPress is actually using */
						__( 'WP_MEMORY_LIMIT in wp-config.php is "%1$s" but WordPress is using "%2$s". This means it\'s defined elsewhere (theme, plugin, or mu-plugin).', 'memory-limit-manager' ),
						$config_value,
						$wp_value
					);
				}
			}
		}
		
		if ( defined( 'WP_MAX_MEMORY_LIMIT' ) ) {
			if ( preg_match( "/define\s*\(\s*['\"]WP_MAX_MEMORY_LIMIT['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $config_content, $matches ) ) {
				$config_value = $matches[1];
				$wp_value = WP_MAX_MEMORY_LIMIT;
				if ( $config_value !== $wp_value ) {
					$conflicts['wp_max_memory_limit_mismatch'] = sprintf(
						/* translators: 1: Value in wp-config.php, 2: Value WordPress is actually using */
						__( 'WP_MAX_MEMORY_LIMIT in wp-config.php is "%1$s" but WordPress is using "%2$s". This means it\'s defined elsewhere (theme, plugin, or mu-plugin).', 'memory-limit-manager' ),
						$config_value,
						$wp_value
					);
				}
			}
		}
		
		return $conflicts;
	}
	
	/**
	 * Get what's actually written in wp-config.php (not what WordPress is using)
	 */
	public function get_config_file_values() {
		$values = array(
			'wp_memory_limit'     => null,
			'wp_max_memory_limit' => null,
		);
		
		if ( ! $this->config_path || ! is_readable( $this->config_path ) ) {
			return $values;
		}
		
		$config_content = file_get_contents( $this->config_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		
		// Get first occurrence of WP_MEMORY_LIMIT
		if ( preg_match( "/define\s*\(\s*['\"]WP_MEMORY_LIMIT['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $config_content, $matches ) ) {
			$values['wp_memory_limit'] = $matches[1];
		}
		
		// Get first occurrence of WP_MAX_MEMORY_LIMIT
		if ( preg_match( "/define\s*\(\s*['\"]WP_MAX_MEMORY_LIMIT['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $config_content, $matches ) ) {
			$values['wp_max_memory_limit'] = $matches[1];
		}
		
		return $values;
	}
}
