@extends('web::layouts.grids.3-9')

@section('title', trans('slackbot::seat.slackbot_admin'))
@section('page_header', trans('slackbot::seat.slackbot_admin'))

@section('left')
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">{{ trans('slackbot::seat.slackbot_relations') }}</h3>
    </div>
    <div class="panel-body">
        <table class="table table-condensed table-hover table-responsive">
            <thead>
                <tr>
                    <th>{{ trans('slackbot::seat.slackbot_relation_name') }}</th>
                    <th>{{ trans('slackbot::seat.slackbot_users_count') }}</th>
                    <th>{{ trans('slackbot::seat.slackbot_relation_creation') }}</th>
                    <th>{{ trans('slackbot::seat.slackbot_relation_updated') }}</th>
                    <th>{{ trans('slackbot::seat.slackbot_status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($slack_relations as $relation)
                <tr>
                    <td>{{ $relation->role->title }}</td>
                    <td>0</td>
                    <td>{{ $relation->created_at }}</td>
                    <td>{{ $relation->updated_at }}</td>
                    <td>
                        @if($relation->status == 1)
                        <span class="label label-success">{{ trans('slackbot::seat.slackbot_relation_enabled') }}</span>
                        @else
                        <span class="label label-danger">{{ trans('slackbot::seat.slackbot_relation_disabled') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="#" type="button" class="btn btn-primary btn-xs col-xs-6">
                                {{ trans('slackbot::seat.slackbot_details') }}
                            </a>
                            <a href="#" type="button" class="btn btn-danger btn-xs col-xs-6">
                                {{ trans('slackbot::seat.slackbot_delete') }}
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <a href="#" type="button" class="btn btn-success btn-xs col-xs-6">
            {{ trans('slackbot::seat.slackbot_create') }}
        </a>
    </div>
</div>