<table class="table table-condensed table-hover table-responsive">
    <thead>
    <tr>
        <th>{{ trans('slackbot::seat.username') }}</th>
        <th>{{ trans('slackbot::seat.channel') }}</th>
        <th>{{ trans('slackbot::seat.created') }}</th>
        <th>{{ trans('slackbot::seat.updated') }}</th>
        <th>{{ trans('slackbot::seat.status') }}</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @foreach($channelGroups as $channel)
        <tr>
            <td>{{ $channel->group->main_character->name }}</td>
            <td>{{ $channel->channel->name }}</td>
            <td>{{ $channel->created_at }}</td>
            <td>{{ $channel->updated_at }}</td>
            <td>{{ $channel->enable }}</td>
            <td>
                <div class="btn-group">
                    <a href="{{ route('slackbot.user.remove', ['group_id' => $channel->group_id, 'channel_id' => $channel->channel_id]) }}" type="button" class="btn btn-danger btn-xs col-xs-12">
                        {{ trans('web::seat.remove') }}
                    </a>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>