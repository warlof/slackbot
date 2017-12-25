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
    @foreach($channelTitles as $filter)
        <tr>
            <td>{{ $filter->related->corporation->corporationName }}</td>
            <td>{{ strip_tags($filter->related->titleName) }}</td>
            <td>{{ $filter->channel->name }}</td>
            <td>{{ $filter->created_at }}</td>
            <td>{{ $filter->updated_at }}</td>
            <td>{{ $filter->enable }}</td>
            <td>
                <div class="btn-group">
                    <a href="{{ route('slackbot.filters.remove', ['title', $filter->channel->id, $filter->related->id]) }}" type="button" class="btn btn-danger btn-xs col-xs-12">
                        {{ trans('web::seat.remove') }}
                    </a>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>