<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 10/12/2017
 * Time: 13:01
 */

namespace Warlof\Seat\Slackbot\Jobs;


use Monolog\Logger;
use Seat\Eveapi\Helpers\JobPayloadContainer;
use Seat\Eveapi\Jobs\Base;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackConfiguration;
use Warlof\Seat\Slackbot\Repositories\Slack\SlackApi;

abstract class AbstractSlackJob extends Base {

    /**
     * @var SlackAuthentication
     */
    protected $auth;

    /**
     * @var SlackApi
     */
    protected $slack;

    public function __construct(JobPayloadContainer $job_payload) {
        parent::__construct($job_payload);

        $configuration = Configuration::getInstance();
        $configuration->setConfiguration(new SlackConfiguration([
        	'http_user_agent'     => '(Clan Daerie;Warlof Tutsimo;Daerie Inc.;Get Off My Lawn)',
        	'logger_level'        => Logger::DEBUG,
	        'logfile_location'    => storage_path('logs/slack.log'),
	        'file_cache_location' => storage_path('cache/slack/'),
        ]));

        if (is_null(setting('warlof.slackbot.credentials.access_token', true)))
            throw new SlackSettingException("warlof.slackbot.credentials.access_token is missing in settings. " .
                                            "Ensure you've link SeAT to a valid Slack Team.");

        $this->auth = new SlackAuthentication([
            'access_token' => setting('warlof.slackbot.credentials.access_token', true),
            'scopes' => [
                'users:read',
                'users:read.email',
                'channels:read',
                'channels:write',
                'groups:read',
                'groups:write',
                'im:read',
                'im:write',
                'mpim:read',
                'mpim:write',
                'read',
                'post',
            ],
        ]);

        $this->slack = new SlackApi();
        $this->slack->setAuthentication($this->auth);
    }

}