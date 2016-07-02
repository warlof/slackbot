@extends('web::layouts.grids.3-9')

@section('title', trans('slackbot::seat.management'))
@section('page_header', trans('slackbot::seat.management'))

@section('left')
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
                        <select name="slack-type" id="slack-type" class="col-md-12">
                            <option value="user">{{ trans('slackbot::seat.user_filter') }}</option>
                            <option value="role">{{ trans('slackbot::seat.role_filter') }}</option>
                            <option value="corporation">{{ trans('slackbot::seat.corporation_filter') }}</option>
                            <option value="alliance">{{ trans('slackbot::seat.alliance_filter') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="slack-user-id">{{ trans('slackbot::seat.username') }}</label>
                        <select name="slack-user-id" id="slack-user-id" class="col-md-12">
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="slack-role-id">{{ trans('slackbot::seat.role') }}</label>
                        <select name="slack-role-id" id="slack-role-id" class="col-md-12" disabled="disabled">
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="slack-corporation-id">{{ trans('slackbot::seat.corporation') }}</label>
                        <select name="slack-corporation-id" id="slack-corporation-id" class="col-md-12" disabled="disabled">
                            @foreach($corporations as $corporation)
                            <option value="{{ $corporation->corporationID }}">{{ $corporation->corporationName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="slack-alliance-id">{{ trans('slackbot::seat.alliance') }}</label>
                        <select name="slack-alliance-id" id="slack-alliance-id" class="col-md-12" disabled="disabled">
                            @foreach($alliances as $alliance)
                            <option value="{{ $alliance->allianceID }}">{{ $alliance->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="slack-channel-id">{{ trans('slackbot::seat.channel') }}</label>
                        <select name="slack-channel-id" id="slack-channel-id" class="col-md-12">
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
@stop

@section('right')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{{ trans('slackbot::seat.authorisations') }}</h3>
        </div>
        <div class="panel-body">

            <ul class="nav nav-pills" id="slack-tabs">
                <li role="presentation" class="active">
                    <a href="#slackbot-username">{{ trans('slackbot::seat.user_filter') }}</a>
                </li>
                <li role="presentation">
                    <a href="#slackbot-role">{{ trans('slackbot::seat.role_filter') }}</a>
                </li>
                <li role="presentation">
                    <a href="#slackbot-corporation">{{ trans('slackbot::seat.corporation_filter') }}</a>
                </li>
                <li role="presentation">
                    <a href="#slackbot-alliance">{{ trans('slackbot::seat.alliance_filter') }}</a>
                </li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="slackbot-username">
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
                        @foreach($channelUsers as $channel)
                            <tr>
                                <td>{{ $channel->user->name }}</td>
                                <td>{{ $channel->channel->name }}</td>
                                <td>{{ $channel->created_at }}</td>
                                <td>{{ $channel->updated_at }}</td>
                                <td>{{ $channel->enable }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('slackbot.user.remove', ['user_id' => $channel->user_id, 'channel_id' => $channel->channel_id]) }}" type="button" class="btn btn-danger btn-xs col-xs-12">
                                            {{ trans('web::seat.remove') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div role="tabpanel" class="tab-pane" id="slackbot-role">
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
                                        <a href="{{ route('slackbot.role.remove', ['role_id' => $channel->role_id, 'channel_id' => $channel->channel_id]) }}" type="button" class="btn btn-danger btn-xs col-xs-12">
                                            {{ trans('web::seat.remove') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div role="tabpanel" class="tab-pane" id="slackbot-corporation">
                    <table class="table table-condensed table-hover table-responsive">
                        <thead>
                        <tr>
                            <th>{{ trans('slackbot::seat.corporation') }}</th>
                            <th>{{ trans('slackbot::seat.channel') }}</th>
                            <th>{{ trans('slackbot::seat.created') }}</th>
                            <th>{{ trans('slackbot::seat.updated') }}</th>
                            <th>{{ trans('slackbot::seat.status') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($channelCorporations as $channel)
                            <tr>
                                <td>{{ $channel->corporation->corporationName }}</td>
                                <td>{{ $channel->channel->name }}</td>
                                <td>{{ $channel->created_at }}</td>
                                <td>{{ $channel->updated_at }}</td>
                                <td>{{ $channel->enable }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('slackbot.corporation.remove', ['corporation_id' => $channel->corporation_id, 'channel_id' => $channel->channel_id]) }}" type="button" class="btn btn-danger btn-xs col-xs-12">
                                            {{ trans('web::seat.remove') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div role="tabpanel" class="tab-pane" id="slackbot-alliance">
                    <table class="table table-condensed table-hover table-responsive">
                        <thead>
                        <tr>
                            <th>{{ trans('slackbot::seat.alliance') }}</th>
                            <th>{{ trans('slackbot::seat.channel') }}</th>
                            <th>{{ trans('slackbot::seat.created') }}</th>
                            <th>{{ trans('slackbot::seat.updated') }}</th>
                            <th>{{ trans('slackbot::seat.status') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($channelAlliances as $channel)
                            <tr>
                                <td>{{ $channel->alliance->name }}</td>
                                <td>{{ $channel->channel->name }}</td>
                                <td>{{ $channel->created_at }}</td>
                                <td>{{ $channel->updated_at }}</td>
                                <td>{{ $channel->enable }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('slackbot.alliance.remove', ['alliance_id' => $channel->alliance_id, 'channel_id' => $channel->channel_id]) }}" type="button" class="btn btn-danger btn-xs col-xs-12">
                                            {{ trans('web::seat.remove') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script type="application/javascript">
        $('#slack-type').change(function(){
            $.each(['slack-user-id', 'slack-role-id', 'slack-corporation-id', 'slack-alliance-id'], function(key, value){
                if (value == ('slack-' + $('#slack-type').val() + '-id')) {
                    $(('#' + value)).prop('disabled', false);
                } else {
                    $(('#' + value)).prop('disabled', true);
                }
            });
        }).select2();

        $('#slack-user-id, #slack-role-id, #slack-corporation-id, #slack-alliance-id, #slack-channel-id').select2();

        $('#slack-tabs a').click(function(e){
            e.preventDefault();
            $(this).tab('show');
        });
    </script>
@stop