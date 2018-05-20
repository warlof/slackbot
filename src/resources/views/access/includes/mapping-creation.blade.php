<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">{{ trans('slackbot::seat.quick_create') }}</h3>
    </div>
    <div class="panel-body">
        <form role="form" action="{{ route('slackbot.add') }}" method="post">
            {{ csrf_field() }}

            <div class="box-body">

                <div class="form-group">
                    <label for="slack-type">{{ trans('slackbot::seat.type') }}</label>
                    <select name="slack-type" id="slack-type" class="form-control">
                        <option value="user">{{ trans('slackbot::seat.user_filter') }}</option>
                        <option value="role">{{ trans('slackbot::seat.role_filter') }}</option>
                        <option value="corporation">{{ trans('slackbot::seat.corporation_filter') }}</option>
                        <option value="title">{{ trans('slackbot::seat.title_filter') }}</option>
                        <option value="alliance">{{ trans('slackbot::seat.alliance_filter') }}</option>
                        <option value="public">{{ trans('slackbot::seat.public_filter') }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="slack-group-id">{{ trans('slackbot::seat.username') }}</label>
                    <select name="slack-group-id" id="slack-group-id" class="form-control">
                        @foreach($groups->sortBy('main_character.name') as $group)
                            <option value="{{ $group->id }}">{{ optional($group->main_character)->name ?: 'Unknown Character' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="slack-role-id">{{ trans('slackbot::seat.role') }}</label>
                    <select name="slack-role-id" id="slack-role-id" class="form-control" disabled="disabled">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="slack-corporation-id">{{ trans('slackbot::seat.corporation') }}</label>
                    <select name="slack-corporation-id" id="slack-corporation-id" class="form-control" disabled="disabled">
                        @foreach($corporations as $corporation)
                            <option value="{{ $corporation->corporation_id }}">{{ $corporation->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="slack-title-id">{{ trans('slackbot::seat.title') }}</label>
                    <select name="slack-title-id" id="slack-title-id" class="form-control" disabled="disabled"></select>
                </div>

                <div class="form-group">
                    <label for="slack-alliance-id">{{ trans('slackbot::seat.alliance') }}</label>
                    <select name="slack-alliance-id" id="slack-alliance-id" class="form-control" disabled="disabled">
                        @foreach($alliances as $alliance)
                            <option value="{{ $alliance->alliance_id }}">{{ $alliance->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="slack-channel-id">{{ trans('slackbot::seat.channel') }}</label>
                    <select name="slack-channel-id" id="slack-channel-id" class="form-control">
                        @foreach($channels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="slack-enabled">{{ trans('slackbot::seat.enabled') }}</label>
                    <input type="checkbox" name="slack-enabled" id="slack-enabled" checked="checked" value="1" />
                </div>

            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary pull-right">{{ trans('slackbot::seat.add') }}</button>
            </div>

        </form>
    </div>
</div>