# Changelog

All notable changes to Memory Limit Manager will be documented in this file.

## [1.0.1] - 2026-01-15

### 🔗 Improved

#### Links & References
- Updated all plugin links to point to official WordPress.org pages
- Plugin Page footer link now directs to wordpress.org/plugins/memory-limit-manager/
- Get Support footer link now directs to wordpress.org/support/plugin/memory-limit-manager/
- Updated Plugin URI in main plugin header
- Updated support links in readme.txt and README.md

#### Visual Assets
- Improved retina banner (1544×500) quality using ImageMagick
- Enhanced banner sharpness for better display on high-DPI screens

#### Documentation & Clarity
- Enhanced automatic backup feature documentation to clarify it backs up wp-config.php only (not entire site)
- Added detailed backup file naming format explanation (wp-config.php.backup-YYYY-MM-DD-HHMMSS)
- Clarified that 5 most recent backups are kept automatically
- Added new "Automatic Backup System" section in readme.txt
- Updated FAQ to explain backup functionality in detail
- Updated admin UI warning message to mention automatic backup creation
- Added prominent green "Automatic Safety Backup" info box in admin interface
- New FAQ: "What exactly gets backed up?" to prevent confusion

## [1.0.0] - 2026-01-07

### 🎉 Initial Public Release

First stable release of Memory Limit Manager, ready for submission.

### ✨ Features

#### Core Functionality
- Easy memory limit management through admin interface
- Smart wp-config.php updates with 5 fallback insertion strategies
- Automatic backup creation before any changes
- Opcode cache clearing for immediate effect (OPcache, APC, WinCache)
- Conflict detection system to identify limits defined elsewhere
- Manual configuration fallback with copy-paste code
- System diagnostics showing file permissions and configuration status

#### User Interface
- Modern, intuitive design with card-based layout
- Quick preset configurations (128M/256M, 256M/512M, 512M/1G, 1G/2G)
- Real-time input validation with instant feedback
- Loading states during form submission
- Clear success/error messages
- Copy-to-clipboard for manual configuration
- Fully responsive layout for desktop, tablet, and mobile

#### Advanced Features
- Values comparison (wp-config.php vs WordPress actual values)
- Conflict resolution with specific solutions
- Settings link on Plugins page for quick access
- External links open in new tabs (rel="noopener")

### 🔒 Security

- Nonce verification for CSRF protection
- Capability checks (administrator-only access)
- Input sanitization for all user input
- Secure file operations with error recovery
- Automatic backup and restore on failure

### 📚 Documentation

- Comprehensive README.md
- Complete readme.txt for WordPress.org
- Inline code documentation
- Help section within plugin interface

### 🛠️ Technical

- **WordPress Compatibility:** 6.0 - 6.7+
- **PHP Compatibility:** 7.4, 8.0, 8.1, 8.2, 8.3
- **Text Domain:** memory-limit-manager
- **License:** GPL v2 or later
- **Coding Standards:** WordPress Coding Standards compliant

---

## Future Releases

### Planned for 1.1.0
- Memory usage analytics
- Historical tracking
- Email notifications for memory issues
- Additional translations

### Planned for 1.2.0
- More preset configurations
- Performance insights
- Automatic optimization recommendations
- WP-CLI support

---

**Note:** This is the first public release. Future versions will maintain backward compatibility and follow semantic versioning.

**Download:** https://wordpress.org/plugins/memory-limit-manager/  
**Support:** https://wordpress.org/support/plugin/memory-limit-manager/  
**Documentation:** Full documentation included in plugin
