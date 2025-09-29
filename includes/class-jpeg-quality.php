<?php

if (!defined('ABSPATH')) {
    exit;
}

class AndwJpegQuality {

    public function __construct() {
        add_filter('jpeg_quality', array($this, 'set_jpeg_quality'), 10, 2);
        add_filter('wp_editor_set_quality', array($this, 'set_jpeg_quality'), 10, 2);
    }

    public function set_jpeg_quality($quality, $mime_type = null) {
        if ($mime_type && $mime_type !== 'image/jpeg') {
            return $quality;
        }

        global $_wp_additional_image_sizes;

        $current_size = $this->get_current_image_size();

        if ($current_size) {
            $quality_option = get_option('andw_jpeg_quality_' . $current_size);
            if ($quality_option && is_numeric($quality_option)) {
                return intval($quality_option);
            }
        }

        $default_quality = get_option('andw_jpeg_quality_default', 82);
        return is_numeric($default_quality) ? intval($default_quality) : 82;
    }

    private function get_current_image_size() {
        global $_wp_additional_image_sizes;

        if (!empty($GLOBALS['andw_current_image_size'])) {
            return $GLOBALS['andw_current_image_size'];
        }

        // デフォルト品質を使用（デバッグ関数の代替）
        return null;
    }

    public static function get_available_image_sizes() {
        $sizes = array();

        $sizes['thumbnail'] = __('サムネイル', 'andw-image-control') . ' (' . get_option('thumbnail_size_w') . 'x' . get_option('thumbnail_size_h') . ')';
        $sizes['medium'] = __('中サイズ', 'andw-image-control') . ' (' . get_option('medium_size_w') . 'x' . get_option('medium_size_h') . ')';
        $sizes['large'] = __('大サイズ', 'andw-image-control') . ' (' . get_option('large_size_w') . 'x' . get_option('large_size_h') . ')';
        $sizes['full'] = __('フルサイズ', 'andw-image-control');

        $additional_sizes = wp_get_additional_image_sizes();
        foreach ($additional_sizes as $name => $data) {
            $label = ucwords(str_replace(array('-', '_'), ' ', $name));
            $sizes[$name] = $label . ' (' . $data['width'] . 'x' . $data['height'] . ')';
        }

        return $sizes;
    }
}