jQuery(document).ready(function($) {
    // グローバル変数として WordPress から渡されたデータを取得
    const recommendedValues = andwImageControlData.recommendedValues;
    const sizeMapping = andwImageControlData.sizeMapping;
    const i18n = andwImageControlData.i18n;

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
                '<p><strong>' + i18n.recommendedApplied + '</strong> ' +
                i18n.settingsCount.replace('%d', appliedCount) + ' ' +
                i18n.savePrompt + '</p>' +
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
            '<input type="number" name="andw_jpeg_quality_thumbnail" value="' + andwImageControlData.thumbnailQuality + '" min="1" max="100" class="small-text" style="width: 70px; text-align: right; margin-bottom: 1rem;" />' +
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
            '<input type="number" name="andw_jpeg_quality_medium" value="' + andwImageControlData.mediumQuality + '" min="1" max="100" class="small-text" style="width: 70px; text-align: right; margin-bottom: 1rem;" />' +
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
            '<input type="number" name="andw_jpeg_quality_large" value="' + andwImageControlData.largeQuality + '" min="1" max="100" class="small-text" style="width: 70px; text-align: right; margin-bottom: 1rem;" />' +
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