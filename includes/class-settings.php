<?php

if (!defined('ABSPATH')) {
    exit;
}

class AndwImageControlSettings {

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
    }

    public function register_settings() {
        register_setting('media', 'andw_jpeg_quality_default', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
            'default' => 82,
        ));

        register_setting('media', 'andw_convert_png_to_jpeg', array(
            'type' => 'boolean',
            'default' => false,
        ));

        register_setting('media', 'andw_png_to_jpeg_quality', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
            'default' => 85,
        ));

        register_setting('media', 'andw_enable_svg_upload', array(
            'type' => 'boolean',
            'default' => false,
        ));

        register_setting('media', 'andw_svg_sanitize', array(
            'type' => 'boolean',
            'default' => true,
        ));

        register_setting('media', 'andw_override_default_sizes', array(
            'type' => 'boolean',
            'default' => false,
        ));

        register_setting('media', 'andw_thumbnail_override_size', array(
            'type' => 'string',
            'default' => '',
        ));

        register_setting('media', 'andw_medium_override_size', array(
            'type' => 'string',
            'default' => '',
        ));

        register_setting('media', 'andw_large_override_size', array(
            'type' => 'string',
            'default' => '',
        ));

        $image_sizes = AndwJpegQuality::get_available_image_sizes();
        foreach ($image_sizes as $size_name => $size_label) {
            register_setting('media', 'andw_jpeg_quality_' . $size_name, array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_quality'),
                'default' => '',
            ));
        }

        add_settings_section(
            'andw_image_control_section',
            __('andW Media Control 設定', 'andw-image-control'),
            array($this, 'section_callback'),
            'media'
        );

        add_settings_field(
            'andw_jpeg_quality_default',
            __('デフォルトJPEG品質', 'andw-image-control'),
            array($this, 'quality_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_jpeg_quality_default', 'label' => __('すべてのサイズのデフォルト品質（1-100）', 'andw-image-control'))
        );

        foreach ($image_sizes as $size_name => $size_label) {
            add_settings_field(
                'andw_jpeg_quality_' . $size_name,
                sprintf(__('%s JPEG品質', 'andw-image-control'), $size_label),
                array($this, 'quality_field_callback'),
                'media',
                'andw_image_control_section',
                array('option_name' => 'andw_jpeg_quality_' . $size_name, 'label' => sprintf(__('%sの品質（空白の場合はデフォルト使用）', 'andw-image-control'), $size_label))
            );
        }

        add_settings_field(
            'andw_convert_png_to_jpeg',
            __('PNG→JPEG変換', 'andw-image-control'),
            array($this, 'checkbox_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_convert_png_to_jpeg', 'label' => __('PNGアップロード時に自動でJPEGバージョンを作成', 'andw-image-control'))
        );

        add_settings_field(
            'andw_png_to_jpeg_quality',
            __('PNG→JPEG変換品質', 'andw-image-control'),
            array($this, 'quality_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_png_to_jpeg_quality', 'label' => __('PNG→JPEG変換時の品質（1-100）', 'andw-image-control'))
        );

        add_settings_field(
            'andw_enable_svg_upload',
            __('SVGアップロード', 'andw-image-control'),
            array($this, 'checkbox_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_enable_svg_upload', 'label' => __('SVGファイルのアップロードを許可', 'andw-image-control'))
        );

        add_settings_field(
            'andw_svg_sanitize',
            __('SVGサニタイズ', 'andw-image-control'),
            array($this, 'checkbox_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_svg_sanitize', 'label' => __('SVGアップロード時にセキュリティサニタイズを実行', 'andw-image-control'))
        );

        add_settings_field(
            'andw_override_default_sizes',
            __('標準サイズ上書き', 'andw-image-control'),
            array($this, 'checkbox_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_override_default_sizes', 'label' => __('WordPress標準の画像サイズを独自サイズで上書き', 'andw-image-control'))
        );

        $size_options = AndwImageSizes::get_size_options();

        add_settings_field(
            'andw_thumbnail_override_size',
            __('サムネイル上書きサイズ', 'andw-image-control'),
            array($this, 'select_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_thumbnail_override_size', 'options' => $size_options, 'label' => __('サムネイルサイズの上書き設定', 'andw-image-control'))
        );

        add_settings_field(
            'andw_medium_override_size',
            __('中サイズ上書きサイズ', 'andw-image-control'),
            array($this, 'select_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_medium_override_size', 'options' => $size_options, 'label' => __('中サイズの上書き設定', 'andw-image-control'))
        );

        add_settings_field(
            'andw_large_override_size',
            __('大サイズ上書きサイズ', 'andw-image-control'),
            array($this, 'select_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_large_override_size', 'options' => $size_options, 'label' => __('大サイズの上書き設定', 'andw-image-control'))
        );
    }

    public function add_settings_page() {
    }

    public function section_callback() {
        echo '<p>' . esc_html__('andW Media Controlプラグインの設定を調整してください。', 'andw-image-control') . '</p>';
    }

    public function quality_field_callback($args) {
        $option_name = $args['option_name'];
        $label = $args['label'];
        $value = get_option($option_name, '');

        echo '<input type="number" id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" value="' . esc_attr($value) . '" min="1" max="100" class="small-text" />';
        echo '<p class="description">' . esc_html($label) . '</p>';
    }

    public function checkbox_field_callback($args) {
        $option_name = $args['option_name'];
        $label = $args['label'];
        $value = get_option($option_name, false);

        echo '<input type="checkbox" id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="' . esc_attr($option_name) . '">' . esc_html($label) . '</label>';
    }

    public function select_field_callback($args) {
        $option_name = $args['option_name'];
        $options = $args['options'];
        $label = $args['label'];
        $value = get_option($option_name, '');

        echo '<select id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '">';
        foreach ($options as $key => $option_label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($key, $value, false) . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html($label) . '</p>';
    }

    public function sanitize_quality($value) {
        if ($value === '' || $value === null) {
            return '';
        }

        $value = intval($value);
        if ($value < 1) {
            return 1;
        }
        if ($value > 100) {
            return 100;
        }
        return $value;
    }
}