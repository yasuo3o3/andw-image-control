# andW Image Control

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Advanced WordPress media control plugin with JPEG quality customization, PNG to JPEG conversion, custom image sizes, SVG support, and enhanced media library UI.

## Features

- **Custom JPEG Quality**: Set different JPEG quality levels for each image size
- **PNG to JPEG Conversion**: Automatic conversion during upload with quality control
- **8 Custom Image Sizes**: Pre-configured sizes (360px, 480px, 600px, 720px, 960px, 1200px, 1440px, 1920px)
- **Enhanced Media Library**: MIME type labels and improved UI
- **Secure SVG Support**: Upload SVG files with comprehensive security sanitization
- **WordPress Standards Compliance**: Follows WordPress coding and security standards

## Installation

1. Download the plugin or clone this repository
2. Upload to `/wp-content/plugins/andw-image-control` directory
3. Activate the plugin through WordPress admin
4. Configure settings under Settings > Media

## Usage

### JPEG Quality Settings
Navigate to **Settings > Media** to configure:
- Default JPEG quality for uploads
- PNG to JPEG conversion toggle
- Individual quality settings for custom image sizes

### Custom Image Sizes
The plugin automatically registers 8 custom image sizes:
- `thumb-sm`: 360x360px (cropped)
- `thumb-md`: 480x480px (cropped)
- `thumb-lg`: 600x600px (cropped)
- `content-sm`: 720px width (proportional)
- `content-md`: 960px width (proportional)
- `content-lg`: 1200px width (proportional)
- `hero-md`: 1440px width (proportional)
- `hero-lg`: 1920px width (proportional)

### SVG Upload
Enable secure SVG uploads in Settings > Media. All SVG files are sanitized using WordPress standards to prevent security vulnerabilities.

## Requirements

- WordPress 5.0 or higher
- PHP 8.1 or higher

## Development Status

This plugin is **ready for submission** to the official WordPress Plugin Directory. All code follows WordPress coding standards (WPCS), has undergone comprehensive security review, and meets WordPress.org review requirements including:

- WPCS compliance with zero errors
- Complete security audit (XSS, CSRF, SQLi prevention)
- Proper nonce verification and permission checks
- WordPress API-only database operations
- PHP 8.1+ compatibility with version enforcement

## License

GPL-2.0-or-later

---

## 日本語説明

WordPress の画像処理を拡張する高機能プラグインです。

### 主な機能
- JPEG品質のカスタマイズ（サイズ別設定可能）
- PNG→JPEG自動変換機能
- 8種類のカスタム画像サイズ
- メディアライブラリUI拡張
- セキュアなSVGアップロード対応

### 設定方法
「設定」>「メディア」から各種設定を調整できます。

このプラグインはWordPress公式ディレクトリへの提出を予定しており、WordPress標準に準拠した開発を行っています。