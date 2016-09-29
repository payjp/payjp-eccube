$(function() {

    // APIキー確認
    $('#api_key_check').click(function() {
        var sk = $('#pay_jp_api_key_api_key_secret').val();

        $.post('api/key_check', {sk:sk}, function(result) {
            $('#api_key_check_result').html(result);
        });
    });

    // 支払いIDの表示
    $('.show_charge_id').each(function() {
        var id = $(this).attr('id');
        var order_id = id.substr(15);
        $.get('/testadmin/plugin/pay_jp/api/charge/' + order_id, function(result) {
            $('#' + id).text(result);
        });
    });
});