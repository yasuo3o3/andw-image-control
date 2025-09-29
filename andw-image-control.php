<?php
/**
 * Plugin Name: andW Media Control
 * Description: 画像品質のカスタマイズ、PNG→JPEG自動変換、独自画像サイズ管理、SVG対応、メディアライブラリUI拡張を提供します。
 * Version: 0.2.0
 * Author: Netservice
 * Author URI: https://netservice.jp/
 * License: GPLv2 or later
 * Text Domain: andw-image-control
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ANDW_IMAGE_CONTROL_VERSION', '0.2.0');
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