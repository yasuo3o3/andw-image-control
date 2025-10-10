=== andW Image Control ===
Contributors: yasuo3o3
Tags: media, jpeg, png, image-quality, compression
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress media control with JPEG quality settings, PNG conversion, custom sizes, SVG support, and enhanced media library.

== Description ==

andW Image Control extends WordPress image processing capabilities with comprehensive media management features.

**Key Features:**

* **Custom JPEG Quality:** Set different JPEG quality levels for each image size
* **PNG to JPEG Conversion:** Automatic conversion during upload with quality control
* **8 Custom Image Sizes:** Pre-configured sizes (360px, 480px, 600px, 720px, 960px, 1200px, 1440px, 1920px)
* **Enhanced Media Library:** MIME type labels and improved UI
* **Secure SVG Support:** Upload SVG files with comprehensive security sanitization
* **WordPress Standards Compliance:** Follows WordPress coding and security standards

This plugin is designed for developers and site administrators who need precise control over image processing and media management.

**日本語説明:**

andW Image Control は WordPress の画像処理を拡張するプラグインです。JPEG品質のカスタマイズ、PNG→JPEG自動変換、独自画像サイズ管理、SVG対応、メディアライブラリUI拡張を提供します。

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/andw-image-control` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Media to configure the plugin options
4. Adjust JPEG quality settings, enable PNG to JPEG conversion, and configure SVG upload as needed

**Installation (日本語):**

1. プラグインファイルを `/wp-content/plugins/andw-image-control` ディレクトリにアップロード
2. WordPress の管理画面でプラグインを有効化
3. 「設定」>「メディア」で各種設定を調整

== Frequently Asked Questions ==

= What JPEG quality range can I set? =

You can set JPEG quality from 1 to 100. WordPress default is 82.

= Are PNG transparency preserved during conversion? =

No, transparency is not preserved. PNG files are converted with white background. Original PNG files are also retained.

= Is SVG upload secure? =

Yes, SVG files are sanitized using WordPress standard wp_kses() function with comprehensive security checks to prevent XSS and XXE attacks.

= Does this plugin affect site performance? =

The plugin is optimized for performance and only processes images during upload. It uses singleton patterns and proper WordPress hooks to minimize overhead.

**FAQ (日本語):**

= JPEG 品質はどの程度設定できますか？ =

1から100の範囲で設定できます。WordPress標準は82です。

= PNG から JPEG への変換で透過は保持されますか？ =

透過は保持されず、白背景で塗りつぶされます。元のPNGファイルも保持されます。

== Changelog ==

= 0.3.1 =
* **Major**: Added recommended quality values button for easy optimal settings application
* **Major**: Improved user experience with visual feedback and guided quality configuration
* **Enhanced**: Quality settings now use recommended values system instead of ineffective defaults
* **Fixed**: Removed meaningless default values that were never applied due to WordPress initialization order
* **Changed**: Author information updated to match WordPress.org submission requirements

= 0.3.0 =
* **Critical**: Fixed WordPress.DB.DirectDatabaseQuery violations in uninstall.php (replaced with WordPress API)
* **Major**: Enhanced AJAX security with upload_files permission checks
* **Major**: Improved CSS/JS versioning with filemtime-based cache control
* **Changed**: WordPress.org review compliance completed
* **Changed**: PHP 8.1 requirement enforcement with version check
* **Fixed**: Autoload optimization for non-critical options to improve performance
* **Security**: Complete nonce verification and permission check implementation
* **Security**: WordPress.Security.EscapeOutput compliance strengthened

= 0.2.0 =
* **Security**: Fixed libxml_disable_entity_loader() deprecation and LIBXML_NOENT DoS vulnerability in SVG processing
* **Security**: Migrated to WordPress standard wp_kses() based secure SVG sanitization
* **Security**: Enhanced WordPress security standards compliance (input validation, output escaping, nonce verification)
* **Fixed**: Resolved AndwImageSizes class singleton pattern to prevent hook duplicate registration
* **Fixed**: Media modal thumbnail display issues (CSS position:relative conflicts)
* **Fixed**: Thumbnail crop checkbox save functionality
* **Changed**: Code review responses and quality improvements

= 0.01 =
* Initial release
* JPEG quality customization by image size
* PNG to JPEG automatic conversion
* 8 custom image sizes (360px to 1920px)
* Media library MIME type labels
* Secure SVG upload support