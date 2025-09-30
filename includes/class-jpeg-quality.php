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

        // 1. ファイル名からサイズ情報を推測（確実性が高い）
        $size_name = $this->extract_size_from_filename($resized);

        // 2. バックトレースでサイズ情報を取得（フォールバック）
        if (!$size_name) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
            $size_name = $this->extract_size_from_backtrace($backtrace);
        }

        if ($size_name) {
            $GLOBALS['andw_current_image_size'] = $size_name;
            $this->write_debug_log("Set current image size: " . $size_name);
        } else {
            $this->write_debug_log("Failed to determine image size");
        }

        return $resized;
    }

    /**
     * バックトレースからサイズ名を抽出する
     */
    private function extract_size_from_backtrace($backtrace) {
        $this->write_debug_log("Analyzing backtrace...");

        foreach ($backtrace as $i => $trace) {
            $this->write_debug_log("Frame $i: " . (isset($trace['function']) ? $trace['function'] : 'unknown'));

            if (isset($trace['function']) && $trace['function'] === 'make_subsize') {
                // make_subsizeの引数からサイズ情報を推測
                if (isset($trace['args']) && is_array($trace['args'])) {
                    $args = $trace['args'];
                    if (isset($args[0]) && is_array($args[0])) {
                        $size_array = $args[0];
                        $this->write_debug_log("Found make_subsize with args: " . json_encode($size_array));
                        return $this->determine_size_name($size_array);
                    }
                }
            }

            // その他のフレームでサイズ情報が含まれる場合の処理
            if (isset($trace['args']) && is_array($trace['args'])) {
                foreach ($trace['args'] as $arg) {
                    if (is_string($arg) && in_array($arg, ['thumbnail', 'medium', 'large', 'full'])) {
                        $this->write_debug_log("Found size string in args: " . $arg);
                        return $arg;
                    }
                }
            }
        }

        return null;
    }

    /**
     * ファイル名からサイズ情報を推測する
     */
    private function extract_size_from_filename($filepath) {
        $filename = basename($filepath);
        $this->write_debug_log("Analyzing filename: " . $filename);

        // ファイル名のパターン: filename-scaled-X-WIDTHxHEIGHT.extension または filename-WIDTHxHEIGHT.extension
        if (preg_match('/-(\d+)x(\d+)\.jpg$/', $filename, $matches)) {
            $width = intval($matches[1]);
            $height = intval($matches[2]);
            $this->write_debug_log("Extracted dimensions from filename: {$width}x{$height}");

            // サイズ情報から対応するサイズ名を特定
            $size_name = $this->determine_size_name(array('width' => $width, 'height' => $height));
            if ($size_name) {
                $this->write_debug_log("Matched size name from filename: " . $size_name);
                return $size_name;
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

        $this->write_debug_log("Trying to match size: {$width}x{$height}");

        // 標準サイズとの照合（高さ0の場合は幅のみ照合）
        $thumb_w = get_option('thumbnail_size_w');
        $thumb_h = get_option('thumbnail_size_h');
        if ($width == $thumb_w && $height == $thumb_h) {
            $this->write_debug_log("Matched thumbnail: {$thumb_w}x{$thumb_h}");
            return 'thumbnail';
        }

        $medium_w = get_option('medium_size_w');
        $medium_h = get_option('medium_size_h');
        if ($width == $medium_w && ($medium_h == 0 || $height == $medium_h)) {
            $this->write_debug_log("Matched medium: {$medium_w}x{$medium_h}");
            return 'medium';
        }

        $large_w = get_option('large_size_w');
        $large_h = get_option('large_size_h');
        if ($width == $large_w && ($large_h == 0 || $height == $large_h)) {
            $this->write_debug_log("Matched large: {$large_w}x{$large_h}");
            return 'large';
        }

        // カスタムサイズとの照合（高さ0の場合は幅のみ照合）
        $additional_sizes = wp_get_additional_image_sizes();
        foreach ($additional_sizes as $name => $data) {
            $this->write_debug_log("Checking custom size {$name}: {$data['width']}x{$data['height']}");
            if ($width == $data['width'] && ($data['height'] == 0 || $height == $data['height'])) {
                $this->write_debug_log("Matched custom size: {$name}");
                return $name;
            }
        }

        $this->write_debug_log("No size match found for {$width}x{$height}");
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