<?php
/**
 * Uninstall script
 * 
 * Runs when the plugin is uninstalled
 */

// Exit if uninstall not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Clean up plugin data
 */
function memory_limit_manager_uninstall() {
    // Delete any transients
    delete_transient( 'memory_limit_manager_success' );
    delete_transient( 'memory_limit_manager_errors' );
    
    // Note: We intentionally DO NOT remove the memory limit definitions from wp-config.php
    // as these are important settings that should persist even after plugin removal.
    // Users can manually adjust or remove these if needed.
    
    // Optional: Clean up backup files (uncomment if you want to remove backups on uninstall)
    /*
    $config_path = ABSPATH . 'wp-config.php';
    if ( ! file_exists( $config_path ) ) {
        $config_path = dirname( ABSPATH ) . '/wp-config.php';
    }
    
    if ( file_exists( $config_path ) ) {
        $dir = dirname( $config_path );
        $pattern = basename( $config_path ) . '.backup-*';
        $backups = glob( $dir . '/' . $pattern );
        
        if ( $backups ) {
            foreach ( $backups as $backup ) {
                @unlink( $backup );
            }
        }
    }
    */
}

// Run cleanup
memory_limit_manager_uninstall();

