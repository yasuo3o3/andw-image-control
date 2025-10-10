<?php

if (!defined('ABSPATH')) {
    exit;
}

class AndwMediaUI {

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
        add_action('wp_ajax_andw_get_mime_type', array($this, 'ajax_get_mime_type'));
        add_action('wp_ajax_andw_get_mime_types_batch', array($this, 'ajax_get_mime_types_batch'));
        add_filter('wp_prepare_attachment_for_js', array($this, 'add_mime_type_to_js'), 10, 3);
    }

    public function enqueue_media_scripts($hook) {
        if ($hook !== 'upload.php' && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        // ファイル更新時間を使ったキャッシュ制御
        $css_file = ANDW_IMAGE_CONTROL_PLUGIN_DIR . 'assets/css/media-ui.css';
        $js_file = ANDW_IMAGE_CONTROL_PLUGIN_DIR . 'assets/js/media-ui.js';

        $css_version = file_exists($css_file) ? filemtime($css_file) : ANDW_IMAGE_CONTROL_VERSION;
        $js_version = file_exists($js_file) ? filemtime($js_file) : ANDW_IMAGE_CONTROL_VERSION;

        wp_enqueue_style(
            'andw-media-ui',
            ANDW_IMAGE_CONTROL_PLUGIN_URL . 'assets/css/media-ui.css',
            array(),
            $css_version
        );

        wp_enqueue_script(
            'andw-media-ui',
            ANDW_IMAGE_CONTROL_PLUGIN_URL . 'assets/js/media-ui.js',
            array('jquery'),
            $js_version,
            true
        );

        wp_localize_script('andw-media-ui', 'andwMediaUI', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('andw_mime_type_nonce'),
        ));
    }

    public function ajax_get_mime_type() {
        check_ajax_referer('andw_mime_type_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_die(esc_html__('ファイル操作の権限がありません', 'andw-image-control'));
        }

        if (!isset($_POST['attachment_id']) || empty($_POST['attachment_id'])) {
            wp_die(esc_html__('無効な添付ファイルID', 'andw-image-control'));
        }

        $attachment_id = intval($_POST['attachment_id']);
        if (!$attachment_id) {
            wp_die(esc_html__('無効な添付ファイルID', 'andw-image-control'));
        }

        $mime_type = get_post_mime_type($attachment_id);
        $label = $this->get_mime_type_label($mime_type);

        wp_send_json_success(array(
            'label' => $label,
            'class' => $this->get_mime_type_class($mime_type),
        ));
    }

    public function ajax_get_mime_types_batch() {
        check_ajax_referer('andw_mime_type_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_die(esc_html__('ファイル操作の権限がありません', 'andw-image-control'));
        }

        if (!isset($_POST['attachment_ids']) || !is_array($_POST['attachment_ids'])) {
            wp_die(esc_html__('無効な添付ファイルID配列', 'andw-image-control'));
        }

        $attachment_ids = array_map('intval', $_POST['attachment_ids']);
        $attachment_ids = array_filter($attachment_ids);

        if (empty($attachment_ids)) {
            wp_die(esc_html__('無効な添付ファイルID配列', 'andw-image-control'));
        }

        $results = array();
        foreach ($attachment_ids as $attachment_id) {
            $mime_type = get_post_mime_type($attachment_id);
            $results[$attachment_id] = array(
                'label' => $this->get_mime_type_label($mime_type),
                'class' => $this->get_mime_type_class($mime_type),
            );
        }

        wp_send_json_success($results);
    }

    public function add_mime_type_to_js($response, $attachment, $meta) {
        if (isset($attachment->post_mime_type)) {
            $response['andw_mime_label'] = $this->get_mime_type_label($attachment->post_mime_type);
            $response['andw_mime_class'] = $this->get_mime_type_class($attachment->post_mime_type);
        }
        return $response;
    }

    private function get_mime_type_label($mime_type) {
        switch ($mime_type) {
            case 'image/jpeg':
                return __('JPG', 'andw-image-control');
            case 'image/png':
                return __('PNG', 'andw-image-control');
            case 'image/gif':
                return __('GIF', 'andw-image-control');
            case 'image/svg+xml':
                return __('SVG', 'andw-image-control');
            case 'image/webp':
                return __('WebP', 'andw-image-control');
            default:
                return __('Other', 'andw-image-control');
        }
    }

    private function get_mime_type_class($mime_type) {
        switch ($mime_type) {
            case 'image/jpeg':
                return 'andw-mime-jpg';
            case 'image/png':
                return 'andw-mime-png';
            case 'image/gif':
                return 'andw-mime-gif';
            case 'image/svg+xml':
                return 'andw-mime-svg';
            case 'image/webp':
                return 'andw-mime-webp';
            default:
                return 'andw-mime-other';
        }
    }
}