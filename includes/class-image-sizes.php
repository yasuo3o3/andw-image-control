<?php

if (!defined('ABSPATH')) {
    exit;
}

class AndwImageSizes {

    private static $hooks_registered = false;

    private $custom_sizes = array(
        'thumb-sm' => array('width' => 360, 'height' => 360, 'crop' => true, 'label' => 'サムネイル小'),
        'thumb-md' => array('width' => 480, 'height' => 480, 'crop' => true, 'label' => 'サムネイル中'),
        'thumb-lg' => array('width' => 600, 'height' => 600, 'crop' => true, 'label' => 'サムネイル大'),
        'content-sm' => array('width' => 720, 'height' => 0, 'crop' => false, 'label' => 'コンテンツ小'),
        'content-md' => array('width' => 960, 'height' => 0, 'crop' => false, 'label' => 'コンテンツ中'),
        'content-lg' => array('width' => 1200, 'height' => 0, 'crop' => false, 'label' => 'コンテンツ大'),
        'hero-md' => array('width' => 1440, 'height' => 0, 'crop' => false, 'label' => 'ヒーロー中'),
        'hero-lg' => array('width' => 1920, 'height' => 0, 'crop' => false, 'label' => 'ヒーロー大'),
    );

    public function __construct() {
        if (!self::$hooks_registered) {
            add_action('after_setup_theme', array($this, 'add_custom_image_sizes'));
            add_filter('image_size_names_choose', array($this, 'add_custom_sizes_to_media_chooser'));
            add_action('admin_init', array($this, 'update_default_image_sizes'));
            self::$hooks_registered = true;
        }
    }

    public function add_custom_image_sizes() {
        foreach ($this->custom_sizes as $name => $data) {
            $width = get_option('andw_image_width_' . $name, $data['width']);
            $height = get_option('andw_image_height_' . $name, $data['height']);
            add_image_size($name, intval($width), intval($height), $data['crop']);
        }
    }

    public function add_custom_sizes_to_media_chooser($sizes) {
        foreach ($this->custom_sizes as $name => $data) {
            $width = get_option('andw_image_width_' . $name, $data['width']);
            $height = get_option('andw_image_height_' . $name, $data['height']);
            $sizes[$name] = $data['label'] . ' (' . $width . 'x' . ($height ?: '自動') . ')';
        }
        return $sizes;
    }


    public function update_default_image_sizes() {
        // 設定保存時のみ実行
        if (isset($_POST['option_page']) && $_POST['option_page'] === 'media') {
            // nonce検証と権限チェック
            check_admin_referer('media-options');

            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            $thumbnail_size = get_option('andw_thumbnail_override_size', '');
            $medium_size = get_option('andw_medium_override_size', '');
            $large_size = get_option('andw_large_override_size', '');

            if ($thumbnail_size && isset($this->custom_sizes[$thumbnail_size])) {
                update_option('thumbnail_size_w', $this->custom_sizes[$thumbnail_size]['width']);
                update_option('thumbnail_size_h', $this->custom_sizes[$thumbnail_size]['height']);
                update_option('thumbnail_crop', $this->custom_sizes[$thumbnail_size]['crop']);
            }

            if ($medium_size && isset($this->custom_sizes[$medium_size])) {
                update_option('medium_size_w', $this->custom_sizes[$medium_size]['width']);
                update_option('medium_size_h', $this->custom_sizes[$medium_size]['height']);
            }

            if ($large_size && isset($this->custom_sizes[$large_size])) {
                update_option('large_size_w', $this->custom_sizes[$large_size]['width']);
                update_option('large_size_h', $this->custom_sizes[$large_size]['height']);
            }
        }
    }

    public function get_custom_sizes() {
        return $this->custom_sizes;
    }

    public static function get_instance() {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }

    public static function get_custom_sizes_static() {
        return self::get_instance()->custom_sizes;
    }

    public static function get_size_options() {
        $instance = self::get_instance();

        $options = array('' => __('変更しない', 'andw-image-control'));

        foreach ($instance->custom_sizes as $name => $data) {
            $options[$name] = $data['label'] . ' (' . $data['width'] . 'x' . ($data['height'] ?: '自動') . ')';
        }

        return $options;
    }

}