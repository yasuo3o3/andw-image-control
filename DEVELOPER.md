# Developer Documentation - andW Image Control

## Development Environment Setup

### Requirements
- PHP 8.1 or higher (enforced with runtime check)
- WordPress 6.0 or higher (WordPress Plugin Directory compliance)
- Composer (for development dependencies and PHPCS)
- Node.js & npm (for build tools, if applicable)
- WP-CLI (for automated testing and plugin checks)
- Git (for version control and release management)

### Installation
```bash
# Clone repository
git clone [repository-url] andw-image-control
cd andw-image-control

# Install PHP dependencies (optional for development)
composer install --dev

# Install Node.js dependencies (if applicable)
npm install
```

## Code Quality & Standards

### WordPress Coding Standards (PHPCS)
```bash
# Install WordPress Coding Standards
composer global require "squizlabs/php_codesniffer=*"
composer global require wp-coding-standards/wpcs
phpcs --config-set installed_paths ~/.composer/vendor/wp-coding-standards/wpcs

# Run PHPCS (current plugin passes with zero errors)
phpcs --standard=WordPress --report=summary .
phpcs --standard=WordPress-Core,WordPress-Extra,WordPress-Docs includes/

# Auto-fix issues where possible
phpcbf --standard=WordPress .

# Memory optimization for large codebases
phpcs -d memory_limit=512M --standard=WordPress --report=summary .
```

### Plugin Check Tool
```bash
# Install WordPress Plugin Check (requires WordPress environment)
wp plugin install plugin-check --activate

# Run plugin check
wp plugin-check check andw-image-control

# Or use online tool: https://wordpress.org/plugins/developers/plugin-check/
```

### Manual Security Review
```bash
# Check for common security issues
grep -r "eval\|exec\|system\|shell_exec" includes/
grep -r "\$_GET\|\$_POST\|\$_REQUEST" includes/
grep -r "echo\|print" includes/ | grep -v "esc_"
grep -r "wp_nonce" includes/
```

## Testing Procedures

### Syntax Validation
```bash
# PHP syntax check
find . -name "*.php" -exec php -l {} \;

# Or for individual files
php -l includes/class-settings.php
```

### Functional Testing Checklist
- [ ] Plugin activation/deactivation
- [ ] Settings page accessibility
- [ ] JPEG quality settings functionality
- [ ] PNG to JPEG conversion
- [ ] Custom image size registration
- [ ] SVG upload with sanitization
- [ ] Media library UI enhancements
- [ ] Thumbnail crop checkbox persistence

### Manual Testing Steps
1. **Fresh WordPress Installation**
   - Install on clean WordPress instance
   - Activate plugin
   - Check for PHP errors in debug.log

2. **Settings Configuration**
   - Navigate to Settings > Media
   - Verify all options are displayed
   - Test save functionality

3. **Image Upload Testing**
   - Upload JPEG images (test quality settings)
   - Upload PNG images (test conversion)
   - Upload SVG files (test sanitization)
   - Verify custom image sizes are generated

4. **Media Library Testing**
   - Check MIME type labels
   - Test thumbnail display
   - Verify crop checkbox functionality

## Build Process

### Version Update Procedure
1. Follow `docs/VERSION-UP.md` guidelines
2. Update version in:
   - `andw-image-control.php` (header and constant)
   - `readme.txt` (Stable tag)
   - `CHANGELOG.txt` (new version entry)

### Pre-Release Checklist
- [x] All PHPCS warnings resolved (zero errors achieved)
- [x] Plugin Check passed
- [x] Security review completed
- [x] Functional testing passed
- [x] Documentation updated
- [x] Version numbers synchronized
- [x] WordPress.org review requirements met
- [x] PHP 8.1+ compatibility verified
- [x] Database operations use WordPress API only
- [x] Complete nonce verification implemented

