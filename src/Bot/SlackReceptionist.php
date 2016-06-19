<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:01
 */

namespace Seat\Slackbot\Bot;

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
        // todo load team and token

        foreach (User::where('active', true)->get() as $user) {
            
            $keys = ApiKey::where('user_id', $user->id)->get();
            $slackUser = SlackUser::where('user_id', $user->id)->get();

            if ($this->isEnabledKey($keys) && $this->isActive($keys)) {
                $allowedChannels = $this->allowed_channels($slackUser);
                if (!$this->isInvited($user)) {
                    $this->processMemberInvitation($user);
                }
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
            'token' => $this->slackTokenApi,
            'set_active' => true
        ];

        if (preg_match('/seat.local/i', $user->email) === 1) {
            throw new SlackMailException();
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/users.admin.invite');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = json_decode(curl_exec($curl));

        if ($result == null || $result['ok'] == false) {
            throw new SlackTeamInvitationException("An error occurred while trying to invite the member.\r\n" .
                curl_error($curl));
        }

        if ($result['ok'] == true) {
            $slackUser = new SlackUser();
            $slackUser->user_id = $user->id;
            $slackUser->invited = true;
            $user->save();
        }
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
        foreach ($channels as $channel) {
            $params = [
                'channel' => $channel,
                'token' => $this->slackTokenApi,
                'user' => $slackUser->slack_id
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/channels.invite');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = json_decode(curl_exec($curl));

            if ($result == null || $result['ok'] == false) {
                throw new SlackChannelException("An error occurred while trying to invite the member.\r\n" .
                    curl_error($curl));
            }
        }
    }

    /**
     * Invite an user to each group
     * 
     * @param SlackUser $slack_user
     * @param $groups
     * @throws SlackGroupException
     */
    function processGroupsInvitation(SlackUser $slack_user, $groups)
    {
        foreach ($groups as $group) {
            $params = [
                'channel' => $group,
                'token' => $this->slackTokenApi,
                'user' => $slack_user->slack_id
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/groups.invite');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = json_decode(curl_exec($curl));
            
            if ($result == null || $result['ok'] == false) {
                throw new SlackGroupException("An error occurred while trying to invite the member.\r\n" .
                    curl_error($curl));
            }
        }
    }
}