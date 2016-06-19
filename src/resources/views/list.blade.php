@extends('web::layouts.grids.3-9')

@section('title', trans('slackbot::seat.slackbot'))
@section('page_header', trans('slackbot::seat.slackbot'))

@section('left')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{{ trans('slackbot::seat.quick_create') }}</h3>
        </div>
        <div class="panel-body">
            <form role="form" action="{{ route('') }}" method="post">
                {{ csrf_field() }}

                <div class="box-body">

                    <div class="form-group">
                        <label for="slackType">{{ trans('slackbot::seat.type') }}</label>
                        <input type="text" name="slackType" id="slackType" class="form-control" placeholder="{{ trans('') }}" />
                    </div>

                    <div class="form-group">
                        <label for="slackUserId">{{ trans('slackbot::seat.username') }}</label>
                        <input type="text" name="slackUserId" id="slackUserId" class="form-control" placeholder="{{ trans('') }}" />
                    </div>

                    <div class="form-group">
                        <label for="slackChannelId">{{ trans('slackbot::seat.channel') }}</label>
                        <input type="text" name="slackChannelId" id="slackChannelId" class="form-control" placeholder="{{ trans('') }}" />
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

            <ul class="nav nav-pills">
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
                            @foreach($channels_users as $channel)
                            <tr>
                                <td>{{ $channel->user()->name }}</td>
                                <td>{{ $channel->channel()->name }}</td>
                                <td>{{ $channel->created_at }}</td>
                                <td>{{ $channel->updated_at }}</td>
                                <td>{{ $channel->enable }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('') }}" type="button" class="btn btn-danger btn-xs col-xs-6">
                                            {{ trans('web::seat.remove') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div role="tabpanel" class="tab-pane active" id="slackbot-role">
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
                        @foreach($channels_roles as $channel)
                            <tr>
                                <td>{{ $channel->role()->title }}</td>
                                <td>{{ $channel->channel()->name }}</td>
                                <td>{{ $channel->created_at }}</td>
                                <td>{{ $channel->updated_at }}</td>
                                <td>{{ $channel->enable }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('') }}" type="button" class="btn btn-danger btn-xs col-xs-6">
                                            {{ trans('web::seat.remove') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div role="tabpanel" class="tab-pane active" id="slackbot-username">
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
                        @foreach($channels_corporations as $channel)
                            <tr>
                                <td>{{ $channel->corporation_id }}</td>
                                <td>{{ $channel->channel()->name }}</td>
                                <td>{{ $channel->created_at }}</td>
                                <td>{{ $channel->updated_at }}</td>
                                <td>{{ $channel->enable }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('') }}" type="button" class="btn btn-danger btn-xs col-xs-6">
                                            {{ trans('web::seat.remove') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div role="tabpanel" class="tab-pane active" id="slackbot-username">
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
                        @foreach($channels_alliances as $channel)
                            <tr>
                                <td>{{ $channel->alliance_id }}</td>
                                <td>{{ $channel->channel()->name }}</td>
                                <td>{{ $channel->created_at }}</td>
                                <td>{{ $channel->updated_at }}</td>
                                <td>{{ $channel->enable }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('') }}" type="button" class="btn btn-danger btn-xs col-xs-6">
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
