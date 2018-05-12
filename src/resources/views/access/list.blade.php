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
                            <option value="{{ $group->id }}">{{ $group->main_character->name }}</option>
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
@stop

@section('right')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{{ trans('slackbot::seat.authorisations') }}</h3>
        </div>
        <div class="panel-body">

            <ul class="nav nav-pills" id="slack-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#slackbot-public" role="tab" data-toggle="tab">{{ trans('slackbot::seat.public_filter') }}</a>
                </li>
                <li role="presentation">
                    <a href="#slackbot-username" role="tab" data-toggle="tab">{{ trans('slackbot::seat.user_filter') }}</a>
                </li>
                <li role="presentation">
                    <a href="#slackbot-role" role="tab" data-toggle="tab">{{ trans('slackbot::seat.role_filter') }}</a>
                </li>
                <li role="presentation">
                    <a href="#slackbot-corporation" role="tab" data-toggle="tab">{{ trans('slackbot::seat.corporation_filter') }}</a>
                </li>
                <li role="presentation">
                    <a href="#slackbot-title" role="tab" data-toggle="tab">{{ trans('slackbot::seat.title_filter') }}</a>
                </li>
                <li role="presentation">
                    <a href="#slackbot-alliance" role="tab" data-toggle="tab">{{ trans('slackbot::seat.alliance_filter') }}</a>
                </li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane fade in active" id="slackbot-public">
                    <table class="table table-condensed table-hover table-responsive">
                        <thead>
                        <tr>
                            <th></th>
                            <th>{{ trans('slackbot::seat.channel') }}</th>
                            <th>{{ trans('slackbot::seat.created') }}</th>
                            <th>{{ trans('slackbot::seat.updated') }}</th>
                            <th>{{ trans('slackbot::seat.status') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($channelPublic as $channel)
                            <tr>
                                <td></td>
                                <td>{{ $channel->channel->name }}</td>
                                <td>{{ $channel->created_at }}</td>
                                <td>{{ $channel->updated_at }}</td>
                                <td>{{ $channel->enable }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('slackbot.public.remove', ['channel_id' => $channel->channel_id]) }}" type="button" class="btn btn-danger btn-xs col-xs-12">
                                            {{ trans('web::seat.remove') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div role="tabpanel" class="tab-pane fade" id="slackbot-username">
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
                </div>
                <div role="tabpanel" class="tab-pane fade" id="slackbot-role">
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
                <div role="tabpanel" class="tab-pane fade" id="slackbot-corporation">
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
                                <td>{{ $channel->corporation->name }}</td>
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
                <div role="tabpanel" class="tab-pane fade" id="slackbot-title">
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
                </div>
                <div role="tabpanel" class="tab-pane fade" id="slackbot-alliance">
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