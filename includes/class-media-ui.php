<?php

if (!defined('ABSPATH')) {
    exit;
}

class AndwMediaUI {

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
        add_action('wp_ajax_andw_get_mime_type', array($this, 'ajax_get_mime_type'));
    }

    public function enqueue_media_scripts($hook) {
        if ($hook !== 'upload.php' && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        wp_enqueue_style(
            'andw-media-ui',
            ANDW_IMAGE_CONTROL_PLUGIN_URL . 'assets/css/media-ui.css',
            array(),
            ANDW_IMAGE_CONTROL_VERSION
        );

        wp_enqueue_script(
            'andw-media-ui',
            ANDW_IMAGE_CONTROL_PLUGIN_URL . 'assets/js/media-ui.js',
            array('jquery'),
            ANDW_IMAGE_CONTROL_VERSION,
            true
        );

        wp_localize_script('andw-media-ui', 'andwMediaUI', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('andw_mime_type_nonce'),
        ));
    }

    public function ajax_get_mime_type() {
        check_ajax_referer('andw_mime_type_nonce', 'nonce');

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

    private function get_mime_type_label($mime_type) {
        switch ($mime_type) {
            case 'image/jpeg':
                return 'JPG';
            case 'image/png':
                return 'PNG';
            case 'image/gif':
                return 'GIF';
            case 'image/svg+xml':
                return 'SVG';
            case 'image/webp':
                return 'WebP';
            default:
                return 'Other';
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