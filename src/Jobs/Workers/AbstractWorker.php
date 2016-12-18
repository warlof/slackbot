<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 13/12/2016
 * Time: 20:04
 */

namespace Warlof\Seat\Slackbot\Jobs\Workers;


use Seat\Web\Models\User;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackLog;

abstract class AbstractWorker
{
    /**
     * @var User the user we're checking access
     */
    protected $user;

    /**
     * The contract for the update call.
     * All update should at least have this function.
     *
     * @return mixed
     */
    abstract protected function call();

    /**
     * Enable to affect an User object to the current Job
     *
     * @param User $user
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Allow to log event into slack log table
     *
     * @param string $eventType
     * @param array $channels
     */
    protected function logEvent(string $eventType, array $channels = null)
    {
        $message = '';
        $channelsString = '';

        $slackChannels = SlackChannel::whereIn('id', $channels)->get();

        if ($channels != null) {
            $channelsString =  $slackChannels->implode('name', ', ');
        }

        switch ($eventType) {
            case 'invite':
                $message = 'The user ' . $this->user->name . ' has been invited to following channels : ' .
                    $channelsString;
                break;
            case 'kick':
                $message = 'The user ' . $this->user->name . ' has been kicked from following channels : ' .
                    $channelsString;
                break;
            case 'mail':
                $message = 'The mail address for user ' . $this->user->name . ' has not been set (' .
                    $this->user->email . ')';
                break;
            case 'sync':
                $message = 'The SeAT database is not synced with Slack users.';
                break;
        }

        SlackLog::create([
            'event' => $eventType,
            'message' => $message
        ]);
    }
}