### Build Commands
```bash
# Create release package (current version 0.5.0)
zip -r andw-image-control-v0.5.0.zip . \
  -x "*.git*" "node_modules/*" "*.log" "*.tmp" ".DS_Store" \
  "composer.json" "composer.lock" "vendor/*" ".gitattributes" \
  "DEVELOPER.md" "README.md" "CHANGELOG.md" ".github/*"

# Using git archive (respects .gitattributes export-ignore)
git archive --format=zip --prefix=andw-image-control/ HEAD -o andw-image-control-v0.5.0.zip

# Or using npm script (if configured)
npm run build
npm run package

# Verify package contents
unzip -l andw-image-control-v0.5.0.zip
```

## CI/CD Implementation Notes

### GitHub Actions Workflow (Recommended)
```yaml
# .github/workflows/ci.yml
name: WordPress Plugin CI
on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  code-quality:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']
        wordpress: ['6.0', '6.4', '6.5', '6.6', '6.7', '6.8']

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mysql, zip
          tools: composer, wp-cli

      - name: Install Composer dependencies
        run: composer install --no-dev --optimize-autoloader

      - name: PHPCS - WordPress Coding Standards
        run: |
          composer global require wp-coding-standards/wpcs
          phpcs --config-set installed_paths ~/.composer/vendor/wp-coding-standards/wpcs
          phpcs --standard=WordPress --report=summary .

      - name: Setup WordPress ${{ matrix.wordpress }}
        run: |
          wp core download --version=${{ matrix.wordpress }} --path=/tmp/wordpress
          wp config create --path=/tmp/wordpress --dbname=test --dbuser=root --dbpass=root --dbhost=127.0.0.1

      - name: WordPress Plugin Check
        run: |
          wp plugin install plugin-check --activate --path=/tmp/wordpress
          wp plugin-check check . --path=/tmp/wordpress

      - name: PHP Syntax Check
        run: find . -name "*.php" -exec php -l {} \;

  security-scan:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Security Audit
        run: |
          # Check for common security issues
          grep -r "eval\|exec\|system\|shell_exec" . || true
          grep -r "\$_GET\|\$_POST\|\$_REQUEST" . | grep -v "esc_" || true

  build-package:
    needs: [code-quality, security-scan]
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Create release package
        run: |
          git archive --format=zip --prefix=andw-image-control/ HEAD -o andw-image-control.zip

      - name: Upload artifact
        uses: actions/upload-artifact@v3
        with:
          name: plugin-package
          path: andw-image-control.zip
```

### Automated Quality Checks
- **PHPCS**: WordPress coding standards validation with zero tolerance for errors
- **Plugin Check**: WordPress.org compliance verification using official tool
- **Security Scan**: Automated vulnerability detection for common WordPress issues
- **PHP Syntax Check**: Syntax validation across all PHP versions
- **Cross-version Testing**: WordPress 6.0-6.8 and PHP 8.1-8.3 compatibility matrix

### Local Development Workflow
```bash
# Pre-commit quality checks
phpcs --standard=WordPress --report=summary .
wp plugin-check check .
php -l $(find . -name "*.php")

# Version update workflow (following docs/VERSION-UP.md)
# 1. Update version in andw-image-control.php
# 2. Update Stable tag in readme.txt
# 3. Add changelog entry to CHANGELOG.txt
# 4. Create git tag and release

# Release preparation
git archive --format=zip --prefix=andw-image-control/ HEAD -o release/andw-image-control-v0.5.0.zip
```

### Deployment Pipeline
1. **Development**: Feature branches with automated PHPCS and Plugin Check
2. **Pull Requests**: Full CI/CD pipeline with code quality gates
3. **Main Branch**: Automated package creation and artifact storage
4. **Release**: Manual tag creation triggers WordPress.org SVN deployment
5. **WordPress.org**: Manual submission following Plugin Directory guidelines

