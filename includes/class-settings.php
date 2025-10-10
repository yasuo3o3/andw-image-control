<?php

if (!defined('ABSPATH')) {
    exit;
}

class AndwImageControlSettings {

    /**
     * 推奨品質値の定義（追加・変更が簡単）
     */
    private function get_recommended_quality_values() {
        return array(
            // 標準サイズ（WordPress既定）
            'andw_jpeg_quality_thumbnail' => 82,
            'andw_jpeg_quality_medium' => 82,
            'andw_jpeg_quality_large' => 82,
            'andw_jpeg_quality_default' => 82,

            // WordPress隠しサイズ
            'andw_jpeg_quality_medium_large' => 50,
            'andw_jpeg_quality_1536x1536' => 56,
            'andw_jpeg_quality_2048x2048' => 62,

            // カスタムサイズ（サムネイル系）
            'andw_jpeg_quality_thumb-sm' => 50,
            'andw_jpeg_quality_thumb-md' => 50,
            'andw_jpeg_quality_thumb-lg' => 50,

            // カスタムサイズ（コンテンツ系）
            'andw_jpeg_quality_content-sm' => 50,
            'andw_jpeg_quality_content-md' => 50,
            'andw_jpeg_quality_content-lg' => 53,

            // カスタムサイズ（ヒーロー系）
            'andw_jpeg_quality_hero-sm' => 56,
            'andw_jpeg_quality_hero-md' => 59,
            'andw_jpeg_quality_hero-lg' => 65,

            // PNG→JPEG変換品質
            'andw_png_to_jpeg_quality' => 85,
        );
    }

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'modify_default_media_fields'));
    }

    public function register_settings() {
        // パフォーマンス最適化: 頻繁に使われないオプションのautoload無効化
        $this->ensure_autoload_optimization();

        register_setting('media', 'andw_jpeg_quality_default', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
        ));

        register_setting('media', 'andw_convert_png_to_jpeg', array(
            'type' => 'boolean',
        ));

        register_setting('media', 'andw_png_to_jpeg_quality', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
        ));

        register_setting('media', 'andw_enable_svg_upload', array(
            'type' => 'boolean',
        ));

        register_setting('media', 'andw_svg_sanitize', array(
            'type' => 'boolean',
        ));

        register_setting('media', 'andw_thumbnail_override_size', array(
            'type' => 'string',
        ));

        register_setting('media', 'andw_medium_override_size', array(
            'type' => 'string',
        ));

        register_setting('media', 'andw_large_override_size', array(
            'type' => 'string',
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
            // カスタムサイズの品質設定を登録（デフォルト値なし）
            if (!in_array($size_name, array('thumbnail', 'medium', 'large', 'full'))) {
                register_setting('media', 'andw_jpeg_quality_' . $size_name, array(
                    'type' => 'integer',
                    'sanitize_callback' => array($this, 'sanitize_quality'),
                ));
            }
        }

        add_settings_section(
            'andw_image_control_section',
            __('andW Image Control 設定', 'andw-image-control'),
            array($this, 'section_callback'),
            'media'
        );

        // 品質セクション
        add_settings_section(
            'andw_quality_section',
            __('品質', 'andw-image-control'),
            array($this, 'quality_section_callback'),
            'media'
        );

        // 規定サイズ[非表示]セクション
        add_settings_section(
            'andw_standard_hidden_section',
            __('規定サイズ[非表示]', 'andw-image-control'),
            array($this, 'standard_hidden_section_callback'),
            'media'
        );

        // カスタムサイズセクション
        add_settings_section(
            'andw_custom_sizes_section',
            __('カスタムサイズ', 'andw-image-control'),
            array($this, 'custom_sizes_section_callback'),
            'media'
        );

        // SVGセクション
        add_settings_section(
            'andw_svg_section',
            __('SVG', 'andw-image-control'),
            array($this, 'svg_section_callback'),
            'media'
        );

        // 上書きサイズセクション
        add_settings_section(
            'andw_override_sizes_section',
            __('上書きサイズ', 'andw-image-control'),
            array($this, 'override_sizes_section_callback'),
            'media'
        );

        // 既存メディア処理セクション
        add_settings_section(
            'andw_existing_media_section',
            __('既存メディアの品質変更', 'andw-image-control'),
            array($this, 'existing_media_section_callback'),
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
                    'andw_custom_sizes_section',
                    array('size_name' => $size_name, 'size_data' => $custom_sizes[$size_name])
                );
            } else {
                // 標準サイズの品質設定は標準セクションで処理するためここでは除外
                if (!in_array($size_name, array('thumbnail', 'medium', 'large', 'full'))) {
                    // 特定サイズのラベルを変更
                    $custom_label = $size_label;
                    if ($size_name === '2048x2048') {
                        $custom_label = __('規定サイズ[非表示] 2048', 'andw-image-control');
                    } elseif ($size_name === '1536x1536') {
                        $custom_label = __('規定サイズ[非表示] 1536', 'andw-image-control');
                    } elseif ($size_name === 'medium_large') {
                        $custom_label = __('規定サイズ[非表示] 768', 'andw-image-control');
                    }

                    // 特定サイズには専用のコールバック関数を使用
                    if ($size_name === '2048x2048' || $size_name === '1536x1536' || $size_name === 'medium_large') {
                        add_settings_field(
                            'andw_jpeg_quality_' . $size_name,
                            $custom_label,
                            array($this, 'standard_hidden_size_field_callback'),
                            'media',
                            'andw_standard_hidden_section',
                            array('option_name' => 'andw_jpeg_quality_' . $size_name)
                        );
                    } else {
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
        }

        add_settings_field(
            'andw_jpeg_quality_default',
            __('デフォルトJPEG品質', 'andw-image-control'),
            array($this, 'simple_quality_field_callback'),
            'media',
            'andw_quality_section',
            array('option_name' => 'andw_jpeg_quality_default')
        );

        add_settings_field(
            'andw_convert_png_to_jpeg',
            __('PNG→JPEG変換', 'andw-image-control'),
            array($this, 'checkbox_field_callback'),
            'media',
            'andw_quality_section',
            array('option_name' => 'andw_convert_png_to_jpeg', 'label' => __('PNGアップロード時に自動でJPEGバージョンを作成', 'andw-image-control'))
        );

        add_settings_field(
            'andw_png_to_jpeg_quality',
            __('PNG→JPEG 品質', 'andw-image-control'),
            array($this, 'simple_quality_field_callback'),
            'media',
            'andw_quality_section',
            array('option_name' => 'andw_png_to_jpeg_quality')
        );

        add_settings_field(
            'andw_enable_svg_upload',
            __('SVGアップロード', 'andw-image-control'),
            array($this, 'checkbox_field_callback'),
            'media',
            'andw_svg_section',
            array('option_name' => 'andw_enable_svg_upload', 'label' => __('SVGファイルのアップロードを許可', 'andw-image-control'))
        );

        add_settings_field(
            'andw_svg_sanitize',
            __('SVGサニタイズ', 'andw-image-control'),
            array($this, 'checkbox_field_callback'),
            'media',
            'andw_svg_section',
            array('option_name' => 'andw_svg_sanitize', 'label' => __('SVGアップロード時にセキュリティサニタイズを実行', 'andw-image-control'))
        );

        $size_options = AndwImageSizes::get_size_options();

        add_settings_field(
            'andw_thumbnail_override_size',
            __('サムネイル上書きサイズ', 'andw-image-control'),
            array($this, 'select_field_callback'),
            'media',
            'andw_override_sizes_section',
            array('option_name' => 'andw_thumbnail_override_size', 'options' => $size_options)
        );

        add_settings_field(
            'andw_medium_override_size',
            __('中サイズ上書きサイズ', 'andw-image-control'),
            array($this, 'select_field_callback'),
            'media',
            'andw_override_sizes_section',
            array('option_name' => 'andw_medium_override_size', 'options' => $size_options)
        );

        add_settings_field(
            'andw_large_override_size',
            __('大サイズ上書きサイズ', 'andw-image-control'),
            array($this, 'select_field_callback'),
            'media',
            'andw_override_sizes_section',
            array('option_name' => 'andw_large_override_size', 'options' => $size_options)
        );

        add_settings_field(
            'andw_regeneration_info',
            __('再生成ツール', 'andw-image-control'),
            array($this, 'regeneration_info_callback'),
            'media',
            'andw_existing_media_section'
        );
    }

    public function add_settings_page() {
    }

    public function section_callback() {
        echo '<p>' . esc_html__('andW Image Controlプラグインの設定を調整してください。', 'andw-image-control') . '</p>';
    }

    public function quality_section_callback() {
        echo '<p style="margin-bottom: 15px;">' . esc_html__('JPEG品質とPNG変換に関する設定です。', 'andw-image-control') . '</p>';

        // 推奨値適用ボタン
        echo '<div class="andw-recommended-section" style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #0073aa; border-radius: 3px;">';
        echo '<h4 style="margin-top: 0; color: #0073aa;">' . esc_html__('推奨値設定', 'andw-image-control') . '</h4>';
        echo '<button type="button" id="andw-apply-recommended-quality" class="button button-secondary" style="margin-bottom: 10px;">';
        echo '<span class="dashicons dashicons-yes-alt" style="margin-right: 5px; line-height: 1;"></span>';
        echo esc_html__('推奨値を適用', 'andw-image-control');
        echo '</button>';
        echo '<p class="description" style="margin: 0; font-size: 13px;">';
        echo esc_html__('各サイズに最適化された品質値をフォームに入力します。保存は手動で「変更を保存」ボタンを押してください。', 'andw-image-control');
        echo '</p>';
        $this->render_recommended_values_info();
        echo '</div>';
    }

    public function standard_hidden_section_callback() {
        echo '<p style="margin-bottom: 15px;">' . esc_html__('WordPressの標準サイズで、メディア選択時には表示されない規定サイズの品質設定です。', 'andw-image-control') . '</p>';
    }

    public function custom_sizes_section_callback() {
        echo '<p style="margin-bottom: 15px;">' . esc_html__('独自に追加されたカスタム画像サイズの設定です。', 'andw-image-control') . '</p>';
    }

    public function svg_section_callback() {
        echo '<p style="margin-bottom: 15px;">' . esc_html__('SVGファイルのアップロードとセキュリティに関する設定です。', 'andw-image-control') . '</p>';
    }

    public function override_sizes_section_callback() {
        echo '<p style="margin-bottom: 15px;">' . esc_html__('WordPress標準の画像サイズを、カスタムサイズで上書きする設定です。', 'andw-image-control') . '</p>';
    }

    public function existing_media_section_callback() {
        echo '<p style="margin-bottom: 15px;">' . esc_html__('設定変更後に既存のメディアファイルを再処理するためのツール情報です。', 'andw-image-control') . '</p>';
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
        return AndwImageSizes::get_custom_sizes_static();
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


    public function standard_hidden_size_field_callback($args) {
        $option_name = $args['option_name'];
        $value = get_option($option_name, 82);

        echo '<div style="display: flex; align-items: center; gap: 8px;">';
        echo '<span>品質</span>';
        echo '<input type="number" id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" value="' . esc_attr($value) . '" min="1" max="100" class="small-text" style="width: 70px;" />';
        echo '</div>';

    }

    public function modify_default_media_fields() {
        // 標準サイズの品質設定を登録（デフォルト値なし - 推奨値ボタンで設定）
        register_setting('media', 'andw_jpeg_quality_thumbnail', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
        ));
        register_setting('media', 'andw_jpeg_quality_medium', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
        ));
        register_setting('media', 'andw_jpeg_quality_large', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
        ));

        // 標準非表示設定サイズの品質設定（デフォルト値なし - 推奨値ボタンで設定）
        register_setting('media', 'andw_jpeg_quality_medium_large', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
        ));
        register_setting('media', 'andw_jpeg_quality_1536x1536', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
        ));
        register_setting('media', 'andw_jpeg_quality_2048x2048', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
        ));

        // thumbnail_crop の保存処理
        add_filter('pre_update_option_thumbnail_crop', array($this, 'handle_thumbnail_crop_option'), 10, 3);

        // WordPressの標準フィールドの後に品質フィールドを追加するスクリプトを追加
        add_action('admin_footer-options-media.php', array($this, 'add_quality_fields_script'));
    }

    /**
     * autoload最適化: 頻繁に使用されないオプションのautoload無効化
     */
    private function ensure_autoload_optimization() {
        $non_autoload_options = array(
            'andw_png_to_jpeg_quality',  // PNG変換品質（変換機能使用時のみ）
            'andw_svg_sanitize',         // SVGサニタイズ（SVG使用時のみ）
            'andw_enable_svg_upload',    // SVG有効化（管理者設定時のみ）
        );

        foreach ($non_autoload_options as $option_name) {
            // 既存オプションのautoloadを無効化（存在する場合のみ）
            if (get_option($option_name) !== false) {
                $value = get_option($option_name);
                delete_option($option_name);
                add_option($option_name, $value, '', 'no');
            }
        }
    }


    public function handle_thumbnail_crop_option($value, $old_value, $option) {
        // メディア設定ページでの保存時のみ処理
        if (isset($_POST['option_page']) && $_POST['option_page'] === 'media') {
            // nonce検証と権限チェック
            check_admin_referer('media-options');

            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'andw-image-control'));
            }

            // チェックボックスがチェックされている場合は1、されていない場合は0
            return isset($_POST['thumbnail_crop']) ? 1 : 0;
        }

        // 他のページからの更新はそのまま通す
        return $value;
    }

    /**
     * 推奨値の説明を表示
     */
    private function render_recommended_values_info() {
        echo '<div style="margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">';
        echo '<strong>' . esc_html__('推奨値の内訳:', 'andw-image-control') . '</strong>';
        echo '<ul style="margin: 8px 0 0 20px; font-size: 12px;">';
        echo '<li><strong>' . esc_html__('標準サイズ', 'andw-image-control') . '</strong>: 82% (' . esc_html__('WordPress標準', 'andw-image-control') . ')</li>';
        echo '<li><strong>' . esc_html__('サムネイル系', 'andw-image-control') . '</strong>: 50% (' . esc_html__('軽量化重視', 'andw-image-control') . ')</li>';
        echo '<li><strong>' . esc_html__('コンテンツ系', 'andw-image-control') . '</strong>: 50-53% (' . esc_html__('バランス重視', 'andw-image-control') . ')</li>';
        echo '<li><strong>' . esc_html__('ヒーロー系', 'andw-image-control') . '</strong>: 56-65% (' . esc_html__('品質重視', 'andw-image-control') . ')</li>';
        echo '<li><strong>' . esc_html__('PNG変換', 'andw-image-control') . '</strong>: 85% (' . esc_html__('変換時高品質', 'andw-image-control') . ')</li>';
        echo '</ul>';
        echo '</div>';
    }

    public function add_quality_fields_script() {
        $thumbnail_quality = get_option('andw_jpeg_quality_thumbnail', 82);
        $medium_quality = get_option('andw_jpeg_quality_medium', 82);
        $large_quality = get_option('andw_jpeg_quality_large', 82);
        $recommended_values = $this->get_recommended_quality_values();
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // 推奨値設定
            const recommendedValues = <?php echo wp_json_encode($recommended_values); ?>;

            // 推奨値適用ボタンのイベントリスナー
            $('#andw-apply-recommended-quality').on('click', function(e) {
                e.preventDefault();

                let appliedCount = 0;
                Object.keys(recommendedValues).forEach(function(fieldId) {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.value = recommendedValues[fieldId];
                        // 視覚的フィードバック
                        $(field).css({
                            'background-color': '#fffbcc',
                            'transition': 'background-color 0.3s'
                        });
                        appliedCount++;

                        // 2秒後に元の色に戻す
                        setTimeout(function() {
                            $(field).css('background-color', '');
                        }, 2000);
                    }
                });

                // 成功メッセージ表示
                if (appliedCount > 0) {
                    // 既存の通知を削除
                    $('.andw-recommended-notice').remove();

                    const notice = $('<div class="notice notice-info inline andw-recommended-notice" style="margin: 15px 0; padding: 10px;">' +
                        '<p><strong>推奨値を入力しました。</strong> ' + appliedCount + '個の設定項目に値を設定しました。「変更を保存」ボタンで確定してください。</p>' +
                        '</div>');

                    $('.submit').before(notice);

                    // 5秒後に通知を自動削除
                    setTimeout(function() {
                        notice.fadeOut(function() {
                            notice.remove();
                        });
                    }, 5000);

                    // 保存ボタンまでスクロール
                    $('html, body').animate({
                        scrollTop: $('.submit').offset().top - 100
                    }, 500);
                }
            });

            // form内で最初以外のH2タイトルにスタイルを適用
            $('form h2:not(:first)').css({
                'border-top': '1px #ddd solid',
                'padding-top': '2rem',
                'margin-top': '2rem'
            });
            // 上書きサイズとサイズオプションのマッピング（品質設定も含む）
            var sizeMapping = {
                'thumb-sm': { width: 360, height: 0, quality: <?php echo esc_js(get_option('andw_jpeg_quality_thumb-sm', 50)); ?> },
                'thumb-md': { width: 480, height: 0, quality: <?php echo esc_js(get_option('andw_jpeg_quality_thumb-md', 50)); ?> },
                'thumb-lg': { width: 600, height: 0, quality: <?php echo esc_js(get_option('andw_jpeg_quality_thumb-lg', 50)); ?> },
                'content-sm': { width: 720, height: 0, quality: <?php echo esc_js(get_option('andw_jpeg_quality_content-sm', 50)); ?> },
                'content-md': { width: 960, height: 0, quality: <?php echo esc_js(get_option('andw_jpeg_quality_content-md', 50)); ?> },
                'content-lg': { width: 1200, height: 0, quality: <?php echo esc_js(get_option('andw_jpeg_quality_content-lg', 53)); ?> },
                'hero-sm': { width: 1440, height: 0, quality: <?php echo esc_js(get_option('andw_jpeg_quality_hero-sm', 56)); ?> },
                'hero-md': { width: 1920, height: 0, quality: <?php echo esc_js(get_option('andw_jpeg_quality_hero-md', 59)); ?> },
                'hero-lg': { width: 2560, height: 0, quality: <?php echo esc_js(get_option('andw_jpeg_quality_hero-lg', 65)); ?> }
            };

            // disabled制御関数
            function updateStandardSizeFields() {
                // サムネイル
                var thumbnailOverride = $('select[name="andw_thumbnail_override_size"]').val();
                var thumbnailInputs = $('input[name="thumbnail_size_w"], input[name="thumbnail_size_h"], input[name="andw_jpeg_quality_thumbnail"]');
                var thumbnailCrop = $('input[name="thumbnail_crop"]');

                if (thumbnailOverride && thumbnailOverride !== '' && sizeMapping[thumbnailOverride]) {
                    // 上書きサイズが選択されている場合
                    $('input[name="thumbnail_size_w"]').val(sizeMapping[thumbnailOverride].width);
                    $('input[name="thumbnail_size_h"]').val(sizeMapping[thumbnailOverride].height);
                    $('input[name="andw_jpeg_quality_thumbnail"]').val(sizeMapping[thumbnailOverride].quality);
                    thumbnailInputs.prop('disabled', true).css('background-color', '#f7f7f7');
                } else {
                    // 上書きサイズが選択されていない場合
                    thumbnailInputs.prop('disabled', false).css('background-color', '');
                }
                // サムネイルを実寸法に切り抜くは常に有効
                thumbnailCrop.prop('disabled', false);

                // 中サイズ
                var mediumOverride = $('select[name="andw_medium_override_size"]').val();
                var mediumInputs = $('input[name="medium_size_w"], input[name="medium_size_h"], input[name="andw_jpeg_quality_medium"]');

                if (mediumOverride && mediumOverride !== '' && sizeMapping[mediumOverride]) {
                    $('input[name="medium_size_w"]').val(sizeMapping[mediumOverride].width);
                    $('input[name="medium_size_h"]').val(sizeMapping[mediumOverride].height);
                    $('input[name="andw_jpeg_quality_medium"]').val(sizeMapping[mediumOverride].quality);
                    mediumInputs.prop('disabled', true).css('background-color', '#f7f7f7');
                } else {
                    mediumInputs.prop('disabled', false).css('background-color', '');
                }

                // 大サイズ
                var largeOverride = $('select[name="andw_large_override_size"]').val();
                var largeInputs = $('input[name="large_size_w"], input[name="large_size_h"], input[name="andw_jpeg_quality_large"]');

                if (largeOverride && largeOverride !== '' && sizeMapping[largeOverride]) {
                    $('input[name="large_size_w"]').val(sizeMapping[largeOverride].width);
                    $('input[name="large_size_h"]').val(sizeMapping[largeOverride].height);
                    $('input[name="andw_jpeg_quality_large"]').val(sizeMapping[largeOverride].quality);
                    largeInputs.prop('disabled', true).css('background-color', '#f7f7f7');
                } else {
                    largeInputs.prop('disabled', false).css('background-color', '');
                }
            }
            // サムネイルを横並び1行に変更
            var thumbnailRow = $('input[name="thumbnail_size_w"]').closest('tr');
            if (thumbnailRow.length) {
                var thumbnailTable = thumbnailRow.closest('table');
                var thumbnailIndex = thumbnailRow.index();

                // 既存の行を分割して再構成
                var widthInput = thumbnailRow.find('input[name="thumbnail_size_w"]');
                var heightInput = $('input[name="thumbnail_size_h"]');
                var cropCheckbox = $('input[name="thumbnail_crop"]');
                var cropLabel = cropCheckbox.next('label');

                // 横並び1行でサムネイル設定を作成
                var thumbnailRowHtml = '<tr style="margin-bottom: 15px;"><th scope="row">サムネイル</th>' +
                    '<td>' +
                    '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">' +
                    '<span>幅</span>' +
                    '<input type="number" name="thumbnail_size_w" value="' + widthInput.val() + '" min="0" class="small-text" style="width: 70px; text-align: right;" />' +
                    '<span>×</span>' +
                    '<span>高さ</span>' +
                    '<input type="number" name="thumbnail_size_h" value="' + heightInput.val() + '" min="0" class="small-text" style="width: 70px; text-align: right;" />' +
                    '<span>品質</span>' +
                    '<input type="number" name="andw_jpeg_quality_thumbnail" value="<?php echo esc_js($thumbnail_quality); ?>" min="1" max="100" class="small-text" style="width: 70px; text-align: right; margin-bottom: 1rem;" />' +
                    '</div>' +
                    '<div>' +
                    '<input type="checkbox" id="thumbnail_crop" name="thumbnail_crop" value="1" ' + (cropCheckbox.is(':checked') ? 'checked' : '') + ' /> ' +
                    '<label for="thumbnail_crop">' + cropLabel.text() + '</label>' +
                    '</div>' +
                    '</td></tr>';

                // 既存の行を削除
                thumbnailRow.remove();
                $('input[name="thumbnail_size_h"]').closest('tr').remove();

                // 新しい行を挿入
                thumbnailTable.find('tr').eq(thumbnailIndex - 1).after(thumbnailRowHtml);
            }

            // 中サイズを横並び1行に変更
            var mediumRow = $('input[name="medium_size_w"]').closest('tr');
            if (mediumRow.length) {
                var mediumTable = mediumRow.closest('table');
                var mediumIndex = mediumRow.index();

                var widthInput = mediumRow.find('input[name="medium_size_w"]');
                var heightInput = $('input[name="medium_size_h"]');

                var mediumRowHtml = '<tr style="margin-bottom: 15px;"><th scope="row">中サイズ</th>' +
                    '<td>' +
                    '<div style="display: flex; align-items: center; gap: 8px;">' +
                    '<span>幅</span>' +
                    '<input type="number" name="medium_size_w" value="' + widthInput.val() + '" min="0" class="small-text" style="width: 70px; text-align: right;" />' +
                    '<span>×</span>' +
                    '<span>高さ</span>' +
                    '<input type="number" name="medium_size_h" value="' + heightInput.val() + '" min="0" class="small-text" style="width: 70px; text-align: right;" />' +
                    '<span>品質</span>' +
                    '<input type="number" name="andw_jpeg_quality_medium" value="<?php echo esc_js($medium_quality); ?>" min="1" max="100" class="small-text" style="width: 70px; text-align: right; margin-bottom: 1rem;" />' +
                    '</div>' +
                    '</td></tr>';

                mediumRow.remove();
                $('input[name="medium_size_h"]').closest('tr').remove();

                mediumTable.find('tr').eq(mediumIndex - 1).after(mediumRowHtml);
            }

            // 大サイズを横並び1行に変更
            var largeRow = $('input[name="large_size_w"]').closest('tr');
            if (largeRow.length) {
                var largeTable = largeRow.closest('table');
                var largeIndex = largeRow.index();

                var widthInput = largeRow.find('input[name="large_size_w"]');
                var heightInput = $('input[name="large_size_h"]');

                var largeRowHtml = '<tr style="margin-bottom: 15px;"><th scope="row">大サイズ</th>' +
                    '<td>' +
                    '<div style="display: flex; align-items: center; gap: 8px;">' +
                    '<span>幅</span>' +
                    '<input type="number" name="large_size_w" value="' + widthInput.val() + '" min="0" class="small-text" style="width: 70px; text-align: right;" />' +
                    '<span>×</span>' +
                    '<span>高さ</span>' +
                    '<input type="number" name="large_size_h" value="' + heightInput.val() + '" min="0" class="small-text" style="width: 70px; text-align: right;" />' +
                    '<span>品質</span>' +
                    '<input type="number" name="andw_jpeg_quality_large" value="<?php echo esc_js($large_quality); ?>" min="1" max="100" class="small-text" style="width: 70px; text-align: right; margin-bottom: 1rem;" />' +
                    '</div>' +
                    '</td></tr>';

                largeRow.remove();
                $('input[name="large_size_h"]').closest('tr').remove();

                largeTable.find('tr').eq(largeIndex - 1).after(largeRowHtml);
            }

            // 初期状態を設定（ページ読み込み時）
            setTimeout(function() {
                updateStandardSizeFields();
            }, 100);

            // 上書きサイズ変更時のイベントリスナー
            $(document).on('change', 'select[name="andw_thumbnail_override_size"]', function() {
                updateStandardSizeFields();
            });

            $(document).on('change', 'select[name="andw_medium_override_size"]', function() {
                updateStandardSizeFields();
            });

            $(document).on('change', 'select[name="andw_large_override_size"]', function() {
                updateStandardSizeFields();
            });
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

    public function regeneration_info_callback() {
        echo '<p style="margin: 0 0 10px 0;">' . esc_html__('設定を変更した後、既存のメディアに新しい品質を適用するには、以下のプラグインを使用してサムネイルを再生成してください。', 'andw-image-control') . '</p>';
        echo '<ul style="margin: 10px 0; padding-left: 20px;">';
        echo '<li><a href="https://wordpress.org/plugins/regenerate-thumbnails/" target="_blank" rel="noopener noreferrer">Regenerate Thumbnails</a> - ' . esc_html__('標準的なサムネイル再生成プラグイン', 'andw-image-control') . '</li>';
        echo '<li><a href="https://wordpress.org/plugins/force-regenerate-thumbnails/" target="_blank" rel="noopener noreferrer">Force Regenerate Thumbnails</a> - ' . esc_html__('強制的にサムネイルを再生成する高機能プラグイン', 'andw-image-control') . '</li>';
        echo '</ul>';
        echo '<p style="margin: 10px 0 0 0; color: #666; font-size: 13px;">' . esc_html__('※これらのプラグインは別途インストールが必要です。', 'andw-image-control') . '</p>';
    }
}