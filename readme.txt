=== andW Media Control ===
Contributors: netservicejp
Tags: media, jpeg, png, svg, image-quality
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.01
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

画像品質のカスタマイズ、PNG→JPEG自動変換、独自画像サイズ管理、SVG対応、メディアライブラリUI拡張を提供します。

== Description ==

andW Media Control は WordPress の画像処理を拡張するプラグインです。

**主な機能:**

* JPEG 品質をサイズごとにカスタマイズ
* PNG アップロード時の自動 JPEG 変換
* 独自画像サイズの追加（360px、480px、600px、720px、960px、1200px、1440px、1920px）
* メディアライブラリでの MIME タイプラベル表示
* SVG ファイルのアップロード対応（セキュリティサニタイズ付き）

== Installation ==

1. プラグインファイルを `/wp-content/plugins/andw-image-control` ディレクトリにアップロード
2. WordPress の管理画面でプラグインを有効化
3. 「設定」>「メディア」で各種設定を調整

== Frequently Asked Questions ==

= JPEG 品質はどの程度設定できますか？ =

1から100の範囲で設定できます。WordPress標準は82です。

= PNG から JPEG への変換で透過は保持されますか？ =

透過は保持されず、白背景で塗りつぶされます。元のPNGファイルも保持されます。

== Changelog ==

= 0.01 =
* 初回リリース