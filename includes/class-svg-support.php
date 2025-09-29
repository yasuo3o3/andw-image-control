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
        $allowed_tags = array(
            'svg', 'g', 'path', 'circle', 'ellipse', 'line', 'rect', 'polyline', 'polygon',
            'text', 'tspan', 'defs', 'clipPath', 'mask', 'pattern', 'linearGradient',
            'radialGradient', 'stop', 'use', 'symbol', 'marker', 'title', 'desc'
        );

        $allowed_attributes = array(
            'id', 'class', 'style', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
            'width', 'height', 'd', 'fill', 'stroke', 'stroke-width', 'stroke-linecap',
            'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset', 'opacity',
            'fill-opacity', 'stroke-opacity', 'transform', 'viewBox', 'xmlns',
            'xmlns:xlink', 'gradientUnits', 'offset', 'stop-color', 'stop-opacity',
            'points', 'font-family', 'font-size', 'font-weight', 'text-anchor'
        );

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
        );

        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        $dom = new DOMDocument();
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = true;

        libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($content);
        libxml_clear_errors();

        if (!$loaded) {
            return false;
        }

        $this->sanitize_dom_node($dom->documentElement, $allowed_tags, $allowed_attributes);

        return $dom->saveXML();
    }

    private function sanitize_dom_node($node, $allowed_tags, $allowed_attributes) {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        if (!in_array(strtolower($node->nodeName), $allowed_tags)) {
            $node->parentNode->removeChild($node);
            return;
        }

        $attributes_to_remove = array();
        foreach ($node->attributes as $attribute) {
            if (!in_array(strtolower($attribute->name), $allowed_attributes)) {
                $attributes_to_remove[] = $attribute->name;
            }
        }

        foreach ($attributes_to_remove as $attr_name) {
            $node->removeAttribute($attr_name);
        }

        $children_to_process = array();
        foreach ($node->childNodes as $child) {
            $children_to_process[] = $child;
        }

        foreach ($children_to_process as $child) {
            $this->sanitize_dom_node($child, $allowed_tags, $allowed_attributes);
        }
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