<table class="table table-condensed table-hover table-responsive">
    <thead>
    <tr>
        <th>{{ trans('slackbot::seat.corporation') }}</th>
        <th>{{ trans('slackbot::seat.title') }}</th>
        <th>{{ trans('slackbot::seat.channel') }}</th>
        <th>{{ trans('slackbot::seat.created') }}</th>
        <th>{{ trans('slackbot::seat.updated') }}</th>
        <th>{{ trans('slackbot::seat.status') }}</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @foreach($channelTitles as $channel)
        <tr>
            <td>{{ $channel->corporation->name }}</td>
            <td>{{ strip_tags($channel->titleName) }}</td>
            <td>{{ $channel->channel->name }}</td>
            <td>{{ $channel->created_at }}</td>
            <td>{{ $channel->updated_at }}</td>
            <td>{{ $channel->enable }}</td>
            <td>
                <div class="btn-group">
                    <a href="{{ route('slackbot.title.remove', ['corporation_id' => $channel->corporation_id, 'title_id' => $channel->title_id, 'channel_id' => $channel->channel_id]) }}" type="button" class="btn btn-danger btn-xs col-xs-12">
                        {{ trans('web::seat.remove') }}
                    </a>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>