<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:01
 */

namespace Seat\Slackbot\Jobs;

use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Slackbot\Exceptions\SlackChannelException;
use Seat\Slackbot\Exceptions\SlackGroupException;
use Seat\Slackbot\Exceptions\SlackMailException;
use Seat\Web\Models\User;
use Seat\Slackbot\Exceptions\SlackTeamInvitationException;
use Seat\Slackbot\Models\SlackUser;

class SlackReceptionist extends AbstractSlack
{

    function call()
    {
        $this->load();

        $keys = ApiKey::where('user_id', $this->user)->get();
            
        if ($this->isEnabledKey($keys) && $this->isActive($keys)) {
            if (!$this->isInvited($this->user)) {
                $this->processMemberInvitation($this->user);
            } else {
                $slackUser = SlackUser::where('user_id', $this->user->id)->first();
                $allowedChannels = $this->allowedChannels($slackUser);
                $this->processChannelsInvitation($slackUser, $allowedChannels);
            }
        }

        return;
    }

    /**
     * Invite the user to a slack team
     * 
     * @param User $user
     * @throws SlackMailException
     * @throws SlackTeamInvitationException
     */
    function processMemberInvitation(User $user)
    {
        $params = [
            'email' => $user->email,
            'set_active' => true
        ];

        if (preg_match('/seat.local/i', $user->email) === 1) {
            throw new SlackMailException();
        }

        $result = $this->processSlackApiPost('/users.admin.invite', $params);
        
        if ($result['ok'] == false) {
            throw new SlackTeamInvitationException($result['error']);
        }

        $slackUser = new SlackUser();
        $slackUser->user_id = $user->id;
        $slackUser->invited = true;
        $user->save();
    }

    /**
     * Invite an user to each channel
     * 
     * @param SlackUser $slackUser
     * @param $channels
     * @throws SlackChannelException
     */
    function processChannelsInvitation(SlackUser $slackUser, $channels)
    {
        $params = [
            'channel' => '',
            'user' => $slackUser->slack_id
        ];
        
        foreach ($channels as $channel) {
            $params['channel'] = $channel;
            
            $result = $this->processSlackApiPost('/channels.invite', $params);

            if ($result['ok'] == false) {
                throw new SlackChannelException($result['error']);
            }
        }
    }

    /**
     * Invite an user to each group
     * 
     * @param SlackUser $slackUser
     * @param $groups
     * @throws SlackGroupException
     */
    function processGroupsInvitation(SlackUser $slackUser, $groups)
    {
        $params = [
            'channel' => '',
            'user' => $slackUser->slack_id
        ];

        foreach ($groups as $group) {
            $params['channel'] = $group;

            $result = $this->processSlackApiPost('/groups.invite', $params);
            
            if ($result['ok'] == false) {
                throw new SlackGroupException($result['error']);
            }
        }
    }
}
