# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2025-09-30

### Security
- libxml_disable_entity_loader() の非推奨化対応と LIBXML_NOENT DoS脆弱性を修正
- DOMDocument から WordPress標準の wp_kses() ベースの安全なSVGサニタイズに移行
- WordPress セキュリティ標準への準拠強化（入力値検証、出力エスケープ、nonce検証）

### Fixed
- AndwImageSizes クラスのシングルトンパターン実装でフック重複登録問題を解決
- メディアモーダルでのサムネイル表示問題を修正（CSS position: relative 競合の解決）
- サムネイルクロップチェックボックスの保存状態が正しく保存されない問題を修正
- remove_settings_field() 関数の存在しないエラーを修正

### Changed
- レビュー指摘事項への対応とコード品質向上
- WordPress コーディング標準への準拠向上

## [0.01] - 2025-09-29

### Added
- 初回リリース
- JPEG 品質をサイズごとにカスタマイズ機能
- PNG アップロード時の自動 JPEG 変換機能
- 独自画像サイズの追加（8種類）
- メディアライブラリでの MIME タイプラベル表示
- SVG ファイルのアップロード対応（セキュリティサニタイズ付き）