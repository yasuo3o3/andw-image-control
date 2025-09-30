# Developer Documentation - andW Media Control

## Development Environment Setup

### Requirements
- PHP 8.1 or higher (enforced with runtime check)
- WordPress 5.0 or higher
- Composer (for development dependencies)
- Node.js & npm (for build tools)

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

# Run PHPCS
phpcs --standard=WordPress .
phpcs --standard=WordPress-Core includes/
phpcs --standard=WordPress-Extra includes/
phpcs --standard=WordPress-Docs includes/

# Auto-fix issues where possible
phpcbf --standard=WordPress .
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
# Create release package
zip -r andw-image-control-v0.3.0.zip . \
  -x "*.git*" "node_modules/*" "*.log" "*.tmp" ".DS_Store"

# Or using npm script (if configured)
npm run build
npm run package
```

## CI/CD Implementation Notes

### GitHub Actions Workflow (Planned)
```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']
        wordpress: ['5.0', '6.0', '6.7']
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - name: Install dependencies
        run: composer install
      - name: PHPCS check
        run: phpcs --standard=WordPress .
      - name: PHPUnit tests
        run: phpunit
```

### Automated Quality Checks
- **PHPCS**: WordPress coding standards validation
- **Plugin Check**: WordPress.org compliance verification
- **Security Scan**: Automated vulnerability detection
- **Unit Tests**: PHPUnit test suite execution

### Deployment Pipeline
1. **Development**: Feature branches → Pull requests
2. **Staging**: Automated testing on staging environment
3. **Production**: Manual release to WordPress.org

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
WordPress 5.0以上、PHP 8.1以上の環境でテストしてください。PHP 8.1未満では動作しません。

### コード品質チェック
WordPress コーディング標準に準拠してください。PHPCSでのチェックを推奨します。

### リリース前チェックリスト
- セキュリティレビュー完了
- 機能テスト完了
- WordPress.org 規約準拠確認
- バージョン番号更新

この文書は継続的に更新され、開発プロセスの改善に役立てられます。