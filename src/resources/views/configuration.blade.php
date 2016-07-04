@extends('web::layouts.grids.4-4-4')

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
                        <label for="slack-configuration-token" class="col-md-4">Slack Test Token</label>
                        <div class="col-md-7">
                            <div class="input-group input-group-sm">
                                @if ($token == null)
                                <input type="text" class="form-control" id="slack-configuration-token" name="slack-configuration-token" />
                                @else
                                <input type="text" class="form-control" id="slack-configuration-token" name="slack-configuration-token" value="{{ $token }}" />
                                @endif
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="client-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
                            </div>
                            <span class="help-block">
                                In order to generate token, please go on <a href="https://api.slack.com/docs/oauth-test-tokens" target="_blank">your slack test tokens</a> and create a new one.
                            </span>
                        </div>
                    </div>
                    {{--
                    <div class="form-group">
                        <label for="slack-configuration-client" class="col-md-4">Slack Client ID</label>
                        <div class="col-md-7">
                            <div class="input-group input-group-sm">
                                @if ($oauth == null)
                                <input type="text" class="form-control" id="slack-configuration-client" name="slack-configuration-client" />
                                @else
                                <input type="text" class="form-control" id="slack-configuration-client" name="slack-configuration-client" value="{{ $oauth->client_id }}" />
                                @endif
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="client-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="slack-configuration-secret" class="col-md-4">Slack Client Secret</label>
                        <div class="col-md-7">
                            <div class="input-group input-group-sm">
                                @if ($oauth == null)
                                <input type="text" class="form-control" id="slack-configuration-secret" name="slack-configuration-secret" />
                                @else
                                <input type="text" class="form-control" id="slack-configuration-secret" name="slack-configuration-secret" value="{{ $oauth->client_secret }}" />
                                @endif
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="secret-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
                            </div>
                            <span class="help-block">
                                In order to generate credentials, please go on <a href="https://api.slack.com/apps" target="_blank">your slack apps</a> and create a new application.
                            </span>
                        </div>
                    </div>
                    --}}
                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary pull-right">Update</button>
                </div>

            </form>
        </div>
    </div>
@stop

@section('center')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Commands</h3>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <div class="col-md-12">
                    @if($token == '')
                        <a href="#" type="button" class="btn btn-success btn-md col-md-12 disabled" role="button">Update Slack channels and groups</a>
                    @else
                        <a href="{{ route('slackbot.command.run', ['command_name' => 'slack:update:channels']) }}" type="button" class="btn btn-success btn-md col-md-12" role="button">Update Slack channels and groups</a>
                    @endif
                    <span class="help-block">
                        This will update known channels and groups from Slack.
                    </span>
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-12">
                    @if($token == '')
                        <a href="#" type="button" class="btn btn-success btn-md col-md-12 disabled" role="button">Update Slack users</a>
                    @else
                        <a href="{{ route('slackbot.command.run', ['command_name' => 'slack:update:users']) }}" type="button" class="btn btn-success btn-md col-md-12" role="button">Update Slack users</a>
                    @endif
                    <span class="help-block">
                        This will try to update known users from Slack Team based on both Slack user email and SeAT user email.
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
    </div>
@stop

@section('right')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-rss"></i> Update feed</h3>
        </div>
        <div class="panel-body" style="height: 500px; overflow-y: scroll">
            {!! $changelog !!}
        </div>
        <div class="panel-footer">
            <div class="row">
                <div class="col-md-6">
                    Installed version: <b>{{ config('slackbot.config.version') }}</b>
                </div>
                <div class="col-md-6">
                    Latest version:
                    <a href="https://packagist.org/packages/warlof/slackbot">
                        <img src="https://poser.pugx.org/warlof/slackbot/v/stable" alt="Slackbot version" />
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script type="application/javascript">
        $('#client-eraser').click(function(){
            $('#slack-configuration-client').val('');
        });

        $('#secret-eraser').click(function(){
            $('#slack-configuration-secret').val('');
        });

        $('[data-toggle="tooltip"]').tooltip();
    </script>
@stop