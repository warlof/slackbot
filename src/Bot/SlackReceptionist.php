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
            $slack_user = SlackUser::where('user_id', $user->id)->get();

            if ($this->isEnabledKey($keys) && $this->isActive($keys)) {
                $allowed_channels = $this->allowed_channels($slack_user);
                if (!$this->isInvited($user)) {
                    $this->processMemberInvitation($user);
                }
                $this->processChannelsInvitation($slack_user, $allowed_channels);
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
            'token' => $this->slack_token_api,
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

        if (!$result = curl_exec($curl)) {
            throw new SlackTeamInvitationException("An error occurred while trying to invite the member.\r\n" .
                curl_error($curl));
        } else {
            $json = json_decode($result);
            if ($json['ok'] == true) {
                $slack_user = new SlackUser();
                $slack_user->user_id = $user->id;
                $slack_user->invited = true;
                $user->save();
            } else {
                throw new SlackTeamInvitationException("An error occurred while trying to invite the member.\r\n" .
                    $json['error']);
            }
        }
    }

    /**
     * Invite an user to each channel
     * 
     * @param SlackUser $slack_user
     * @param $channel_ids
     * @throws SlackChannelException
     */
    function processChannelsInvitation(SlackUser $slack_user, $channel_ids)
    {
        foreach ($channel_ids as $channel_id) {
            $params = [
                'channel' => $channel_id,
                'token' => $this->slack_token_api,
                'user' => $slack_user->slack_id
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/channels.invite');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            if (!$result = curl_exec($curl)) {
                throw new SlackChannelException("An error occurred while trying to invite the member.\r\n" .
                    curl_error($curl));
            } else {
                $json = json_decode($result);
                if ($json['ok'] != true) {
                    throw new SlackChannelException("An error occurred while trying to invite the member.\r\n" .
                        $json['error']);
                }
            }
        }
    }

    /**
     * Invite an user to each group
     * 
     * @param SlackUser $slack_user
     * @param $group_ids
     * @throws SlackGroupException
     */
    function processGroupsInvitation(SlackUser $slack_user, $group_ids)
    {
        foreach ($group_ids as $group_id) {
            $params = [
                'channel' => $group_id,
                'token' => $this->slack_token_api,
                'user' => $slack_user->slack_id
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/groups.invite');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            if (!$result = curl_exec($curl)) {
                throw new SlackGroupException("An error occurred while trying to invite the member.\r\n" .
                    curl_error($curl));
            } else {
                $json = json_decode($result);
                if ($json['ok'] != true) {
                    throw new SlackGroupException("An error occurred while trying to invite the member.\r\n" .
                        $json['error']);
                }
            }
        }
    }
}