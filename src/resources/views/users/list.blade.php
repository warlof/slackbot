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
                    defaultContent: '<button class="btn btn-sm btn-danger">Remove</button>',
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

        $('#users-table tbody').on('click', 'button', function(){
            var data = table.row($(this).parents('tr')).data();
            $('#user-remove').find('input[name="slack_id"]').val(data.slack_id).parent().submit();
        });
    });
</script>
@endpush