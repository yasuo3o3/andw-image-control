<?php

if (!defined('ABSPATH')) {
    exit;
}

class AndwImageControlSettings {

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'modify_default_media_fields'));
    }

    public function register_settings() {
        register_setting('media', 'andw_jpeg_quality_default', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
            'default' => 82,
        ));

        register_setting('media', 'andw_convert_png_to_jpeg', array(
            'type' => 'boolean',
            'default' => true,
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
        $custom_sizes = $this->get_custom_image_sizes();

        foreach ($image_sizes as $size_name => $size_label) {
            if (isset($custom_sizes[$size_name])) {
                register_setting('media', 'andw_image_width_' . $size_name, array(
                    'type' => 'integer',
                    'sanitize_callback' => array($this, 'sanitize_dimension'),
                    'default' => $custom_sizes[$size_name]['width'],
                ));
                register_setting('media', 'andw_image_height_' . $size_name, array(
                    'type' => 'integer',
                    'sanitize_callback' => array($this, 'sanitize_dimension'),
                    'default' => $custom_sizes[$size_name]['height'],
                ));
            }
            // 標準サイズの場合は別途処理
            if (!in_array($size_name, array('thumbnail', 'medium', 'large', 'full'))) {
                register_setting('media', 'andw_jpeg_quality_' . $size_name, array(
                    'type' => 'integer',
                    'sanitize_callback' => array($this, 'sanitize_quality'),
                    'default' => 82,
                ));
            }
        }

        add_settings_section(
            'andw_image_control_section',
            __('andW Media Control 設定', 'andw-image-control'),
            array($this, 'section_callback'),
            'media'
        );


        foreach ($image_sizes as $size_name => $size_label) {
            if (isset($custom_sizes[$size_name])) {
                $label = $custom_sizes[$size_name]['label'];
                add_settings_field(
                    'andw_image_size_' . $size_name,
                    $label,
                    array($this, 'image_size_field_callback'),
                    'media',
                    'andw_image_control_section',
                    array('size_name' => $size_name, 'size_data' => $custom_sizes[$size_name])
                );
            } else {
                // 標準サイズの品質設定は標準セクションで処理するためここでは除外
                if (!in_array($size_name, array('thumbnail', 'medium', 'large', 'full'))) {
                    // 特定サイズのラベルを変更
                    $custom_label = $size_label;
                    if ($size_name === '2048x2048') {
                        $custom_label = __('標準非表示設定2048の品質', 'andw-image-control');
                    } elseif ($size_name === '1536x1536') {
                        $custom_label = __('標準非表示設定1536の品質', 'andw-image-control');
                    }

                    add_settings_field(
                        'andw_jpeg_quality_' . $size_name,
                        $custom_label,
                        array($this, 'quality_field_callback'),
                        'media',
                        'andw_image_control_section',
                        array('option_name' => 'andw_jpeg_quality_' . $size_name, 'label' => '')
                    );
                }
            }
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
            __('PNG→JPEG 品質', 'andw-image-control'),
            array($this, 'simple_quality_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_png_to_jpeg_quality')
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

        $size_options = AndwImageSizes::get_size_options();

        add_settings_field(
            'andw_thumbnail_override_size',
            __('サムネイル上書きサイズ', 'andw-image-control'),
            array($this, 'select_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_thumbnail_override_size', 'options' => $size_options)
        );

        add_settings_field(
            'andw_medium_override_size',
            __('中サイズ上書きサイズ', 'andw-image-control'),
            array($this, 'select_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_medium_override_size', 'options' => $size_options)
        );

        add_settings_field(
            'andw_large_override_size',
            __('大サイズ上書きサイズ', 'andw-image-control'),
            array($this, 'select_field_callback'),
            'media',
            'andw_image_control_section',
            array('option_name' => 'andw_large_override_size', 'options' => $size_options)
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

    public function sanitize_dimension($value) {
        if ($value === '' || $value === null) {
            return 0;
        }

        $value = intval($value);
        return ($value < 0) ? 0 : $value;
    }

    private function get_custom_image_sizes() {
        $image_sizes_instance = new AndwImageSizes();
        return $image_sizes_instance->get_custom_sizes();
    }

    public function image_size_field_callback($args) {
        $size_name = $args['size_name'];
        $size_data = $args['size_data'];

        $width_option = 'andw_image_width_' . $size_name;
        $height_option = 'andw_image_height_' . $size_name;
        $quality_option = 'andw_jpeg_quality_' . $size_name;

        $width_value = get_option($width_option, $size_data['width']);
        $height_value = get_option($height_option, $size_data['height']);
        $quality_value = get_option($quality_option, 82);

        echo '<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">';
        echo '<span>幅</span>';
        echo '<input type="number" id="' . esc_attr($width_option) . '" name="' . esc_attr($width_option) . '" value="' . esc_attr($width_value) . '" min="0" class="small-text" style="width: 70px;" />';
        echo '<span>×</span>';
        echo '<span>高さ</span>';
        echo '<input type="number" id="' . esc_attr($height_option) . '" name="' . esc_attr($height_option) . '" value="' . esc_attr($height_value) . '" min="0" class="small-text" style="width: 70px;" />';
        echo '<span>／</span>';
        echo '<span>品質</span>';
        echo '<input type="number" id="' . esc_attr($quality_option) . '" name="' . esc_attr($quality_option) . '" value="' . esc_attr($quality_value ?: 82) . '" min="1" max="100" class="small-text" style="width: 70px;" />';
        echo '</div>';
    }

    public function simple_quality_field_callback($args) {
        $option_name = $args['option_name'];
        $value = get_option($option_name, 85);

        echo '<input type="number" id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" value="' . esc_attr($value) . '" min="1" max="100" class="small-text" />';
    }

    public function modify_default_media_fields() {
        // 標準サイズの品質設定を登録
        register_setting('media', 'andw_jpeg_quality_thumbnail', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
            'default' => 82,
        ));
        register_setting('media', 'andw_jpeg_quality_medium', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
            'default' => 82,
        ));
        register_setting('media', 'andw_jpeg_quality_large', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
            'default' => 82,
        ));

        // WordPressの標準フィールドの後に品質フィールドを追加するスクリプトを追加
        add_action('admin_footer-options-media.php', array($this, 'add_quality_fields_script'));
    }

    public function add_quality_fields_script() {
        $thumbnail_quality = get_option('andw_jpeg_quality_thumbnail', 82);
        $medium_quality = get_option('andw_jpeg_quality_medium', 82);
        $large_quality = get_option('andw_jpeg_quality_large', 82);
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // サムネイル品質フィールドを追加
            var thumbnailRow = $('input[name="thumbnail_size_h"]').closest('tr');
            if (thumbnailRow.length) {
                var thumbnailCell = thumbnailRow.find('td').first();
                thumbnailCell.append(' ／ 品質 <input type="number" name="andw_jpeg_quality_thumbnail" value="<?php echo esc_attr($thumbnail_quality); ?>" min="1" max="100" class="small-text" style="width: 70px;" />');
            }

            // 中サイズ品質フィールドを追加
            var mediumRow = $('input[name="medium_size_h"]').closest('tr');
            if (mediumRow.length) {
                var mediumCell = mediumRow.find('td').first();
                mediumCell.append(' ／ 品質 <input type="number" name="andw_jpeg_quality_medium" value="<?php echo esc_attr($medium_quality); ?>" min="1" max="100" class="small-text" style="width: 70px;" />');
            }

            // 大サイズ品質フィールドを追加
            var largeRow = $('input[name="large_size_h"]').closest('tr');
            if (largeRow.length) {
                var largeCell = largeRow.find('td').first();
                largeCell.append(' ／ 品質 <input type="number" name="andw_jpeg_quality_large" value="<?php echo esc_attr($large_quality); ?>" min="1" max="100" class="small-text" style="width: 70px;" />');
            }
        });
        </script>
        <?php
    }

    public function select_field_callback($args) {
        $option_name = $args['option_name'];
        $options = $args['options'];
        $value = get_option($option_name, '');

        echo '<select id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '">';
        foreach ($options as $key => $option_label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($key, $value, false) . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
    }
}