$(function() {

    // スコープ内の共用関数
    var dismissErrorIndication = function() {
        $('#pay_jp_credit_card_info_body').removeClass('has-error');
        $('.pay_jp_error_message').remove();
    };
    var restrictInputDigits = function(digits) {
        var maxDigits = digits;
        return (function(evt) {
            var val = $(evt.target).val();
            var newVal = '';
            var i, chr;
            for (i = 0; i < val.length && newVal.length < maxDigits; i++) {
                chr = val.substring(i, i + 1);
                if (chr.match(/\d/)) {
                    newVal += chr;
                }
            }
            $(evt.target).val(newVal);
            dismissErrorIndication();
        });
    };
    var judgeInputDigits = function(val, digits) {
        return !!val.match('\\d{' + digits + '}');
    };
    var judgeInputDigits2 = function(val, digits1, digits2) {
        return !!val.match('\\d{' + digits1 + ',' + digits2 + '}');
    };

    // エラー表示がある場合はスクロール位置を調整する
    if ($('.pay_jp_error_message')[0]) {
        window.scrollTo(0, $('#pay_jp_form_table').offset().top - 50);
    }

    // 名義が変更された時にエラー表示を消す
    $('#shopping_pay_jp_card_name').change(dismissErrorIndication);

    // カード番号を4桁の数字に制限する
    $('#shopping_pay_jp_card_number1').change(restrictInputDigits(4));
    $('#shopping_pay_jp_card_number2').change(restrictInputDigits(4));
    $('#shopping_pay_jp_card_number3').change(restrictInputDigits(4));
    $('#shopping_pay_jp_card_number4').change(restrictInputDigits(4));

    // セキュリティコードを4桁の数字に制限する
    $('#shopping_pay_jp_card_cvv').change(restrictInputDigits(4));

    // 有効期限を2桁の数字に制限する
    $('#shopping_pay_jp_card_exp_month').change(restrictInputDigits(2));
    $('#shopping_pay_jp_card_exp_year').change(restrictInputDigits(2));

    // エラーモーダルをBODYの末尾に追加
    var buff2 = '<div id="pay_jp_error_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="PayJpModalLabel">';
    buff2 += '<div class="modal-dialog modal-sm">';
    buff2 += '<div class="modal-content">';
    buff2 += '<div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>';
    buff2 += '<h4 class="modal-title" id="PayJpModalLabel">エラー</h4> </div>';
    buff2 += '<div class="modal-body" id="pay_jp_error_content"></div>';
    buff2 += '</div></div></div>';
    $('body').append(buff2);

    // 別のクレジットカードを使用するボタン
    $('#reset_credit_card').click(function() {
        $('#pay_jp_form_table').show(500);
        $('#reset_credit_card_block').remove();
        setTimeout(function () {
            window.scrollTo(0, $('#pay_jp_form_table').offset().top - 50);
        }, 200);
    });

    // フォーム送信時点でのバリデーション
    $('#shopping-form').submit(function () {
        var errorMessage = null;

        // お支払い方法が変更された場合は何もしない（グローバル変数参照）
        //noinspection JSUnresolvedVariable
        if ($('input[name="shopping[payment]"]:checked').val() != payJpCreditCardPaymentId) {
            return true;
        }

        // カード情報が入力済みなら何もしない
        if ($('#ignore_credit_card_information').val() == 1) {
            return true;
        }

        // 名義に1文字以上入力されていること
        if ($('#shopping_pay_jp_card_name').val().length === 0) {
            errorMessage = '{{ credit_card_holder_required }}';
        }

        // カード番号にそれぞれ4桁の数字が入力されていること
        if (errorMessage === null) {
            if (! (judgeInputDigits($('#shopping_pay_jp_card_number1').val(), 4)
                && judgeInputDigits($('#shopping_pay_jp_card_number2').val(), 4)
                && judgeInputDigits($('#shopping_pay_jp_card_number3').val(), 4)
                && judgeInputDigits($('#shopping_pay_jp_card_number4').val(), 4))) {
                errorMessage = '{{ credit_card_number_required }}';
            }
        }

        // セキュリティコードに3〜4桁の数字が入力されていること
        if (errorMessage === null) {
            if (!judgeInputDigits2($('#shopping_pay_jp_card_cvv').val(), 3, 4)) {
                errorMessage = '{{ security_code_required }}';
            }
        }

        // 有効期限にそれぞれ2桁の数字が入力されていること
        if (errorMessage === null) {
            if (!(judgeInputDigits($('#shopping_pay_jp_card_exp_year').val(), 2)
                && judgeInputDigits($('#shopping_pay_jp_card_exp_month').val(), 2))) {
                errorMessage = '{{ expiration_required }}';
            }
        }

        // エラーメッセージを表示してフォーム送信を中断
        if (errorMessage !== null) {
            $('.prevention-masked').remove();
            $('#order-button').removeAttr('disabled');
            $('#pay_jp_error_content').text(errorMessage);
            $('#pay_jp_error_modal').modal({
                backdrop: true,
                show: true
            });
            $('#pay_jp_credit_card_info_body').addClass('has-error');
            return false;
        }

        return true;
    });
});