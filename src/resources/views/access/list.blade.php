@extends('web::layouts.grids.3-9')

@section('title', trans('slackbot::seat.management'))
@section('page_header', trans('slackbot::seat.management'))

@section('left')

    @include('slackbot::access.includes.mapping-creation')
    
@stop

@section('right')

    @include('slackbot::access.includes.mapping-table')

@stop

@push('javascript')
    <script type="application/javascript">
        function getCorporationTitle() {
            $('#slack-title-id').empty();

            $.ajax('{{ route('slackbot.json.titles') }}', {
                data: {
                    corporation_id: $('#slack-corporation-id').val()
                },
                dataType: 'json',
                method: 'GET',
                success: function(data){
                    for (var i = 0; i < data.length; i++) {
                        $('#slack-title-id').append($('<option></option>').attr('value', data[i].title_id).text(data[i].name));
                    }
                }
            });
        }

        $('#slack-type').change(function(){
            $.each(['slack-group-id', 'slack-role-id', 'slack-corporation-id', 'slack-title-id', 'slack-alliance-id'], function(key, value){
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

        $('#slack-corporation-id').change(function(){
            getCorporationTitle();
        });

        $('#slack-group-id, #slack-role-id, #slack-corporation-id, #slack-title-id, #slack-alliance-id, #slack-channel-id').select2();

        $('#slack-tabs').find('a').click(function(e){
            e.preventDefault();
            $(this).tab('show');
        });

        $(document).ready(function(){
            getCorporationTitle();
        });
    </script>
@endpush