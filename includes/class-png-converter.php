<?php

if (!defined('ABSPATH')) {
    exit;
}

class AndwPngConverter {

    public function __construct() {
        add_filter('wp_handle_upload_prefilter', array($this, 'convert_png_to_jpeg'));
        add_action('add_attachment', array($this, 'create_jpeg_version'));
    }

    public function convert_png_to_jpeg($file) {
        if (!$this->should_convert_png()) {
            return $file;
        }

        if ($file['type'] !== 'image/png') {
            return $file;
        }

        $this->store_original_png_info($file);
        return $file;
    }

    public function create_jpeg_version($attachment_id) {
        if (!$this->should_convert_png()) {
            return;
        }

        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_mime_type !== 'image/png') {
            return;
        }

        $original_file = get_attached_file($attachment_id);
        if (!$original_file || !file_exists($original_file)) {
            return;
        }

        $jpeg_file = $this->convert_png_file_to_jpeg($original_file);
        if (!$jpeg_file) {
            return;
        }

        $this->create_jpeg_attachment($attachment_id, $jpeg_file);
    }

    private function convert_png_file_to_jpeg($png_file) {
        if (!function_exists('imagecreatefrompng') || !function_exists('imagejpeg')) {
            return false;
        }

        $png_image = imagecreatefrompng($png_file);
        if (!$png_image) {
            return false;
        }

        $width = imagesx($png_image);
        $height = imagesy($png_image);

        $jpeg_image = imagecreatetruecolor($width, $height);
        if (!$jpeg_image) {
            imagedestroy($png_image);
            return false;
        }

        $white = imagecolorallocate($jpeg_image, 255, 255, 255);
        imagefill($jpeg_image, 0, 0, $white);

        imagecopy($jpeg_image, $png_image, 0, 0, 0, 0, $width, $height);

        $path_info = pathinfo($png_file);

        // 既存ファイルとの衝突を回避
        $jpeg_file = wp_unique_filename($path_info['dirname'], $path_info['filename'] . '.jpg');
        $jpeg_file = $path_info['dirname'] . '/' . $jpeg_file;

        $quality = get_option('andw_png_to_jpeg_quality', 85);
        $success = imagejpeg($jpeg_image, $jpeg_file, intval($quality));

        imagedestroy($png_image);
        imagedestroy($jpeg_image);

        return $success ? $jpeg_file : false;
    }

    private function create_jpeg_attachment($png_attachment_id, $jpeg_file) {
        $png_attachment = get_post($png_attachment_id);
        if (!$png_attachment) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $relative_path = str_replace($upload_dir['basedir'] . '/', '', $jpeg_file);

        $attachment_data = array(
            'post_mime_type' => 'image/jpeg',
            /* translators: %s: 元の PNG 画像のタイトル */
            'post_title' => sprintf(__('%s (JPEG)', 'andw-image-control'), $png_attachment->post_title),
            'post_content' => $png_attachment->post_content,
            'post_excerpt' => $png_attachment->post_excerpt,
            'post_status' => 'inherit',
            'post_parent' => $png_attachment->post_parent,
        );

        $jpeg_attachment_id = wp_insert_attachment($attachment_data, $relative_path);

        if (!is_wp_error($jpeg_attachment_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $metadata = wp_generate_attachment_metadata($jpeg_attachment_id, $jpeg_file);
            wp_update_attachment_metadata($jpeg_attachment_id, $metadata);

            update_post_meta($jpeg_attachment_id, '_andw_converted_from_png', $png_attachment_id);
            update_post_meta($png_attachment_id, '_andw_jpeg_version', $jpeg_attachment_id);

            return $jpeg_attachment_id;
        }

        return false;
    }

    private function should_convert_png() {
        return get_option('andw_convert_png_to_jpeg', false);
    }

    private function store_original_png_info($file) {
        if (!isset($GLOBALS['andw_png_conversion_queue'])) {
            $GLOBALS['andw_png_conversion_queue'] = array();
        }
        $GLOBALS['andw_png_conversion_queue'][] = $file;
    }
}