### WordPress.org Submission Process
```bash
# 1. Final pre-submission checks
phpcs --standard=WordPress --report=summary . # Must be zero errors
wp plugin-check check . # Must pass all checks

# 2. Create clean package using git archive
git tag v0.5.0
git archive --format=zip --prefix=andw-image-control/ v0.5.0 -o andw-image-control-v0.5.0.zip

# 3. Verify package contents (no dev files)
unzip -l andw-image-control-v0.5.0.zip | grep -E "\.(git|composer|npm|node)"

# 4. Submit to WordPress.org Plugin Directory
# Upload via https://wordpress.org/plugins/developers/add/
```

## File Structure

```
andw-image-control/
├── andw-image-control.php      # Main plugin file
├── readme.txt                  # WordPress.org readme
├── README.md                   # GitHub readme
├── CHANGELOG.txt               # Version history
├── DEVELOPER.md                # This file
├── includes/                   # Core plugin classes
│   ├── class-settings.php      # Settings management
│   ├── class-image-sizes.php   # Custom image sizes
│   ├── class-jpeg-quality.php  # JPEG quality control
│   ├── class-png-converter.php # PNG conversion
│   ├── class-svg-support.php   # SVG upload support
│   └── class-media-ui.php      # Media library UI
├── assets/                     # CSS/JS assets
│   ├── css/media-ui.css        # Media library styling
│   └── js/media-ui.js          # Media library JavaScript
└── docs/                       # Documentation
    └── VERSION-UP.md           # Version update procedures
```

## Security Considerations

### WordPress Security Standards
- **Input Validation**: All user inputs validated using WordPress functions
- **Output Escaping**: All outputs escaped using `esc_html()`, `esc_attr()`, etc.
- **Nonce Verification**: All form submissions protected with nonces
- **Capability Checks**: Proper user permission verification
- **SQL Injection Prevention**: Use of WordPress database API

### SVG Security Implementation
- **wp_kses() Sanitization**: WordPress standard HTML/XML filtering
- **Allowed Tags/Attributes**: Whitelist approach for SVG elements
- **Entity Prevention**: Protection against XXE and entity expansion attacks
- **Script Removal**: Elimination of JavaScript and event handlers

## Contributing

### Code Standards
- Follow WordPress PHP Coding Standards
- Use proper DocBlocks for all functions/methods
- Implement singleton patterns where appropriate
- Ensure all user-facing strings are translatable

### Pull Request Process
1. Create feature branch from `main`
2. Implement changes with tests
3. Run PHPCS and Plugin Check
4. Submit PR with detailed description
5. Address review feedback
6. Merge after approval

### Issue Reporting
- Use GitHub Issues for bug reports
- Include WordPress/PHP version information
- Provide steps to reproduce
- Include relevant error messages

---

## 日本語開発者向け情報

### 開発環境構築
WordPress 6.0以上、PHP 8.1以上の環境でテストしてください。PHP 8.1未満では動作しません。

### コード品質チェック
WordPress コーディング標準に準拠してください。PHPCSでのチェックを推奨します。
```bash
# WordPress コーディング標準チェック
phpcs --standard=WordPress --report=summary .

# プラグインチェック
wp plugin-check check .
```

### CI/CD導入メモ
- GitHub Actions による自動テスト（PHP 8.1-8.3, WordPress 6.0-6.8）
- PHPCS による自動コード品質チェック
- WordPress Plugin Check による自動準拠性確認
- セキュリティスキャンの自動実行
- リリースパッケージの自動生成

### リリース前チェックリスト（v0.5.0対応済み）
- [x] セキュリティレビュー完了
- [x] 機能テスト完了
- [x] WordPress.org 規約準拠確認
- [x] 国際化対応完了
- [x] パフォーマンス最適化完了
- [x] バージョン番号更新（0.5.0）
- [x] WPCS準拠（エラー0）
- [x] Plugin Check合格

### WordPress.org提出準備
このプラグインはWordPress.org公式ディレクトリへの提出準備が完了しています。
全てのコード品質基準、セキュリティ要件、パフォーマンス要件を満たしています。

この文書は継続的に更新され、開発プロセスの改善に役立てられます。