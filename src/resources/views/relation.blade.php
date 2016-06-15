@extends('web::layouts.grids.3-9')

@section('title', trans('slackbot::seat.slackbot_admin'))
@section('page_header', trans('slackbot::seat.slackbot_admin'))

@section('left')
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">{{ trans('slackbot::seat.slackbot_relation_creation') }}</h3>
    </div>
    <div class="panel-body">
        <form method="post" action="{{ route('slack-admin.relation.create') }}">
            <div class="box-body">
                <div class="form-group">
                    <label for="slackbot-group">{{ trans('slackbot::seat.slackbot_relation_name') }}</label>
                    <select name="slackbot-group" id="slackbot-group" class="form-control">
                        @foreach($seat_groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="slackbot-channels">{{ trans_choice('slackbot::seat.slackbot_channel', 2) }}</label>
                    <select multiple name="slackbot-channels" id="slackbot-channels" class="form-control">
                        @foreach($slack_channels as $channel)
                        <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                        @endforeach
                        @foreach($slack_groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary pull-right">
                    {{ trans('slackbot::seat.slackbot_create') }}
                </button>
            </div>
        </form>
    </div>
    <div class="panel-footer">
        <a href="#" type="button" class="btn btn-success btn-xs col-xs-6">
            {{ trans('slackbot::seat.slackbot_create') }}
        </a>
    </div>
</div>