<?php

if (!defined('ABSPATH')) {
    exit;
}

class AndwSvgSupport {

    public function __construct() {
        add_filter('upload_mimes', array($this, 'add_svg_mime_type'));
        add_filter('wp_check_filetype_and_ext', array($this, 'fix_svg_mime_type'), 10, 4);
        add_filter('wp_handle_upload_prefilter', array($this, 'sanitize_svg'));
        add_action('admin_head', array($this, 'fix_svg_display'));
    }

    public function add_svg_mime_type($mimes) {
        if (!get_option('andw_enable_svg_upload', false)) {
            return $mimes;
        }

        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }

    public function fix_svg_mime_type($data, $file, $filename, $mimes) {
        if (!get_option('andw_enable_svg_upload', false)) {
            return $data;
        }

        $ext = isset($data['ext']) ? $data['ext'] : '';
        $type = isset($data['type']) ? $data['type'] : '';

        if (strlen($ext) < 1) {
            $exploded = explode('.', $filename);
            $ext = strtolower(end($exploded));
        }

        if ($ext === 'svg') {
            $data['type'] = 'image/svg+xml';
            $data['ext'] = 'svg';
        }

        return $data;
    }

    public function sanitize_svg($file) {
        if (!get_option('andw_enable_svg_upload', false)) {
            return $file;
        }

        if ($file['type'] !== 'image/svg+xml') {
            return $file;
        }

        if (!get_option('andw_svg_sanitize', true)) {
            return $file;
        }

        $svg_content = file_get_contents($file['tmp_name']);
        if ($svg_content === false) {
            $file['error'] = __('SVGファイルの読み込みに失敗しました', 'andw-image-control');
            return $file;
        }

        $sanitized_content = $this->sanitize_svg_content($svg_content);
        if ($sanitized_content === false) {
            $file['error'] = __('安全でないSVGファイルです', 'andw-image-control');
            return $file;
        }

        file_put_contents($file['tmp_name'], $sanitized_content);

        return $file;
    }

    private function sanitize_svg_content($content) {
        // WordPress推奨のwp_kses()を使用した安全なSVGサニタイズ
        $allowed_tags = array(
            'svg' => array(
                'class' => array(),
                'id' => array(),
                'width' => array(),
                'height' => array(),
                'viewbox' => array(),
                'xmlns' => array(),
                'xmlns:xlink' => array(),
            ),
            'g' => array(
                'class' => array(),
                'id' => array(),
                'transform' => array(),
                'fill' => array(),
                'stroke' => array(),
            ),
            'path' => array(
                'class' => array(),
                'id' => array(),
                'd' => array(),
                'fill' => array(),
                'stroke' => array(),
                'stroke-width' => array(),
                'transform' => array(),
            ),
            'circle' => array(
                'class' => array(),
                'id' => array(),
                'cx' => array(),
                'cy' => array(),
                'r' => array(),
                'fill' => array(),
                'stroke' => array(),
                'stroke-width' => array(),
            ),
            'ellipse' => array(
                'class' => array(),
                'id' => array(),
                'cx' => array(),
                'cy' => array(),
                'rx' => array(),
                'ry' => array(),
                'fill' => array(),
                'stroke' => array(),
                'stroke-width' => array(),
            ),
            'line' => array(
                'class' => array(),
                'id' => array(),
                'x1' => array(),
                'y1' => array(),
                'x2' => array(),
                'y2' => array(),
                'stroke' => array(),
                'stroke-width' => array(),
            ),
            'rect' => array(
                'class' => array(),
                'id' => array(),
                'x' => array(),
                'y' => array(),
                'width' => array(),
                'height' => array(),
                'fill' => array(),
                'stroke' => array(),
                'stroke-width' => array(),
            ),
            'polyline' => array(
                'class' => array(),
                'id' => array(),
                'points' => array(),
                'fill' => array(),
                'stroke' => array(),
                'stroke-width' => array(),
            ),
            'polygon' => array(
                'class' => array(),
                'id' => array(),
                'points' => array(),
                'fill' => array(),
                'stroke' => array(),
                'stroke-width' => array(),
            ),
            'text' => array(
                'class' => array(),
                'id' => array(),
                'x' => array(),
                'y' => array(),
                'font-family' => array(),
                'font-size' => array(),
                'font-weight' => array(),
                'text-anchor' => array(),
                'fill' => array(),
            ),
            'tspan' => array(
                'class' => array(),
                'id' => array(),
                'x' => array(),
                'y' => array(),
                'font-family' => array(),
                'font-size' => array(),
                'font-weight' => array(),
                'text-anchor' => array(),
                'fill' => array(),
            ),
            'defs' => array(
                'class' => array(),
                'id' => array(),
            ),
            'clippath' => array(
                'class' => array(),
                'id' => array(),
            ),
            'mask' => array(
                'class' => array(),
                'id' => array(),
            ),
            'pattern' => array(
                'class' => array(),
                'id' => array(),
                'x' => array(),
                'y' => array(),
                'width' => array(),
                'height' => array(),
            ),
            'lineargradient' => array(
                'class' => array(),
                'id' => array(),
                'gradientunits' => array(),
                'x1' => array(),
                'y1' => array(),
                'x2' => array(),
                'y2' => array(),
            ),
            'radialgradient' => array(
                'class' => array(),
                'id' => array(),
                'gradientunits' => array(),
                'cx' => array(),
                'cy' => array(),
                'r' => array(),
            ),
            'stop' => array(
                'class' => array(),
                'id' => array(),
                'offset' => array(),
                'stop-color' => array(),
                'stop-opacity' => array(),
            ),
            'use' => array(
                'class' => array(),
                'id' => array(),
                'x' => array(),
                'y' => array(),
                'width' => array(),
                'height' => array(),
                'href' => array(),
                'xlink:href' => array(),
            ),
            'symbol' => array(
                'class' => array(),
                'id' => array(),
                'viewbox' => array(),
            ),
            'marker' => array(
                'class' => array(),
                'id' => array(),
                'markerwidth' => array(),
                'markerheight' => array(),
                'refx' => array(),
                'refy' => array(),
                'orient' => array(),
            ),
            'title' => array(),
            'desc' => array(),
        );

        // 危険なパターンのチェック（追加の安全策）
        $dangerous_patterns = array(
            '/<script[^>]*?>.*?<\/script>/is',
            '/on\w+\s*=/i',
            '/javascript:/i',
            '/data:text\/html/i',
            '/<iframe[^>]*?>.*?<\/iframe>/is',
            '/<object[^>]*?>.*?<\/object>/is',
            '/<embed[^>]*?>/is',
            '/<link[^>]*?>/is',
            '/<meta[^>]*?>/is',
            '/<!ENTITY/i',
            '/<!DOCTYPE[^>]*\[/i',
        );

        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        // WordPress標準のwp_kses()を使用した安全なHTML/XMLサニタイズ
        $sanitized_content = wp_kses($content, $allowed_tags);

        // 追加の検証：正しいXMLフォーマットかチェック
        if (empty($sanitized_content) || strpos($sanitized_content, '<svg') === false) {
            return false;
        }

        return $sanitized_content;
    }


    public function fix_svg_display() {
        echo '<style type="text/css">
            .attachment-266x266, .thumbnail img {
                width: 100% !important;
                height: auto !important;
            }
            .media-icon img[src$=".svg"] {
                width: 100%;
                height: auto;
            }
        </style>';
    }
}