<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 21:31
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Containers;


use Monolog\Logger;
use Warlof\Seat\Slackbot\Repositories\Slack\Cache\FileCache;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\Traits\ConstructsContainers;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\Traits\ValidatesContainers;
use Warlof\Seat\Slackbot\Repositories\Slack\Fetchers\GuzzleFetcher;
use Warlof\Seat\Slackbot\Repositories\Slack\Log\FileLogger;

class SlackConfiguration extends AbstractArrayAccess {

    use ConstructsContainers, ValidatesContainers;

    protected $data = [
        'http_user_agent'  => 'SeAT Slack Connector',

        // Fetcher
        'fetcher'             => GuzzleFetcher::class,

        // Logger
        'logger'              => FileLogger::class,
        'logger_level'        => Logger::INFO,
        'logfile_location'    => 'logs/slack.log',

        // Cache
        'cache'               => FileCache::class,
        'file_cache_location' => 'cache/',
    ];

}
