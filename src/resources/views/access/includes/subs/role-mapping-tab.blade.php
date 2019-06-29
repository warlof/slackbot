<table class="table table-condensed table-hover table-responsive">
    <thead>
    <tr>
        <th>{{ trans('slackbot::seat.role') }}</th>
        <th>{{ trans('slackbot::seat.channel') }}</th>
        <th>{{ trans('slackbot::seat.created') }}</th>
        <th>{{ trans('slackbot::seat.updated') }}</th>
        <th>{{ trans('slackbot::seat.status') }}</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @foreach($channelRoles as $channel)
        <tr>
            <td>{{ $channel->role->title }}</td>
            <td>{{ $channel->channel->name }}</td>
            <td>{{ $channel->created_at }}</td>
            <td>{{ $channel->updated_at }}</td>
            <td>{{ $channel->enable }}</td>
            <td>
                <div class="btn-group">
                    <form method="post" action="{{ route('slackbot.role.remove', ['role_id' => $channel->role_id, 'channel_id' => $channel->channel_id]) }}">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}
                        <button type="submit" class="btn btn-danger btn-xs col-xs-12">{{ trans('web::seat.remove') }}</button>
                    </form>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>