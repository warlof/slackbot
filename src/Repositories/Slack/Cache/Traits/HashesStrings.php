<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 07/12/2017
 * Time: 23:02
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Cache\Traits;


trait HashesStrings {

    public function hashString(string $string) : string
    {
        return sha1($string);
    }

}