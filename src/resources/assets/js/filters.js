function getCorporationTitle(route) {
    $('#slack-title-id').empty();

    $.ajax(route, {
        data: {
            corporation_id: $('#slack-corporation-id').val()
        },
        dataType: 'json',
        method: 'GET',
        success: function(data){
            for (var i = 0; i < data.length; i++) {
                $('#slack-title-id').append($('<option></option>').attr('value', data[i].titleID).text(data[i].titleName));
            }
        }
    });
}

$('#slack-type').change(function(){
    $.each(['slack-user-id', 'slack-role-id', 'slack-corporation-id', 'slack-title-id', 'slack-alliance-id'], function(key, value){
        if (value === ('slack-' + $('#slack-type').val() + '-id')) {
            $(('#' + value)).prop('disabled', false);
        } else {
            $(('#' + value)).prop('disabled', true);
        }
    });

    if ($('#slack-type').val() === 'title') {
        $('#slack-corporation-id, #slack-title-id').prop('disabled', false);
    }
}).select2();

$('#slack-user-id, #slack-role-id, #slack-corporation-id, #slack-title-id, #slack-alliance-id, #slack-channel-id').select2();

$('#slack-tabs').find('a').click(function(e){
    e.preventDefault();
    $(this).tab('show');
});
