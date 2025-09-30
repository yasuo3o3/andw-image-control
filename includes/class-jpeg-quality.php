<?php

if (!defined('ABSPATH')) {
    exit;
}

class AndwJpegQuality {

    public function __construct() {
        add_filter('jpeg_quality', array($this, 'set_jpeg_quality'), 10, 2);
        add_filter('wp_editor_set_quality', array($this, 'set_jpeg_quality'), 10, 2);

        // ステップ1: サイズ情報確認のためのフック追加
        add_filter('wp_generate_attachment_metadata', array($this, 'debug_attachment_metadata'), 10, 2);
        add_filter('image_make_intermediate_size', array($this, 'capture_image_size'), 10, 1);
    }

    public function set_jpeg_quality($quality, $mime_type = null) {
        if ($mime_type && $mime_type !== 'image/jpeg') {
            return $quality;
        }

        global $_wp_additional_image_sizes;

        $current_size = $this->get_current_image_size();

        // ステップ3: 品質設定適用のデバッグ
        $this->write_debug_log("=== set_jpeg_quality called ===");
        $this->write_debug_log("Original quality: " . $quality);
        $this->write_debug_log("Current size: " . ($current_size ?: 'null'));

        if ($current_size) {
            $quality_option = get_option('andw_jpeg_quality_' . $current_size);
            $this->write_debug_log("Quality option for {$current_size}: " . ($quality_option ?: 'not set'));
            if ($quality_option && is_numeric($quality_option)) {
                $this->write_debug_log("Applied quality: " . intval($quality_option));
                return intval($quality_option);
            }
        }

        $default_quality = get_option('andw_jpeg_quality_default', 82);
        $this->write_debug_log("Applied default quality: " . $default_quality);
        return is_numeric($default_quality) && $default_quality != '' ? intval($default_quality) : 82;
    }

    /**
     * ステップ1: wp_generate_attachment_metadata フィルタでデバッグ情報を出力
     */
    public function debug_attachment_metadata($metadata, $attachment_id) {
        $this->write_debug_log("=== wp_generate_attachment_metadata called ===");
        $this->write_debug_log("Attachment ID: " . $attachment_id);

        // バックトレースで呼び出し元を確認
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $this->write_debug_log("Call stack:");
        foreach ($backtrace as $i => $trace) {
            if (isset($trace['function'])) {
                $this->write_debug_log("  [$i] " . (isset($trace['class']) ? $trace['class'] . '::' : '') . $trace['function']);
            }
        }

        return $metadata;
    }

    /**
     * ステップ1: image_make_intermediate_size フィルタでサイズ情報をキャプチャ
     */
    public function capture_image_size($resized) {
        // サイズ情報をログに出力
        $this->write_debug_log("=== image_make_intermediate_size called ===");
        $this->write_debug_log("Resized: " . $resized);

        // バックトレースでサイズ情報を取得
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        $size_name = $this->extract_size_from_backtrace($backtrace);

        if ($size_name) {
            $GLOBALS['andw_current_image_size'] = $size_name;
            $this->write_debug_log("Set current image size: " . $size_name);
        }

        return $resized;
    }

    /**
     * バックトレースからサイズ名を抽出する
     */
    private function extract_size_from_backtrace($backtrace) {
        foreach ($backtrace as $trace) {
            if (isset($trace['function']) && $trace['function'] === 'make_subsize') {
                // make_subsizeの引数からサイズ情報を推測
                if (isset($trace['args']) && is_array($trace['args'])) {
                    $args = $trace['args'];
                    if (isset($args[0]) && is_array($args[0])) {
                        $size_array = $args[0];
                        return $this->determine_size_name($size_array);
                    }
                }
            }

            // その他のフレームでサイズ情報が含まれる場合の処理
            if (isset($trace['args']) && is_array($trace['args'])) {
                foreach ($trace['args'] as $arg) {
                    if (is_string($arg) && in_array($arg, ['thumbnail', 'medium', 'large', 'full'])) {
                        return $arg;
                    }
                }
            }
        }

        return null;
    }

    /**
     * サイズ配列からサイズ名を特定する
     */
    private function determine_size_name($size) {
        if (is_string($size)) {
            return $size;
        }

        if (!is_array($size) || !isset($size['width']) || !isset($size['height'])) {
            return null;
        }

        $width = $size['width'];
        $height = $size['height'];

        // 標準サイズとの照合
        if ($width == get_option('thumbnail_size_w') && $height == get_option('thumbnail_size_h')) {
            return 'thumbnail';
        }
        if ($width == get_option('medium_size_w') && $height == get_option('medium_size_h')) {
            return 'medium';
        }
        if ($width == get_option('large_size_w') && $height == get_option('large_size_h')) {
            return 'large';
        }

        // カスタムサイズとの照合
        $additional_sizes = wp_get_additional_image_sizes();
        foreach ($additional_sizes as $name => $data) {
            if ($width == $data['width'] && $height == $data['height']) {
                return $name;
            }
        }

        return null;
    }

    /**
     * デバッグログ出力
     */
    private function write_debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[andW Image Control] ' . $message);
        }
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