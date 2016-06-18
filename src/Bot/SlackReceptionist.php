<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:01
 */

namespace Seat\Slackbot\Bot;



use Seat\Eveapi\Models\Account\AccountStatus;
use Seat\Eveapi\Models\Account\ApiKeyInfoCharacters;
use Seat\Eveapi\Models\Character\CharacterSheet;
use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Web\Models\Acl\RoleUser;
use Seat\Web\Models\User;
use Seat\Slackbot\Exceptions\SlackTeamInvitationException;
use Seat\Slackbot\Models\SlackUser;

class SlackReceptionist
{
    const SLACK_URI_PATTERN = "https://%s.slack.com/api";

    protected $slack_team_api;
    protected $slack_token_api;

    function call()
    {
        // todo load team and token

        foreach (User::where('active', true)->get() as $user) {
            $keys = [];
            foreach (ApiKey::where('user_id', $user->id)->get() as $key) {
                $keys[] = $key->keyId;
            }

            if ($this->isEnabledKey($keys) && $this->isActive($keys)) {
                $allowed_channels = $this->allowed_channels($user);
                if (!$this->isInvited($user->id)) {
                    $this->processMemberInvitation($user);
                }
                $this->processChannelsInvitation($user, $allowed_channels);
            }
        }

        return;
    }

    /**
     * Return true if all API Key are still enable
     *
     * @param $key_ids[]
     * @return bool
     */
    function isEnabledKey($key_ids)
    {
        $success = 0;
        
        foreach ($key_ids as $key_id) {
            $row = ApiKey::where('keyID', $key_id)
                ->where('enabled', true)
                ->count();

            if ($row != null)
                $success++;
        }
        
        if ($success == count($key_ids))
            return true;
        
        return false;
    }

    /**
     * Return true if at least one API Key is still paid until now
     *
     * @param $key_ids[]
     * @return bool
     */
    function isActive($key_ids)
    {
        foreach ($key_ids as $key_id) {
            return (boolean) AccountStatus::where('keyID', $key_id)
                ->whereDate('paidUntil', '>=', date('Y-m-d'))
                ->count();
        }

        return false;
    }

    /**
     * Determine if an user has already been invited
     * 
     * @param $user_id
     * @return bool
     */
    function isInvited($user_id)
    {
        return (boolean) SlackUser::where('user_id', $user_id)
            ->where('invited', true)
            ->count();
    }

    /**
     * Determine all channels in which an user is allowed to be
     * 
     * @param User $user
     * @return array
     */
    function allowed_channels(User $user)
    {
        $channels = [];

        $rows = User::join('slack_channels_users', 'slack_channels_users.user_id', 'users.id')
            ->select('channel_id')
            ->where('users.id', $user->id)
            ->union(
                RoleUser::join('slack_channels_roles', 'slack_channels_roles.role_id', 'role_user.role_id')
                ->where('role_user.user_id', $user->id)
                ->select('channel_id')
                ->get()
            )->union(
                ApiKey::join('account_api_key_info_characters', 'account_api_key_info_characters.keyID', 'eve_api_keys.key_id')
                ->join('slack_channels_corporations', 'slack_channels_corporations.corporation_id', 'account_api_key_info_characters.corporationID')
                ->where('eve_api_keys.user_id', $user->id)
                ->select('channel_id')
                ->get()
            )->union(
                CharacterSheet::join('slack_channels_alliances', 'slack_channels_alliances.alliance_id', 'character_character_sheets.allianceID')
                ->join('account_api_key_info_characters', 'account_api_key_info_characters.characterID', 'character_character_sheets.characterID')
                ->join('eve_api_keys', 'eve_api_keys.key_id', 'account_api_key_info_characters.keyID')
                ->where('eve_api_keys.user_id', $user->id)
                ->select('channel_id')
                ->get()
            )->get();

        foreach ($rows as $row) {
            $channels[] = $row->channel_id;
        }

        return $channels;
    }

    function member_channels()
    {
        return [];
    }

    /**
     * Invite the user to a slack team
     * 
     * @param User $user
     * @throws SlackTeamInvitationException
     */
    function processMemberInvitation(User $user)
    {
        $params = [
            'email' => $user->email,
            'token' => $this->slack_token_api,
            'set_active' => true
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->slack_team_api . '/users.admin.invite');
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
     * @param User $user
     * @param $channel_ids
     * @throws SlackTeamInvitationException
     */
    function processChannelsInvitation(User $user, $channel_ids)
    {
        foreach ($channel_ids as $channel_id) {
            $params = [
                'channel' => $channel_id,
                'token' => $this->slack_token_api,
                'user' => $user->id
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->slack_team_api . '/channels.invite');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            if (!$result = curl_exec($curl)) {
                throw new SlackTeamInvitationException("An error occurred while trying to invite the member.\r\n" .
                    curl_error($curl));
            } else {
                $json = json_decode($result);
                if ($json['ok'] != true) {
                    throw new SlackTeamInvitationException("An error occurred while trying to invite the member.\r\n" .
                        $json['error']);
                }
            }
        }
    }

    /**
     * Invite an user to each group
     * 
     * @param User $user
     * @param $group_ids
     * @throws SlackTeamInvitationException
     */
    function processGroupsInvitation(User $user, $group_ids)
    {
        foreach ($group_ids as $group_id) {
            $params = [
                'channel' => $group_id,
                'token' => $this->slack_token_api,
                'user' => $user->id
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->slack_team_api . '/groups.invite');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            if (!$result = curl_exec($curl)) {
                throw new SlackTeamInvitationException("An error occurred while trying to invite the member.\r\n" .
                    curl_error($curl));
            } else {
                $json = json_decode($result);
                if ($json['ok'] != true) {
                    throw new SlackTeamInvitationException("An error occurred while trying to invite the member.\r\n" .
                        $json['error']);
                }
            }
        }
    }
}