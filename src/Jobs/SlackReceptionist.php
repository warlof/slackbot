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
use Seat\Slackbot\Helpers\SlackApi;
use Seat\Web\Models\User;
use Seat\Slackbot\Exceptions\SlackTeamInvitationException;
use Seat\Slackbot\Models\SlackUser;

class SlackReceptionist extends AbstractSlack
{

    function call()
    {
        // call the parent call method in order to load the Slack Api Token
        parent::call();

        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();

        // invite user only if both account are subscribed and keys active
        if ($this->isEnabledKey($keys) && $this->isActive($keys)) {
            // if the user is not yet invited, invite him to team
            if ($this->isInvited($this->user) == false) {
                $this->processMemberInvitation($this->user);
            // in other case, invite him to channels and groups
            } else {
                // get the attached slack user
                $slackUser = SlackUser::where('user_id', $this->user->id)->first();
                // control that we already know it's slack ID (mean that he creates his account
                if ($slackUser->slack_id != null) {
                    $channels = $this->memberOfChannels($slackUser);
                    $allowedChannels = $this->allowedChannels($slackUser);
                    $this->processChannelsInvitation($slackUser, array_diff($allowedChannels, $channels));
                }
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

        // check that the user mail is not a "random" mail
        if (preg_match('/seat.local/i', $user->email) === 1) {
            throw new SlackMailException();
        }

        // call invite endpoint from Slack Api in order to invite the user to the team
        $result = SlackApi::post('/users.admin.invite', $params);
        
        if ($result['ok'] == false) {
            throw new SlackTeamInvitationException($result['error']);
        }

        // update Slack user relation
        $slackUser = new SlackUser();
        $slackUser->user_id = $user->id;
        $slackUser->invited = true;
        $user->save();
    }

    /**
     * Invite an user to each channel
     * 
     * @param SlackUser $slackUser
     * @param array $channels
     * @throws SlackChannelException
     */
    function processChannelsInvitation(SlackUser $slackUser, $channels)
    {
        $params = [
            'channel' => '',
            'user' => $slackUser->slack_id
        ];
        
        // iterate over each channel ID and invite the user
        foreach ($channels as $channel) {
            $params['channel'] = $channel;
            
            $result = SlackApi::post('/channels.invite', $params);

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

        // iterate over each group ID and invite the user
        foreach ($groups as $group) {
            $params['channel'] = $group;

            $result = SlackApi::post('/groups.invite', $params);
            
            if ($result['ok'] == false) {
                throw new SlackGroupException($result['error']);
            }
        }
    }
}
