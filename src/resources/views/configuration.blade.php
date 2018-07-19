@extends('web::layouts.grids.4-4-4')

@section('title', trans('slackbot::seat.settings'))
@section('page_header', trans('slackbot::seat.settings'))

@section('left')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Configuration</h3>
        </div>
        <div class="panel-body">
            <form role="form" action="{{ route('slack.oauth.configuration.post') }}" method="post" class="form-horizontal">
                {{ csrf_field() }}

                <div class="box-body">

                    <legend>Slack API</legend>

                    <p class="callout callout-warning text-justify">{!! trans('slackbot::seat.existing_setup_disclaimer', ['client_id' => '<code>Client ID</code>', 'client_secret' => '<code>Client Secret</code>']) !!}</p>

                    <div class="form-group">
                        <label for="slack-configuration-client" class="col-md-4">Slack Client ID</label>
                        <div class="col-md-7">
                            <div class="input-group input-group-sm">
                                @if (setting('warlof.slackbot.credentials.client_id', true) == null)
                                <input type="text" class="form-control" id="slack-configuration-client"
                                       name="slack-configuration-client" />
                                @else
                                <input type="text" class="form-control " id="slack-configuration-client"
                                       name="slack-configuration-client" value="{{ setting('warlof.slackbot.credentials.client_id', true) }}" readonly />
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
                                @if (setting('warlof.slackbot.credentials.client_secret', true) == null)
                                <input type="text" class="form-control" id="slack-configuration-secret"
                                       name="slack-configuration-secret" />
                                @else
                                <input type="text" class="form-control" id="slack-configuration-secret"
                                       name="slack-configuration-secret" value="{{ setting('warlof.slackbot.credentials.client_secret', true) }}" readonly />
                                @endif
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="secret-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="slack-configuration-verification" class="col-md-4">Slack Verification Token</label>
                        <div class="col-md-7">
                            <div class="input-group input-group-sm">
                                @if (setting('warlof.slackbot.credentials.verification_token', true) == null)
                                    <input type="text" class="form-control" id="slack-configuration-verification"
                                           name="slack-configuration-verification" />
                                @else
                                    <input type="text" class="form-control" id="slack-configuration-verification"
                                           name="slack-configuration-verification" value="{{ setting('warlof.slackbot.credentials.verification_token', true) }}" readonly />
                                @endif
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="verification-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
                            </div>
                            <span class="help-block">
                                In order to generate credentials, please go on <a href="https://api.slack.com/apps" target="_blank">your slack apps</a> and create a new application.
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

@section('center')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Commands</h3>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <div class="col-md-12">
                    @if(setting('warlof.slackbot.credentials.access_token', true) == '')
                        <a href="#" type="button" class="btn btn-success btn-md col-md-12 disabled" role="button">Update Slack channels and groups</a>
                    @else
                        <a href="{{ route('slackbot.command.run', ['commandName' => 'slack:conversation:sync']) }}" type="button" class="btn btn-success btn-md col-md-12" role="button">Update Slack channels and groups</a>
                    @endif
                    <span class="help-block">
                        This will update known channels and groups from Slack.
                    </span>
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-12">
                    @if(setting('warlof.slackbot.credentials.access_token', true) == '')
                        <a href="#" type="button" class="btn btn-success btn-md col-md-12 disabled" role="button">Update Slack users</a>
                    @else
                        <a href="{{ route('slackbot.command.run', ['commandName' => 'slack:user:sync']) }}" type="button" class="btn btn-success btn-md col-md-12" role="button">Update Slack users</a>
                    @endif
                    <span class="help-block">
                        This will try to update known users from Slack Team based on both Slack user email and SeAT user email.
                    </span>
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-12">
                    @if(setting('warlof.slackbot.credentials.access_token', true) == '')
                        <a href="#" type="button" class="btn btn-danger btn-md col-md-12 disabled" role="button">Kick everybody</a>
                    @else
                        <a href="{{ route('slackbot.command.run', ['commandName' => 'slack:user:terminator']) }}" type="button" class="btn btn-danger btn-md col-md-12" role="button">Kick everybody</a>
                    @endif
                    <span class="help-block">
                        This will kick every user from every conversations into the connected Slack Team. Please proceed carefully.
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

@push('javascript')
    <script type="application/javascript">
        $('#client-eraser').on('click', function(){
            var slack_client = $('#slack-configuration-client');
            slack_client.val('');
            slack_client.removeAttr("readonly");
        });

        $('#secret-eraser').on('click', function(){
            var slack_secret = $('#slack-configuration-secret');
            slack_secret.val('');
            slack_secret.removeAttr("readonly");
        });

        $('#verification-eraser').on('click', function(){
            var slack_verification = $('#slack-configuration-verification');
            slack_verification.val('');
            slack_verification.removeAttr("readonly");
        });

        $('[data-toggle="tooltip"]').tooltip();
    </script>
@endpush