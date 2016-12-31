@extends('web::layouts.grids.12')

@section('title', trans('slackbot::seat.management'))
@section('page_header', trans('slackbot::seat.mapping'))

@section('full')

    <div id="user-alert" class="callout callout-danger hidden">
        <h4></h4>
        <p></p>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{{ trans('slackbot::seat.user_list') }}</h3>
        </div>
        <div class="panel-body">
            <table class="table table-condensed table-hover table-responsive no-margin" id="users-table" data-page-length="25">
                <thead>
                    <tr>
                        <th>SeAT ID</th>
                        <th>SeAT Username</th>
                        <th>Slack ID</th>
                        <th>Slack Display Name</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
            <form method="post" id="user-remove" action="{{ route('slackbot.json.user.remove') }}" class="hidden">
                {{ csrf_field() }}
                <input type="hidden" name="slack_id" />
            </form>
        </div>
    </div>

    <div class="modal fade" id="user-channels" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close pull-right" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <!--
                    <button type="button" class="btn btn-xs pull-right" style="background: none">
                        <i class="fa fa-refresh"></i>
                    </button>
                    -->
                    <h4 class="modal-title">
                        <span id="slack_username"></span>
                        (<span id="seat_username"></span>) is member of following
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-condensed table-hover" id="channels">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th># Users</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-condensed table-hover" id="groups">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th># Users</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('javascript')
<script type="text/javascript">
    $(function(){
        var table = $('table#users-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('slackbot.json.users') }}',
            columns: [
                {data: 'user_id'},
                {data: 'user_name'},
                {data: 'slack_id'},
                {data: 'slack_name'},
                @if (auth()->user()->has('slackbot.security'))
                {
                    data: null,
                    targets: -1,
                    defaultContent: '<button class="btn btn-xs btn-info">Channels</button> <button class="btn btn-xs btn-danger">Remove</button>',
                    orderable: false
                }
                @endif
            ],
            "fnDrawCallback": function(){
                $(document).ready(function(){
                    $('img').unveil(100);
                });
            }
        });

        $('#users-table tbody').on('click', 'button.btn-info', function(){
            var row = table.row($(this).parents('tr')).data();

            $.ajax({
                url: '{{ route('slackbot.json.user.channels', ['id' => null]) }}',
                data: {'slack_id' : row.slack_id},
                success: function(data){
                    $('#channels').find('tbody tr').remove();
                    $('#groups').find('tbody tr').remove();

                    $('#slack_username').text(row.slack_name);
                    $('#seat_username').text(row.user_name);

                    if (data['channels']) {
                        for (var i = 0; i < data['channels'].length; i++) {
                            $('#channels').find('tbody').append('<tr><td>' + data['channels'][i][0] + '</td><td>' +
                                    data['channels'][i][1] + '</td><td>' + data['channels'][i][2] + '</td></tr>');
                        }
                    }

                    if (data['groups']) {
                        for (var i = 0; i < data['groups'].length; i++) {
                            $('#groups').find('tbody').append('<tr><td>' + data['groups'][i][0] + '</td><td>' +
                                    data['groups'][i][1] + '</td><td>' + data['groups'][i][2] + '</td></tr>');
                        }
                    }
                }
            });

            $('#user-channels').modal('show');
        });

        $('#users-table tbody').on('click', 'button.btn-danger', function(){
            var data = table.row($(this).parents('tr')).data();
            $('#user-remove').find('input[name="slack_id"]').val(data.slack_id).parent().submit();
        });
    });
</script>
@endpush