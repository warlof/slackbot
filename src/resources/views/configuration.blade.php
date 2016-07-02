@extends('web::layouts.grids.4-8')

@section('title', trans('slackbot::seat.settings'))
@section('page_header', trans('slackbot::seat.settings'))

@section('left')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Configuration</h3>
        </div>
        <div class="panel-body">
            <form role="form" action="{{ route('slackbot.configuration.post') }}" method="post" class="form-horizontal">
                {{ csrf_field() }}

                <div class="box-body">

                    <legend>Slack API</legend>

                    <div class="form-group">
                        <label for="slack-configuration-token" class="col-md-4">Slack Token</label>
                        <div class="col-md-7">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="slack-configuration-token" name="slack-configuration-token" value="{{ $token }}" />
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="token-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
                            </div>
                            <span class="help-block">
                                In order to generate a token, please go on <a href="https://api.slack.com/docs/oauth-test-tokens" target="_blank">slack api test tokens</a>.
                            </span>
                        </div>
                    </div>

                    <legend>Slack Team</legend>

                    <div class="form-group">
                        <div class="col-md-12">
                            @if($token == '')
                            <a href="#" type="button" class="btn btn-success btn-md col-md-12 disabled" role="button">Update Slack Channel and groups</a>
                            @else
                            <a href="#" type="button" class="btn btn-success btn-md col-md-12 disabled" role="button">Update Slack Channel and groups</a>
                            @endif
                            <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="Will be implemented in a later release"></i>
                            <span class="help-block">
                                This will update known channels and groups from Slack.
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12">
                            @if($token == '')
                            <a href="#" type="button" class="btn btn-success btn-md col-md-12 disabled" role="button">Invite SeAT User</a>
                            @else
                            <a href="#" type="button" class="btn btn-success btn-md col-md-12 disabled" role="button">Invite SeAT User</a>
                            @endif
                            <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="Will be implemented in a later release"></i>
                            <span class="help-block">
                                This will invite all SeAT user which are not yet part of the Slack Team.
                                If user are already part of the team, the bot will invite them to all granted channel and groups.
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12">
                            @if($token == '')
                            <a href="#" type="button" class="btn btn-warning btn-md col-md-12 disabled" role="button">Update Slack Member</a>
                            @else
                            <a href="#" type="button" class="btn btn-warning btn-md col-md-12 disabled" role="button">Update Slack Member</a>
                            @endif
                            <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="Will be implemented in a later release"></i>
                            <span class="help-block">
                                Warning, this will kick all Slack member the bot is not able to link to SeAT user account.
                                The bot is using both SeAT user mail and Slack member mail in order to bind the account.
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12">
                            @if($token == '')
                            <a href="#" type="button" class="btn btn-warning btn-md col-md-12 disabled" role="button">Kick SeAT User</a>
                            @else
                            <a href="#" type="button" class="btn btn-warning btn-md col-md-12 disabled" role="button">Kick SeAT User</a>
                            @endif
                            <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="Will be implemented in a later release"></i>
                            <span class="help-block">
                                This will kick all Slack member which not met the channels and groups rules.
                            </span>
                        </div>
                    </div>

                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary pull-right">Update</button>
                </div>

            </form>
        </div>
    </div>
@stop

@section('javascript')
    <script type="application/javascript">
        $('#token-eraser').click(function(){
            $('#slack-configuration-token').val('');
        });

        $('[data-toggle="tooltip"]').tooltip();
    </script>
@stop