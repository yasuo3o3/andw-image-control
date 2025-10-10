<?php
/**
 * Plugin Name: andW Image Control
 * Description: 画像品質のカスタマイズ、PNG→JPEG自動変換、独自画像サイズ管理、SVG対応、メディアライブラリUI拡張を提供します。
 * Version: 0.3.1
 * Author: yasuo3o3
 * Author URI: https://yasuo-o.xyz/
 * License: GPLv2 or later
 * Text Domain: andw-image-control
 */

if (!defined('ABSPATH')) {
    exit;
}

// PHP 8.1以上が必要
if (version_compare(PHP_VERSION, '8.1', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('andW Image Control プラグインはPHP 8.1以上が必要です。現在のバージョン: ', 'andw-image-control') . PHP_VERSION;
        echo '</p></div>';
    });
    return;
}

define('ANDW_IMAGE_CONTROL_VERSION', '0.3.1');
define('ANDW_IMAGE_CONTROL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ANDW_IMAGE_CONTROL_PLUGIN_URL', plugin_dir_url(__FILE__));

class AndwImageControl {

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        $this->load_includes();
        $this->init_hooks();
    }

    private function load_includes() {
        require_once ANDW_IMAGE_CONTROL_PLUGIN_DIR . 'includes/class-jpeg-quality.php';
        require_once ANDW_IMAGE_CONTROL_PLUGIN_DIR . 'includes/class-png-converter.php';
        require_once ANDW_IMAGE_CONTROL_PLUGIN_DIR . 'includes/class-image-sizes.php';
        require_once ANDW_IMAGE_CONTROL_PLUGIN_DIR . 'includes/class-media-ui.php';
        require_once ANDW_IMAGE_CONTROL_PLUGIN_DIR . 'includes/class-svg-support.php';
        require_once ANDW_IMAGE_CONTROL_PLUGIN_DIR . 'includes/class-settings.php';
    }

    private function init_hooks() {
        new AndwJpegQuality();
        new AndwPngConverter();
        AndwImageSizes::get_instance();
        new AndwMediaUI();
        new AndwSvgSupport();
        new AndwImageControlSettings();
    }
}

new AndwImageControl();