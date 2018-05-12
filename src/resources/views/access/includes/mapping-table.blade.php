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
                @include('slackbot::access.includes.subs.public-mapping-tab')
            </div>
            <div role="tabpanel" class="tab-pane fade" id="slackbot-username">
                @include('slackbot::access.includes.subs.user-mapping-tab')
            </div>
            <div role="tabpanel" class="tab-pane fade" id="slackbot-role">
                @include('slackbot::access.includes.subs.role-mapping-tab')
            </div>
            <div role="tabpanel" class="tab-pane fade" id="slackbot-corporation">
                @include('slackbot::access.includes.subs.corporation-mapping-tab')
            </div>
            <div role="tabpanel" class="tab-pane fade" id="slackbot-title">
                @include('slackbot::access.includes.subs.title-mapping-tab')
            </div>
            <div role="tabpanel" class="tab-pane fade" id="slackbot-alliance">
                @include('slackbot::access.includes.subs.alliance-mapping-tab')
            </div>
        </div>
    </div>
</div>