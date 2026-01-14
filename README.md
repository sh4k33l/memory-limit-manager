# Memory Limit Manager

A beautiful and user-friendly plugin to easily manage memory limits (`WP_MEMORY_LIMIT` and `WP_MAX_MEMORY_LIMIT`) through an intuitive admin interface.

## Features

✨ **Modern UI/UX** - Clean, gradient-based design with smooth animations  
📊 **Real-time Status** - View current memory limits at a glance  
🎯 **Quick Presets** - One-click presets for common configurations  
✅ **Smart Validation** - Real-time input validation and error checking  
🔒 **Safe Updates** - Automatic backups before modifying wp-config.php  
⚡ **Opcode Cache Clearing** - Changes take effect immediately  
🔍 **Conflict Detection** - Identifies if limits are defined elsewhere  
📋 **Manual Fallback** - Copy-paste code if automatic update fails  
📱 **Responsive Design** - Works perfectly on all screen sizes  
🎨 **Beautiful Animations** - Smooth transitions and visual feedback  

## Installation

### Via WordPress.org (Recommended)
1. Log in to your admin panel
2. Navigate to **Plugins → Add New**
3. Search for "Memory Limit Manager"
4. Click **Install Now** and then **Activate**

### Manual Installation
1. Download the plugin ZIP file
2. Upload it to your WordPress site's `/wp-content/plugins/` directory
3. Extract the ZIP file
4. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

### Accessing the Plugin

After activation, go to **Settings → Memory Manager** in your WordPress admin panel.

### Current Status

The dashboard displays three key metrics:
- **WP Memory Limit** - Memory available to WordPress frontend
- **WP Max Memory Limit** - Memory available to WordPress admin
- **PHP Memory Limit** - Your server's PHP memory limit

### Updating Memory Limits

1. **Manual Entry**: Enter custom values in the format `256M`, `512M`, `1G`, etc.
2. **Quick Presets**: Use one of the preset buttons:
   - **Low**: 128M / 256M (for small sites)
   - **Medium**: 256M / 512M (for standard sites)
   - **High**: 512M / 1G (for large sites)
   - **Very High**: 1G / 2G (for enterprise sites)
3. Click **Update Memory Limits**
4. The plugin will:
   - Create a backup of wp-config.php
   - Update the file with new values
   - Verify the changes
   - Clear opcode cache

### System Diagnostics

The diagnostics section shows:
- wp-config.php location and permissions
- File writability status
- WordPress and PHP version compatibility
- Values comparison (wp-config.php vs actual)
- Conflict detection

## Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 7.4 or higher
- **Permissions:** wp-config.php must be writable (644 or 640)

## Troubleshooting

### Values Don't Change After Update

**Possible causes:**
1. Memory limits defined elsewhere (theme, mu-plugin, another plugin)
2. Browser cache needs clearing
3. WordPress cache needs clearing

**Solutions:**
1. Check the "Values Comparison" section for conflicts
2. Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
3. Clear WordPress cache if using a caching plugin
4. Check theme's functions.php for memory defines
5. Check wp-content/mu-plugins/ for conflicting files

### "Could not write to wp-config.php"

**Cause:** File permission issue

**Solutions:**
1. Via FTP: Change wp-config.php permissions to 644
2. Via cPanel: File Manager → wp-config.php → Permissions → 644
3. Via SSH: `chmod 644 wp-config.php`
4. Last resort: Use the Manual Configuration option provided by the plugin

### Manual Configuration

If automatic updates don't work:
1. The plugin will display copy-paste code
2. Connect via FTP or cPanel
3. Open wp-config.php
4. Find the line: `/* That's all, stop editing! Happy publishing. */`
5. Paste the code ABOVE that line
6. Save the file

## Security

- ✅ Nonce verification for CSRF protection
- ✅ Capability checks (administrator-only access)
- ✅ Input sanitization and validation
- ✅ Secure file operations with automatic backups
- ✅ Error recovery and restoration

## Support

For support, please visit: [https://muhammadshakeel.com/](https://muhammadshakeel.com/)

## License

GPL v2 or later - [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Author

**Muhammad Shakeel**  
Website: [https://muhammadshakeel.com/](https://muhammadshakeel.com/)  
Plugin Page: [https://muhammadshakeel.com/memory-limit-manager/](https://muhammadshakeel.com/memory-limit-manager/)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and release notes.
