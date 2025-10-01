<?php
/**
 * andW Media Control プラグインアンインストール処理
 *
 * プラグイン削除時にデータベースから関連オプションを削除
 */

// WordPress からの正当なアンインストール呼び出しでない場合は終了
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// andw_ プレフィックスの固定オプション削除
$fixed_options = array(
    'andw_jpeg_quality_default',
    'andw_convert_png_to_jpeg',
    'andw_png_to_jpeg_quality',
    'andw_enable_svg_upload',
    'andw_svg_sanitize',
    'andw_thumbnail_override_size',
    'andw_medium_override_size',
    'andw_large_override_size',
    'andw_jpeg_quality_thumbnail',
    'andw_jpeg_quality_medium',
    'andw_jpeg_quality_large',
    'andw_jpeg_quality_medium_large',
    'andw_jpeg_quality_1536x1536',
    'andw_jpeg_quality_2048x2048',
);

foreach ($fixed_options as $option) {
    delete_option($option);
}

// カスタム画像サイズの動的オプション削除
$custom_sizes = array('thumb-sm', 'thumb-md', 'thumb-lg', 'content-sm', 'content-md', 'content-lg', 'hero-sm', 'hero-md', 'hero-lg');

foreach ($custom_sizes as $size_name) {
    delete_option('andw_image_width_' . $size_name);
    delete_option('andw_image_height_' . $size_name);
    delete_option('andw_jpeg_quality_' . $size_name);
}

// WordPress標準画像サイズのカスタム品質設定削除（768px, 1536px, 2048px）
$standard_sizes = array('768', '1536', '2048');
foreach ($standard_sizes as $size) {
    delete_option('andw_jpeg_quality_' . $size);
}

// その他の andw_ プレフィックスオプションを安全に削除（将来の拡張対応）
$all_options = wp_load_alloptions();
foreach ($all_options as $option_name => $option_value) {
    if (strpos($option_name, 'andw_') === 0) {
        delete_option($option_name);
    }
}

// トランジェントキャッシュの削除（存在する場合）
delete_transient('andw_image_sizes_cache');
delete_transient('andw_jpeg_quality_cache');

// オプションのautoloadキャッシュをクリア
wp_cache_flush();